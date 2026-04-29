<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title >History Admin CRP</title>

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">

	<style>
		:root {
			--bg-page: #f5f8fc;
			--ink-dark: #1f2937;
			--primary-dark: #00223E;
			--primary: #005689;
			--teal: #1D976C;
			--teal-soft: #e8f7f1;
			--sky-soft: #edf6fc;
			--amber: #f6c453;
			--amber-soft: #fff3d0;
			--line: #d7e0ea;
		}

		body {
			background: radial-gradient(circle at top right, #fff6dc 0%, #f6f8fc 44%, #eef3fb 100%);
			color: var(--ink-dark);
			font-family: 'Inter', 'Segoe UI', Tahoma, sans-serif;
		}

		.page-shell {
			padding-top: 1.25rem;
			padding-bottom: 2rem;
		}

		.top-hero {
			border-radius: 14px;
			background: linear-gradient(135deg, var(--primary-dark), var(--primary) 52%, var(--teal));
			color: #ffffff;
			padding: 1rem 1.2rem;
			box-shadow: 0 12px 26px rgba(0, 34, 62, 0.18);
			margin-bottom: 1rem;
			text-align: center;
		}

		.top-hero h5 {
			margin-bottom: 0;
			letter-spacing: 0.4px;
			font-weight: 700;
			width: 100%;
		}

		.controls-card {
			border: 0;
			border-radius: 14px;
			box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
			background: rgba(255, 255, 255, 0.95);
			margin-bottom: 0;
		}

		.controls-row {
			display: flex;
			flex-wrap: wrap;
			align-items: flex-end;
			justify-content: flex-start;
			gap: 1rem;
		}

		.controls-row > div {
			min-width: 180px;
		}

		.controls-row.border-bottom {
			border-bottom: 1px solid var(--line) !important;
		}

		.period-label {
			font-weight: 700;
			background: linear-gradient(135deg, var(--primary-dark), var(--teal));
			color: #ffffff;
			border-radius: 999px;
			padding: 0.45rem 0.9rem;
			min-width: 120px;
			text-align: right;
			display: inline-block;
			box-shadow: 0 8px 18px rgba(0, 34, 62, 0.12);
		}

		.period-label-right {
			margin-left: auto;
		}

		.history-card {
			border: none;
			border-top: 4px solid var(--primary);
			border-radius: 12px;
			box-shadow: 0 4px 16px rgba(0, 0, 0, .06);
			background: #ffffff;
		}

		.pagination-wrap {
			background: #ffffff;
			border: 1px solid var(--line);
			border-radius: 10px;
			padding: .45rem .6rem;
		}

		.pagination-info {
			color: #495057;
			font-size: .9rem;
			white-space: nowrap;
		}

		#paginationNav .page-link {
			background: #fff;
			border: 1px solid #cfd4da;
			color: #212529;
			min-width: 38px;
			text-align: center;
			font-weight: 500;
		}

		#paginationNav .page-item.active .page-link {
			background: #fff;
			color: #212529;
			border: 2px solid #212529;
			font-weight: 700;
			text-decoration: underline;
		}

		#paginationNav .page-item.disabled .page-link {
			background: #f8f9fa;
			color: #6c757d;
			border-style: dashed;
			cursor: not-allowed;
		}

		#paginationNav .page-item.ellipsis .page-link {
			pointer-events: none;
		}

		.table-wrapper {
			max-height: 75vh;
			overflow-y: auto;
			overflow-x: auto;
		}

		#tblHistory {
			margin-bottom: 0;
			min-width: 1350px;
		}

		.table tbody td {
			font-size: 0.875rem;
			vertical-align: middle;
		}

		.table-hover tbody tr:hover {
			background: #f0f8ff;
		}

		#tblHistory th,
		#tblHistory td {
			border: 1px solid var(--line);
			text-align: center;
			vertical-align: middle;
			white-space: nowrap;
			font-size: 0.85rem;
		}

		#tblHistory th {
			font-weight: 700;
		}

		#tblHistory thead th {
			background: #e8f1f8;
			color: #004466;
			font-size: 0.85rem;
			font-weight: 700;
			position: sticky;
			z-index: 10;
		}

		#tblHistory thead tr:nth-child(1) th { top: 0; }
		#tblHistory thead tr:nth-child(2) th { top: var(--row1-h, 40px); }
		#tblHistory thead tr:nth-child(3) th { top: calc(var(--row1-h, 40px) + var(--row2-h, 33px)); }

		#tblHistory tbody td {
			background: #ffffff;
			font-size: 0.85rem;
		}

		.head-red {
			background: #e8f1f8 !important;
			color: #004466 !important;
		}

		.head-yellow {
			background: #e8f1f8 !important;
			color: #004466 !important;
		}

		.head-yellow-soft {
			background: #e8f1f8 !important;
			color: #004466 !important;
		}

		.head-green {
			background: #e8f1f8 !important;
			color: #004466 !important;
		}

		.head-month {
			background: #e8f1f8 !important;
			color: #004466 !important;
			letter-spacing: 0.6px;
			font-size: 0.85rem !important;
		}

		.empty-state {
			color: #6b7280;
			background: #fbfcff;
		}

		.note-box {
			border-left: 4px solid var(--teal);
			background: linear-gradient(90deg, rgba(29, 151, 108, 0.08), rgba(0, 86, 137, 0.04));
			border-radius: 10px;
			padding: 0.7rem 0.9rem;
			font-size: 0.84rem;
			color: #475569;
			margin-top: 0.75rem;
		}

		.summary-card {
			border-top-color: var(--teal);
		}

		.summary-picker-wrap {
			min-width: 210px;
		}

		#tblSummary th,
		#tblSummary td {
			border: 1px solid var(--line);
			text-align: center;
			vertical-align: middle;
		}

		#tblSummary thead th {
			background: #e9f8ff;
			color: #1f2937;
			font-size: 0.85rem;
			font-weight: 700;
		}

		#tblSummary tbody td {
			background: #ffffff;
			font-size: 0.85rem;
		}

		.summary-note {
			margin-top: 0.75rem;
			font-size: 0.81rem;
			color: #475569;
			background: #f7fbff;
			border: 1px dashed #cddceb;
			border-radius: 8px;
			padding: 0.6rem 0.75rem;
		}

		.filter-panel {
			background: #fff;
			border: 1px solid #d9d9d9;
			border-radius: 10px;
			padding: .75rem;
		}

		.column-filter {
			min-width: 0;
			font-size: .8rem;
		}

		.select2-container--bootstrap-5 .select2-selection {
			min-height: calc(1.5em + .5rem + 2px);
			font-size: .8rem;
		}

		.select2-container--bootstrap-5 .select2-dropdown .select2-results__option {
			font-size: .8rem;
		}

		@media (max-width: 768px) {
			.period-label {
				width: 100%;
			}

			.controls-row > div {
				width: 100%;
				min-width: 0;
			}

		}
	</style>
</head>
<body>

<div class="container-fluid page-shell">
	<a href="<?= base_url('home') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 mb-3 shadow-sm">
		<i class="bi bi-house-door-fill me-1"></i> Kembali ke Beranda
	</a>

	<div class="top-hero">
		<h5>HISTORY CRP SPAREPART</h5>
	</div>

	<div class="card history-card">
		<div class="card-body controls-row border-bottom">
			<div>
				<label class="form-label fw-semibold small mb-1">Bulan / Tahun</label>
				<input type="month" class="form-control form-control-sm" id="monthPicker" value="<?= date('Y-m') ?>">
			</div>
			<div>
				<label class="form-label fw-semibold small mb-1">Show Data</label>
				<select class="form-select form-select-sm" id="show_limit">
					<option value="50">50</option>
					<option value="100" selected>100</option>
					<option value="200">200</option>
					<option value="all">Semua data</option>
				</select>
			</div>

			<span class="period-label period-label-right" id="periodLabel">-</span>
		</div>
		<div class="card-body p-0">
			<div class="table-wrapper">
				<table class="table table-bordered table-hover table-sm mb-0 text-nowrap" id="tblHistory">
				<thead id="historyHead"></thead>
				<tbody id="historyBody">
					<tr class="empty-state">
						<td colspan="4" class="py-3">Memuat data history...</td>
					</tr>
				</tbody>
				</table>
			</div>
		</div>
	</div>

	<div class="filter-panel mt-3 mb-2">
		<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
			<div>
				<div class="fw-semibold small">Filter Kolom</div>
				<div class="text-muted small">Pilih nilai untuk memfilter data tabel di atas.</div>
			</div>
			<div>
				<button type="button" class="btn btn-sm btn-outline-secondary" id="filterResetBtn">Reset Semua</button>
			</div>
		</div>
		<div class="row g-2">
			<div class="col-12 col-md-6 col-lg-4 col-xl-3">
				<label class="form-label small fw-semibold mb-1">Part Number</label>
				<select class="form-select form-select-sm column-filter" id="filterPartNumber" data-filter-key="part_number">
					<option value="">Semua part number</option>
				</select>
			</div>
			<div class="col-12 col-md-6 col-lg-4 col-xl-3">
				<label class="form-label small fw-semibold mb-1">Description</label>
				<select class="form-select form-select-sm column-filter" id="filterDescription" data-filter-key="description">
					<option value="">Semua description</option>
				</select>
			</div>
		</div>
	</div>

	<div class="pagination-wrap d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
		<div class="pagination-info" id="paginationInfo">Menampilkan 0 hingga 0 dari 0 data</div>
		<nav aria-label="Pagination History Admin">
			<ul class="pagination pagination-sm mb-0" id="paginationNav"></ul>
		</nav>
	</div>

	<?= view('History_adm/summary') ?>

	<!-- <div class="note-box">
		Navigasi periode sudah aktif. Ketika bulan diubah, label header JAN/FEB dan rentang minggu W1-W5 akan ikut berubah secara otomatis.
	</div> -->
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
const monthPicker = document.getElementById('monthPicker');
const showLimitSelect = document.getElementById('show_limit');
const filterPartNumber = document.getElementById('filterPartNumber');
const filterDescription = document.getElementById('filterDescription');
const resetFiltersButton = document.getElementById('filterResetBtn');
const periodLabel = document.getElementById('periodLabel');
const historyHead = document.getElementById('historyHead');
const historyBody = document.getElementById('historyBody');
const paginationInfo = document.getElementById('paginationInfo');
const paginationNav = document.getElementById('paginationNav');
const summaryYearPicker = document.getElementById('summaryYearPicker');
const summaryBody = document.getElementById('summaryBody');
const summaryCaption = document.getElementById('summaryCaption');
const summaryNote = document.getElementById('summaryNote');
const DATA_URL = '<?= base_url('history-admin/data') ?>';
let currentPage = 1;
let currentPagination = {};
let select2Ready = false;
let summaryRequestToken = 0;
let lastColumnCount = 1;

function initSelect2Filters() {
	if (select2Ready || typeof window.jQuery === 'undefined' || !window.jQuery.fn.select2) {
		return;
	}

	const $partSelect = window.jQuery(filterPartNumber);
	const $descSelect = window.jQuery(filterDescription);

	$partSelect.select2({
		theme: 'bootstrap-5',
		placeholder: 'Semua part number',
		allowClear: true,
		width: '100%'
	});

	$descSelect.select2({
		theme: 'bootstrap-5',
		placeholder: 'Semua description',
		allowClear: true,
		width: '100%'
	});

	$partSelect.on('change', () => {
		loadHistoryData(monthPicker.value, 1, { showLoading: false });
		loadYearlySummary(getSelectedSummaryYear());
	});
	$descSelect.on('change', () => {
		loadHistoryData(monthPicker.value, 1, { showLoading: false });
		loadYearlySummary(getSelectedSummaryYear());
	});

	select2Ready = true;
}

function getSelectedFilterValue(element) {
	if (!element) {
		return '';
	}

	return String(element.value ?? '').trim();
}

function setSelectOptions(element, values, selectedValue, placeholderText) {
	if (!element) {
		return;
	}

	const options = [
		`<option value="">${placeholderText}</option>`,
		...values.map(value => `<option value="${escapeHtml(value)}">${escapeHtml(value)}</option>`),
	];

	element.innerHTML = options.join('');
	if (selectedValue && values.includes(selectedValue)) {
		element.value = selectedValue;
	} else {
		element.value = '';
	}

	if (select2Ready) {
		window.jQuery(element).trigger('change.select2');
	}
}

function updateFilterOptions(filterOptions = {}, activeFilters = {}) {
	const partNumbers = Array.isArray(filterOptions.part_numbers) ? filterOptions.part_numbers : [];
	const descriptions = Array.isArray(filterOptions.descriptions) ? filterOptions.descriptions : [];
	const selectedPart = String(activeFilters.part_number ?? getSelectedFilterValue(filterPartNumber));
	const selectedDescription = String(activeFilters.description ?? getSelectedFilterValue(filterDescription));

	setSelectOptions(filterPartNumber, partNumbers, selectedPart, 'Semua part number');
	setSelectOptions(filterDescription, descriptions, selectedDescription, 'Semua description');
}

function parseMonthValue(monthValue) {
	const [yearStr, monthStr] = String(monthValue).split('-');
	return {
		year: Number(yearStr),
		month: Number(monthStr)
	};
}

function formatMonthShort(year, month) {
	const dt = new Date(year, month - 1, 1);
	const label = new Intl.DateTimeFormat('en', { month: 'short', year: '2-digit' }).format(dt);
	return label.replace(' ', '-');
}

function formatPeriodChip(year, month) {
	const dt = new Date(year, month - 1, 1);
	return new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(dt).toUpperCase();
}

function updateStickyHeaderOffsets() {
	const rows = document.querySelectorAll('#tblHistory thead tr');
	if (rows.length < 2) {
		return;
	}

	document.documentElement.style.setProperty('--row1-h', rows[0].getBoundingClientRect().height + 'px');
	document.documentElement.style.setProperty('--row2-h', (rows[1]?.getBoundingClientRect().height ?? 0) + 'px');
}

function renderSummaryLoading() {
	if (!summaryBody) {
		return;
	}

	summaryBody.innerHTML = '<tr class="empty-state"><td colspan="4" class="py-3">Memuat ringkasan summary tahunan...</td></tr>';
}

function getSelectedSummaryYear() {
	const rawValue = String(summaryYearPicker?.value ?? '');
	const year = Number(rawValue.split('-')[0] ?? '');
	if (Number.isFinite(year) && year >= 2000 && year <= 2100) {
		return year;
	}

	return Number(new Date().getFullYear());
}

function formatMonthYearLabel(year, month) {
	return new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' })
		.format(new Date(year, month - 1, 1))
		.toUpperCase();
}

function renderSummary(rows = [], year) {
	if (!summaryBody) {
		return;
	}

	if (!Array.isArray(rows) || rows.length === 0) {
		renderSummaryError('Data summary tahunan tidak tersedia.');
		return;
	}

	const html = rows.map(item => {
		const monthLabel = formatMonthYearLabel(year, Number(item.month ?? 1));
		const targetAccumulation = formatNumber(item.target_accumulation ?? 0, 2);
		const monthlyAchievement = formatNumber(item.monthly_achievement ?? 0, 2);
		const achievementAccumulation = formatNumber(item.achievement_accumulation ?? 0, 2);

		return `
			<tr>
				<td class="fw-semibold">${escapeHtml(monthLabel)}</td>
				<td>${targetAccumulation}</td>
				<td>${monthlyAchievement}</td>
				<td>${achievementAccumulation}</td>
			</tr>
		`;
	}).join('');

	summaryBody.innerHTML = html;

	if (summaryCaption) {
		summaryCaption.textContent = `Ringkasan JANUARI - DESEMBER ${year} berdasarkan filter data aktif.`;
	}

	if (summaryNote) {
		summaryNote.textContent = 'Keterangan Summary: TARGET AKUMULASI = total target maksimum seluruh data per bulan. MONTHLY ACHIEVEMENT = total realisasi pemakaian pada bulan tersebut. ACHIEVEMENT AKUMULASI = total realisasi periode sebelumnya + realisasi bulan berjalan.';
	}
}

function renderSummaryError(message) {
	if (!summaryBody) {
		return;
	}

	summaryBody.innerHTML = `<tr class="empty-state"><td colspan="4" class="py-3">${escapeHtml(message)}</td></tr>`;
}

function renderPeriodHeader() {
	if (!monthPicker.value) {
		return;
	}

	const { year, month } = parseMonthValue(monthPicker.value);
	periodLabel.textContent = formatPeriodChip(year, month);
	loadHistoryData(monthPicker.value);
}

function loadYearlySummary(year) {
	renderSummaryLoading();

	const requestToken = ++summaryRequestToken;
	const partNumberFilter = getSelectedFilterValue(filterPartNumber);
	const descriptionFilter = getSelectedFilterValue(filterDescription);
	const months = Array.from({ length: 12 }, (_, idx) => idx + 1);

	const requests = months.map(month => {
		const query = new URLSearchParams({
			month: `${year}-${String(month).padStart(2, '0')}`,
			page: '1',
			limit: 'all',
		});

		if (partNumberFilter !== '') {
			query.set('part_number', partNumberFilter);
		}

		if (descriptionFilter !== '') {
			query.set('description', descriptionFilter);
		}

		return fetch(`${DATA_URL}?${query.toString()}`)
			.then(response => {
				if (!response.ok) {
					throw new Error('HTTP ' + response.status);
				}

				return response.json();
			})
			.then(payload => ({
				month,
				target_accumulation: Number(payload.summary?.target_accumulation ?? 0),
				monthly_achievement: Number(payload.summary?.monthly_achievement ?? 0),
				achievement_accumulation: Number(payload.summary?.achievement_accumulation ?? 0),
			}));
	});

	Promise.allSettled(requests)
		.then(results => {
			if (requestToken !== summaryRequestToken) {
				return;
			}

			const rows = months.map((month, index) => {
				const result = results[index];
				if (result && result.status === 'fulfilled' && result.value) {
					return result.value;
				}

				return {
					month,
					target_accumulation: 0,
					monthly_achievement: 0,
					achievement_accumulation: 0,
				};
			});

			renderSummary(rows, year);
		})
		.catch(() => {
			if (requestToken !== summaryRequestToken) {
				return;
			}

			renderSummaryError('Gagal memuat summary tahunan. Silakan coba lagi.');
		});
}

function formatNumber(value, digits = 2) {
	const parsed = Number(value ?? 0);
	if (!Number.isFinite(parsed)) {
		return '0';
	}

	return parsed.toLocaleString('id-ID', {
		minimumFractionDigits: digits,
		maximumFractionDigits: digits,
	});
}

function escapeHtml(value) {
	return String(value ?? '')
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#39;');
}

function renderEmptyState(message, colSpan = 1) {
	historyHead.innerHTML = '';
	historyBody.innerHTML = `<tr class="empty-state"><td colspan="${colSpan}" class="py-4">${message}</td></tr>`;
}

function renderPaginationNav(page, totalPages, hasPrev, hasNext, isAll) {
	if (!paginationNav) {
		return;
	}

	const addButton = (label, targetPage, disabled = false, active = false, ellipsis = false) => {
		const liClass = [
			'page-item',
			disabled ? 'disabled' : '',
			active ? 'active' : '',
			ellipsis ? 'ellipsis' : '',
		].filter(Boolean).join(' ');

		if (ellipsis) {
			return `<li class="${liClass}"><span class="page-link">...</span></li>`;
		}

		return `<li class="${liClass}"><button type="button" class="page-link" data-page="${targetPage}">${label}</button></li>`;
	};

	const buttons = [];
	const prevDisabled = isAll || !hasPrev;
	const nextDisabled = isAll || !hasNext;

	buttons.push(addButton('&laquo;', 1, prevDisabled));
	buttons.push(addButton('&lsaquo;', page - 1, prevDisabled));

	if (totalPages <= 7) {
		for (let i = 1; i <= totalPages; i += 1) {
			buttons.push(addButton(String(i), i, false, i === page));
		}
	} else {
		buttons.push(addButton('1', 1, false, page === 1));
		buttons.push(addButton('2', 2, false, page === 2));

		if (page > 4) {
			buttons.push(addButton('...', page, true, false, true));
		}

		const start = Math.max(3, page - 1);
		const end = Math.min(totalPages - 2, page + 1);
		for (let i = start; i <= end; i += 1) {
			buttons.push(addButton(String(i), i, false, i === page));
		}

		if (page < totalPages - 3) {
			buttons.push(addButton('...', page, true, false, true));
		}

		buttons.push(addButton(String(totalPages - 1), totalPages - 1, false, page === totalPages - 1));
		buttons.push(addButton(String(totalPages), totalPages, false, page === totalPages));
	}

	buttons.push(addButton('&rsaquo;', page + 1, nextDisabled));
	buttons.push(addButton('&raquo;', totalPages, nextDisabled));

	paginationNav.innerHTML = buttons.join('');
}

function buildDynamicHeader(period, weeks) {
	const weekHeaderCells = weeks.map(week => {
		return `<th class="head-green">${week.label}</th>`;
	}).join('');

	const monthShort = period.month_short ?? formatMonthShort(period.year, period.month);
	const beforeMonthShort = period.before_month_short ?? '-';
	const averageYear = Number(period.year ?? 0) > 0 ? Number(period.year) - 1 : '-';
	const targetYear = Number(period.year ?? 0) > 0 ? Number(period.year) : '-';

	historyHead.innerHTML = `
		<tr>
			<th class="head-red" rowspan="2">NO</th>
			<th class="head-red" rowspan="2">PART NUMBER</th>
			<th class="head-red" rowspan="2">DESCRIPTION</th>
			<th class="head-red" rowspan="2">UNIT PRICE</th>
			<th class="head-red" rowspan="2">AVERAGE QTY<br>PER MONTH - ${averageYear}</th>
			<th class="head-red" rowspan="2">TARGET MAX QTY<br>PER MONTH - ${targetYear}</th>
			<th class="head-yellow" colspan="${weeks.length}">USAGE QTY PER WEEK - ${monthShort}</th>
			<th class="head-month" rowspan="2">TOTAL<br>PER BULAN</th>
			<th class="head-month" rowspan="2">CR</th>
		</tr>
		<tr>
			${weekHeaderCells}
		</tr>
	`;

	// Update last column count for loading state
	lastColumnCount = 8 + weeks.length;
}

function buildDynamicBody(rows, weeks) {
	if (!Array.isArray(rows) || rows.length === 0) {
		renderEmptyState('Tidak ada data history untuk periode yang dipilih.', lastColumnCount);
		return;
	}

	const bodyHtml = rows.map(row => {
		const weekCells = weeks.map((_, idx) => {
			const weekValue = Number(row.week_totals?.[idx] ?? 0);
			return `<td>${formatNumber(weekValue, 2)}</td>`;
		}).join('');

		return `
			<tr>
				<td>${row.no}</td>
				<td>${escapeHtml(row.part_number ?? '-')}</td>
				<td class="text-start">${escapeHtml(row.description ?? '-')}</td>
				<td>${formatNumber(row.unit_price, 2)}</td>
				<td>${formatNumber(row.average_qty, 2)}</td>
				<td>${formatNumber(row.target_max_qty, 2)}</td>
				${weekCells}
				<td>${formatNumber(row.total_qty, 2)}</td>

				<td>${formatNumber(row.cr_value, 2)}</td>
			</tr>
		`;
	}).join('');

	historyBody.innerHTML = bodyHtml;
}

function updatePagination(meta = {}) {
	const page = Number(meta.page ?? 1);
	const perPage = Number(meta.per_page ?? 0);
	const total = Number(meta.total ?? 0);
	const totalPages = Number(meta.total_pages ?? 1);
	const hasPrev = Boolean(meta.has_prev);
	const hasNext = Boolean(meta.has_next);
	const isAll = Boolean(meta.is_all);

	const start = total === 0 ? 0 : ((page - 1) * perPage) + 1;
	const end = total === 0 ? 0 : (isAll ? total : Math.min(total, page * perPage));

	if (paginationInfo) {
		paginationInfo.textContent = `Menampilkan ${start} hingga ${end} dari ${total} data`;
	}

	renderPaginationNav(page, totalPages, hasPrev, hasNext, isAll);
	currentPage = page;
	currentPagination = meta;
}

function loadHistoryData(month, page = 1, options = {}) {
	const limit = showLimitSelect?.value ?? '100';
	const showLoading = options.showLoading !== false;
	const partNumberFilter = getSelectedFilterValue(filterPartNumber);
	const descriptionFilter = getSelectedFilterValue(filterDescription);
	currentPage = page;

	// Use lastColumnCount for colspan, with a sensible default
	const colSpan = lastColumnCount > 1 ? lastColumnCount : 10;
	
	if (showLoading) {
		renderEmptyState('Memuat data history...', colSpan);
		historyBody.innerHTML = '<tr class="empty-state"><td colspan="' + colSpan + '" class="py-4">Memuat data history...</td></tr>';
	}

	const query = new URLSearchParams({
		month,
		page: String(page),
		limit: String(limit),
	});

	if (partNumberFilter !== '') {
		query.set('part_number', partNumberFilter);
	}

	if (descriptionFilter !== '') {
		query.set('description', descriptionFilter);
	}

	fetch(`${DATA_URL}?${query.toString()}`)
		.then(response => {
			if (!response.ok) {
				throw new Error('HTTP ' + response.status);
			}

			return response.json();
		})
		.then(payload => {
			const weeks = Array.isArray(payload.weeks) ? payload.weeks : [];
			const rows = Array.isArray(payload.rows) ? payload.rows : [];
			const meta = payload.pagination ?? {};
			updateFilterOptions(payload.filter_options ?? {}, payload.filters ?? {});

			if (weeks.length === 0) {
				renderEmptyState('Struktur minggu tidak tersedia untuk periode ini.', 1);
				updatePagination({ page: 1, per_page: 0, total: 0, total_pages: 1, has_prev: false, has_next: false, is_all: false });
				return;
			}

			buildDynamicHeader(payload.period ?? {}, weeks);
			buildDynamicBody(rows, weeks);
			updatePagination(meta);
			updateStickyHeaderOffsets();
		})
		.catch(() => {
			renderEmptyState('Gagal memuat data history. Silakan coba lagi.', 1);
			updatePagination({ page: 1, per_page: 0, total: 0, total_pages: 1, has_prev: false, has_next: false, is_all: false });
		});
}

monthPicker.addEventListener('change', () => loadHistoryData(monthPicker.value, 1));
if (summaryYearPicker) {
	summaryYearPicker.addEventListener('change', () => {
		loadYearlySummary(getSelectedSummaryYear());
	});
}
showLimitSelect.addEventListener('change', () => loadHistoryData(monthPicker.value, 1));
if (resetFiltersButton) {
	resetFiltersButton.addEventListener('click', () => {
		if (filterPartNumber) {
			filterPartNumber.value = '';
		}

		if (filterDescription) {
			filterDescription.value = '';
		}

		if (select2Ready) {
			window.jQuery(filterPartNumber).trigger('change.select2');
			window.jQuery(filterDescription).trigger('change.select2');
		}

		loadHistoryData(monthPicker.value, 1);
		loadYearlySummary(getSelectedSummaryYear());
	});
}
if (paginationNav) {
	paginationNav.addEventListener('click', event => {
		const button = event.target.closest('button[data-page]');
		if (!button || button.closest('.disabled')) {
			return;
		}

		const targetPage = Number(button.dataset.page ?? currentPage);
		if (!Number.isFinite(targetPage) || targetPage < 1 || targetPage === currentPage) {
			return;
		}

		loadHistoryData(monthPicker.value, targetPage, { showLoading: false });
	});
}
window.addEventListener('resize', updateStickyHeaderOffsets);
window.addEventListener('load', () => {
	initSelect2Filters();
	renderPeriodHeader();
	loadYearlySummary(getSelectedSummaryYear());
});
</script>

</body>
</html>

