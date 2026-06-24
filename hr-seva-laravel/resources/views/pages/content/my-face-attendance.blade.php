<div class="container-fluid py-4 px-3 px-lg-4">
        <div class="glass p-3">
          <div id="pageStatus" class="alert alert-info">Loading your attendance records.</div>
          <div class="table-responsive mt-3">
            <table class="table table-hover align-middle mb-0">
              <thead><tr><th>Employee ID</th><th>Employee Name</th><th>Department</th><th>Designation</th><th>Date</th><th>IN</th><th>OUT</th><th>Hours</th><th>Status</th><th>IN Status</th><th>OUT Status</th><th>Remarks</th></tr></thead>
              <tbody id="myOwnAttendanceBody"></tbody>
            </table>
          </div>
        </div>
      </div>

@push('modals')
</div>
  </div><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
@endpush
