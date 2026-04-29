<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CRP Sparepart Dashboard</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">

  <style>
    body { font-family: system-ui, sans-serif; background: #f5f7fa; }
    .header { 
      background: linear-gradient(90deg, #005689, #003f66); 
      color: white; 
      padding: 1rem; 
      border-radius: 10px; 
      box-shadow: 0 4px 16px rgba(0,86,137,.3);
      text-align: center;
      margin-bottom: 1.5rem;
    }
    .card-custom { 
      border: none; 
      border-top: 4px solid #005689; 
      border-radius: 12px; 
      box-shadow: 0 4px 16px rgba(0,0,0,.06);
    }
    .table tbody td { font-size: 0.875rem; vertical-align: middle; }
    .table-hover tbody tr:hover { background: #f0f8ff; }
    .row-controlled { background: #fff6df; }
    .row-controlled:hover { background: #ffefc4 !important; }
    .btn-control-toggle {
      min-width: 34px;
      font-weight: 700;
      border-width: 1.5px;
    }
    .btn-graph-toggle {
      min-width: 84px;
    }

    /* ── STICKY HEADER FIX ── */
    /* Wrapper harus overflow-y: auto agar sticky bekerja */
    .table-wrapper {
      max-height: 75vh;
      overflow-y: auto;
      overflow-x: auto;
    }

    /* Semua th di thead menjadi sticky */
    #tblCrp thead th {
      background: #e8f1f8;
      color: #004466;
      font-size: 0.875rem;
      text-align: center;
      vertical-align: middle;
      position: sticky;
      z-index: 10;
    }

    /*
      Row-1  (rowspan-3 cells + colspan headers): top = 0
      Row-2  (rowspan-2 cells + colspan ACHIEVEMENT): top = tinggi row-1
      Row-3  (AMOUNT | PERSEN): top = tinggi row-1 + tinggi row-2

      Kita ukur tinggi tiap row dengan JS setelah render,
      tapi default CSS-nya set dulu agar langsung rapi tanpa JS.
    */
    #tblCrp thead tr:nth-child(1) th { top: 0; }
    #tblCrp thead tr:nth-child(2) th { top: var(--row1-h, 40px); }
    #tblCrp thead tr:nth-child(3) th { top: calc(var(--row1-h, 40px) + var(--row2-h, 33px)); }

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
  <a href="<?= base_url('home') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 mb-3 shadow-sm">
    <i class="bi bi-house-door-fill me-1"></i> Kembali ke Beranda
  </a>

  <div class="header">
    <h5 class="mb-0 fw-semibold" id="pageTitle">DASHBOARD CRP<br>LIST ITEM SPAREPART UNTUK CRP</h5>
  </div>

  <div class="card card-custom mb-3">
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label fw-semibold small mb-1">Summary Amount (Semua Part Number)</label>
        <div class="small text-muted" id="summaryAmountText">Actual Amount vs Amount Tahun Lalu</div>
      </div>
      <div style="height: 460px;">
        <canvas id="summaryAmountChart"></canvas>
      </div>
      <div class="mt-3 pt-2 border-top small" id="summaryAmountConclusion">Kesimpulan: -</div>
    </div>
  </div>
  <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
    <div class="d-flex flex-wrap align-items-end gap-3">
      <div>
        <label class="form-label fw-semibold small mb-1">Bulan / Tahun</label>
        <input type="month" class="form-control form-control-sm" id="chart_month" value="<?= date('Y-m') ?>">
      </div>
      <div>
        <label class="form-label fw-semibold small mb-1">Show Data</label>
        <select class="form-select form-select-sm" id="show_limit">
          <option value="50">50</option>
          <option value="100" selected>100</option>
          <option value="200">200</option>
          <option value="all">Semua data</option>
          <option value="controlled">Hanya yang di-control</option>
        </select>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= base_url('crp/export-excel') ?>" class="btn btn-primary" id="btnExport">
        <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
      </a>
    </div>
  </div>

  <div class="card card-custom">
    <div class="card-body p-0">
      <!-- Ganti class table-responsive + style menjadi div.table-wrapper -->
      <div class="table-wrapper">
        <table class="table table-bordered table-hover table-sm mb-0 text-nowrap" id="tblCrp">
          <thead>
            <tr>
              <th rowspan="3">NO</th>
              <th rowspan="3">PART NUMBER</th>
              <th rowspan="3">DESCRIPTION</th>
              <th colspan="2" id="totalYear">TOTAL 2025</th>
              <th colspan="3" id="targetYear">TARGET & ACHIEVEMENT CRP 2026</th>
              <th rowspan="3">VARIANCE AMOUNT</th>
              <th rowspan="3">GRAFIK</th>
              <th rowspan="3">KONTROL</th>
            </tr>
            <tr>
              <th rowspan="2" id="usageYear">USAGE<br>QTY 2025</th>
              <th rowspan="2" id="amountYear">AMOUNT 2025</th>
              <th rowspan="2">TARGET 5%</th>
              <th colspan="2">ACHIEVEMENT</th>
            </tr>
            <tr>
              <th>AMOUNT</th>
              <th>PERSEN</th>
            </tr>
          </thead>
          <tbody>
            <!-- Data diisi via PHP / AJAX -->
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
        <select class="form-select form-select-sm column-filter" data-filter-key="part_number"><option value="">Semua part number</option></select>
      </div>
      <div class="col-12 col-md-6 col-lg-4 col-xl-3">
        <label class="form-label small fw-semibold mb-1">Description</label>
        <select class="form-select form-select-sm column-filter" data-filter-key="description"><option value="">Semua description</option></select>
      </div>
      <div class="col-12 col-md-6 col-lg-4 col-xl-2">
        <label class="form-label small fw-semibold mb-1">Usage Qty</label>
        <select class="form-select form-select-sm column-filter" data-filter-key="usage_qty"><option value="">Semua qty</option></select>
      </div>
      <div class="col-12 col-md-6 col-lg-4 col-xl-2">
        <label class="form-label small fw-semibold mb-1">Achievement %</label>
        <select class="form-select form-select-sm column-filter" data-filter-key="ach_persen"><option value="">Semua persen</option></select>
      </div>
      <div class="col-12 col-md-6 col-lg-4 col-xl-2">
        <label class="form-label small fw-semibold mb-1">Variance Amount</label>
        <select class="form-select form-select-sm column-filter" data-filter-key="variance_amount">
          <option value="">Semua variance</option>
          <option value="positive">Positif</option>
          <option value="negative">Negatif</option>
        </select>
      </div>
    </div>
  </div>

  <div class="pagination-wrap d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
    <div class="pagination-info" id="paginationInfo">Menampilkan 0 hingga 0 dari 0 data</div>
    <nav aria-label="Pagination CRP">
      <ul class="pagination pagination-sm mb-0" id="paginationNav"></ul>
    </nav>
  </div>

  <div class="modal fade" id="graphModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title mb-0" id="graphModalTitle">Grafik Penggunaan</h5>
            <div class="small text-muted" id="chartSummary">Actual Penggunaan vs Max Kuota</div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div style="height: 420px;">
            <canvas id="usageQuotaChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
/* ── Hitung tinggi tiap header-row, set CSS variable ── */
function fixStickyHeaderOffsets() {
  const thead = document.querySelector('#tblCrp thead');
  const rows  = thead ? thead.querySelectorAll('tr') : [];
  if (rows.length >= 2) {
    const h1 = rows[0].getBoundingClientRect().height;
    const h2 = rows[1].getBoundingClientRect().height;
    document.documentElement.style.setProperty('--row1-h', h1 + 'px');
    document.documentElement.style.setProperty('--row2-h', h2 + 'px');
  }
}

/* ── Logika asli (tidak diubah sama sekali) ── */
const API_URL    = '<?= base_url('crp/data') ?>';
const CHART_API_URL = '<?= base_url('crp/chart-usage') ?>';
const SUMMARY_CHART_API_URL = '<?= base_url('crp/chart-summary-amount') ?>';
const EXPORT_URL = '<?= base_url('crp/export-excel') ?>';
const CONTROL_URL = '<?= base_url('crp/control') ?>';
const CONTROL_UPDATE_EVENT_KEY = 'crp-control-updated';
// Interval auto refresh dashboard: 3 jam (3 * 60 * 60 * 1000 = 10800000 milliseconds)
const AUTO_REFRESH_INTERVAL_MS = 10800000;
// Timer untuk menyimpan ID dari setInterval agar dapat dibatalkan jika diperlukan
let autoRefreshTimer = null;
let currentPage = 1;
let pageSize = '100';
let controlMode = 'all';
let usageQuotaChart = null;
let summaryAmountChart = null;
let activeChartPartNumber = '';
let activeChartMonth = '';
let usageChartCache = new Map();
let isSyncingColumnFilters = false;
let lastSummaryMonth = '';
let cachedRows = [];
let cachedMonth = '';
let cachedYear = new Date().getFullYear();
const columnFilterKeys = ['part_number', 'description', 'usage_qty', 'ach_persen', 'variance_amount'];
const optionalDataLabelsPlugin = (typeof ChartDataLabels !== 'undefined') ? [ChartDataLabels] : [];

const graphModalElement = document.getElementById('graphModal');
const graphModal = graphModalElement ? new bootstrap.Modal(graphModalElement) : null;

function formatNumber(n) {
  if (n == null) return '-';
  return parseFloat(n).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatFilterOptionLabel(key, value) {
  if (value == null || value === '') {
    return '';
  }

  if (key === 'usage_qty') {
    return formatNumber(value);
  }

  if (key === 'ach_persen') {
    return `${formatNumber(value)}%`;
  }

  return String(value);
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function getActiveColumnFilters() {
  const filters = {};

  columnFilterKeys.forEach(key => {
    const element = document.querySelector(`.column-filter[data-filter-key="${key}"]`);
    if (element && element.value) {
      filters[key] = element.value;
    }
  });

  return filters;
}

function initColumnFiltersSelect2() {
  if (typeof window.jQuery === 'undefined' || !window.jQuery.fn || !window.jQuery.fn.select2) {
    return;
  }

  const searchableKeys = ['part_number', 'description', 'usage_qty', 'ach_persen'];

  searchableKeys.forEach(key => {
    const element = document.querySelector(`.column-filter[data-filter-key="${key}"]`);
    if (!element) {
      return;
    }

    window.jQuery(element).select2({
      theme: 'bootstrap-5',
      width: '100%',
      allowClear: true,
      placeholder: 'Ketik atau pilih',
    });
  });
}

function syncColumnFilters(filterOptions = {}) {
  isSyncingColumnFilters = true;

  columnFilterKeys.forEach(key => {
    const element = document.querySelector(`.column-filter[data-filter-key="${key}"]`);
    if (!element || element.disabled) {
      return;
    }

    if (key === 'variance_amount') {
      const currentVariance = element.value;
      const allowed = new Set(['', 'positive', 'negative']);
      if (!allowed.has(currentVariance)) {
        element.value = '';
      }

      if (typeof window.jQuery !== 'undefined' && window.jQuery.fn && window.jQuery.fn.select2) {
        window.jQuery(element).trigger('change.select2');
      }

      return;
    }

    const currentValue = element.value;
    const options = Array.isArray(filterOptions[key]) ? filterOptions[key] : [];
    const html = ['<option value="">Semua data</option>'];

    options.forEach(option => {
      const value = String(option.value ?? '');
      const label = option.label ?? formatFilterOptionLabel(key, value);
      html.push(`<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`);
    });

    element.innerHTML = html.join('');

    if (currentValue && options.some(option => String(option.value ?? '') === currentValue)) {
      element.value = currentValue;
    }

    if (typeof window.jQuery !== 'undefined' && window.jQuery.fn && window.jQuery.fn.select2) {
      window.jQuery(element).trigger('change.select2');
    }
  });

  isSyncingColumnFilters = false;
}

function resetAllColumnFilters() {
  isSyncingColumnFilters = true;

  columnFilterKeys.forEach(key => {
    const element = document.querySelector(`.column-filter[data-filter-key="${key}"]`);
    if (element) {
      element.value = '';

      if (typeof window.jQuery !== 'undefined' && window.jQuery.fn && window.jQuery.fn.select2) {
        window.jQuery(element).trigger('change.select2');
      }
    }
  });

  isSyncingColumnFilters = false;

  applyClientSideView(1);
}

function updateYearHeaders(year) {
  const prev = year - 1;
  document.getElementById('pageTitle').innerHTML  = `DASHBOARD CRP<br>LIST ITEM SPAREPART UNTUK CRP ${year}`;
  document.getElementById('totalYear').textContent  = `TOTAL ${prev}`;
  document.getElementById('usageYear').innerHTML    = `USAGE<br>QTY ${prev}`;
  document.getElementById('amountYear').textContent = `AMOUNT ${prev}`;
  document.getElementById('targetYear').textContent = `TARGET & ACHIEVEMENT CRP ${year}`;
}

function updateExportLink(month) {
  const btnExport = document.getElementById('btnExport');
  btnExport.href = `${EXPORT_URL}?month=${encodeURIComponent(month)}`;
}

function syncPartSelector(rows) {
  const selector = document.getElementById('part_selector');
  if (!selector) {
    return;
  }

  const currentSelection = selectedPartNumber;
  const seen = new Set();
  const options = ['<option value="">Pilih part number</option>'];

  rows.forEach(row => {
    const partNumber = String(row.PART_NUMBER ?? '').trim();
    if (!partNumber || seen.has(partNumber)) {
      return;
    }

    seen.add(partNumber);
    const description = String(row.DESCRIPTION ?? '-').replace(/"/g, '&quot;');
    options.push(`<option value="${partNumber}">${partNumber} - ${description}</option>`);
  });

  selector.innerHTML = options.join('');

  if (currentSelection && seen.has(currentSelection)) {
    selector.value = currentSelection;
    selectedPartNumber = currentSelection;
    return;
  }

  if (selector.options.length > 1) {
    selector.selectedIndex = 1;
    selectedPartNumber = selector.value;
    return;
  }

  selectedPartNumber = '';
}

function renderUsageQuotaChart(payload) {
  const canvas = document.getElementById('usageQuotaChart');
  const summary = document.getElementById('chartSummary');
  if (!canvas) {
    return;
  }

  if (usageQuotaChart) {
    usageQuotaChart.destroy();
  }

  const labels = payload.labels ?? [];
  const actualUsage = payload.actual_usage ?? [];
  const actualMonthly = payload.actual_monthly ?? [];
  const maxQuota = payload.max_quota ?? [];
  const partNumber = payload.part_number ?? '-';
  const maxQuotaValue = formatNumber(payload.max_quota_val ?? 0);

  if (summary) {
    summary.textContent = `Part ${partNumber} | Max Kuota: ${maxQuotaValue}`;
  }

  usageQuotaChart = new Chart(canvas, {
    data: {
      labels,
      datasets: [
        {
          type: 'bar',
          label: 'Actual Per Bulan',
          data: actualMonthly,
          backgroundColor: 'rgba(100, 150, 200, 0.7)',
          borderColor: 'rgba(100, 150, 200, 1)',
          borderWidth: 1,
        },
        {
          type: 'bar',
          label: 'Actual Kumulatif',
          data: actualUsage,
          backgroundColor: 'rgba(138, 168, 79, 0.85)',
          borderColor: 'rgba(138, 168, 79, 1)',
          borderWidth: 1,
        },
        {
          type: 'line',
          label: 'Max Kuota',
          data: maxQuota,
          borderColor: '#2f6fb0',
          backgroundColor: '#2f6fb0',
          pointRadius: 2.5,
          pointHoverRadius: 4,
          borderWidth: 2,
          tension: 0.15,
          fill: false,
        },
      ],
    },
    plugins: optionalDataLabelsPlugin,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        datalabels: {
          anchor: (context) => context.dataset.type === 'line' ? 'end' : 'end',
          align: (context) => context.dataset.type === 'line' ? 'top' : 'top',
          offset: (context) => context.dataset.type === 'line' ? 6 : 2,
          clamp: true,
          clip: false,
          font: (context) => {
            if (context.dataset.type === 'line') {
              return {
                weight: '600',
                size: 10,
              };
            }

            return {
              weight: 'bold',
              size: 11,
            };
          },
          color: (context) => {
            if (context.datasetIndex === 1) return '#d95f02';
            if (context.datasetIndex === 2) return '#b71c1c';
            return '#333';
          },
          formatter: (value) => {
            if (value === null || value === undefined) return '';
            return Number(value).toLocaleString('id-ID', { 
              minimumFractionDigits: 0, 
              maximumFractionDigits: 0 
            });
          },
          display: function(context) {
            const value = context.dataset.data?.[context.dataIndex];
            if (value === null || value === undefined) {
              return false;
            }

            // Tampilkan label untuk bar dan semua line per bulan.
            return true;
          }
        },
        legend: {
          position: 'bottom',
        },
        title: {
          display: true,
          text: `${partNumber}`,
          font: {
            size: 14,
            weight: '600',
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: value => Number(value).toLocaleString('id-ID'),
          },
        },
      },
    },
  });
}

function buildLoadingUsagePayload(month, partNumber) {
  const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Des'];
  const actualMonthly = Array.from({ length: 12 }, () => null);
  const actualCumulative = Array.from({ length: 12 }, () => null);

  return {
    status: 'loading',
    part_number: partNumber,
    year: Number(String(month).split('-')[0] ?? new Date().getFullYear()),
    labels,
    actual_usage: actualCumulative,
    actual_monthly: actualMonthly,
    max_quota: Array.from({ length: 12 }, () => null),
    max_quota_val: null,
  };
}

function getUsageChartCacheKey(month, partNumber) {
  return `${String(month).trim()}::${String(partNumber).trim()}`;
}

function loadUsageChart(month) {
  if (!activeChartPartNumber || !month) {
    return;
  }

  const requestedPartNumber = String(activeChartPartNumber).trim();
  const requestedMonth = month;
  const cacheKey = getUsageChartCacheKey(requestedMonth, requestedPartNumber);
  const url = `${CHART_API_URL}?month=${encodeURIComponent(requestedMonth)}&part_number=${encodeURIComponent(requestedPartNumber)}&_t=${Date.now()}`;

  const cachedPayload = usageChartCache.get(cacheKey);
  if (cachedPayload) {
    renderUsageQuotaChart(cachedPayload);
  }

  fetch(url, { cache: 'no-store' })
    .then(r => {
      if (!r.ok) {
        throw new Error(`HTTP ${r.status}`);
      }
      return r.json();
    })
    .then(json => {
      if (requestedPartNumber !== activeChartPartNumber || requestedMonth !== activeChartMonth) {
        return;
      }

      usageChartCache.set(cacheKey, json);
      renderUsageQuotaChart(json);
    })
    .catch(err => {
      console.error(err);
      const summary = document.getElementById('chartSummary');

      if (!cachedPayload) {
        renderUsageQuotaChart(buildLoadingUsagePayload(requestedMonth, requestedPartNumber));
      }

      if (summary) {
        summary.textContent = cachedPayload
          ? `Menampilkan data real terakhir (cache). Gagal refresh: ${err.message}`
          : `Gagal memuat data aktual: ${requestedPartNumber} | ${err.message}`;
      }
    });
}

function loadSummaryAmountChart(month) {
  fetch(`${SUMMARY_CHART_API_URL}?month=${encodeURIComponent(month)}`)
    .then(r => {
      if (!r.ok) {
        throw new Error(`HTTP ${r.status}`);
      }
      return r.json();
    })
    .then(json => {
      renderSummaryAmountChart(json);
    })
    .catch(err => {
      console.error(err);
      const summary = document.getElementById('summaryAmountText');
      if (summary) {
        summary.textContent = `Gagal memuat grafik: ${err.message}`;
      }
    });
}

function renderSummaryAmountChart(payload) {
  const canvas = document.getElementById('summaryAmountChart');
  const summary = document.getElementById('summaryAmountText');
  const conclusion = document.getElementById('summaryAmountConclusion');
  if (!canvas) {
    return;
  }

  if (summaryAmountChart) {
    summaryAmountChart.destroy();
  }

  const labels = payload.labels ?? [];
  const amountCurrent = payload.amount_current ?? [];
  const amountPrevious = payload.amount_previous ?? [];
  const amountCurrentNumeric = amountCurrent.map(value => {
    if (value === null || value === undefined) {
      return null;
    }

    return Number(value);
  });
  const year = payload.year ?? new Date().getFullYear();
  const prevYear = payload.prev_year ?? year - 1;
  const totalCurr = formatNumber(payload.total_curr_year ?? 0);
  const totalPrev = formatNumber(payload.total_prev_year ?? 0);
  const totalCurrValue = Number(payload.total_curr_year ?? 0);
  const totalPrevValue = Number(payload.total_prev_year ?? 0);

  const amountCurrentCumulative = [];
  let runningCurrentAmount = 0;

  amountCurrentNumeric.forEach(value => {
    if (value === null) {
      amountCurrentCumulative.push(null);
      return;
    }

    runningCurrentAmount += value;
    amountCurrentCumulative.push(runningCurrentAmount);
  });

  const amountPreviousNumeric = amountPrevious.map(value => {
    if (value === null || value === undefined) {
      return null;
    }

    return Number(value);
  });

  const amountPreviousCumulative = [];
  let runningPreviousAmount = 0;

  amountPreviousNumeric.forEach(value => {
    if (value === null) {
      amountPreviousCumulative.push(null);
      return;
    }

    runningPreviousAmount += value;
    amountPreviousCumulative.push(runningPreviousAmount);
  });

  if (summary) {
    summary.textContent = `${year}: ${totalCurr} | ${prevYear}: ${totalPrev}`;
  }

  if (conclusion) {
    const diff = totalCurrValue - totalPrevValue;
    const absDiff = Math.abs(diff);
    const diffLabel = formatNumber(absDiff);
    const percentChange = totalPrevValue === 0 ? null : (absDiff / totalPrevValue) * 100;
    const percentLabel = percentChange === null
      ? '-'
      : `${percentChange.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}%`;

    if (diff < 0) {
      conclusion.textContent = `Kesimpulan ${prevYear}-${year}: Total amount menurun sebesar ${diffLabel} (${percentLabel}) dibanding tahun ${prevYear}.`;
    } else if (diff > 0) {
      conclusion.textContent = `Kesimpulan ${prevYear}-${year}: Total amount meningkat sebesar ${diffLabel} (${percentLabel}) dibanding tahun ${prevYear}.`;
    } else {
      conclusion.textContent = `Kesimpulan ${prevYear}-${year}: Total amount tidak berubah (0,00%).`;
    }
  }

  summaryAmountChart = new Chart(canvas, {
    data: {
      labels,
      datasets: [
        {
          type: 'bar',
          label: `Amount ${year}`,
          data: amountCurrentNumeric,
          backgroundColor: 'rgba(54, 162, 235, 0.7)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 1,
        },
        {
          type: 'bar',
          label: `Amount ${prevYear}`,
          data: amountPreviousNumeric,
          backgroundColor: 'rgba(255, 115, 0, 0.35)',
          borderColor: '#ff7300',
          borderWidth: 1,
        },
        {
          type: 'line',
          label: `Akumulatif ${year}`,
          data: amountCurrentCumulative,
          borderColor: '#1f7a4c',
          backgroundColor: '#1f7a4c',
          pointRadius: 2.5,
          pointHoverRadius: 4,
          borderWidth: 2.5,
          tension: 0.12,
          fill: false,
        },
        {
          type: 'line',
          label: `Akumulatif ${prevYear}`,
          data: amountPreviousCumulative,
          borderColor: '#eda847',
          backgroundColor: '#eda847',
          pointRadius: 2,
          pointHoverRadius: 4,
          borderWidth: 2.5,
          tension: 0.12,
          borderDash: [4, 3],
          fill: false,
        },
      ],
    },
    plugins: optionalDataLabelsPlugin,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        datalabels: {
          anchor: 'end',
          align: 'top',
          font: {
            weight: 'bold',
            size: 10,
          },
          color: function(context) {
            // Warna berbeda untuk setiap dataset
            const colors = ['#333', '#ff7300', '#1f7a4c', '#eda847'];
            return colors[context.datasetIndex] || '#333';
          },
          formatter: (value) => {
            if (value === null || value === undefined) return '';
            return Number(value).toLocaleString('id-ID', { 
              minimumFractionDigits: 0, 
              maximumFractionDigits: 0 
            });
          },
          display: function(context) {
            return context.dataset.data[context.dataIndex] !== null;
          },
          clip: false,
          clamp: true,
          offset: 6,
        },
        legend: {
          position: 'bottom',
        },
        title: {
          display: false,
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: value => Number(value).toLocaleString('id-ID'),
          },
        },
      },
    },
  });
}

function openUsageChart(month, partNumber, description = '-') {
  if (!partNumber || !month) {
    return;
  }

  activeChartPartNumber = partNumber;
  activeChartMonth = month;

  const title = document.getElementById('graphModalTitle');
  const summary = document.getElementById('chartSummary');

  if (title) {
    title.textContent = `Grafik Penggunaan ${partNumber}`;
  }

  if (summary) {
    summary.textContent = description && description !== '-'
      ? `${description} | Periode ${month}`
      : `Periode ${month}`;
  }

  if (graphModal) {
    graphModal.show();
  }

  renderUsageQuotaChart(buildLoadingUsagePayload(month, partNumber));

  loadUsageChart(month);
}

function renderGraphButton(row) {
  const partNumber = String(row.PART_NUMBER ?? '').trim();
  const description = String(row.DESCRIPTION ?? '-');

  return `
    <button
      type="button"
      class="btn btn-sm btn-outline-primary btn-graph-toggle"
      data-part-number="${escapeHtml(partNumber)}"
      data-description="${escapeHtml(description)}"
      title="Lihat grafik penggunaan"
      aria-label="Lihat grafik penggunaan"
    >
      <i class="bi bi-graph-up"></i> Grafik
    </button>`;
}

function renderActionButton(row) {
  const controlled = Boolean(row.CONTROLLED);
  const btnClass = controlled ? 'btn-success' : 'btn-outline-success';
  const buttonTitle = controlled ? 'Batalkan kontrol item' : 'Setujui kontrol item';

  return `
    <button
      type="button"
      class="btn btn-sm ${btnClass} btn-control-toggle"
      data-part-number="${row.PART_NUMBER ?? ''}"
      data-controlled="${controlled ? '1' : '0'}"
      title="${buttonTitle}"
      aria-label="${buttonTitle}"
    >
      <i class="bi bi-check-lg"></i>
    </button>`;
}

function applyControlState(button, controlled) {
  const row = button.closest('tr');
  button.dataset.controlled = controlled ? '1' : '0';
  button.classList.toggle('btn-success', controlled);
  button.classList.toggle('btn-outline-success', !controlled);
  button.title = controlled ? 'Batalkan kontrol item' : 'Setujui kontrol item';
  button.setAttribute('aria-label', button.title);
  button.innerHTML = '<i class="bi bi-check-lg"></i>';

  if (row) {
    row.classList.toggle('row-controlled', controlled);
  }
}

function setButtonLoading(button, isLoading) {
  button.disabled = isLoading;
  button.innerHTML = isLoading
    ? '<span class="spinner-border spinner-border-sm"></span>'
    : '<i class="bi bi-check-lg"></i>';
}

function updateControlStatus(button) {
  const partNumber = button.dataset.partNumber ?? '';
  const month = document.getElementById('chart_month').value;
  const nextControlled = button.dataset.controlled !== '1';

  if (!partNumber || !month) {
    return;
  }

  const previousControlled = button.dataset.controlled === '1';
  setButtonLoading(button, true);

  fetch(CONTROL_URL, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify({
      month,
      part_number: partNumber,
      controlled: nextControlled,
    }),
  })
    .then(r => {
      if (!r.ok) {
        throw new Error(`HTTP ${r.status}`);
      }
      return r.json();
    })
    .then(json => {
      const rowData = cachedRows.find(row => String(row.PART_NUMBER ?? '') === partNumber);
      if (rowData) {
        rowData.CONTROLLED = Boolean(json.controlled);
      }

      applyControlState(button, Boolean(json.controlled));

      // Trigger sinkronisasi lintas-tab (mis. tab monitor user) tanpa refresh manual.
      localStorage.setItem(CONTROL_UPDATE_EVENT_KEY, JSON.stringify({
        part_number: partNumber,
        month,
        controlled: Boolean(json.controlled),
        timestamp: Date.now(),
      }));

      if (controlMode === 'controlled') {
        applyClientSideView(1);
      }
    })
    .catch(err => {
      applyControlState(button, previousControlled);
      console.error(err);
      alert(`Gagal mengubah status control: ${err.message}`);
    })
    .finally(() => {
      button.disabled = false;
      button.innerHTML = '<i class="bi bi-check-lg"></i>';
    });
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

function getRowVarianceAmount(row, year) {
  const rowVariance = Number(row.VARIANCE_AMOUNT);
  if (Number.isFinite(rowVariance)) {
    return rowVariance;
  }

  const prevAmount = Number(row['AMOUNT_' + (year - 1)] ?? 0);
  const currentAmount = Number(row.ACH_AMOUNT ?? 0);
  return prevAmount - currentAmount;
}

function buildFilterOptionsFromRows(rows, year) {
  const optionMap = {
    part_number: new Map(),
    description: new Map(),
    usage_qty: new Map(),
    ach_persen: new Map(),
  };

  rows.forEach(row => {
    const partNumber = String(row.PART_NUMBER ?? '').trim();
    if (partNumber && !optionMap.part_number.has(partNumber)) {
      optionMap.part_number.set(partNumber, { value: partNumber, label: partNumber });
    }

    const description = String(row.DESCRIPTION ?? '').trim();
    if (description && !optionMap.description.has(description)) {
      optionMap.description.set(description, { value: description, label: description });
    }

    const usageQtyRaw = row['USAGE_QTY_' + (year - 1)];
    if (usageQtyRaw !== null && usageQtyRaw !== undefined && usageQtyRaw !== '') {
      const usageQty = String(usageQtyRaw);
      if (!optionMap.usage_qty.has(usageQty)) {
        optionMap.usage_qty.set(usageQty, { value: usageQty, label: formatFilterOptionLabel('usage_qty', usageQtyRaw) });
      }
    }

    const achPersenRaw = row.ACH_PERSEN;
    if (achPersenRaw !== null && achPersenRaw !== undefined && achPersenRaw !== '') {
      const achPersen = String(achPersenRaw);
      if (!optionMap.ach_persen.has(achPersen)) {
        optionMap.ach_persen.set(achPersen, { value: achPersen, label: formatFilterOptionLabel('ach_persen', achPersenRaw) });
      }
    }
  });

  return {
    part_number: Array.from(optionMap.part_number.values()),
    description: Array.from(optionMap.description.values()),
    usage_qty: Array.from(optionMap.usage_qty.values()),
    ach_persen: Array.from(optionMap.ach_persen.values()),
  };
}

function getClientFilteredRows() {
  const activeFilters = getActiveColumnFilters();
  const baseRows = controlMode === 'controlled'
    ? cachedRows.filter(row => Boolean(row.CONTROLLED))
    : cachedRows;

  return baseRows.filter(row => {
    return Object.entries(activeFilters).every(([key, value]) => {
      if (!value) {
        return true;
      }

      if (key === 'variance_amount') {
        const varianceAmount = getRowVarianceAmount(row, cachedYear);
        if (value === 'positive') {
          return varianceAmount > 0;
        }

        if (value === 'negative') {
          return varianceAmount < 0;
        }

        return true;
      }

      if (key === 'part_number') {
        return String(row.PART_NUMBER ?? '') === value;
      }

      if (key === 'description') {
        return String(row.DESCRIPTION ?? '') === value;
      }

      if (key === 'usage_qty') {
        return String(row['USAGE_QTY_' + (cachedYear - 1)] ?? '') === value;
      }

      if (key === 'ach_persen') {
        return String(row.ACH_PERSEN ?? '') === value;
      }

      return true;
    });
  });
}

function renderRows(rows, year) {
  const tbody = document.querySelector('#tblCrp tbody');
  if (!tbody) {
    return;
  }

  if (rows.length === 0) {
    tbody.innerHTML = '<tr><td colspan="11" class="text-center text-muted py-3">No data available</td></tr>';
    return;
  }

  tbody.innerHTML = rows.map(d => {
    const varianceAmount = getRowVarianceAmount(d, year);

    return `
    <tr class="${d.CONTROLLED ? 'row-controlled' : ''}">
      <td class="text-center">${d.NO}</td>
      <td>${d.PART_NUMBER ?? '-'}</td>
      <td>${d.DESCRIPTION ?? '-'}</td>
      <td class="text-end">${formatNumber(d['USAGE_QTY_' + (year - 1)])}</td>
      <td class="text-end">${formatNumber(d['AMOUNT_' + (year - 1)])}</td>
      <td class="text-end">${formatNumber(d.TARGET_5PCT)}</td>
      <td class="text-end">${formatNumber(d.ACH_AMOUNT)}</td>
      <td class="text-center">${d.ACH_PERSEN ?? '-'}</td>
      <td class="text-end fw-semibold">${formatNumber(varianceAmount)}</td>
      <td class="text-center">${renderGraphButton(d)}</td>
      <td class="text-center">${renderActionButton(d)}</td>
    </tr>`;
  }).join('');
}

function applyClientSideView(page = 1) {
  const rows = getClientFilteredRows();
  const isAll = pageSize === 'all';
  const total = rows.length;
  const perPage = isAll ? (total || 1) : Math.max(1, Number(pageSize) || 1);
  const totalPages = isAll ? 1 : Math.max(1, Math.ceil(total / perPage));
  const safePage = Math.min(Math.max(1, page), totalPages);
  const startIndex = (safePage - 1) * perPage;
  const visibleRows = isAll ? rows : rows.slice(startIndex, startIndex + perPage);

  syncPartSelector(rows);
  renderRows(visibleRows, cachedYear);
  updatePagination({
    page: safePage,
    per_page: isAll ? total : perPage,
    total,
    total_pages: totalPages,
    has_prev: safePage > 1,
    has_next: safePage < totalPages,
    is_all: isAll,
  });
}

function loadData(month, page = 1, options = {}) {
  const forceFetch = Boolean(options.forceFetch);
  const shouldFetch = forceFetch || cachedMonth !== month || cachedRows.length === 0;
  const selectedYear = parseInt(month.split('-')[0], 10);
  if (!Number.isNaN(selectedYear)) {
    updateYearHeaders(selectedYear);
  }

  updateExportLink(month);

  if (!shouldFetch) {
    applyClientSideView(page);
    return;
  }

  const tbody = document.querySelector('#tblCrp tbody');
  tbody.innerHTML = '<tr><td colspan="11" class="text-center py-3"><span class="spinner-border spinner-border-sm me-2"></span>Loading data...  </td></tr>';

  const queryParams = new URLSearchParams({
    month,
    page: '1',
    limit: 'all',
    control_mode: 'all',
  });

  fetch(`${API_URL}?${queryParams.toString()}`)
    .then(r => {
      if (!r.ok) throw new Error(`HTTP ${r.status}`);
      return r.json();
    })
    .then(json => {
      const rows = Array.isArray(json.data) ? json.data : [];
      const year = parseInt(json.year ?? month.split('-')[0]);
      cachedRows = rows;
      cachedMonth = month;
      cachedYear = Number.isNaN(year) ? selectedYear : year;

      updateYearHeaders(cachedYear);
      syncColumnFilters(buildFilterOptionsFromRows(cachedRows, cachedYear));

      if (lastSummaryMonth !== month) {
        loadSummaryAmountChart(month);
        lastSummaryMonth = month;
      }

      applyClientSideView(page);
    })
    .catch(err => {
      tbody.innerHTML = `<tr><td colspan="11" class="text-center text-danger py-3">Failed to load data: ${err.message}</td></tr>`;
      updatePagination({ page: 1, per_page: 0, total: 0, total_pages: 1, has_prev: false, has_next: false, is_all: pageSize === 'all' });
      console.error(err);
    });
}

const monthPicker = document.getElementById('chart_month');
const showLimit = document.getElementById('show_limit');
monthPicker.addEventListener('change', () => loadData(monthPicker.value, 1));
showLimit.addEventListener('change', () => {
  if (showLimit.value === 'controlled') {
    controlMode = 'controlled';
    pageSize = 'all';
  } else {
    controlMode = 'all';
    pageSize = showLimit.value;
  }

  applyClientSideView(1);
});
document.addEventListener('change', event => {
  const filter = event.target.closest('.column-filter[data-filter-key]');
  if (!filter || filter.disabled || isSyncingColumnFilters) {
    return;
  }

  // Filter select2 sudah ditangani oleh event select2:select/select2:clear.
  if (filter.classList.contains('select2-hidden-accessible')) {
    return;
  }

  applyClientSideView(1);
});

if (typeof window.jQuery !== 'undefined') {
  window.jQuery(document).on('select2:select select2:clear', '.column-filter[data-filter-key]', function () {
    if (isSyncingColumnFilters) {
      return;
    }

    applyClientSideView(1);
  });
}
document.getElementById('filterResetBtn').addEventListener('click', resetAllColumnFilters);
document.getElementById('paginationNav').addEventListener('click', event => {
  const button = event.target.closest('button[data-page]');
  if (!button || button.closest('.disabled')) {
    return;
  }

  const targetPage = Number(button.dataset.page ?? currentPage);
  if (!Number.isFinite(targetPage) || targetPage < 1 || targetPage === currentPage) {
    return;
  }

  applyClientSideView(targetPage);
});
document.querySelector('#tblCrp tbody').addEventListener('click', event => {
  const chartButton = event.target.closest('.btn-graph-toggle');
  if (chartButton) {
    openUsageChart(
      monthPicker.value,
      String(chartButton.dataset.partNumber ?? '').trim(),
      String(chartButton.dataset.description ?? '-').trim()
    );
    return;
  }

  const button = event.target.closest('.btn-control-toggle');
  if (!button) {
    return;
  }

  updateControlStatus(button);
});

function refreshFromCurrentSelection() {
  if (!monthPicker.value) {
    return;
  }

  loadData(monthPicker.value, currentPage, { forceFetch: true });
}

function setupAutoRefresh() {
  // Hentikan timer auto refresh yang sudah berjalan sebelumnya (jika ada)
  if (autoRefreshTimer !== null) {
    clearInterval(autoRefreshTimer);
    autoRefreshTimer = null;
  }
  
  // Mulai interval auto refresh dengan periode 3 jam
  // Setiap 3 jam, dashboard akan secara otomatis me-reload data terbaru dari server
  autoRefreshTimer = setInterval(() => {
    // Ambil bulan yang dipilih user
    const currentMonth = monthPicker.value;
    
    // Jika ada bulan yang dipilih, refresh data dengan flag forceFetch untuk force reload dari server
    if (currentMonth) {
      console.log(`[Auto Refresh] Refreshing dashboard data at ${new Date().toLocaleString('id-ID')}`);
      loadData(currentMonth, currentPage, { forceFetch: true });
    }
  }, AUTO_REFRESH_INTERVAL_MS);
  
  // Log ke console untuk memastikan auto refresh sudah aktif
  console.log(`[Dashboard] Auto refresh aktivitas diperlukan setiap ${AUTO_REFRESH_INTERVAL_MS / 1000 / 60 / 60} jam`);
}

// Auto refresh akan berjalan otomatis setiap 3 jam untuk memastikan data dashboard selalu up-to-date

window.addEventListener('load', () => {
  initColumnFiltersSelect2();
  loadData(monthPicker.value, 1);
  /* Hitung offset setelah browser render header */
  requestAnimationFrame(fixStickyHeaderOffsets);
  
  // Aktifkan auto refresh dashboard setiap 3 jam
  setupAutoRefresh();
});
</script>

</body>
</html>