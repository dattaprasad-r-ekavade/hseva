<div class="container-fluid py-4 px-3 px-lg-4">
        <div class="glass p-3">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-3"><label class="form-label fw-semibold">Month</label><select class="form-select" id="reportMonth"><option value="1">Jan</option><option value="2">Feb</option><option value="3">Mar</option><option value="4">Apr</option><option value="5">May</option><option value="6">Jun</option><option value="7">Jul</option><option value="8">Aug</option><option value="9">Sep</option><option value="10">Oct</option><option value="11">Nov</option><option value="12">Dec</option></select></div>
            <div class="col-12 col-md-3"><label class="form-label fw-semibold">Year</label><input class="form-control" id="reportYear" type="number" min="2024"></div>
            <div class="col-12 col-md-3"><label class="form-label fw-semibold">Employee</label><select class="form-select" id="employeeIdReport"><option value="">All employees</option></select></div>
            <div class="col-12 col-md-3 d-flex gap-2"><button class="btn btn-primary flex-fill" id="btnLoadReport" type="button"><i class="bi bi-search"></i> Load</button><button class="btn btn-outline-success" id="btnExportReport" type="button"><i class="bi bi-file-earmark-excel"></i></button></div>
          </div>
          <div class="table-responsive mt-3">
            <table class="table table-hover align-middle mb-0">
              <thead><tr><th>Employee ID</th><th>Employee Name</th><th>Department</th><th>Designation</th><th>Present</th><th>Late</th><th>Early Out</th><th>Missing OUT</th><th>Total Hours</th></tr></thead>
              <tbody id="attendanceReportBody"></tbody>
            </table>
          </div>
        </div>
      </div>

@push('modals')
</div>
  </div><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
@endpush
