<div class="container-fluid py-4 px-3 px-lg-4">
        <div class="row g-3">
          <div class="col-12">
            <div class="glass hero-card p-3">
              <div class="d-flex align-items-center gap-3">
                <div class="profile-chip" id="avatarText">--</div>
                <div>
                  <div class="fw-semibold fs-5" id="empName">-</div>
                  <div class="small text-muted-3" id="empMeta">-</div>
                </div>
                <div class="ms-auto d-flex align-items-center gap-2">
                  <button class="btn btn-outline-secondary btn-sm" id="docBtn" type="button" disabled><i class="bi bi-file-earmark-pdf"></i> View Document</button>
                  <button class="btn btn-primary btn-sm" id="btnApplyLeaveQuick" type="button" data-bs-toggle="modal" data-bs-target="#applyLeaveModal"><i class="bi bi-calendar-plus"></i> Apply Leave</button>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-xl-6">
            <div class="glass p-3">
              <div class="section-title mb-2">Employee Details</div>
              <div id="employeeDetails"></div>
            </div>
          </div>
          <div class="col-12 col-xl-6">
            <div class="glass p-3">
              <div class="section-title mb-2">Salary & Statutory</div>
              <div id="salaryDetails"></div>
            </div>
          </div>
          <div class="col-12">
            <div class="glass p-3">
              <div class="section-title mb-2">Advance Salary</div>
              <div class="row g-2 mb-3" id="advanceSummary"></div>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" data-no-datetime="true">
                  <thead>
                    <tr>
                      <th>Period</th>
                      <th class="text-end">Scheduled</th>
                      <th class="text-end">Deducted</th>
                      <th class="text-end">Balance After</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody id="advanceHistoryBody"></tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="glass p-3">
              <div class="section-title mb-2">Leave Summary</div>
              <div class="row g-2" id="leaveSummary"></div>
              <div class="mt-3">
                <div class="section-title mb-2">Leave Details</div>
                <div class="table-responsive">
                  <table class="table table-sm align-middle mb-0" data-no-datetime="true">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Leave Type</th>
                        <th class="text-end">Days</th>
                        <th>Status</th>
                        <th>Reason</th>
                      </tr>
                    </thead>
                    <tbody id="leaveDetailsBody"></tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

@push('modals')
</div>

  <div class="modal fade" id="applyLeaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Apply Leave</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="leaveApplyForm" class="row g-2" novalidate>
            <div class="col-12 col-md-2">
              <label class="form-label small text-muted-3 mb-1">Mode</label>
              <select class="form-select" id="leaveMode">
                <option value="single" selected>Single</option>
                <option value="range">Range</option>
              </select>
            </div>
            <div class="col-12 col-md-3" id="leaveSingleWrap">
              <label class="form-label small text-muted-3 mb-1">Leave Date</label>
              <input class="form-control" id="leaveDateSingle" type="date">
            </div>
            <div class="col-12 col-md-3 d-none" id="leaveRangeWrap">
              <div class="row g-2">
                <div class="col-6">
                  <label class="form-label small text-muted-3 mb-1">From</label>
                  <input class="form-control" id="leaveFromDate" type="date">
                </div>
                <div class="col-6">
                  <label class="form-label small text-muted-3 mb-1">To</label>
                  <input class="form-control" id="leaveToDate" type="date">
                </div>
              </div>
            </div>
            <div class="col-12 col-md-2" id="leaveHalfDayWrap">
              <label class="form-label small text-muted-3 mb-1">Half Day</label>
              <select class="form-select" id="leaveHalfDay">
                <option value="No" selected>No</option>
                <option value="Yes">Yes</option>
              </select>
            </div>
            <div class="col-12 col-md-2">
              <label class="form-label small text-muted-3 mb-1">Days</label>
              <input class="form-control" id="leaveApplyDays" type="text" value="1" readonly>
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label small text-muted-3 mb-1">Type</label>
              <select class="form-select" id="leaveApplyType" required>
                <option value="" selected disabled>Select</option>
                <option value="CL">CL</option>
                <option value="SL">SL</option>
                <option value="EL">EL</option>
                <option value="LOP">LOP</option>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label small text-muted-3 mb-1">Reason</label>
              <input class="form-control" id="leaveApplyReason" type="text" placeholder="Reason for leave" required>
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label small text-muted-3 mb-1">Status</label>
              <select class="form-select" id="leaveApplyStatus">
                <option value="Pending" selected>Pending</option>
                <option value="Not Approved">Not Approved</option>
                <option value="Approved">Approved</option>
              </select>
            </div>
            <div class="col-12 d-flex gap-2">
              <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-check2-circle"></i> Apply Leave</button>
              <button class="btn btn-outline-secondary btn-sm" id="btnLeaveApplyReset" type="button">Reset</button>
            </div>
          </form>
          <div id="leaveApplyMsg" class="small mt-2 mb-0 text-muted-3"></div>
        </div>
      </div>
    </div>
  </div>

  
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
@endpush
