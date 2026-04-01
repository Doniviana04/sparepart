<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Monitor User CRP</title>

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

	<style>
		body { font-family: system-ui, sans-serif; background: #f5f7fa; }
		.header {
			background: linear-gradient(90deg, #0f766e, #155e75);
			color: white;
			padding: 1rem;
			border-radius: 10px;
			box-shadow: 0 4px 16px rgba(15, 118, 110, .25);
			text-align: center;
			margin-bottom: 1.5rem;
		}
		.card-custom {  
			border: none;
			border-top: 4px solid #0f766e;
			border-radius: 12px;
			box-shadow: 0 4px 16px rgba(0, 0, 0, .06);
		}
		.table-wrapper {
			max-height: 75vh;
			overflow-y: auto;
			overflow-x: auto;
		}
		#tblMonitor thead th {
			background: #e6fffb;
			color: #134e4a;
			font-size: 0.875rem;
			text-align: center;
			vertical-align: middle;
			position: sticky;
			z-index: 10;
		}
		#tblMonitor thead tr:nth-child(1) th { top: 0; }
		#tblMonitor thead tr:nth-child(2) th { top: var(--row1-h, 40px); }
		.table tbody td { font-size: 0.875rem; vertical-align: middle; }
		.table-hover tbody tr:hover { background: #ecfeff; }
		.badge-control {
			background: #f59e0b;
			color: #fff;
			font-weight: 600;
			padding: .45rem .7rem;
			border-radius: 999px;
			display: inline-block;
		}
		.pagination-wrap {
			background: #fff;
			border: 1px solid #d9d9d9;
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
	</style>
</head>
<body>

<div class="container-fluid py-4">

	<!-- Back navigation -->
	<a href="<?= base_url('home') ?>" class="d-inline-flex align-items-center gap-1 text-secondary text-decoration-none mb-3 small fw-semibold">
		<i class="bi bi-chevron-left"></i> Beranda
	</a>

	<div class="header">
		<h5 class="mb-0 fw-semibold" id="pageTitle">MONITOR PEMAKAIAN QUOTA SPAREPART</h5>
	</div>

	<div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
		<div>
			<label class="form-label fw-semibold small mb-1">Bulan / Tahun</label>
			<input type="month" class="form-control form-control-sm" id="chart_month" value="<?= date('Y-m') ?>">
			<div class="mt-2">
				<label class="form-label fw-semibold small mb-1">Show</label>
				<select class="form-select form-select-sm" id="show_limit">
					<option value="50">50</option>
					<option value="100" selected>100</option>
					<option value="200">200</option>
					<option value="all">Semua data</option>
				</select>
			</div>
		</div>
	</div>

	<div class="card card-custom">
		<div class="card-body p-0">
			<div class="table-wrapper">
				<table class="table table-bordered table-hover table-sm mb-0 text-nowrap" id="tblMonitor">
					<thead>
						<tr>
							<th rowspan="2">NO</th>
							<th rowspan="2">PART NUMBER</th>
							<th rowspan="2">DESCRIPTION</th>
							<th rowspan="2" id="quotaYearHeader">QUOTA QTY <?= date('Y') ?></th>
							<th rowspan="2">
								JUMLAH PEMAKAIAN<br>
								UP TO DATE
							</th>
							<th colspan="2">SISA QUOTA</th>
							<th rowspan="2">KETERANGAN</th>
							<th rowspan="2">STATUS</th>
						</tr>
						<tr>
							<th>AKTUAL</th>
							<th>IDEAL</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</div>
	</div>

	<div class="pagination-wrap d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
		<div class="pagination-info" id="paginationInfo">Menampilkan 0 hingga 0 dari 0 data</div>
		<nav aria-label="Pagination Monitor User">
			<ul class="pagination pagination-sm mb-0" id="paginationNav"></ul>
		</nav>
	</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<script>
function fixStickyHeaderOffsets() {
	const thead = document.querySelector('#tblMonitor thead');
	const rows = thead ? thead.querySelectorAll('tr') : [];
	if (rows.length >= 2) {
		const h1 = rows[0].getBoundingClientRect().height;
		const h2 = rows[1].getBoundingClientRect().height;
		document.documentElement.style.setProperty('--row1-h', h1 + 'px');
		document.documentElement.style.setProperty('--row2-h', h2 + 'px');
	}
}

const API_URL = '<?= base_url('monitor-user/data') ?>';
const CONTROL_UPDATE_EVENT_KEY = 'crp-control-updated';
const AUTO_REFRESH_INTERVAL_MS = 10000;
let autoRefreshTimer = null;
let currentPage = 1;
let pageSize = '100';

function formatNumber(n) {
	if (n == null) return '-';
	return parseFloat(n).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function updateYearHeaders(year) {
	document.getElementById('pageTitle').textContent = `MONITOR PEMAKAIAN QUOTA SPAREPART ${year}`;
	document.getElementById('quotaYearHeader').textContent = `QUOTA QTY ${year}`;
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

	document.getElementById('paginationInfo').textContent = `Menampilkan ${start} hingga ${end} dari ${total} data`;
	renderPaginationNav(page, totalPages, hasPrev, hasNext, isAll);
	currentPage = page;
}

function renderPaginationNav(page, totalPages, hasPrev, hasNext, isAll) {
	const nav = document.getElementById('paginationNav');
	if (!nav) {
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

	nav.innerHTML = buttons.join('');
}

function loadData(month, page = 1, options = {}) {
	const showLoading = options.showLoading !== false;
	const selectedYear = parseInt(month.split('-')[0], 10);
	if (!Number.isNaN(selectedYear)) {
		updateYearHeaders(selectedYear);
	}

	const tbody = document.querySelector('#tblMonitor tbody');
	if (showLoading) {
		tbody.innerHTML = '<tr><td colspan="9" class="text-center py-3"><span class="spinner-border spinner-border-sm me-2"></span>Loading data...</td></tr>';
	}

	fetch(`${API_URL}?month=${encodeURIComponent(month)}&page=${page}&limit=${encodeURIComponent(pageSize)}`)
		.then(r => {
			if (!r.ok) throw new Error(`HTTP ${r.status}`);
			return r.json();
		})
		.then(json => {
			const rows = json.data ?? [];
			const meta = json.pagination ?? {};
			const year = parseInt(json.year ?? month.split('-')[0], 10);
			updateYearHeaders(year);
			updatePagination(meta);

			if (rows.length === 0) {
				tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-3">No data available</td></tr>';
				return;
			}

			tbody.innerHTML = rows.map(d => `
				<tr>
					<td class="text-center">${d.NO}</td>
					<td>${d.PART_NUMBER ?? '-'}</td>
					<td>${d.DESCRIPTION ?? '-'}</td>
					<td class="text-end">${formatNumber(d.QUOTA_QTY)}</td>
					<td class="text-end">${formatNumber(d.UPTODATE_USAGE)}</td>
					<td class="text-end">${formatNumber(d.SISA_AKTUAL)}</td>
					<td class="text-end">${formatNumber(d.SISA_IDEAL)}</td>
					<td class="text-center">${d.KETERANGAN ?? '-'}</td>
					<td class="text-center"><span class="badge-control">${d.STATUS ?? 'Normal'}</span></td>
				</tr>`).join('');
		})
		.catch(err => {
			tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-3">Failed to load data: ${err.message}</td></tr>`;
			updatePagination({ page: 1, per_page: 0, total: 0, total_pages: 1, has_prev: false, has_next: false, is_all: pageSize === 'all' });
			console.error(err);
		});
}

function refreshFromCurrentSelection() {
	if (!monthPicker.value) {
		return;
	}

	loadData(monthPicker.value, currentPage, { showLoading: false });
}

function setupAutoRefresh() {
	if (autoRefreshTimer !== null) {
		clearInterval(autoRefreshTimer);
	}

	autoRefreshTimer = window.setInterval(() => {
		if (!document.hidden) {
			refreshFromCurrentSelection();
		}
	}, AUTO_REFRESH_INTERVAL_MS);
}

const monthPicker = document.getElementById('chart_month');
const showLimit = document.getElementById('show_limit');
monthPicker.addEventListener('change', () => loadData(monthPicker.value, 1));
showLimit.addEventListener('change', () => {
	pageSize = showLimit.value;
	loadData(monthPicker.value, 1);
});
document.getElementById('paginationNav').addEventListener('click', event => {
	const button = event.target.closest('button[data-page]');
	if (!button || button.closest('.disabled')) {
		return;
	}

	const targetPage = Number(button.dataset.page ?? currentPage);
	if (!Number.isFinite(targetPage) || targetPage < 1 || targetPage === currentPage) {
		return;
	}

	loadData(monthPicker.value, targetPage);
});
window.addEventListener('storage', event => {
	if (event.key === CONTROL_UPDATE_EVENT_KEY) {
		refreshFromCurrentSelection();
	}
});
document.addEventListener('visibilitychange', () => {
	if (!document.hidden) {
		refreshFromCurrentSelection();
	}
});
window.addEventListener('load', () => {
	loadData(monthPicker.value, 1);
	setupAutoRefresh();
	requestAnimationFrame(fixStickyHeaderOffsets);
});
</script>

</body>
</html>
