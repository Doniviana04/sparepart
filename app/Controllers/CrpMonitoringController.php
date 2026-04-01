<?php

namespace App\Controllers;

use App\Libraries\CrpApiService;
use CodeIgniter\API\ResponseTrait;

class CrpMonitoringController extends BaseController
{
    use ResponseTrait;

    protected CrpApiService $crpApi;

    /**
     * Inisialisasi service API CRP untuk halaman monitoring quota.
     */
    public function __construct()
    {
        $this->crpApi = new CrpApiService();
    }

    /**
     * GET /crp/monitoring
     */
    public function index()
    {
        return view('crp_dashboard/dashboard_monitoring_crp');
    }

    /**
     * GET /crp/monitoring/data?month=2026-03
     */
    public function getData()
    {
        @set_time_limit(300);

        [$year, $month] = $this->resolvePeriod();
        $rows = $this->buildMonitoringData($year, $month);

        return $this->respond([
            'status' => 'success',
            'year'   => $year,
            'month'  => $month,
            'data'   => $rows,
        ]);
    }

    /**
     * Memvalidasi parameter month dan mengembalikan [year, month].
     */
    private function resolvePeriod(): array
    {
        $monthYear = (string) ($this->request->getGet('month') ?? date('Y-m'));

        if (!preg_match('/^(\d{4})-(\d{2})$/', $monthYear, $matches)) {
            return [(int) date('Y'), (int) date('m')];
        }

        $year = (int) $matches[1];
        $month = max(1, min(12, (int) $matches[2]));

        return [$year, $month];
    }

    /**
     * Menyusun data monitoring quota per part number.
     */
    private function buildMonitoringData(int $year, int $month): array
    {
        $quotaRows = $this->crpApi->getAdjustmentsByYearUntilMonth($year, 12);
        $uptodateRows = $this->crpApi->getAdjustmentsByYearUntilMonth($year, $month);

        $items = [];

        foreach ($quotaRows as $row) {
            $warehouse = strtoupper(trim((string) ($row['WAREHOUSE'] ?? '')));
            if ($warehouse !== 'KWSPT') {
                continue;
            }

            $partNumber = $this->normalizePartNumber((string) ($row['ITEM'] ?? ''));
            if ($partNumber === '') {
                continue;
            }

            if (!isset($items[$partNumber])) {
                $items[$partNumber] = [
                    'part_number' => $partNumber,
                    'description' => $this->resolveDescription($row),
                    'quota_qty'   => 0.0,
                    'uptodate'    => 0.0,
                ];
            }

            $items[$partNumber]['quota_qty'] += abs((float) ($row['QTY_ADJ'] ?? 0));

            if ($items[$partNumber]['description'] === '-') {
                $items[$partNumber]['description'] = $this->resolveDescription($row);
            }
        }

        foreach ($uptodateRows as $row) {
            $warehouse = strtoupper(trim((string) ($row['WAREHOUSE'] ?? '')));
            if ($warehouse !== 'KWSPT') {
                continue;
            }

            $partNumber = $this->normalizePartNumber((string) ($row['ITEM'] ?? ''));
            if ($partNumber === '' || !isset($items[$partNumber])) {
                continue;
            }

            $items[$partNumber]['uptodate'] += abs((float) ($row['QTY_ADJ'] ?? 0));
        }

        ksort($items);

        $result = [];
        $no = 1;

        foreach ($items as $item) {
            $quotaQty = round((float) $item['quota_qty'], 2);
            $uptodate = round((float) $item['uptodate'], 2);
            $aktual = round($quotaQty - $uptodate, 2);
            $ideal = round(($quotaQty / 12) * $month, 2);

            $keterangan = $aktual > $ideal ? 'Harap lebih hemat' : 'OK';
            $status = $aktual > $ideal ? 'Perlu Control' : 'OK';

            $result[] = [
                'NO'               => $no++,
                'PART_NUMBER'      => $item['part_number'],
                'DESCRIPTION'      => $item['description'],
                'QUOTA_QTY'        => $quotaQty,
                'UPTODATE_USAGE'   => $uptodate,
                'SISA_AKTUAL'      => $aktual,
                'SISA_IDEAL'       => $ideal,
                'KETERANGAN'       => $keterangan,
                'STATUS'           => $status,
            ];
        }

        return $result;
    }

    /**
     * Menentukan deskripsi dari beberapa alternatif field API.
     */
    private function resolveDescription(array $row): string
    {
        $description = trim((string) (
            $row['DESCRIPTION']
            ?? $row['ITEM_DESC']
            ?? $row['ITEM_NAME']
            ?? '-'
        ));

        return $description === '' ? '-' : $description;
    }

    /**
     * Menormalisasi part number agar konsisten saat agregasi.
     */
    private function normalizePartNumber(string $partNumber): string
    {
        return strtoupper(trim($partNumber));
    }
}
