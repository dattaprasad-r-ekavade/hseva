<div class="container-fluid py-4 px-3 px-lg-4">
        <div class="row g-3">
          <div class="col-12 col-xl-7">
            <div class="glass p-3">
              <div class="camera-shell">
                <video id="cameraVideo" autoplay muted playsinline></video>
                <div class="camera-overlay"><div class="camera-guide"></div></div>
              </div>
              <div class="d-flex flex-wrap gap-2 mt-3">
                <button class="btn btn-outline-success active" type="button" data-scan-mode="IN">IN Scan</button>
                <button class="btn btn-outline-warning" type="button" data-scan-mode="OUT">OUT Scan</button>
              </div>
              <div class="d-flex flex-wrap gap-2 mt-3">
                <button class="btn btn-primary" id="btnScanNow" type="button"><i class="bi bi-camera-video"></i> Scan Now</button>
                <span class="status-pill" id="recognizedEmployeeBadge">Employee: -</span>
                <span class="status-pill" id="lastActionBadge">Last action: -</span>
                <span class="status-pill" id="lastTimeBadge">Current time: -</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-xl-5">
            <div class="glass p-3 h-100">
              <div id="scanMessage" class="scan-message p-3">
                <div class="fw-semibold" id="scanMessageTitle">Preparing camera</div>
                <div class="small text-muted-3" id="scanMessageLine">For office device setup, employees can directly scan without choosing their name.</div>
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="glass p-3">
              <div class="fw-semibold mb-3">Selected Employee Attendance This Month</div>
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead><tr><th>Employee ID</th><th>Employee Name</th><th>Department</th><th>Designation</th><th>Date</th><th>IN</th><th>OUT</th><th>Hours</th><th>Status</th><th>IN Status</th><th>OUT Status</th><th>Remarks</th></tr></thead>
                  <tbody id="myAttendanceBody"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

@push('modals')
</div>
  </div><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
@endpush
