<?php

namespace App\Controllers;

use App\Libraries\CrpApiService;
use Config\Database;
use CodeIgniter\API\ResponseTrait;

class MonitorUserController extends BaseController
{
	use ResponseTrait;

	protected CrpApiService $crpApi;

	/**
	 * Inisialisasi service API CRP agar bisa dipakai di seluruh method controller.
	 */
	public function __construct()
	{
		$this->crpApi = new CrpApiService();
	}

	/**
	 * Menampilkan halaman monitor user.
	 */
	public function index()
	{
		return view('monitor_user/monitor_user');
	}

	/**
	 * Endpoint JSON utama monitor user.
	 * Hanya mengembalikan data part yang sudah diberi aksi control oleh admin.
	 *
	 * Query:
	 * - month (opsional) format YYYY-MM
	 */
	public function getData()
	{
		@set_time_limit(300);

		[$year, $month] = $this->resolvePeriod();
		$result = array_values(array_filter(
			$this->buildMonitorData($year, $month),
			static fn(array $row): bool => !empty($row['CONTROLLED'])
		));
		$pagination = $this->resolvePagination();
		$pagedData = $this->paginateRows($result, $pagination['page'], $pagination['per_page'], $pagination['is_all']);

		return $this->respond([
			'status' => 'success',
			'data'   => $pagedData['data'],
			'year'   => $year,
			'month'  => $month,
			'pagination' => $pagedData['meta'],
		]);
	}

	/**
	 * Memvalidasi dan memecah parameter month menjadi [year, month].
	 * Jika format tidak valid, fallback ke bulan berjalan.
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
	 * Ambil parameter pagination dari query string.
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
	 * Potong data sesuai halaman yang diminta dan siapkan metadata pagination.
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
	 * Menyusun data monitor per part number:
	 * - QUOTA_QTY dari tahun sebelumnya (Jan-Des)
	 * - UPTODATE_USAGE dari tahun terpilih (Jan-bulan picker)
	 * - SISA_AKTUAL, SISA_IDEAL, KETERANGAN, dan STATUS
	 */
	private function buildMonitorData(int $year, int $month): array
	{
		$controlMarks = $this->getControlMarksByPeriod($year, $month);
		$controlledPartNumbers = array_keys($controlMarks);
		$prevYear = $year - 1;
		$quotaRows = $this->crpApi->getAdjustmentsByYearUntilMonth($prevYear, 12);
		$uptodateRows = $this->crpApi->getAdjustmentsByYearUntilMonth($year, $month);

		// Ambil master SQL sekali agar description monitor konsisten dengan dashboard CRP.
		$itemKeys = [];
		$apiDescriptions = [];
		foreach ([$quotaRows, $uptodateRows] as $rows) {
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

		foreach ($controlledPartNumbers as $partNumber) {
			$itemKeys[$partNumber] = true;
		}

		$sqlMasterMap = $this->getSparepartMasterMap(array_keys($itemKeys));

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
					'PART_NUMBER'           => $partNumber,
					'DESCRIPTION'           => $this->resolveFinalDescription($partNumber, $sqlMasterMap, $apiDescriptions[$partNumber] ?? $this->resolveDescription($row)),
					'QUOTA_QTY'             => 0,
					'JUMLAH_PEMAKAIAN'      => 0,
				];
			}

			$qty = abs((float) ($row['QTY_ADJ'] ?? 0));
			$items[$partNumber]['QUOTA_QTY'] += $qty;

			if ($items[$partNumber]['DESCRIPTION'] === '-') {
				$items[$partNumber]['DESCRIPTION'] = $this->resolveFinalDescription($partNumber, $sqlMasterMap, $apiDescriptions[$partNumber] ?? $this->resolveDescription($row));
			}
		}

		foreach ($uptodateRows as $row) {
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
					'PART_NUMBER'           => $partNumber,
					'DESCRIPTION'           => $this->resolveFinalDescription($partNumber, $sqlMasterMap, $apiDescriptions[$partNumber] ?? '-'),
					'QUOTA_QTY'             => 0,
					'JUMLAH_PEMAKAIAN'      => 0,
				];
			}

			$qty = abs((float) ($row['QTY_ADJ'] ?? 0));
			$items[$partNumber]['JUMLAH_PEMAKAIAN'] += $qty;
		}

		// Fallback: part yang sudah ditandai control tetap harus muncul walaupun API CRP timeout.
		foreach ($controlledPartNumbers as $partNumber) {
			if (isset($items[$partNumber])) {
				continue;
			}

			$items[$partNumber] = [
				'PART_NUMBER'           => $partNumber,
				'DESCRIPTION'           => $this->resolveFinalDescription($partNumber, $sqlMasterMap, '-'),
				'QUOTA_QTY'             => 0,
				'JUMLAH_PEMAKAIAN'      => 0,
			];
		}

		$result = [];
		$no = 1;

		ksort($items);

		foreach ($items as $partNumber => $item) {
			$quotaQty = round((float) ($item['QUOTA_QTY'] ?? 0), 2);
			$pemakaianQty = round((float) ($item['JUMLAH_PEMAKAIAN'] ?? 0), 2);

			$sisaAktual = round($quotaQty - $pemakaianQty, 2);
			$sisaIdeal = round(($quotaQty / 12) * $month, 2);
			$controlled = isset($controlMarks[$partNumber]);

			$keterangan = $sisaAktual < $sisaIdeal ? 'Harap lebih hemat' : 'OK';
			$status = $controlled ? 'Perlu Control' : 'Normal';

			$result[] = [
				'NO'                          => $no++,
				'PART_NUMBER'                 => $partNumber,
				'DESCRIPTION'                 => (string) ($item['DESCRIPTION'] ?? '-'),
				'QUOTA_QTY'                   => $quotaQty,
				'UPTODATE_USAGE'              => $pemakaianQty,
				'SISA_AKTUAL'                 => $sisaAktual,
				'SISA_IDEAL'                  => $sisaIdeal,
				'KETERANGAN'                  => $keterangan,
				'STATUS'                      => $status,
				'CONTROLLED'                  => $controlled,
			];
		}

		return $result;
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
			log_message('error', 'Gagal membaca master sparepart_price monitor: {message}', [
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
	 * Mengambil daftar part yang ditandai control pada periode tertentu.
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
			log_message('error', 'Gagal membaca control marks monitor ({period}): {message}', [
				'period' => $period,
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
	 * Menormalisasi part number agar konsisten saat dipakai sebagai key.
	 */
	private function normalizePartNumber(string $partNumber): string
	{
		return strtoupper(trim($partNumber));
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
}
