<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CRP Sparepart Dashboard</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
    <h5 class="mb-0 fw-semibold" id="pageTitle">LIST ITEM SPAREPART UNTUK CRP</h5>
  </div>

  <div class="card card-custom mb-3">
    <div class="card-body">
      <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
        <div>
          <label class="form-label fw-semibold small mb-1">Part Number (Grafik)</label>
          <select class="form-select form-select-sm" id="part_selector" style="min-width: 280px;">
            <option value="">Pilih part number</option>
          </select>
        </div>
        <div class="small text-muted" id="chartSummary">Actual Penggunaan vs Max Kuota</div>
      </div>
      <div style="height: 320px;">
        <canvas id="usageQuotaChart"></canvas>
      </div>
    </div>
  </div>

  <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
    <div class="d-flex flex-wrap align-items-end gap-3">
      <div>
        <label class="form-label fw-semibold small mb-1">Bulan / Tahun</label>
        <input type="month" class="form-control form-control-sm" id="chart_month" value="<?= date('Y-m') ?>">
      </div>
      <div>
        <label class="form-label fw-semibold small mb-1">Show</label>
        <select class="form-select form-select-sm" id="show_limit">
          <option value="50">50</option>
          <option value="100" selected>100</option>
          <option value="200">200</option>
          <option value="all">Semua data</option>
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
              <th rowspan="3">AKSI</th>
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

  <div class="pagination-wrap d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
    <div class="pagination-info" id="paginationInfo">Menampilkan 0 hingga 0 dari 0 data</div>
    <nav aria-label="Pagination CRP">
      <ul class="pagination pagination-sm mb-0" id="paginationNav"></ul>
    </nav>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

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
const EXPORT_URL = '<?= base_url('crp/export-excel') ?>';
const CONTROL_URL = '<?= base_url('crp/control') ?>';
const CONTROL_UPDATE_EVENT_KEY = 'crp-control-updated';
let currentPage = 1;
let pageSize = '100';
let usageQuotaChart = null;
let selectedPartNumber = '';

function formatNumber(n) {
  if (n == null) return '-';
  return parseFloat(n).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function updateYearHeaders(year) {
  const prev = year - 1;
  document.getElementById('pageTitle').textContent  = `LIST ITEM SPAREPART UNTUK CRP ${year}`;
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
          label: 'Actual Penggunaan',
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
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
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

function loadUsageChart(month) {
  if (!selectedPartNumber) {
    const summary = document.getElementById('chartSummary');
    if (summary) {
      summary.textContent = 'Actual Penggunaan vs Max Kuota';
    }
    if (usageQuotaChart) {
      usageQuotaChart.destroy();
      usageQuotaChart = null;
    }
    return;
  }

  fetch(`${CHART_API_URL}?month=${encodeURIComponent(month)}&part_number=${encodeURIComponent(selectedPartNumber)}`)
    .then(r => {
      if (!r.ok) {
        throw new Error(`HTTP ${r.status}`);
      }
      return r.json();
    })
    .then(json => {
      renderUsageQuotaChart(json);
    })
    .catch(err => {
      console.error(err);
      const summary = document.getElementById('chartSummary');
      if (summary) {
        summary.textContent = `Gagal memuat grafik: ${err.message}`;
      }
    });
}

function renderActionButton(row) {
  const controlled = Boolean(row.CONTROLLED);
  const label = controlled ? 'Batalkan' : 'Tandai Control';
  const btnClass = controlled ? 'btn-warning' : 'btn-outline-secondary';

  return `
    <button
      type="button"
      class="btn btn-sm ${btnClass} btn-control-toggle"
      data-part-number="${row.PART_NUMBER ?? ''}"
      data-controlled="${controlled ? '1' : '0'}"
    >
      ${label}
    </button>`;
}

function applyControlState(button, controlled) {
  const row = button.closest('tr');
  button.dataset.controlled = controlled ? '1' : '0';
  button.classList.toggle('btn-warning', controlled);
  button.classList.toggle('btn-outline-secondary', !controlled);
  button.textContent = controlled ? 'Batalkan' : 'Tandai Control';

  if (row) {
    row.classList.toggle('row-controlled', controlled);
  }
}

function setButtonLoading(button, isLoading) {
  button.disabled = isLoading;
  button.innerHTML = isLoading
    ? '<span class="spinner-border spinner-border-sm"></span>'
    : button.textContent;
}

function updateControlStatus(button) {
  const partNumber = button.dataset.partNumber ?? '';
  const month = document.getElementById('chart_month').value;
  const nextControlled = button.dataset.controlled !== '1';

  if (!partNumber || !month) {
    return;
  }

  const previousLabel = button.textContent;
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
      applyControlState(button, Boolean(json.controlled));

      // Trigger sinkronisasi lintas-tab (mis. tab monitor user) tanpa refresh manual.
      localStorage.setItem(CONTROL_UPDATE_EVENT_KEY, JSON.stringify({
        part_number: partNumber,
        month,
        controlled: Boolean(json.controlled),
        timestamp: Date.now(),
      }));
    })
    .catch(err => {
      button.textContent = previousLabel;
      console.error(err);
      alert(`Gagal mengubah status control: ${err.message}`);
    })
    .finally(() => {
      button.disabled = false;
      if (button.dataset.controlled === '1') {
        button.innerHTML = 'Batalkan';
      } else {
        button.innerHTML = 'Tandai Control';
      }
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

function loadData(month, page = 1) {
  const selectedYear = parseInt(month.split('-')[0], 10);
  if (!Number.isNaN(selectedYear)) {
    updateYearHeaders(selectedYear);
  }

  updateExportLink(month);

  const tbody = document.querySelector('#tblCrp tbody');
  tbody.innerHTML = '<tr><td colspan="9" class="text-center py-3"><span class="spinner-border spinner-border-sm me-2"></span>Loading data...  </td></tr>';

  fetch(`${API_URL}?month=${encodeURIComponent(month)}&page=${page}&limit=${encodeURIComponent(pageSize)}`)
    .then(r => {
      if (!r.ok) throw new Error(`HTTP ${r.status}`);
      return r.json();
    })
    .then(json => {
      const rows = json.data ?? [];
      const meta = json.pagination ?? {};
      const year = parseInt(json.year ?? month.split('-')[0]);
      updateYearHeaders(year);
      updatePagination(meta);

      syncPartSelector(rows);
      loadUsageChart(month);

      if (rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-3">No data available</td></tr>';
        return;
      }

      tbody.innerHTML = rows.map(d => `
        <tr class="${d.CONTROLLED ? 'row-controlled' : ''}">
          <td class="text-center">${d.NO}</td>
          <td>${d.PART_NUMBER ?? '-'}</td>
          <td>${d.DESCRIPTION ?? '-'}</td>
          <td class="text-end">${formatNumber(d['USAGE_QTY_' + (year - 1)])}</td>
          <td class="text-end">${formatNumber(d['AMOUNT_' + (year - 1)])}</td>
          <td class="text-end">${formatNumber(d.TARGET_5PCT)}</td>
          <td class="text-end">${formatNumber(d.ACH_AMOUNT)}</td>
          <td class="text-center">${d.ACH_PERSEN ?? '-'}</td>
          <td class="text-center">${renderActionButton(d)}</td>
        </tr>`).join('');
    })
    .catch(err => {
      tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-3">Failed to load data: ${err.message}</td></tr>`;
      updatePagination({ page: 1, per_page: 0, total: 0, total_pages: 1, has_prev: false, has_next: false, is_all: pageSize === 'all' });
      console.error(err);
    });
}

const monthPicker = document.getElementById('chart_month');
const showLimit = document.getElementById('show_limit');
const partSelector = document.getElementById('part_selector');
monthPicker.addEventListener('change', () => loadData(monthPicker.value, 1));
showLimit.addEventListener('change', () => {
  pageSize = showLimit.value;
  loadData(monthPicker.value, 1);
});
partSelector.addEventListener('change', event => {
  selectedPartNumber = String(event.target.value ?? '');
  loadUsageChart(monthPicker.value);
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
document.querySelector('#tblCrp tbody').addEventListener('click', event => {
  const button = event.target.closest('.btn-control-toggle');
  if (!button) {
    return;
  }

  updateControlStatus(button);
});
window.addEventListener('load', () => {
  loadData(monthPicker.value, 1);
  /* Hitung offset setelah browser render header */
  requestAnimationFrame(fixStickyHeaderOffsets);
});
</script>

</body>
</html>