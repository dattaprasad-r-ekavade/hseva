<div class="container-fluid py-4 px-3 px-lg-4">
      <div class="glass p-3 mb-3">
        <div class="row g-2 align-items-end">
          <div class="col-12 col-lg-3">
            <label class="form-label fw-semibold">Year</label>
            <select class="form-select" id="yearSel"></select>
          </div>
          <div class="col-12 col-lg-3">
            <label class="form-label fw-semibold">Filter Month</label>
            <select class="form-select" id="filterMonth">
              <option value="">All</option>
            </select>
          </div>
          <div class="col-12 col-lg-6 d-flex gap-2">
            <button class="btn btn-primary" id="btnAdd" type="button"><i class="bi bi-plus-lg"></i> Add Challan</button>
            <button class="btn btn-outline-secondary" id="btnExport" type="button"><i class="bi bi-download"></i> Export CSV</button>
          </div>
        </div>
      </div>

      <div class="glass p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fw-semibold">Compliance challan List (CRUD)</div>
          <div class="small text-muted-3" id="rowCount">0 rows</div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Sr. No</th>
                <th>Month</th>
                <th>Year</th>
                <th>Challan Type</th>
                <th>Due Date</th>
                <th>Date & Time</th>
                <th>Status</th>
                <th class="text-end">Amount</th>
                <th>PDF</th>
                <th>Notes</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody id="tbody"></tbody>
          </table>
        </div>
      </div>
      <div class="text-center small text-muted-3 mt-4">&copy; <span id="yr"></span> HR Compliance Portal</div>
    </div>

@push('modals')
</div>

<div class="modal fade" id="entryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content u-modal-radius-16">
      <div class="modal-header">
        <h5 class="modal-title" id="entryTitle">Add Compliance challan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form class="modal-body" id="entryForm">
        <div class="row g-3">
          <div class="col-md-4"><label class="form-label">Month</label><select class="form-select" id="eMonth" required></select></div>
          <div class="col-md-4"><label class="form-label">Challan Type</label><input class="form-control" id="eType" required placeholder="PF / ESIC / PT / TDS"></div>
          <div class="col-md-4"><label class="form-label">Due Date</label><input class="form-control" id="eDue" type="date" required></div>
          <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" id="eStatus"><option>Pending</option><option>In Progress</option><option>Completed</option></select></div>
          <div class="col-md-4"><label class="form-label">Amount</label><input class="form-control" id="eAmount" type="number" min="0" step="0.01"></div>
          <div class="col-md-4"><label class="form-label">PDF</label><input class="form-control" id="ePdf" type="file" accept="application/pdf,.pdf"></div>
          <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" id="eNotes" rows="2"></textarea></div>
        </div>
      </form>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
        <button class="btn btn-primary" id="btnSaveEntry" type="button">Save</button>
      </div>
    </div>
  </div>
</div>
@endpush
