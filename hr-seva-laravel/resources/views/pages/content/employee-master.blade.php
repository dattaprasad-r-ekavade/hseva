<div class="content-wrap py-4 px-3 px-lg-4">
    <div class="page-max">
      <div class="d-lg-none d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
          <h4 class="mb-0">Employee Master</h4>
          <div class="text-muted">Auto-calc components</div>
        </div>
        <div class="d-flex gap-2"></div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="p-3 bg-white border rounded-3 shadow-sm"><div class="text-muted small">Control Month</div><div class="fs-5 fw-semibold" id="controlMonthLabel">-</div><div class="text-muted small">Used for auto calculations</div></div></div>
        <div class="col-md-3"><div class="p-3 bg-white border rounded-3 shadow-sm"><div class="text-muted small">Gross Split</div><div class="fs-5 fw-semibold" id="ctcSplitLabel">-</div><div class="text-muted small" id="ctcSplitSub">-</div></div></div>
        <div class="col-md-3"><div class="p-3 bg-white border rounded-3 shadow-sm"><div class="text-muted small">ESI Wage Limit</div><div class="fs-5 fw-semibold" id="esiLimitLabel">-</div><div class="text-muted small">ESI applicable if CTC = limit</div></div></div>
        <div class="col-md-3"><div class="p-3 bg-white border rounded-3 shadow-sm"><div class="text-muted small">PF Cap</div><div class="fs-5 fw-semibold" id="pfCapLabel">-</div><div class="text-muted small">PF applicable if status Active</div></div></div>
      </div>

      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="row g-3 align-items-end">
            <div class="col-md-5"><label class="form-label">Search</label><input class="form-control" id="searchInput" placeholder="Search by Emp ID, Name, Dept, Designation..."></div>
            <div class="col-md-3"><label class="form-label">Department</label><select class="form-select" id="filterDept"><option value="">All</option></select></div>
            <div class="col-md-2"><label class="form-label">Status</label><select class="form-select" id="filterStatus"><option value="">All</option><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div>
            <div class="col-md-2 d-grid"><button class="btn btn-outline-secondary" id="clearFilters" type="button">Clear</button></div>
    </div>
    </div>
      </div>

      <div class="card shadow-sm border-0 mt-4">
        <div class="card-body">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h6 class="mb-0">Employee Master List</h6>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <div class="text-muted small" id="resultCount">-</div>
              <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">Add Employee</button>
              <button class="btn btn-outline-secondary btn-sm" type="button" id="btnImportEmployees">Import</button>
              <button class="btn btn-outline-secondary btn-sm" type="button" id="btnExportEmployees">Export</button>
              <input type="file" id="importEmployeesFile" accept=".xls,.xlsx,.csv" class="d-none">
            </div>
          </div>
          <div class="table-responsive">
            <table class="table align-middle mb-0" data-no-datetime="true">
              <thead class="table-light"><tr><th>Sr. No</th><th>Employee ID</th><th>Employee Name</th><th class="nowrap">DOJ</th><th>Department</th><th>Designation</th><th class="nowrap">Emp type</th><th>Status</th><th class="text-end">Gross Monthly</th><th>Doc View</th><th class="text-center">Action</th></tr></thead>
              <tbody id="empTbody"></tbody>
            </table>
          </div>
          <div class="d-flex justify-content-end mt-3">
            <button class="btn btn-outline-danger btn-sm btn-clear-history" id="btnClearEmployeeHistory" type="button">
              <i class="bi bi-trash3"></i> Clear Employee History
            </button>
          </div>
          <div class="text-center text-muted py-4 d-none" id="emptyState">No employees yet. Click <span class="fw-semibold">Add Employee</span>.</div>
        </div>
      </div>
    </div>
      </div>

@push('modals')
</div>

  
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    

<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Add Employee</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form class="modal-body needs-validation" novalidate id="empForm">
        <div class="row g-3">
          <div class="col-md-3"><label class="form-label">Employee ID</label><input class="form-control" id="empId" required placeholder="EMP001"><div class="invalid-feedback">Enter employee ID.</div></div>
          <div class="col-md-5"><label class="form-label">Employee_Name</label><input class="form-control" id="empName" required placeholder="Full name"><div class="invalid-feedback">Enter employee name.</div></div>
          <div class="col-md-4"><label class="form-label">DOJ</label><input class="form-control" id="doj" type="date" required><div class="invalid-feedback">Select DOJ.</div></div>
          <div class="col-md-4"><label class="form-label">Department</label><input class="form-control" id="dept" required placeholder="Enter department"><div class="invalid-feedback">Enter department.</div></div>
          <div class="col-md-4"><label class="form-label">Designation</label><input class="form-control" id="designation" required placeholder="e.g., Executive"><div class="invalid-feedback">Enter designation.</div></div>
          <div class="col-md-4"><label class="form-label">Emp type</label><select class="form-select" id="employmentType" required><option value="">Select employee type</option></select><div class="invalid-feedback">Select employment type.</div><div class="form-text">Managed from Employee Type master.</div></div>
          <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" id="status" required><option value="Active" selected>Active</option><option value="Inactive">Inactive</option></select><div class="invalid-feedback">Select status.</div></div>
          <div class="col-md-3"><label class="form-label">Gross Monthly</label><input class="form-control" id="baseCtc" type="number" min="0" step="1" required placeholder="e.g., 30000"><div class="invalid-feedback">Enter gross monthly amount.</div></div>
          <div class="col-md-3"><label class="form-label">CTC Monthly</label><input class="form-control" id="ctcMonthly" type="text" readonly placeholder="Auto calculated"></div>
          <div class="col-md-3"><label class="form-label">CTC Yearly</label><input class="form-control" id="ctcYearly" type="text" readonly placeholder="Auto calculated"></div>
          <div class="col-md-3"><label class="form-label">Address</label><textarea class="form-control" id="empAddress" rows="2" placeholder="Enter full address"></textarea></div>
          <div class="col-12"><div class="form-text">CTC Monthly = Gross Monthly + total CTC Add-on. CTC Yearly = CTC Monthly x 12.</div></div>
          <div class="col-12">
            <label class="form-label mb-1">Emergency Contact Detail (2 persons)</label>
            <div class="row g-2">
              <div class="col-md-4"><input class="form-control" id="emergencyName1" placeholder="Contact 1 Name"></div>
              <div class="col-md-4"><input class="form-control" id="emergencyPhone1" placeholder="Contact 1 Mobile"></div>
              <div class="col-md-4"><input class="form-control" id="emergencyRelation1" placeholder="Contact 1 Relation"></div>
              <div class="col-md-4"><input class="form-control" id="emergencyName2" placeholder="Contact 2 Name"></div>
              <div class="col-md-4"><input class="form-control" id="emergencyPhone2" placeholder="Contact 2 Mobile"></div>
              <div class="col-md-4"><input class="form-control" id="emergencyRelation2" placeholder="Contact 2 Relation"></div>
            </div>
          </div>
          <div class="col-md-4"><label class="form-label">Mobile</label><input class="form-control" id="mobile" placeholder="Mobile"></div>
          <div class="col-md-8"><label class="form-label">Email</label><input class="form-control" id="email" type="email" placeholder="Email"></div>
          <div class="col-md-3"><label class="form-label">UAN</label><input class="form-control" id="uan" placeholder="UAN number"></div>
          <div class="col-md-3"><label class="form-label">Aadhar Card</label><input class="form-control" id="aadharNo" placeholder="Aadhar number"></div>
          <div class="col-md-3"><label class="form-label">PAN Card</label><input class="form-control" id="panCard" placeholder="PAN card"></div>
          <div class="col-md-3"><label class="form-label">PF Number</label><input class="form-control" id="pfNo" placeholder="PF number"></div>
          <div class="col-md-3"><label class="form-label">ESI Number</label><input class="form-control" id="esiNo" placeholder="ESI number"></div>
          <div class="col-md-3"><label class="form-label">Bank Name</label><input class="form-control" id="bankName" placeholder="Bank name"></div>
          <div class="col-md-3"><label class="form-label">Bank Account</label><input class="form-control" id="bankAc" placeholder="Bank account number"></div>
          <div class="col-md-3"><label class="form-label">IFSC</label><input class="form-control" id="ifsc" placeholder="IFSC code"></div>
          <div class="col-md-6">
            <label class="form-label">Individual Leave Allocation</label>
            <div class="row g-2">
              <div class="col-4"><div class="input-group"><span class="input-group-text">CL</span><input class="form-control text-end" id="leaveCl" type="number" min="0" step="0.5" value="0"></div></div>
              <div class="col-4"><div class="input-group"><span class="input-group-text">SL</span><input class="form-control text-end" id="leaveSl" type="number" min="0" step="0.5" value="0"></div></div>
              <div class="col-4"><div class="input-group"><span class="input-group-text">EL</span><input class="form-control text-end" id="leaveEl" type="number" min="0" step="0.5" value="0"></div></div>
            </div>
            <div class="row g-2 mt-1">
              <div class="col-6"><div class="input-group"><span class="input-group-text">Leaves Taken</span><input class="form-control text-end" id="leaveTakenPreview" type="text" value="0" readonly></div></div>
              <div class="col-6"><div class="input-group"><span class="input-group-text">LOP Taken</span><input class="form-control text-end" id="lopTakenPreview" type="text" value="0" readonly></div></div>
            </div>
            <div class="form-text">Set per-employee total leaves. Balance is auto-deducted from Leave entries.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Attachment (PDF)</label>
            <input class="form-control" id="empAttachmentPdf" type="file" accept="application/pdf,.pdf">
            <div class="form-text">Optional. Upload PDF only.</div>
          </div>
        </div>
      </form>
      <div class="modal-footer"><button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-primary" form="empForm" type="submit">Save Employee</button></div>
    </div>
  </div>
</div>
@endpush
