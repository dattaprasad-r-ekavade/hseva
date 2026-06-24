<div class="container-fluid py-4 px-3 px-lg-4">
      <div class="row g-3">
        <div class="col-12">
          <div class="card shadow-sm border-0">
            <div class="card-body">
            <div class="fw-semibold mb-2">Upload ESIC Challan</div>
            <div class="text-muted small mb-3">Upload monthly challan PDF and save metadata.</div>
            <form id="challanForm">
              <div class="row g-3">
                <div class="col-12 col-md-6 col-lg-3">
                  <label class="form-label fw-semibold">Month</label>
                  <select class="form-select" id="monthSel" required>
                    <option value="" selected disabled>Choose</option>
                    <option value="1">Jan</option><option value="2">Feb</option><option value="3">Mar</option>
                    <option value="4">Apr</option><option value="5">May</option><option value="6">Jun</option>
                    <option value="7">Jul</option><option value="8">Aug</option><option value="9">Sep</option>
                    <option value="10">Oct</option><option value="11">Nov</option><option value="12">Dec</option>
                  </select>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                  <label class="form-label fw-semibold">Year</label>
                  <select class="form-select" id="yearSel" required>
                    <option value="" selected disabled>Choose</option>
                    <option value="2026">2026</option><option value="2025">2025</option><option value="2024">2024</option>
                  </select>
                </div>
                <div class="col-12 col-lg-6">
                  <label class="form-label fw-semibold">Challan No</label>
                  <input class="form-control" id="challanNo" placeholder="Enter challan number" required />
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                  <label class="form-label fw-semibold">Paid Date</label>
                  <input class="form-control" id="paidDate" type="date" required />
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                  <label class="form-label fw-semibold">Amount</label>
                  <input class="form-control" id="amount" type="number" min="0" step="0.01" required />
                </div>
                <div class="col-12 col-lg-8">
                  <label class="form-label fw-semibold">Upload Challan PDF</label>
                  <input class="form-control" id="challanPdf" type="file" accept="application/pdf,.pdf" required />
                </div>
                <div class="col-12 col-lg-4 d-grid align-self-end">
                  <button class="btn btn-primary" type="submit"><i class="bi bi-upload me-1"></i> Save Challan</button>
                </div>
              </div>
            </form>
            <div class="small text-muted-3 mt-2">Storage: <span id="storageMode" class="fw-semibold">Browser localStorage</span></div>
            </div>
          </div>
        </div>

        <div class="col-12">
          <div class="card shadow-sm border-0">
            <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
              <div>
                <div class="fw-semibold">Saved ESIC Challan List</div>
                <div class="text-muted small">View/download/delete uploaded challans.</div>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" id="btnRefresh" type="button"><i class="bi bi-arrow-repeat"></i> Refresh</button>
                <button class="btn btn-outline-danger btn-sm btn-clear-history" id="btnClearAll" type="button"><i class="bi bi-trash3"></i> Clear History</button>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0" data-no-datetime="true">
                <thead>
                  <tr>
                    <th>Sr. No</th>
                    <th>Month</th>
                    <th>Challan No</th>
                    <th>Paid Date</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Uploaded At</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody id="sheetListBody"></tbody>
              </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
              <div class="small text-muted-3" id="sheetListCount">-</div>
            </div>
            </div>
          </div>
        </div>
      </div>

      <div class="text-center small text-muted-3 mt-4">
        &copy; <span id="yr2"></span> HR Compliance Portal
      </div>
    </div>

@push('modals')
</div>
@endpush
