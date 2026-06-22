<div class="container-fluid py-4 px-3 px-lg-4">

        <!-- Header -->
        <div class="row g-3">
          <div class="col-12">
            <div class="glass p-3">
              <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
                <div>
                  <div class="fw-semibold fs-5 mb-1">Apply Leave for Employees</div>
                  <div class="small text-muted-3">
                    Search employee by name or employee ID. Selecting an employee auto-fills details.
                  </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                  <span class="badge-soft"><i class="bi bi-search me-1"></i> Search dropdown</span>
                  <span class="badge-soft"><i class="bi bi-check2-circle me-1"></i> Approved / Pending</span>
                  <span class="badge-soft"><i class="bi bi-filetype-csv me-1"></i> CSV export</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-3 mt-0">
          <!-- Form -->
          <div class="col-12 col-lg-4">
            <div class="glass p-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-semibold">New Leave Entry</div>
                <span class="badge-soft">Search: Name / ID</span>
              </div>

              <form id="leaveForm" class="needs-validation" novalidate>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Select Employee *</label>
                  <select id="employeeSelect" placeholder="Search by employee ID or name..." required></select>
                  <div class="invalid-feedback d-block u-hidden" id="empSelectError">Please select employee.</div>
                </div>

                <!-- Auto fields -->
                <div class="row g-2">
                  <div class="col-6">
                    <label class="form-label small text-muted-3 mb-1">Employee ID</label>
                    <input class="form-control mono" id="empId" readonly>
                  </div>
                  <div class="col-6">
                    <label class="form-label small text-muted-3 mb-1">Employee Name</label>
                    <input class="form-control" id="empName" readonly>
                  </div>
                </div>

                <div class="row g-2 mt-2">
                  <div class="col-6">
                    <label class="form-label small text-muted-3 mb-1">Department</label>
                    <input class="form-control" id="dept" readonly>
                  </div>
                  <div class="col-6">
                    <label class="form-label small text-muted-3 mb-1">Designation</label>
                    <input class="form-control" id="desig" readonly>
                  </div>
                </div>

                <hr class="my-3" />

                <!-- Date mode -->
                <div class="mb-3">
                  <label class="form-label fw-semibold">Leave Mode</label>
                  <select class="form-select" id="dateMode">
                    <option value="single" selected>Single Date</option>
                    <option value="range">From - To Date</option>
                  </select>
                </div>

                <!-- Single date -->
                <div id="singleDateWrap" class="row g-2">
                  <div class="col-7">
                    <div class="mb-3">
                      <label class="form-label fw-semibold">Leave Date</label>
                      <input type="date" class="form-control" id="leaveDate">
                    </div>
                  </div>
                  <div class="col-5">
                    <div class="mb-3">
                      <label class="form-label fw-semibold">Half Day?</label>
                      <select class="form-select" id="halfDay">
                        <option value="No" selected>No</option>
                        <option value="Yes">Yes</option>
                      </select>
                    </div>
                  </div>
                </div>

                <!-- Range dates -->
                <div id="rangeDateWrap" class="row g-2 d-none">
                  <div class="col-6">
                    <div class="mb-3">
                      <label class="form-label fw-semibold">From</label>
                      <input type="date" class="form-control" id="fromDate">
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="mb-3">
                      <label class="form-label fw-semibold">To</label>
                      <input type="date" class="form-control" id="toDate">
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="glass-soft p-3 mb-3">
                      <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-calculator"></i>
                        <div class="small text-muted-3">Days (basic):</div>
                        <div class="ms-auto fw-semibold" id="daysText">0</div>
                      </div>
                      <div class="small text-muted-3 mt-1">Holiday/weekly-off exclusion can be added later.</div>
                    </div>
                  </div>
                </div>

                <!-- Leave type -->
                <div class="mb-3">
                  <label class="form-label fw-semibold">Leave Type *</label>
                  <select class="form-select" id="leaveType" required>
                    <option value="" selected disabled>Select</option>
                    <option value="CL">CL - Casual</option>
                    <option value="SL">SL - Sick</option>
                    <option value="EL">EL - Earned</option>
                    <option value="LOP">LOP - Loss of Pay</option>
                  </select>
                  <div class="invalid-feedback">Select leave type.</div>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Reason *</label>
                  <textarea class="form-control" id="reason" rows="3" placeholder="Reason / note" required></textarea>
                  <div class="invalid-feedback">Enter reason.</div>
                </div>

                <div class="row g-2">
                  <div class="col-6">
                    <div class="mb-3">
                      <label class="form-label fw-semibold">Status</label>
                      <select class="form-select" id="status">
                        <option value="Approved" selected>Approved</option>
                        <option value="Pending">Pending</option>
                      </select>
                      <div class="small text-muted-3 mt-1">Choose Pending if Agency approval required.</div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="mb-3">
                      <label class="form-label fw-semibold">Marked By *</label>
                      <input class="form-control" id="markedBy" value="Client HR" required>
                      <div class="invalid-feedback">Required.</div>
                    </div>
                  </div>
                </div>

                <div class="d-grid gap-2">
                  <button class="btn btn-primary" type="submit">
                    <i class="bi bi-check2-circle me-1"></i> Save Leave
                  </button>
                  <button class="btn btn-outline-secondary" type="button" id="btnReset">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                  </button>
                </div>

                <div class="mt-3 small text-muted-3">
                  <i class="bi bi-info-circle me-1"></i>
                  Search employee by typing (example: <span class="mono">EMP001</span> or <span>Rohit</span>)
                </div>
              </form>
            </div>
          </div>

          <!-- Table -->
          <div class="col-12 col-lg-8">
            <div class="glass p-3">
              <div class="mb-3">
                <div>
                  <div class="fw-semibold">Applied Leaves</div>
                  <div class="small text-muted-3">Stored entries with filter, view and export.</div>
                  <div class="small text-muted-3 mt-1">Storage: <span id="storageMode" class="fw-semibold">Loading...</span></div>
                </div>

                <div class="row g-2 mt-1">
                  <div class="col-12 col-md-4">
                    <label class="form-label small text-muted-3 mb-1">Month</label>
                    <select class="form-select" id="filterMonth">
                      <option value="">All</option>
                      <option value="1">Jan</option><option value="2">Feb</option><option value="3">Mar</option>
                      <option value="4">Apr</option><option value="5">May</option><option value="6">Jun</option>
                      <option value="7">Jul</option><option value="8">Aug</option><option value="9">Sep</option>
                      <option value="10">Oct</option><option value="11">Nov</option><option value="12">Dec</option>
                    </select>
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="form-label small text-muted-3 mb-1">Year</label>
                    <select class="form-select" id="filterYear">
                      <option value="">All</option>
                      <option>2026</option><option>2025</option><option>2024</option>
                    </select>
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="form-label small text-muted-3 mb-1">Type</label>
                    <select class="form-select" id="filterType">
                      <option value="">All</option>
                      <option value="CL">CL</option><option value="SL">SL</option><option value="EL">EL</option>
                      <option value="LOP">LOP</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" data-no-datetime="true">
                  <thead>
                    <tr>
                      <th>Sr. No</th>
                      <th>Employee</th>
                      <th>Dept/Desig</th>
                      <th>Date(s)</th>
                      <th>Type</th>
                      <th>Days</th>
                      <th>CL Bal</th>
                      <th>SL Bal</th>
                      <th>EL Bal</th>
                      <th>Total Bal</th>
                      <th>Reason</th>
                      <th>Status</th>
                      <th>Marked By</th>
                      <th class="text-end">Action</th>
                    </tr>
                  </thead>
                  <tbody id="tbody"></tbody>
                </table>
              </div>

              <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="small text-muted-3" id="countText">0 entries</div>
                <button class="btn btn-outline-secondary btn-sm" id="btnClearFilters" type="button">
                  <i class="bi bi-funnel me-1"></i> Clear Filters
                </button>
              </div>
            </div>
          </div>

        </div>

        <div class="text-center small text-muted-3 mt-4">
          &copy; <span id="yr"></span> HR Compliance Portal
        </div>
      </div>

@push('modals')
</div>

  <!-- Mobile Sidebar -->
  
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    

  <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Import Leave Entries</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input class="form-control" id="importFile" type="file" accept=".xlsx,.xls,.csv" />
          <div class="small text-muted-3 mt-2" id="importInfo">No file selected.</div>
          <div class="small text-muted-3 mt-2">
            Columns supported: Employee ID, Employee Name, Department, Designation, From Date, To Date, Days, Leave Type, Reason, Status, Marked By.
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Close</button>
          <button class="btn btn-primary" type="button" id="btnImportUpload">Import</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
@endpush
