<?php

namespace App\Libraries;

use Config\ApiCrp;

class CrpApiService
{
    protected $client;
    protected $config;
    protected string $adjustmentEndpoint;
    protected int $maxConsecutiveFailures = 3;

    public function __construct()
    {
        $this->config = config(ApiCrp::class);
        $this->adjustmentEndpoint = $this->resolveAdjustmentEndpoint((string) $this->config->baseURL);

        $this->client = \Config\Services::curlrequest([
            'timeout'     => $this->config->timeout,
            'http_errors' => false,
            'verify'      => false, // nonaktifkan SSL verify jika pakai IP/port internal
        ]);
    }

    /**
     * Ambil data adjustment berdasarkan tanggal (format: YYYY-MM-DD)
     * Endpoint: GET <baseURL>AdjSamplingSparepart?tanggal=<tanggal>
     */
    public function getAdjustments(string $tanggal): array
    {
        try {
            $response = $this->client->get($this->adjustmentEndpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config->token,
                    'Accept'        => 'application/json',
                ],
                'query' => [
                    'tanggal' => $tanggal,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                log_message('error', "CRP API getAdjustments({$tanggal}) HTTP {$response->getStatusCode()}: " . $response->getBody());
                return [];
            }

            $data = json_decode($response->getBody(), true);
            return $data['data'] ?? (is_array($data) ? $data : []);

        } catch (\Exception $e) {
            log_message('error', 'CRP API exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil gabungan data adjustment untuk seluruh bulan dalam satu tahun.
     * Memanggil endpoint sekali per bulan dan menggabungkan hasilnya.
     */
    public function getAdjustmentsByYear(int $year): array
    {
        return $this->getAdjustmentsByYearUntilMonth($year, 12);
    }

    /**
     * Ambil gabungan data adjustment dari Januari sampai bulan yang dipilih.
     */
    public function getAdjustmentsByYearUntilMonth(int $year, int $maxMonth): array
    {
        $maxMonth = max(1, min(12, $maxMonth));
        $cacheKey = sprintf('crp_adjustments_%04d_%02d', $year, $maxMonth);
        $staleCacheKey = $cacheKey . '_stale';
        $cache = \Config\Services::cache();
        $cached = $cache->get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $staleCached = $cache->get($staleCacheKey);

        $result = [];
        $seen = [];
        $consecutiveFailures = 0;
        $stoppedEarly = false;

        $startDate = new \DateTime(sprintf('%04d-01-01', $year));
        $endDate = new \DateTime(sprintf('%04d-%02d-01', $year, $maxMonth));
        $endDate->modify('last day of this month');

        for ($date = clone $startDate; $date <= $endDate; $date->modify('+1 day')) {
            $tanggal = $date->format('Y-m-d');
            $response = $this->fetchAdjustmentsWithStatus($tanggal);
            $rows = $response['rows'];

            if (!$response['ok']) {
                if (($response['fatal'] ?? false) === true) {
                    log_message('error', 'CRP API fatal failure on {date}. Stop pulling remaining dates.', [
                        'date' => $tanggal,
                    ]);
                    $stoppedEarly = true;
                    break;
                }

                $consecutiveFailures++;
                if ($consecutiveFailures >= $this->maxConsecutiveFailures) {
                    log_message('error', 'CRP API stopped early after {failures} consecutive failures on range {start} s/d {end}.', [
                        'failures' => $consecutiveFailures,
                        'start'    => $startDate->format('Y-m-d'),
                        'end'      => $endDate->format('Y-m-d'),
                    ]);
                    $stoppedEarly = true;
                    break;
                }

                continue;
            }

            $consecutiveFailures = 0;

            foreach ($rows as $row) {
                $uniqueKey = (string) ($row['NO_ADJ'] ?? '');

                if ($uniqueKey === '') {
                    $uniqueKey = implode('|', [
                        (string) ($row['ITEM'] ?? ''),
                        (string) ($row['WAREHOUSE'] ?? ''),
                        (string) ($row['ADJ_DATE'] ?? ''),
                        (string) ($row['QTY_ADJ'] ?? ''),
                        (string) ($row['PRICE'] ?? ''),
                    ]);
                }

                if (isset($seen[$uniqueKey])) {
                    continue;
                }

                $seen[$uniqueKey] = true;
                $result[] = $row;
            }
        }

        if ($stoppedEarly && is_array($staleCached)) {
            log_message('notice', 'CRP API fallback ke stale cache karena pull parsial untuk key {key}.', [
                'key' => $staleCacheKey,
            ]);

            return $staleCached;
        }

        if (!$stoppedEarly && $result !== []) {
            $cache->save($cacheKey, $result, 900);
            $cache->save($staleCacheKey, $result, 86400);

            return $result;
        }

        if (is_array($staleCached)) {
            log_message('notice', 'CRP API fallback to stale cache for key {key}.', ['key' => $staleCacheKey]);
            return $staleCached;
        }

        return $result;
    }

    /**
     * Ambil data adjustment sekaligus status sukses request.
     *
    * @return array{ok: bool, fatal: bool, rows: array}
     */
    private function fetchAdjustmentsWithStatus(string $tanggal): array
    {
        try {
            $response = $this->client->get($this->adjustmentEndpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config->token,
                    'Accept'        => 'application/json',
                ],
                'query' => [
                    'tanggal' => $tanggal,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                log_message('error', "CRP API getAdjustments({$tanggal}) HTTP {$response->getStatusCode()}: " . $response->getBody());

                return [
                    'ok' => false,
                    'fatal' => false,
                    'rows' => [],
                ];
            }

            $data = json_decode($response->getBody(), true);

            return [
                'ok' => true,
                'fatal' => false,
                'rows' => $data['data'] ?? (is_array($data) ? $data : []),
            ];
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $fatal = stripos($message, 'Could not resolve host') !== false
                || stripos($message, 'Could not connect') !== false;

            log_message('error', 'CRP API exception: ' . $message);

            return [
                'ok' => false,
                'fatal' => $fatal,
                'rows' => [],
            ];
        }
    }

    private function resolveAdjustmentEndpoint(string $baseUrl): string
    {
        $baseUrl = trim($baseUrl);

        if ($baseUrl === '') {
            return 'AdjSamplingSparepart';
        }

        $baseUrl = preg_replace('/[?#].*$/', '', $baseUrl) ?? $baseUrl;
        $baseUrl = rtrim($baseUrl, '/');

        if (preg_match('#/AdjSamplingSparepart$#i', $baseUrl)) {
            return $baseUrl;
        }

        return $baseUrl . '/AdjSamplingSparepart';
    }
}