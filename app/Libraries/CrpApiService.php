<?php

namespace App\Libraries;

use Config\ApiCrp;
use Config\Database;

class CrpApiService
{
    protected $client;
    protected $config;
    protected string $adjustmentEndpoint;
    protected int $maxConsecutiveFailures = 3;

    public function __construct()
    {
        $this->config = config(ApiCrp::class);
        $this->adjustmentEndpoint = env('api.baseURL');

        $this->client = \Config\Services::curlrequest([
            'timeout'     => env('api.timeout'),
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
                    'Authorization' => 'Bearer ' . env('api.token'),
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

        $currentYear = (int) date('Y');

        if ($year < $currentYear) {
            return $this->getCachedAdjustmentsByYearUntilMonth($year, $maxMonth);
        }

        // Jika konfigurasi API tidak tersedia, tetap coba data dari cache agar dashboard/chart tidak kosong seragam.
        if (!$this->hasApiConfiguration()) {
            return $this->getCachedAdjustmentsByYearUntilMonth($year, $maxMonth);
        }

        return $this->getLiveAdjustmentsByYearUntilMonth($year, $maxMonth);
    }

    /**
     * Ambil data adjustment dari cache lokal saja (tanpa request API).
     */
    public function getCachedOnlyAdjustmentsByYearUntilMonth(int $year, int $maxMonth): array
    {
        $maxMonth = max(1, min(12, $maxMonth));

        $result = [];
        $seen = [];
        $cachedRowsByDate = $this->loadCachedAdjustmentsByRange($year, $maxMonth);

        if ($cachedRowsByDate === []) {
            return [];
        }

        ksort($cachedRowsByDate);

        foreach ($cachedRowsByDate as $tanggal => $rows) {
            if (!is_array($rows) || $rows === []) {
                continue;
            }

            $rows = $this->attachSourceDateToRows($rows, (string) $tanggal);

            foreach ($rows as $row) {
                $uniqueKey = $this->buildAdjustmentUniqueKey($row);

                if (isset($seen[$uniqueKey])) {
                    continue;
                }

                $seen[$uniqueKey] = true;
                $result[] = $row;
            }
        }

        return $result;
    }

    /**
     * Ambil data historis dari cache lokal. Jika cache belum lengkap, fallback ke API hanya untuk tanggal yang kosong.
     */
    private function getCachedAdjustmentsByYearUntilMonth(int $year, int $maxMonth): array
    {
        $maxMonth = max(1, min(12, $maxMonth));

        $result = [];
        $seen = [];
        $failedDates = [];
        $cachedRowsByDate = $this->loadCachedAdjustmentsByRange($year, $maxMonth);
        $canUseApiFallback = $this->hasApiConfiguration();

        $startDate = new \DateTime(sprintf('%04d-01-01', $year));
        $endDate = new \DateTime(sprintf('%04d-%02d-01', $year, $maxMonth));
        $endDate->modify('last day of this month');

        for ($date = clone $startDate; $date <= $endDate; $date->modify('+1 day')) {
            $tanggal = $date->format('Y-m-d');
            $rows = $cachedRowsByDate[$tanggal] ?? null;

            if ($rows === null && $canUseApiFallback) {
                $response = $this->fetchAdjustmentsWithStatus($tanggal);

                if (!$response['ok']) {
                    $failedDates[] = $tanggal;
                    continue;
                }

                $rows = $response['rows'];
                $this->storeAdjustmentsToCache($tanggal, $rows);
            }

            if (!is_array($rows) || $rows === []) {
                continue;
            }

            $rows = $this->attachSourceDateToRows($rows, $tanggal);

            foreach ($rows as $row) {
                $uniqueKey = $this->buildAdjustmentUniqueKey($row);

                if (isset($seen[$uniqueKey])) {
                    continue;
                }

                $seen[$uniqueKey] = true;
                $result[] = $row;
            }
        }

        if ($failedDates !== []) {
            log_message('warning', 'CRP cache/API gagal pada {failedCount} tanggal saat tarik historis {start} s/d {end}. Contoh tanggal: {sampleDates}', [
                'failedCount' => count($failedDates),
                'start'       => $startDate->format('Y-m-d'),
                'end'         => $endDate->format('Y-m-d'),
                'sampleDates' => implode(', ', array_slice($failedDates, 0, 10)),
            ]);
        }

        return $result;
    }

    /**
     * Ambil data tahun berjalan langsung dari API dan simpan hasil sukses ke cache.
     */
    private function getLiveAdjustmentsByYearUntilMonth(int $year, int $maxMonth): array
    {
        $maxMonth = max(1, min(12, $maxMonth));

        $result = [];
        $seen = [];
        $failedDates = [];
        $cachedRowsByDate = $this->loadCachedAdjustmentsByRange($year, $maxMonth);

        $startDate = new \DateTime(sprintf('%04d-01-01', $year));
        $endDate = new \DateTime(sprintf('%04d-%02d-01', $year, $maxMonth));
        $endDate->modify('last day of this month');

        for ($date = clone $startDate; $date <= $endDate; $date->modify('+1 day')) {
            $tanggal = $date->format('Y-m-d');
            $response = $this->fetchAdjustmentsWithStatus($tanggal);

            if (!$response['ok']) {
                $cachedRows = $cachedRowsByDate[$tanggal] ?? null;

                if (is_array($cachedRows) && $cachedRows !== []) {
                    $rows = $this->attachSourceDateToRows($cachedRows, $tanggal);
                    foreach ($rows as $row) {
                        $uniqueKey = $this->buildAdjustmentUniqueKey($row);

                        if (isset($seen[$uniqueKey])) {
                            continue;
                        }

                        $seen[$uniqueKey] = true;
                        $result[] = $row;
                    }

                    continue;
                }

                $failedDates[] = $tanggal;
                continue;
            }

            $rows = $this->attachSourceDateToRows($response['rows'], $tanggal);
            $this->storeAdjustmentsToCache($tanggal, $response['rows']);

            foreach ($rows as $row) {
                $uniqueKey = $this->buildAdjustmentUniqueKey($row);

                if (isset($seen[$uniqueKey])) {
                    continue;
                }

                $seen[$uniqueKey] = true;
                $result[] = $row;
            }
        }

        if ($failedDates !== []) {
            log_message('warning', 'CRP API gagal pada {failedCount} tanggal saat tarik range {start} s/d {end}. Contoh tanggal: {sampleDates}', [
                'failedCount' => count($failedDates),
                'start'       => $startDate->format('Y-m-d'),
                'end'         => $endDate->format('Y-m-d'),
                'sampleDates' => implode(', ', array_slice($failedDates, 0, 10)),
            ]);
        }

        return $result;
    }

    /**
     * Memuat cache adjustment untuk range tanggal tertentu.
     *
     * @return array<string, array>
     */
    private function loadCachedAdjustmentsByRange(int $year, int $maxMonth): array
    {
        $startDate = sprintf('%04d-01-01', $year);
        $endDate = (new \DateTime(sprintf('%04d-%02d-01', $year, max(1, min(12, $maxMonth)))))->modify('last day of this month')->format('Y-m-d');

        try {
            $db = Database::connect('sparepart_price');
            $rows = $db->table('crp_adjustment_daily_cache')
                ->select('cache_date, payload_json')
                ->where('cache_date >=', $startDate)
                ->where('cache_date <=', $endDate)
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'Gagal membaca cache adjustment CRP: {message}', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }

        $cachedRowsByDate = [];
        foreach ($rows as $row) {
            $tanggal = trim((string) ($row['cache_date'] ?? ''));
            if ($tanggal === '') {
                continue;
            }

            $payload = json_decode((string) ($row['payload_json'] ?? '[]'), true);
            if (!is_array($payload)) {
                $payload = [];
            }

            $cachedRowsByDate[$tanggal] = $payload;
        }

        return $cachedRowsByDate;
    }

    /**
     * Simpan response harian ke cache lokal.
     */
    private function storeAdjustmentsToCache(string $tanggal, array $rows): void
    {
        try {
            $db = Database::connect('sparepart_price');
            $builder = $db->table('crp_adjustment_daily_cache');

            $payload = json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($payload === false) {
                return;
            }

            $data = [
                'cache_date'   => $tanggal,
                'cache_year'   => (int) substr($tanggal, 0, 4),
                'cache_month'  => (int) substr($tanggal, 5, 2),
                'record_count' => count($rows),
                'payload_json' => $payload,
                'fetched_at'   => date('Y-m-d H:i:s'),
            ];

            $existing = $builder
                ->select('cache_date')
                ->where('cache_date', $tanggal)
                ->get()
                ->getFirstRow('array');

            if ($existing !== null) {
                $builder->where('cache_date', $tanggal)->update($data);
                return;
            }

            $builder->insert($data);
        } catch (\Throwable $e) {
            log_message('warning', 'Gagal menyimpan cache adjustment CRP ({tanggal}): {message}', [
                'tanggal' => $tanggal,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Buat key unik untuk deduplikasi baris adjustment.
     */
    private function buildAdjustmentUniqueKey(array $row): string
    {
        $uniqueKey = (string) ($row['NO_ADJ'] ?? '');

        if ($uniqueKey === '') {
            return implode('|', [
                (string) ($row['ITEM'] ?? ''),
                (string) ($row['WAREHOUSE'] ?? ''),
                (string) ($row['_source_tanggal'] ?? ''),
                (string) ($row['QTY_ADJ'] ?? ''),
                (string) ($row['PRICE'] ?? ''),
            ]);
        }

        return implode('|', [
            $uniqueKey,
            (string) ($row['ITEM'] ?? ''),
            (string) ($row['WAREHOUSE'] ?? ''),
            (string) ($row['_source_tanggal'] ?? ''),
            (string) ($row['QTY_ADJ'] ?? ''),
            (string) ($row['PRICE'] ?? ''),
            (string) ($row['ADJ_DATE'] ?? ''),
        ]);
    }

    private function attachSourceDateToRows(array $rows, string $tanggal): array
    {
        if ($rows === []) {
            return [];
        }

        foreach ($rows as &$row) {
            if (!is_array($row)) {
                $row = [];
            }

            $row['_source_tanggal'] = $tanggal;
        }
        unset($row);

        return $rows;
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

    private function hasApiConfiguration(): bool
    {
        if (trim((string) $this->adjustmentEndpoint) === '') {
            log_message('error', 'CRP API config invalid. Pastikan api.baseURL terisi di file .env.');
            return false;
        }

        if (trim((string) $this->config->token) === '' && trim((string) env('api.token')) === '') {
            log_message('error', 'CRP API config invalid. Pastikan api.token terisi di file .env.');
            return false;
        }

        return true;
    }
}