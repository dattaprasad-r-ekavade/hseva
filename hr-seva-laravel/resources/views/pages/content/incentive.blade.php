<div class="container-fluid py-4 px-3 px-lg-4">
      <div class="alert alert-warning d-none" id="clientScopeNotice"></div>
      <form id="incentiveForm" class="glass p-3 mb-3">
        <div class="row g-3 align-items-end">
          <div class="col-md-4"><label class="form-label fw-semibold">Employee</label><select id="empId" class="form-select"><option value="">Select employee</option></select></div>
          <div class="col-md-3"><label class="form-label fw-semibold">Date</label><input id="incentiveDate" type="date" class="form-control"></div>
          <div class="col-md-3"><label class="form-label fw-semibold">Amount</label><input id="amount" type="number" class="form-control text-end" min="0" step="0.01" placeholder="0.00"></div>
          <div class="col-md-2"><label class="form-label fw-semibold">Action</label><button class="btn btn-primary w-100" type="submit"><i class="bi bi-magic"></i> Generate</button></div>
          <div class="col-12"><label class="form-label fw-semibold">Remarks</label><textarea id="remarks" class="form-control" rows="2" placeholder="Optional note"></textarea></div>
        </div>
      </form>
      <div class="glass p-3">
        <div class="section-head mb-2"><div><h6 class="fw-bold mb-0">Created Incentives</h6><div class="text-muted-3"><span id="recordCount">0</span> records | <span id="totalAmount">Rs 0.00</span></div></div><button id="btnClearAll" class="btn btn-sm btn-outline-danger" type="button"><i class="bi bi-trash"></i> Clear History</button></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0" data-no-datetime="true"><thead><tr><th>Sr. No</th><th>Employee</th><th>Date</th><th class="text-end">Amount</th><th>Remarks</th><th>Created On</th><th class="text-end">Action</th></tr></thead><tbody id="incentiveTable"></tbody></table></div>
      </div>
    </div>

@push('modals')
</div>
</div><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
@endpush
