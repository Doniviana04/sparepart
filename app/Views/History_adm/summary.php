<div class="card history-card summary-card mt-3">
	<div class="card-body">
		<div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
			<div>
				<div class="fw-semibold">Summary History CRP Sparepart</div>
				<div class="text-muted small" id="summaryCaption">Ringkasan capaian Januari-Desember berdasarkan tahun yang dipilih.</div>
			</div>
			<div class="summary-picker-wrap">
				<label class="form-label fw-semibold small mb-1" for="summaryYearPicker">Show Picker Tahun</label>
				<input type="month" class="form-control form-control-sm" id="summaryYearPicker" value="<?= date('Y-m') ?>">
			</div>
		</div>

		<div class="table-responsive">
			<table class="table table-bordered table-sm mb-0" id="tblSummary">
				<thead>
					<tr>
						<th>BULAN</th>
						<th>TARGET AKUMULASI</th>
						<th>MONTHLY ACHIEVEMENT</th>
						<th>ACHIEVEMENT AKUMULASI</th>
					</tr>
				</thead>
				<tbody id="summaryBody">
					<tr class="empty-state">
						<td colspan="4" class="py-3">Memuat ringkasan summary...</td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- <div class="summary-note" id="summaryNote">
			Keterangan Summary: TARGET AKUMULASI adalah total target maksimum seluruh data pada periode terpilih. MONTHLY ACHIEVEMENT adalah total realisasi pemakaian pada bulan berjalan. ACHIEVEMENT AKUMULASI adalah total realisasi periode sebelumnya ditambah realisasi bulan berjalan.
		</div> -->
	</div>
</div>
