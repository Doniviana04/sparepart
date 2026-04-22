<?php

namespace App\Controllers;

use App\Libraries\CrpApiService;
use CodeIgniter\API\ResponseTrait;
use Config\Database;

class HistoryAdmController extends BaseController
{
    use ResponseTrait;

    protected CrpApiService $crpApi;

    public function __construct()
    {
        $this->crpApi = \Config\Services::crpApiService();
    }

    /**
     * GET /history-admin
     * Tampilkan halaman history admin CRP.
     */
    public function index(): string
    {
        return view('History_adm/history_adm');
    }

    /**
     * GET /history-admin/data?month=YYYY-MM
     * Data history admin dengan total usage per minggu.
     */
    public function getData()
    {
        @set_time_limit(300);

        try {
            [$year, $month] = $this->resolvePeriod();
            [$beforeYear, $beforeMonth] = $this->resolvePreviousPeriod($year, $month);

            $currentRows = $this->crpApi->getAdjustmentsByYearUntilMonth($year, $month);
            $prevYearRows = $this->crpApi->getAdjustmentsByYearUntilMonth($year - 1, 12);

            $weeklyRanges = $this->buildWeeklyRanges($year, $month);
            $currentPeriodKey = sprintf('%04d-%02d', $year, $month);
            $beforePeriodKey = sprintf('%04d-%02d', $beforeYear, $beforeMonth);

            $usageCurrentByPartDay = [];
            $usageCurrentByPartTotal = [];
            $usageBeforeByPartTotal = [];
            $usagePrevYearByPartTotal = [];
            $partApiDescriptions = [];

            foreach ($currentRows as $row) {
            if (!$this->isRelevantWarehouse($row)) {
                continue;
            }

            $partNumber = $this->resolvePartNumberFromAdjustmentRow($row);
            if ($partNumber === '') {
                continue;
            }

            $apiDescription = $this->resolveDescription($row);
            if ($apiDescription !== '-' && !isset($partApiDescriptions[$partNumber])) {
                $partApiDescriptions[$partNumber] = $apiDescription;
            }

            $periodInfo = $this->resolvePeriodFromAdjustmentRow($row, $year);
            if ($periodInfo === null) {
                continue;
            }

            $periodKey = sprintf('%04d-%02d', $periodInfo['year'], $periodInfo['month']);
            $qty = abs((float) ($row['QTY_ADJ'] ?? 0));

            if ($periodKey === $currentPeriodKey) {
                $day = max(1, min(31, $periodInfo['day']));
                if (!isset($usageCurrentByPartDay[$partNumber])) {
                    $usageCurrentByPartDay[$partNumber] = [];
                }

                $usageCurrentByPartDay[$partNumber][$day] = ($usageCurrentByPartDay[$partNumber][$day] ?? 0) + $qty;
                $usageCurrentByPartTotal[$partNumber] = ($usageCurrentByPartTotal[$partNumber] ?? 0) + $qty;
            }

            if ($periodKey === $beforePeriodKey) {
                $usageBeforeByPartTotal[$partNumber] = ($usageBeforeByPartTotal[$partNumber] ?? 0) + $qty;
            }
            }

            foreach ($prevYearRows as $row) {
            if (!$this->isRelevantWarehouse($row)) {
                continue;
            }

            $partNumber = $this->resolvePartNumberFromAdjustmentRow($row);
            if ($partNumber === '') {
                continue;
            }

            $usagePrevYearByPartTotal[$partNumber] = ($usagePrevYearByPartTotal[$partNumber] ?? 0) + abs((float) ($row['QTY_ADJ'] ?? 0));

            $apiDescription = $this->resolveDescription($row);
            if ($apiDescription !== '-' && !isset($partApiDescriptions[$partNumber])) {
                $partApiDescriptions[$partNumber] = $apiDescription;
            }
            }

            $partNumbers = array_keys($usageCurrentByPartTotal);
            sort($partNumbers);

            $masterMap = $this->getSparepartMasterMap($partNumbers);

            $rows = [];
            $no = 1;
            foreach ($partNumbers as $partNumber) {
            $dailyUsage = $usageCurrentByPartDay[$partNumber] ?? [];
            $totalQty = (float) ($usageCurrentByPartTotal[$partNumber] ?? 0);
            $beforeQty = (float) ($usageBeforeByPartTotal[$partNumber] ?? 0);
            $prevYearTotal = (float) ($usagePrevYearByPartTotal[$partNumber] ?? 0);
            $avgMonthly = $prevYearTotal / 12;
            $targetMax = ($prevYearTotal * 0.95) / 12;

            $unitPrice = $this->resolveFinalPrice($partNumber, $masterMap, 0);
            $description = $this->resolveFinalDescription($partNumber, $masterMap, $partApiDescriptions[$partNumber] ?? '-');

            $weekTotals = [];
            foreach ($weeklyRanges as $range) {
                $weekTotal = 0.0;
                for ($day = $range['start']; $day <= $range['end']; $day++) {
                    $weekTotal += (float) ($dailyUsage[$day] ?? 0);
                }
                $weekTotals[] = round($weekTotal, 2);
            }

            $crAchievement = $beforeQty > 0
                ? (($totalQty - $beforeQty) / $beforeQty) * 100
                : ($totalQty > 0 ? 100.0 : 0.0);

            $rows[] = [
                'no' => $no++,
                'part_number' => $partNumber,
                'description' => $description,
                'unit_price' => round($unitPrice, 2),
                'average_qty' => round($avgMonthly, 2),
                'target_max_qty' => round($targetMax, 2),
                'week_totals' => $weekTotals,
                'total_qty' => round($totalQty, 2),
                'after_qty' => round($totalQty, 2),
                'before_qty' => round($beforeQty, 2),
                'cr_achievement' => round($crAchievement, 2),
            ];
            }

            $payload = [
                'status' => 'success',
                'period' => [
                    'year' => $year,
                    'month' => $month,
                    'label' => strtoupper(date('M', strtotime(sprintf('%04d-%02d-01', $year, $month)))),
                    'month_short' => date('M-y', strtotime(sprintf('%04d-%02d-01', $year, $month))),
                    'before_month_short' => date('M-y', strtotime(sprintf('%04d-%02d-01', $beforeYear, $beforeMonth))),
                    'before_year' => $beforeYear,
                    'before_month' => $beforeMonth,
                ],
                'weeks' => $weeklyRanges,
                'rows' => $rows,
            ];

            return $this->respond($this->sanitizePayloadUtf8($payload));
        } catch (\Throwable $e) {
            log_message('error', 'History admin getData gagal: {message}', [
                'message' => $e->getMessage(),
            ]);

            return $this->failServerError('Gagal mengambil data history admin.');
        }
    }

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

    private function resolvePreviousPeriod(int $year, int $month): array
    {
        if ($month === 1) {
            return [$year - 1, 12];
        }

        return [$year, $month - 1];
    }

    private function buildWeeklyRanges(int $year, int $month): array
    {
        $lastDay = (int) date('t', strtotime(sprintf('%04d-%02d-01', $year, $month)));
        $firstDate = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $firstDayOfWeek = (int) $firstDate->format('w');

        $daysUntilSunday = (7 - $firstDayOfWeek) % 7;
        $firstEnd = min($lastDay, 1 + $daysUntilSunday);

        $ranges = [[
            'label' => 'W1',
            'start' => 1,
            'end' => $firstEnd,
        ]];

        $start = $firstEnd + 1;
        $index = 2;
        while ($start <= $lastDay) {
            $end = min($lastDay, $start + 6);
            $ranges[] = [
                'label' => 'W' . $index,
                'start' => $start,
                'end' => $end,
            ];
            $start = $end + 1;
            $index++;
        }

        return $ranges;
    }

    private function isRelevantWarehouse(array $row): bool
    {
        $warehouse = strtoupper(trim((string) ($row['WAREHOUSE'] ?? '')));
        return $warehouse === 'KWSPT';
    }

    private function normalizePartNumber(string $partNumber): string
    {
        return strtoupper(trim($partNumber));
    }

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
            log_message('error', 'Gagal membaca master sparepart_price history admin: {message}', [
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
                'description' => $this->normalizeUtf8Text(trim((string) ($row['description'] ?? ''))),
                'price' => (float) ($row['price'] ?? 0),
            ];
        }

        return $master;
    }

    private function resolveFinalDescription(string $partNumber, array $sqlMasterMap, string $apiDescription = '-'): string
    {
        if (isset($sqlMasterMap[$partNumber])) {
            $sqlDescription = $this->normalizeUtf8Text(trim((string) ($sqlMasterMap[$partNumber]['description'] ?? '')));
            if ($sqlDescription !== '') {
                return $sqlDescription;
            }
        }

        $apiDescription = $this->normalizeUtf8Text(trim($apiDescription));
        return $apiDescription !== '' ? $apiDescription : '-';
    }

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

    /**
     * Sanitasi rekursif payload agar semua string valid UTF-8 sebelum di-json encode.
     */
    private function sanitizePayloadUtf8($value)
    {
        if (is_string($value)) {
            return $this->normalizeUtf8Text($value);
        }

        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $safeKey = is_string($key) ? $this->normalizeUtf8Text($key) : $key;
                $sanitized[$safeKey] = $this->sanitizePayloadUtf8($item);
            }

            return $sanitized;
        }

        return $value;
    }
}
