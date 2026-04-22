<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title >History Admin CRP</title>

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
			margin-bottom: 1rem;
		}

		.period-label {
			font-weight: 700;
			background: linear-gradient(135deg, var(--primary-dark), var(--teal));
			color: #ffffff;
			border-radius: 999px;
			padding: 0.45rem 0.9rem;
			min-width: 120px;
			text-align: center;
			display: inline-block;
			box-shadow: 0 8px 18px rgba(0, 34, 62, 0.12);
		}

		.history-card {
			border: 0;
			border-radius: 14px;
			overflow: hidden;
			box-shadow: 0 10px 26px rgba(15, 23, 42, 0.08);
			background: #ffffff;
		}

		.table-wrap {
			overflow: auto;
			max-height: 75vh;
			background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
		}

		#tblHistory {
			margin-bottom: 0;
			min-width: 1350px;
		}

		#tblHistory th,
		#tblHistory td {
			border: 1px solid var(--line);
			text-align: center;
			vertical-align: middle;
			white-space: nowrap;
			font-size: 0.84rem;
		}

		#tblHistory th {
			font-weight: 700;
		}

		#tblHistory thead th {
			position: sticky;
			z-index: 5;
			background: #e5fcfa;
			color: #1f2937;
			font-weight: 700;
		}

		#tblHistory thead tr:nth-child(1) th { top: 0; }
		#tblHistory thead tr:nth-child(2) th { top: var(--head-r1, 38px); }
		#tblHistory thead tr:nth-child(3) th { top: calc(var(--head-r1, 38px) + var(--head-r2, 38px)); }
		#tblHistory thead tr:nth-child(4) th { top: calc(var(--head-r1, 38px) + var(--head-r2, 38px) + var(--head-r3, 38px)); }

		.head-red {
			background: #ffffff;
			color: #1f2937;
		}

		.head-yellow {
			background: #ffffff;
			color: #1f2937;
		}

		.head-yellow-soft {
			background: #ffffff;
			color: #1f2937;
		}

		.head-green {
			background: #ffffff;
			color: #1f2937;
		}

		.head-month {
			background: #f8fafc;
			color: #1f2937;
			letter-spacing: 0.6px;
			font-size: 1.25rem;
		}

		#tblHistory thead tr:nth-child(1) th,
		#tblHistory thead tr:nth-child(2) th,
		#tblHistory thead tr:nth-child(3) th,
		#tblHistory thead tr:nth-child(4) th {
			background: #e5fcfa !important;
			color: #1f2937 !important;
		}

		#tblHistory tbody td {
			background: #ffffff;
			color: #1f2937;
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

		@media (max-width: 768px) {
			.period-label {
				width: 100%;
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
		<h5>HISTORY ADMIN CRP SPAREPART</h5>
	</div>

	<div class="card controls-card">
		<div class="card-body d-flex flex-wrap align-items-end justify-content-between gap-3">
			<div>
				<label class="form-label fw-semibold small mb-1">Bulan / Tahun</label>
				<input type="month" class="form-control form-control-sm" id="monthPicker" value="<?= date('Y-m') ?>">
			</div>

			<span class="period-label" id="periodLabel">-</span>
		</div>
	</div>

	<div class="card history-card">
		<div class="table-wrap">
			<table class="table table-bordered table-sm" id="tblHistory">
				<thead id="historyHead"></thead>
				<tbody id="historyBody">
					<tr class="empty-state">
						<td class="py-4">Memuat data history...</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

	<div class="note-box">
		Navigasi periode sudah aktif. Ketika bulan diubah, label header JAN/FEB dan rentang minggu W1-W5 akan ikut berubah secara otomatis.
	</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<script>
const monthPicker = document.getElementById('monthPicker');
const periodLabel = document.getElementById('periodLabel');
const historyHead = document.getElementById('historyHead');
const historyBody = document.getElementById('historyBody');
const DATA_URL = '<?= base_url('history-admin/data') ?>';

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

	document.documentElement.style.setProperty('--head-r1', rows[0].getBoundingClientRect().height + 'px');
	document.documentElement.style.setProperty('--head-r2', (rows[1]?.getBoundingClientRect().height ?? 0) + 'px');
	document.documentElement.style.setProperty('--head-r3', (rows[2]?.getBoundingClientRect().height ?? 0) + 'px');
}

function renderPeriodHeader() {
	if (!monthPicker.value) {
		return;
	}

	const { year, month } = parseMonthValue(monthPicker.value);
	periodLabel.textContent = formatPeriodChip(year, month);
	loadHistoryData(monthPicker.value);
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

function buildDynamicHeader(period, weeks) {
	const weekHeaderCells = weeks.map(week => {
		return `<th class="head-green">${week.label}<br>Tgl. ${week.start}-${week.end}</th>`;
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
			<th class="head-month" rowspan="2">AFTER<br>${monthShort}</th>
			<th class="head-month" rowspan="2">BEFORE<br>${beforeMonthShort}</th>
			<th class="head-month" rowspan="2">CR %</th>
		</tr>
		<tr>
			${weekHeaderCells}
		</tr>
	`;
}

function buildDynamicBody(rows, weeks) {
	if (!Array.isArray(rows) || rows.length === 0) {
		renderEmptyState('Tidak ada data history untuk periode yang dipilih.', 6 + weeks.length + 4);
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
				<td>${formatNumber(row.after_qty, 2)}</td>
				<td>${formatNumber(row.before_qty, 2)}</td>
				<td>${formatNumber(row.cr_achievement, 2)}%</td>
			</tr>
		`;
	}).join('');

	historyBody.innerHTML = bodyHtml;
}

function loadHistoryData(month) {
	renderEmptyState('Memuat data history...', 1);

	fetch(`${DATA_URL}?month=${encodeURIComponent(month)}`)
		.then(response => {
			if (!response.ok) {
				throw new Error('HTTP ' + response.status);
			}

			return response.json();
		})
		.then(payload => {
			const weeks = Array.isArray(payload.weeks) ? payload.weeks : [];
			const rows = Array.isArray(payload.rows) ? payload.rows : [];

			if (weeks.length === 0) {
				renderEmptyState('Struktur minggu tidak tersedia untuk periode ini.', 1);
				return;
			}

			buildDynamicHeader(payload.period ?? {}, weeks);
			buildDynamicBody(rows, weeks);
			updateStickyHeaderOffsets();
		})
		.catch(() => {
			renderEmptyState('Gagal memuat data history. Silakan coba lagi.', 1);
		});
}

monthPicker.addEventListener('change', renderPeriodHeader);
window.addEventListener('resize', updateStickyHeaderOffsets);
window.addEventListener('load', renderPeriodHeader);
</script>

</body>
</html>

