<?php

namespace App\Controllers;

use App\Libraries\CrpApiService;
use Config\Database;
use CodeIgniter\API\ResponseTrait;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CrpController extends BaseController
{
    use ResponseTrait;

    protected CrpApiService $crpApi;

    /**
     * Inisialisasi service API CRP untuk kebutuhan data dashboard.
     */
    public function __construct()
    {
        $this->crpApi = \Config\Services::crpApiService();
    }

    /**
     * GET /crp
     * Tampilkan halaman dashboard CRP
     */
    public function index()
    {
        return view('crp_dashboard/crp_dashboard');
    }

    /**
     * Menampilkan halaman monitor user.
     */
    public function monitorUser()
    {
        return view('monitor_user/monitor_user');
    }

    /**
     * GET /crp/data?month=2026-03
     * Mengembalikan JSON data yang sudah diolah untuk tabel dashboard
     */
    public function getData()
    {
        @set_time_limit(300);

        [$year, $month] = $this->resolvePeriod();
        $prevYear = $year - 1;
        $controlMode = $this->resolveControlMode();
        $result = $this->buildResultData($year, $month);

        if ($controlMode === 'controlled') {
            $result = array_values(array_filter(
                $result,
                static fn(array $row): bool => !empty($row['CONTROLLED'])
            ));
        }

        $filters = $this->buildFilterOptions($result, $year);
        $result = $this->applyColumnFilters($result, $year);

        $result = $this->reindexRowNumbers($result);
        $pagination = $this->resolvePagination();
        $pagedData = $this->paginateRows($result, $pagination['page'], $pagination['per_page'], $pagination['is_all']);

        return $this->respond([
            'status'    => 'success',
            'data'      => $pagedData['data'],
            'year'      => $year,
            'month'     => $month,
            'prev_year' => $prevYear,
            'control_mode' => $controlMode,
            'filters'   => $filters,
            'pagination'=> $pagedData['meta'],
        ]);
    }

    /**
     * GET /crp/chart-usage?month=2026-03&part_number=XYZ
     * Mengembalikan data grafik Actual Penggunaan vs Max Kuota per part number.
     */
    public function getUsageChartData()
    {
        @set_time_limit(300);

        [$year, $month] = $this->resolvePeriod();
        $partNumber = $this->normalizePartNumber((string) ($this->request->getGet('part_number') ?? ''));

        if ($partNumber === '') {
            return $this->failValidationErrors('Parameter part_number wajib diisi.');
        }

        $snapshot = $this->getUsageChartSnapshot($year, $month);
        $maxQuota = (float) ($snapshot['quota'][$partNumber] ?? 0);
        $monthlyUsage = $snapshot['monthly'][$partNumber] ?? array_fill(1, 12, 0.0);
        $quotaMatchedRows = (int) ($snapshot['quota_rows'][$partNumber] ?? 0);
        $actualMatchedRows = (int) ($snapshot['actual_rows'][$partNumber] ?? 0);

        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Des'];
        $actualCumulative = [];
        $actualMonthly = [];
        $running = 0.0;

        for ($i = 1; $i <= 12; $i++) {
            if ($i <= $month) {
                $running += (float) ($monthlyUsage[$i] ?? 0);
                $actualCumulative[] = round($running, 2);
                $actualMonthly[] = round((float) ($monthlyUsage[$i] ?? 0), 2);
            } else {
                $actualCumulative[] = null;
                $actualMonthly[] = null;
            }
        }

        $maxQuotaLine = array_fill(0, 12, round($maxQuota, 2));

        log_message('debug', 'CRP chart-usage part={part} period={period} matched_quota_rows={quotaRows} matched_actual_rows={actualRows} max_quota={maxQuota}', [
            'part' => $partNumber,
            'period' => sprintf('%04d-%02d', $year, $month),
            'quotaRows' => $quotaMatchedRows,
            'actualRows' => $actualMatchedRows,
            'maxQuota' => round($maxQuota, 2),
        ]);

        return $this->respond([
            'status'        => 'success',
            'part_number'   => $partNumber,
            'year'          => $year,
            'labels'        => $labels,
            'actual_usage'  => $actualCumulative,
            'actual_monthly' => $actualMonthly,
            'max_quota'     => $maxQuotaLine,
            'max_quota_val' => round($maxQuota, 2),
            'snapshot_cached' => (bool) ($snapshot['cached'] ?? false),
        ])
            // Pastikan browser/proxy tidak menyajikan response chart lama untuk part lain.
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0');
    }

    /**
     * Snapshot agregasi usage chart per periode (tahun-bulan).
     * Tujuan: endpoint chart cepat saat user klik banyak part number.
     */
    private function getUsageChartSnapshot(int $year, int $month): array
    {
        $cacheKey = sprintf('crp_usage_snapshot_%04d_%02d', $year, $month);
        $cache = \Config\Services::cache();
        $cached = $cache->get($cacheKey);

        if (is_array($cached)
            && isset($cached['quota'], $cached['monthly'], $cached['quota_rows'], $cached['actual_rows'])
            && is_array($cached['quota'])
            && is_array($cached['monthly'])
        ) {
            $cached['cached'] = true;
            return $cached;
        }

        $prevYear = $year - 1;
        $quotaRows = $this->crpApi->getAdjustmentsByYearUntilMonth($prevYear, 12);
        $actualRows = $this->crpApi->getAdjustmentsByYearUntilMonth($year, $month);

        $quotaByPart = [];
        $quotaRowsByPart = [];
        foreach ($quotaRows as $row) {
            $warehouse = strtoupper(trim((string) ($row['WAREHOUSE'] ?? '')));
            if ($warehouse !== 'KWSPT') {
                continue;
            }

            $part = $this->resolvePartNumberFromAdjustmentRow($row);
            if ($part === '') {
                continue;
            }

            $quotaByPart[$part] = (float) ($quotaByPart[$part] ?? 0) + abs((float) ($row['QTY_ADJ'] ?? 0));
            $quotaRowsByPart[$part] = (int) ($quotaRowsByPart[$part] ?? 0) + 1;
        }

        $monthlyByPart = [];
        $actualRowsByPart = [];
        foreach ($actualRows as $row) {
            $warehouse = strtoupper(trim((string) ($row['WAREHOUSE'] ?? '')));
            if ($warehouse !== 'KWSPT') {
                continue;
            }

            $part = $this->resolvePartNumberFromAdjustmentRow($row);
            if ($part === '') {
                continue;
            }

            $periodInfo = $this->resolvePeriodFromAdjustmentRow($row, $year);
            if ($periodInfo === null || (int) ($periodInfo['year'] ?? 0) !== $year) {
                continue;
            }

            $monthFromRow = (int) ($periodInfo['month'] ?? 0);
            if ($monthFromRow < 1 || $monthFromRow > 12) {
                continue;
            }

            if (!isset($monthlyByPart[$part])) {
                $monthlyByPart[$part] = array_fill(1, 12, 0.0);
            }

            $monthlyByPart[$part][$monthFromRow] += abs((float) ($row['QTY_ADJ'] ?? 0));
            $actualRowsByPart[$part] = (int) ($actualRowsByPart[$part] ?? 0) + 1;
        }

        $snapshot = [
            'quota' => $quotaByPart,
            'monthly' => $monthlyByPart,
            'quota_rows' => $quotaRowsByPart,
            'actual_rows' => $actualRowsByPart,
            'cached' => false,
            'generated_at' => date('Y-m-d H:i:s'),
        ];

        $cache->save($cacheKey, $snapshot, 300);

        return $snapshot;
    }

    /**
     * GET /crp/chart-summary-amount?month=2026-03
     * Mengembalikan data grafik summary Amount dari semua part number.
     * Bars: Amount current year (per bulan sampai selected month)
     * Line: Amount previous year (full 12 months)
     */
    public function getSummaryAmountChartData()
    {
        @set_time_limit(300);

        [$year, $month] = $this->resolvePeriod();
        $prevYear = $year - 1;

        // Ambil data tahun sebelumnya (full year)
        $adjustmentsPrev = $this->crpApi->getAdjustmentsByYearUntilMonth($prevYear, 12);

        // Ambil data tahun berjalan sampai bulan terpilih
        $adjustmentsCurr = $this->crpApi->getAdjustmentsByYearUntilMonth($year, $month);

        // Ambil master untuk resolve price
        $itemKeys = [];
        foreach ([$adjustmentsPrev, $adjustmentsCurr] as $rows) {
            foreach ($rows as $row) {
                $warehouse = strtoupper(trim((string) ($row['WAREHOUSE'] ?? '')));
                if ($warehouse !== 'KWSPT') {
                    continue;
                }
                $partNumber = $this->normalizePartNumber((string) ($row['ITEM'] ?? ''));
                if ($partNumber !== '') {
                    $itemKeys[$partNumber] = true;
                }
            }
        }

        $sqlMasterMap = $this->getSparepartMasterMap(array_keys($itemKeys));

        // Agregasi monthly amount untuk tahun sebelumnya (full year)
        $monthlyAmountPrev = array_fill(1, 12, 0.0);
        foreach ($adjustmentsPrev as $row) {
            $warehouse = strtoupper(trim((string) ($row['WAREHOUSE'] ?? '')));
            if ($warehouse !== 'KWSPT') {
                continue;
            }

            $key = $this->normalizePartNumber((string) ($row['ITEM'] ?? ''));
            if ($key === '') {
                continue;
            }

            $monthFromRow = $this->resolveMonthFromAdjustmentRow($row);
            if ($monthFromRow === null) {
                $monthFromRow = 12; // default ke Dec jika tidak dapat parse
            }

            $qty = abs((float) ($row['QTY_ADJ'] ?? 0));
            $price = $this->resolveFinalPrice($key, $sqlMasterMap, (float) ($row['PRICE'] ?? 0));
            $monthlyAmountPrev[$monthFromRow] += ($qty * $price);
        }

        // Agregasi monthly amount untuk tahun berjalan (until selected month)
        $monthlyAmountCurr = array_fill(1, 12, 0.0);
        foreach ($adjustmentsCurr as $row) {
            $warehouse = strtoupper(trim((string) ($row['WAREHOUSE'] ?? '')));
            if ($warehouse !== 'KWSPT') {
                continue;
            }

            $key = $this->normalizePartNumber((string) ($row['ITEM'] ?? ''));
            if ($key === '') {
                continue;
            }

            $monthFromRow = $this->resolveMonthFromAdjustmentRow($row);
            if ($monthFromRow === null) {
                $monthFromRow = $month;
            }

            $qty = abs((float) ($row['QTY_ADJ'] ?? 0));
            $price = $this->resolveFinalPrice($key, $sqlMasterMap, (float) ($row['PRICE'] ?? 0));
            $monthlyAmountCurr[$monthFromRow] += ($qty * $price);
        }

        // Format untuk chart: current year hanya sampai selected month
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Des'];
        $amountCurrFormatted = [];
        $amountPrevFormatted = [];

        for ($i = 1; $i <= 12; $i++) {
            // Current year: null untuk bulan yang belum terlewat
            if ($i <= $month) {
                $amountCurrFormatted[] = round($monthlyAmountCurr[$i], 2);
            } else {
                $amountCurrFormatted[] = null;
            }

            // Previous year: full 12 months
            $amountPrevFormatted[] = round($monthlyAmountPrev[$i], 2);
        }

        $totalPrevYear = array_sum($monthlyAmountPrev);
        $totalCurrYear = array_sum($monthlyAmountCurr);

        return $this->respond([
            'status'           => 'success',
            'year'             => $year,
            'prev_year'        => $prevYear,
            'labels'           => $labels,
            'amount_current'   => $amountCurrFormatted,
            'amount_previous'  => $amountPrevFormatted,
            'total_curr_year'  => round($totalCurrYear, 2),
            'total_prev_year'  => round($totalPrevYear, 2),
        ]);
    }

    /**
     * GET /monitor-user/data?month=YYYY-MM
     * Mengembalikan data dashboard yang hanya berstatus CONTROLLED.
     */
    public function getMonitorUserData()
    {
        @set_time_limit(300);

        [$year, $month] = $this->resolvePeriod();
        $prevYear = $year - 1;
        $result = array_values(array_filter(
            $this->buildResultData($year, $month),
            static fn(array $row): bool => !empty($row['CONTROLLED'])
        ));
        $pagination = $this->resolvePagination();
        $pagedData = $this->paginateRows($result, $pagination['page'], $pagination['per_page'], $pagination['is_all']);

        return $this->respond([
            'status'    => 'success',
            'data'      => $pagedData['data'],
            'year'      => $year,
            'month'     => $month,
            'prev_year' => $prevYear,
            'pagination'=> $pagedData['meta'],
        ]);
    }

    /**
     * GET /crp/export-excel?month=2026-03
     * Download data dashboard dalam format XLSX
     */
    public function exportExcel()
    {
        @set_time_limit(120);

        try {
            [$year, $month] = $this->resolvePeriod();
            $prevYear = $year - 1;
            $result = $this->buildResultData($year, $month);

            $headers = [
                'NO',
                'PART_NUMBER',
                'DESCRIPTION',
                'USAGE_QTY_' . $prevYear,
                'AMOUNT_' . $prevYear,
                'TARGET_5PCT',
                'ACH_AMOUNT',
                'ACH_PERSEN',
                'VARIANCE_AMOUNT',
                'STATUS_CONTROL',
            ];

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('CRP Dashboard');
            $sheet->fromArray($headers, null, 'A1');

            $rowNum = 2;
            foreach ($result as $row) {
                $achPercent = (float) rtrim((string) ($row['ACH_PERSEN'] ?? '0%'), '%') / 100;
                $prevAmount = (float) ($row['AMOUNT_' . $prevYear] ?? 0);
                $achAmount = (float) ($row['ACH_AMOUNT'] ?? 0);
                $varianceAmount = $achAmount - $prevAmount;

                $sheet->setCellValue("A{$rowNum}", (int) ($row['NO'] ?? 0));
                $sheet->setCellValue("B{$rowNum}", (string) ($row['PART_NUMBER'] ?? '-'));
                $sheet->setCellValue("C{$rowNum}", (string) ($row['DESCRIPTION'] ?? '-'));
                $sheet->setCellValue("D{$rowNum}", (float) ($row['USAGE_QTY_' . $prevYear] ?? 0));
                $sheet->setCellValue("E{$rowNum}", $prevAmount);
                $sheet->setCellValue("F{$rowNum}", (float) ($row['TARGET_5PCT'] ?? 0));
                $sheet->setCellValue("G{$rowNum}", $achAmount);
                $sheet->setCellValue("H{$rowNum}", $achPercent);
                $sheet->setCellValue("I{$rowNum}", $varianceAmount);
                $sheet->setCellValue("J{$rowNum}", (string) ($row['CONTROL_STATUS'] ?? 'Normal'));
                $rowNum++;
            }

            $lastRow = max(1, $rowNum - 1);
            $lastCol = Coordinate::stringFromColumnIndex(count($headers));
            $range = "A1:{$lastCol}{$lastRow}";

            $sheet->freezePane('A2');

            for ($col = 1; $col <= count($headers); $col++) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }

            $sheet->getStyle('A1:J1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F4E78'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            if ($lastRow >= 2) {
                $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D2:G{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle("H2:H{$lastRow}")->getNumberFormat()->setFormatCode('0.00%');
                $sheet->getStyle("I2:I{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle("D2:I{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("J2:J{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A2:J{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            }

            $sheet->getStyle($range)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D9D9D9'],
                    ],
                ],
            ]);

            $writer = new Xlsx($spreadsheet);
            ob_start();
            $writer->save('php://output');
            $xlsxData = (string) ob_get_clean();

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            $filename = sprintf('crp_dashboard_%04d-%02d.xlsx', $year, $month);

            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->setBody($xlsxData);
        } catch (\Throwable $e) {
            log_message('error', 'Export Excel CRP gagal ({period}): {message}', [
                'period' => sprintf('%04d-%02d', $year ?? 0, $month ?? 0),
                'message' => $e->getMessage(),
            ]);

            return $this->response
                ->setStatusCode(500)
                ->setBody('Gagal export Excel. Silakan coba lagi saat koneksi API CRP stabil.');
        }
    }

    /**
     * POST /crp/control
     * Menandai atau membatalkan status control part number pada periode tertentu.
     */
    public function setControlStatus()
    {
        $payload = $this->request->getJSON(true);
        if (!is_array($payload) || $payload === []) {
            $payload = $this->request->getPost();
        }

        $monthYear = trim((string) ($payload['month'] ?? date('Y-m')));
        if (!preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
            return $this->failValidationErrors('Format month harus YYYY-MM.');
        }

        $partNumber = $this->normalizePartNumber((string) ($payload['part_number'] ?? ''));
        if ($partNumber === '') {
            return $this->failValidationErrors('Part number wajib diisi.');
        }

        $controlled = filter_var($payload['controlled'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($controlled === null) {
            return $this->failValidationErrors('Nilai controlled harus boolean (true/false).');
        }

        try {
            $db = Database::connect('sparepart_price');
            $table = $db->table('crp_control_marks');
            $db->transStart();

            $existing = $table
                ->select('part_number')
                ->where('period_month', $monthYear)
                ->where('part_number', $partNumber)
                ->get()
                ->getFirstRow('array');

            if ($existing !== null) {
                $updated = $table
                    ->where('period_month', $monthYear)
                    ->where('part_number', $partNumber)
                    ->update(['controlled' => $controlled ? 1 : 0]);

                if ($updated === false) {
                    throw new \RuntimeException('Update control mark gagal dieksekusi.');
                }
            } elseif ($controlled) {
                $inserted = $table->insert([
                    'period_month' => $monthYear,
                    'part_number'  => $partNumber,
                    'controlled'   => 1,
                ]);

                if ($inserted === false) {
                    throw new \RuntimeException('Insert control mark gagal dieksekusi.');
                }
            }

            $db->transComplete();
            if ($db->transStatus() === false) {
                throw new \RuntimeException('Transaksi simpan control mark gagal.');
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menyimpan control mark CRP ({period}/{part}): {message}', [
                'period'  => $monthYear,
                'part'    => $partNumber,
                'message' => $e->getMessage(),
                'controlled' => $controlled ? 1 : 0,
            ]);

            return $this->failServerError('Gagal menyimpan status control.');
        }

        return $this->respond([
            'status'         => 'success',
            'month'          => $monthYear,
            'part_number'    => $partNumber,
            'controlled'     => $controlled,
            'control_status' => $controlled ? 'Perlu Control' : 'Normal',
        ]);
    }

    /**
     * Memvalidasi parameter month dan mengembalikan [year, month].
     * Fallback ke periode berjalan jika format tidak valid.
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
     * Ambil parameter halaman dari query string.
     */
    private function resolvePagination(): array
    {
        $rawLimit = $this->request->getGet('limit');
        $page = (int) ($this->request->getGet('page') ?? 1);
        $isAll = is_string($rawLimit) && strtolower(trim($rawLimit)) === 'all';
        $perPage = (int) ($rawLimit ?? 100);

        $page = max(1, $page);

        if ($isAll) {
            return [
                'page' => 1,
                'per_page' => null,
                'is_all' => true,
            ];
        }

        $allowedPerPage = [50, 100, 200];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 100;
        }

        return [
            'page' => $page,
            'per_page' => $perPage,
            'is_all' => false,
        ];
    }

    /**
     * Ambil mode filter control dari query string.
     */
    private function resolveControlMode(): string
    {
        $mode = strtolower(trim((string) ($this->request->getGet('control_mode') ?? 'all')));

        return in_array($mode, ['all', 'controlled'], true) ? $mode : 'all';
    }

    /**
     * Menomori ulang baris agar kolom NO tetap berurutan setelah filtering.
     */
    private function reindexRowNumbers(array $rows): array
    {
        $no = 1;
        foreach ($rows as &$row) {
            $row['NO'] = $no++;
        }
        unset($row);

        return $rows;
    }

    /**
     * Potong array data menjadi halaman tertentu dan sertakan metadata pagination.
     */
    private function paginateRows(array $rows, int $page, ?int $perPage, bool $isAll = false): array
    {
        $total = count($rows);

        if ($isAll || $perPage === null) {
            return [
                'data' => array_values($rows),
                'meta' => [
                    'page' => 1,
                    'per_page' => $total,
                    'total' => $total,
                    'total_pages' => 1,
                    'has_prev' => false,
                    'has_next' => false,
                    'is_all' => true,
                ],
            ];
        }

        $totalPages = max(1, (int) ceil($total / $perPage));
        $currentPage = min($page, $totalPages);
        $offset = ($currentPage - 1) * $perPage;

        return [
            'data' => array_values(array_slice($rows, $offset, $perPage)),
            'meta' => [
                'page' => $currentPage,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_prev' => $currentPage > 1,
                'has_next' => $currentPage < $totalPages,
                'is_all' => false,
            ],
        ];
    }

    /**
     * Menyusun dataset utama dashboard CRP per part number:
     * - usage/amount tahun sebelumnya
     * - achievement tahun berjalan
     * - target, persen, dan status control.
     */
    private function buildResultData(int $year, int $month): array
    {
        $prevYear = $year - 1;
        $controlMarks = $this->getControlMarksByPeriod($year, $month);

        // Ambil data tahun sebelumnya: selalu Januari-Desember (full year)
        $adjustmentsPrev = $this->crpApi->getAdjustmentsByYearUntilMonth($prevYear, 12);
        // Ambil data tahun berjalan: Januari sampai bulan terpilih user
        $adjustmentsCurr = $this->crpApi->getAdjustmentsByYearUntilMonth($year, $month);

        // Ambil master SQL sekali agar description dashboard CRP konsisten dengan monitor user.
        $itemKeys = [];
        $apiDescriptions = [];
        foreach ([$adjustmentsPrev, $adjustmentsCurr] as $rows) {
            foreach ($rows as $row) {
                $warehouse = strtoupper(trim((string) ($row['WAREHOUSE'] ?? '')));
                if ($warehouse !== 'KWSPT') {
                    continue;
                }

                $partNumber = $this->normalizePartNumber((string) ($row['ITEM'] ?? ''));
                if ($partNumber === '') {
                    continue;
                }

                $itemKeys[$partNumber] = true;

                $apiDescription = $this->resolveDescription($row);
                if ($apiDescription !== '-' && !isset($apiDescriptions[$partNumber])) {
                    $apiDescriptions[$partNumber] = $apiDescription;
                }
            }
        }

        $sqlMasterMap = $this->getSparepartMasterMap(array_keys($itemKeys));

        // Agregasi data per item
        $items = [];

        // Proses tahun sebelumnya (USAGE 2025)
        foreach ($adjustmentsPrev as $adj) {
            $warehouse = strtoupper(trim((string) ($adj['WAREHOUSE'] ?? '')));
            if ($warehouse !== 'KWSPT') {
                continue;
            }

            
            $key = $this->normalizePartNumber((string) ($adj['ITEM'] ?? ''));
            if ($key === '') {
                continue;
            }

            if (!isset($items[$key])) {
                $items[$key] = [
                    'ITEM'        => $key,
                    'DESCRIPTION' => $this->resolveFinalDescription($key, $sqlMasterMap, $apiDescriptions[$key] ?? $this->resolveDescription($adj)),
                    'usage_qty'   => 0,
                    'amount'      => 0,
                ];
            }

            if (($items[$key]['DESCRIPTION'] ?? '-') === '-') {
                $items[$key]['DESCRIPTION'] = $this->resolveFinalDescription($key, $sqlMasterMap, $apiDescriptions[$key] ?? $this->resolveDescription($adj));
            }

            // Karena QTY_ADJ bisa negatif/positif, ambil nilai absolut atau sesuai bisnis rule
            $qty   = abs((float)$adj['QTY_ADJ']);
            $price = $this->resolveFinalPrice($key, $sqlMasterMap, (float) ($adj['PRICE'] ?? 0));

            $items[$key]['usage_qty'] += $qty;
            $items[$key]['amount']    += $qty * $price;
        }

        // Proses tahun berjalan (achievement 2026)
        foreach ($adjustmentsCurr as $adj) {
            $warehouse = strtoupper(trim((string) ($adj['WAREHOUSE'] ?? '')));
            if ($warehouse !== 'KWSPT') {
                continue;
            }

            $key = $this->normalizePartNumber((string) ($adj['ITEM'] ?? ''));
            if ($key === '') {
                continue;
            }

            if (!isset($items[$key])) {
                $items[$key] = [
                    'ITEM'        => $key,
                    'DESCRIPTION' => $this->resolveFinalDescription($key, $sqlMasterMap, $apiDescriptions[$key] ?? $this->resolveDescription($adj)),
                    'usage_qty'   => 0,
                    'amount'      => 0,
                ];
            }

            if (($items[$key]['DESCRIPTION'] ?? '-') === '-') {
                $items[$key]['DESCRIPTION'] = $this->resolveFinalDescription($key, $sqlMasterMap, $apiDescriptions[$key] ?? $this->resolveDescription($adj));
            }

            $qty   = abs((float)$adj['QTY_ADJ']);
            $price = $this->resolveFinalPrice($key, $sqlMasterMap, (float) ($adj['PRICE'] ?? 0));

            $items[$key]['ach_amount'] = ($items[$key]['ach_amount'] ?? 0) + ($qty * $price);
        }

        // Hitung target & persentase
        $result = [];
        $no = 1;
        foreach ($items as $item) {
            $prev_amount   = $item['amount'] ?? 0;
            $target_5pct   = $prev_amount * 0.05;
            $ach_amount    = $item['ach_amount'] ?? 0;
            $persen        = $prev_amount > 0 ? ($ach_amount / $prev_amount) * 100 : 0;
            $variance      = $prev_amount - $ach_amount;
            $partNumber    = $this->normalizePartNumber((string) ($item['ITEM'] ?? ''));
            $controlled    = isset($controlMarks[$partNumber]);

            $result[] = [
                'NO'              => $no++,
                'PART_NUMBER'     => $partNumber,
                'DESCRIPTION'     => $item['DESCRIPTION'],
                'USAGE_QTY_' . $prevYear  => round($item['usage_qty'], 2),
                'AMOUNT_' . $prevYear     => round($prev_amount, 2),
                'TARGET_5PCT'     => round($target_5pct, 2),
                'ACH_AMOUNT'      => round($ach_amount, 2),
                'ACH_PERSEN'      => round($persen, 2) . '%',
                'VARIANCE_AMOUNT' => round($variance, 2),
                'CONTROLLED'      => $controlled,
                'CONTROL_STATUS'  => $controlled ? 'Perlu Control' : 'Normal',
            ];
        }

        return $result;
    }

    /**
     * Terapkan filter dropdown per kolom sebelum pagination.
     */
    private function applyColumnFilters(array $rows, int $year): array
    {
        $prevYear = $year - 1;
        $filters = [
            'part_number'     => trim((string) ($this->request->getGet('filter_part_number') ?? '')),
            'description'     => trim((string) ($this->request->getGet('filter_description') ?? '')),
            'usage_qty'       => trim((string) ($this->request->getGet('filter_usage_qty') ?? '')),
            'ach_persen'      => trim((string) ($this->request->getGet('filter_ach_persen') ?? '')),
            'variance_amount' => trim((string) ($this->request->getGet('filter_variance_amount') ?? '')),
        ];

        return array_values(array_filter($rows, function (array $row) use ($filters, $prevYear): bool {
            foreach ($filters as $key => $expected) {
                if ($expected === '') {
                    continue;
                }

                $actual = $this->getColumnFilterValue($row, $key, $prevYear);
                if ($key === 'variance_amount') {
                    $variance = (float) ($row['VARIANCE_AMOUNT'] ?? 0);
                    if ($expected === 'positive' && $variance <= 0) {
                        return false;
                    }

                    if ($expected === 'negative' && $variance >= 0) {
                        return false;
                    }

                    continue;
                }

                if ($actual !== $expected) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * Kumpulkan opsi dropdown unik untuk setiap kolom filter.
     */
    private function buildFilterOptions(array $rows, int $year): array
    {
        $prevYear = $year - 1;
        $options = [
            'part_number'     => [],
            'description'     => [],
            'usage_qty'       => [],
            'ach_persen'      => [],
            'variance_amount' => [
                ['value' => 'positive', 'label' => 'Positif'],
                ['value' => 'negative', 'label' => 'Negatif'],
            ],
        ];

        foreach ($rows as $row) {
            foreach (array_keys($options) as $key) {
                if ($key === 'variance_amount') {
                    continue;
                }

                $value = $this->getColumnFilterValue($row, $key, $prevYear);
                if ($value === '' || isset($options[$key][$value])) {
                    continue;
                }

                $options[$key][$value] = [
                    'value' => $value,
                    'label' => $this->formatFilterLabel($key, $value),
                ];
            }
        }

        foreach ($options as $key => $values) {
            $options[$key] = array_values($values);
        }

        return $options;
    }

    /**
     * Ambil nilai filter dalam format canonical agar mudah dibandingkan.
     */
    private function getColumnFilterValue(array $row, string $key, int $prevYear): string
    {
        return match ($key) {
            'part_number' => strtoupper(trim((string) ($row['PART_NUMBER'] ?? ''))),
            'description' => trim((string) ($row['DESCRIPTION'] ?? '')),
            'usage_qty' => $this->formatCanonicalNumber($row['USAGE_QTY_' . $prevYear] ?? null),
            'ach_persen' => $this->formatCanonicalPercent($row['ACH_PERSEN'] ?? null),
            'variance_amount' => trim((string) ($row['VARIANCE_AMOUNT'] ?? '')),
            default => '',
        };
    }

    /**
     * Format label dropdown agar lebih mudah dibaca pengguna.
     */
    private function formatFilterLabel(string $key, string $value): string
    {
        return match ($key) {
            'usage_qty' => number_format((float) $value, 2, ',', '.'),
            'ach_persen' => number_format((float) $value, 2, ',', '.') . '%',
            default => $value,
        };
    }

    /**
     * Normalisasi angka menjadi string canonical 2 desimal.
     */
    private function formatCanonicalNumber($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value, 2, '.', '');
    }

    /**
     * Normalisasi persentase menjadi string canonical 2 desimal tanpa tanda %.
     */
    private function formatCanonicalPercent($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) rtrim((string) $value, '%'), 2, '.', '');
    }

    /**
     * Mengambil daftar part yang ditandai control untuk periode tertentu.
     */
    private function getControlMarksByPeriod(int $year, int $month): array
    {
        $period = sprintf('%04d-%02d', $year, $month);

        try {
            $db = Database::connect('sparepart_price');
            $rows = $db->table('crp_control_marks')
                ->select('part_number')
                ->where('period_month', $period)
                ->where('controlled', 1)
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'Gagal membaca control marks CRP ({period}): {message}', [
                'period'  => $period,
                'message' => $e->getMessage(),
            ]);

            return [];
        }

        $marks = [];
        foreach ($rows as $row) {
            $partNumber = $this->normalizePartNumber((string) ($row['part_number'] ?? ''));
            if ($partNumber !== '') {
                $marks[$partNumber] = true;
            }
        }

        return $marks;
    }

    /**
     * Menormalisasi part number agar konsisten sebagai key agregasi.
     */
    private function normalizePartNumber(string $partNumber): string
    {
        return strtoupper(trim($partNumber));
    }

    /**
     * Ambil bulan (1-12) dari baris adjustment API.
     */
    private function resolveMonthFromAdjustmentRow(array $row): ?int
    {
        // API ADJ_DATE kadang tidak valid; prioritaskan tanggal request dari service.
        $sourceTanggal = trim((string) ($row['_source_tanggal'] ?? $row['_SOURCE_TANGGAL'] ?? ''));
        if ($sourceTanggal !== '') {
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $sourceTanggal, $matches) === 1) {
                $month = (int) $matches[2];
                if ($month >= 1 && $month <= 12) {
                    return $month;
                }
            }

            $timestamp = strtotime($sourceTanggal);
            if ($timestamp !== false) {
                $month = (int) date('n', $timestamp);
                if ($month >= 1 && $month <= 12) {
                    return $month;
                }
            }
        }

        foreach (['MONTH', 'MON', 'BULAN', 'MM'] as $monthField) {
            if (isset($row[$monthField]) && is_numeric($row[$monthField])) {
                $month = (int) $row[$monthField];
                if ($month >= 1 && $month <= 12) {
                    return $month;
                }
            }
        }

        foreach (['_SOURCE_TANGGAL', '_source_tanggal', 'DATE', 'TRANS_DATE', 'DOC_DATE', 'TANGGAL', 'ADJ_DATE', 'POSTING_DATE', 'DATE_ADJ', 'TRX_DATE', 'CREATE_DATE'] as $dateField) {
            $dateValue = trim((string) ($row[$dateField] ?? ''));
            if ($dateValue === '') {
                continue;
            }

            // Format numerik umum: YYYYMMDD
            if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $dateValue, $matches) === 1) {
                $month = (int) $matches[2];
                if ($month >= 1 && $month <= 12) {
                    return $month;
                }
            }

            // Format umum: DD/MM/YYYY atau DD-MM-YYYY
            if (preg_match('/^\d{2}[\/\-](\d{2})[\/\-]\d{4}$/', $dateValue, $matches) === 1) {
                $month = (int) $matches[1];
                if ($month >= 1 && $month <= 12) {
                    return $month;
                }
            }

            $timestamp = strtotime($dateValue);
            if ($timestamp === false) {
                continue;
            }

            $month = (int) date('n', $timestamp);
            if ($month >= 1 && $month <= 12) {
                return $month;
            }
        }

        return null;
    }

    /**
     * Resolve periode transaksi dari baris adjustment.
     * Disamakan dengan History Admin agar agregasi qty konsisten antar halaman.
     */
    private function resolvePeriodFromAdjustmentRow(array $row, int $defaultYear): ?array
    {
        $sourceTanggal = trim((string) ($row['_source_tanggal'] ?? $row['_SOURCE_TANGGAL'] ?? ''));
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $sourceTanggal, $matches) === 1) {
            return [
                'year' => (int) $matches[1],
                'month' => (int) $matches[2],
                'day' => (int) $matches[3],
            ];
        }

        foreach (['DATE', 'TRANS_DATE', 'DOC_DATE', 'TANGGAL', 'ADJ_DATE', 'POSTING_DATE', 'DATE_ADJ', 'TRX_DATE', 'CREATE_DATE'] as $field) {
            $value = trim((string) ($row[$field] ?? ''));
            if ($value === '') {
                continue;
            }

            $timestamp = strtotime($value);
            if ($timestamp === false) {
                continue;
            }

            return [
                'year' => (int) date('Y', $timestamp),
                'month' => (int) date('n', $timestamp),
                'day' => (int) date('j', $timestamp),
            ];
        }

        foreach (['MONTH', 'MON', 'BULAN', 'MM'] as $monthField) {
            if (!isset($row[$monthField]) || !is_numeric($row[$monthField])) {
                continue;
            }

            $month = (int) $row[$monthField];
            if ($month < 1 || $month > 12) {
                continue;
            }

            $day = 1;
            if (isset($row['DAY']) && is_numeric($row['DAY'])) {
                $day = max(1, min(31, (int) $row['DAY']));
            }

            return [
                'year' => $defaultYear,
                'month' => $month,
                'day' => $day,
            ];
        }

        return null;
    }

    /**
     * Menentukan deskripsi item dari beberapa kemungkinan field API.
     */
    private function resolveDescription(array $row): string
    {
        $description = trim((string) (
            $row['DESCRIPTION']
            ?? $row['ITEM_DESC']
            ?? $row['ITEM_NAME']
            ?? '-'
        ));

        $description = $this->normalizeUtf8Text($description);

        return $description === '' ? '-' : $description;
    }

    /**
     * Mengambil master ITEM dari SQL Server (item, description, price) sekali per request.
     */
    private function getSparepartMasterMap(array $partNumbers): array
    {
        if ($partNumbers === []) {
            return [];
        }

        try {
            $db = Database::connect('sparepart_price');
            $rows = [];

            foreach (array_chunk($partNumbers, 1000) as $chunk) {
                $chunkRows = $db->table('sparepart_price')
                    ->select('item, description, price')
                    ->whereIn('item', $chunk)
                    ->get()
                    ->getResultArray();

                if ($chunkRows !== []) {
                    $rows = array_merge($rows, $chunkRows);
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gagal membaca master sparepart_price CRP: {message}', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }

        $master = [];
        foreach ($rows as $row) {
            $key = $this->normalizePartNumber((string) ($row['item'] ?? ''));
            if ($key === '') {
                continue;
            }

            $master[$key] = [
                'description' => trim((string) ($row['description'] ?? '')),
                'price'       => (float) ($row['price'] ?? 0),
            ];
        }

        return $master;
    }

    /**
     * Menentukan description final: prioritas SQL Server, fallback ke API.
     */
    private function resolveFinalDescription(string $partNumber, array $sqlMasterMap, string $apiDescription = '-'): string
    {
        if (isset($sqlMasterMap[$partNumber])) {
            $sqlDescription = trim((string) ($sqlMasterMap[$partNumber]['description'] ?? ''));
            if ($sqlDescription !== '') {
                return $this->normalizeUtf8Text($sqlDescription);
            }
        }

        $apiDescription = $this->normalizeUtf8Text(trim($apiDescription));

        return $apiDescription !== '' ? $apiDescription : '-';
    }

    /**
     * Menentukan price final: prioritas SQL Server, fallback ke API.
     */
    private function resolveFinalPrice(string $partNumber, array $sqlMasterMap, float $apiPrice = 0): float
    {
        if (isset($sqlMasterMap[$partNumber])) {
            $sqlPrice = (float) ($sqlMasterMap[$partNumber]['price'] ?? 0);
            if ($sqlPrice > 0) {
                return $sqlPrice;
            }
        }

        return $apiPrice > 0 ? $apiPrice : 0;
    }

    /**
     * Ambil part number dari beberapa kemungkinan nama field API.
     */
    private function resolvePartNumberFromAdjustmentRow(array $row): string
    {
        foreach (['ITEM', 'PART_NUMBER', 'PART_NO', 'PARTNUMBER', 'ITEM_NO', 'MATERIAL'] as $field) {
            $candidate = $this->normalizePartNumber((string) ($row[$field] ?? ''));
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * Menormalkan teks agar aman di-encode ke JSON (UTF-8 valid).
     */
    private function normalizeUtf8Text(string $text): string
    {
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_check_encoding') && mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        if (function_exists('mb_convert_encoding')) {
            $detected = mb_detect_encoding($text, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'UTF-16LE', 'UTF-16BE'], true);
            if ($detected !== false) {
                $converted = mb_convert_encoding($text, 'UTF-8', $detected);
                if (is_string($converted) && $converted !== '') {
                    return $converted;
                }
            }
        }

        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'UTF-8//IGNORE', $text);
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }

        return preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $text) ?? '';
    }

    // Route export Excel bisa ditambahkan nanti (gunakan library seperti PhpSpreadsheet)
/**
     * GET /crp/debug-adjustments?month=2026-03&item=PART_NUMBER
     * Endpoint debug untuk cek kenapa ACH_AMOUNT/PERSEN tidak muncul
     */
    public function debugAdjustments()
    {
        [$year, $month] = $this->resolvePeriod();
        $itemFilter = strtoupper(trim((string) ($this->request->getGet('item') ?? '')));

        $adjustmentsCurr = $this->crpApi->getAdjustmentsByYearUntilMonth($year, $month);

        $kwsptRecords = 0;
        $nonKwsptRecords = 0;
        $allItems = [];
        $kwsptItems = [];
        $nonKwsptSamples = [];
        $itemMatches = [];

        foreach ($adjustmentsCurr as $adj) {
            $item = strtoupper(trim((string) ($adj['ITEM'] ?? '')));
            $warehouse = strtoupper(trim((string) ($adj['WAREHOUSE'] ?? '')));

            if ($item !== '') {
                $allItems[$item] = true;
            }

            if ($warehouse === 'KWSPT') {
                $kwsptRecords++;
                if ($item !== '') {
                    $kwsptItems[$item] = true;
                }
            } else {
                $nonKwsptRecords++;
                if (count($nonKwsptSamples) < 20) {
                    $nonKwsptSamples[] = [
                        'ITEM'      => $adj['ITEM'] ?? null,
                        'WAREHOUSE' => $adj['WAREHOUSE'] ?? null,
                        'QTY_ADJ'   => $adj['QTY_ADJ'] ?? null,
                        'PRICE'     => $adj['PRICE'] ?? null,
                    ];
                }
            }

            if ($itemFilter !== '' && $item === $itemFilter) {
                $itemMatches[] = [
                    'ITEM'      => $adj['ITEM'] ?? null,
                    'WAREHOUSE' => $adj['WAREHOUSE'] ?? null,
                    'QTY_ADJ'   => $adj['QTY_ADJ'] ?? null,
                    'PRICE'     => $adj['PRICE'] ?? null,
                    'DATE'      => $adj['DATE'] ?? null,
                ];
            }
        }

        return $this->respond([
            'status' => 'success',
            'period' => sprintf('%04d-%02d', $year, $month),
            'summary' => [
                'curr_total_records'       => count($adjustmentsCurr),
                'curr_kwspt_records'       => $kwsptRecords,
                'curr_non_kwspt_records'   => $nonKwsptRecords,
                'curr_distinct_items'      => count($allItems),
                'curr_distinct_kwspt_items'=> count($kwsptItems),
                'item_filter'              => $itemFilter !== '' ? $itemFilter : null,
                'item_filter_found'        => $itemFilter !== '' ? count($itemMatches) > 0 : null,
            ],
            'item_filter_records' => $itemMatches,
            'sample_non_kwspt'    => $nonKwsptSamples,
            'sample_all_items'    => array_slice(array_keys($allItems), 0, 100),
            'sample_kwspt_items'  => array_slice(array_keys($kwsptItems), 0, 100),
        ]);
    }

    /**
     * GET /crp/debug-part-qty?month=2026-04&part_number=PART&warehouse=KWSPT
     * Endpoint debug untuk cek quantity data per part & warehouse
     */
    public function debugPartQty()
    {
        [$year, $month] = $this->resolvePeriod();
        $partNumber = $this->normalizePartNumber((string) ($this->request->getGet('part_number') ?? ''));
        $warehouse = strtoupper(trim((string) ($this->request->getGet('warehouse') ?? '')));

        if ($partNumber === '') {
            return $this->failValidationErrors('Parameter part_number wajib diisi.');
        }

        if ($warehouse === '') {
            return $this->failValidationErrors('Parameter warehouse wajib diisi.');
        }

        try {
            $adjustmentsCurr = $this->crpApi->getAdjustmentsByYearUntilMonth($year, $month);
            $adjustmentsPrev = $this->crpApi->getAdjustmentsByYearUntilMonth($year - 1, $month);

            // Filter untuk part & warehouse tertentu
            $currMatches = array_filter($adjustmentsCurr, function ($adj) use ($partNumber, $warehouse) {
                return strtoupper(trim((string) ($adj['ITEM'] ?? ''))) === $partNumber
                    && strtoupper(trim((string) ($adj['WAREHOUSE'] ?? ''))) === $warehouse;
            });

            $prevMatches = array_filter($adjustmentsPrev, function ($adj) use ($partNumber, $warehouse) {
                return strtoupper(trim((string) ($adj['ITEM'] ?? ''))) === $partNumber
                    && strtoupper(trim((string) ($adj['WAREHOUSE'] ?? ''))) === $warehouse;
            });

            // Hitung aggregate
            $currTotal = 0;
            $currPrice = 0;
            foreach ($currMatches as $adj) {
                $currTotal += (float) ($adj['QTY_ADJ'] ?? 0);
                $currPrice = (float) ($adj['PRICE'] ?? 0);
            }

            $prevTotal = 0;
            $prevPrice = 0;
            foreach ($prevMatches as $adj) {
                $prevTotal += (float) ($adj['QTY_ADJ'] ?? 0);
                $prevPrice = (float) ($adj['PRICE'] ?? 0);
            }

            return $this->respond([
                'status' => 'success',
                'period' => sprintf('%04d-%02d', $year, $month),
                'part_number' => $partNumber,
                'warehouse' => $warehouse,
                'current_year' => [
                    'year' => $year,
                    'total_qty' => $currTotal,
                    'price' => $currPrice,
                    'record_count' => count($currMatches),
                    'sample_records' => array_slice(array_values($currMatches), 0, 10),
                ],
                'previous_year' => [
                    'year' => $year - 1,
                    'total_qty' => $prevTotal,
                    'price' => $prevPrice,
                    'record_count' => count($prevMatches),
                    'sample_records' => array_slice(array_values($prevMatches), 0, 10),
                ],
                'variance' => [
                    'qty_diff' => $currTotal - $prevTotal,
                    'qty_persen' => $prevTotal != 0 ? (($currTotal - $prevTotal) / $prevTotal * 100) : 0,
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Debug part-qty gagal ({period}/{part}/{warehouse}): {message}', [
                'period'    => sprintf('%04d-%02d', $year, $month),
                'part'      => $partNumber,
                'warehouse' => $warehouse,
                'message'   => $e->getMessage(),
            ]);

            return $this->fail('Debug part-qty gagal: ' . $e->getMessage(), 500);
        }
    }
    
}