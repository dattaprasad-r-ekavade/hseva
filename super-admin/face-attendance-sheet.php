<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Face Attendance Sheet | Super Admin | HR Seva</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../assets/css/app-common.css" rel="stylesheet">
  <link href="../assets/css/face-attendance.css" rel="stylesheet">
</head>
<body data-face-page="sheet">
  <div class="app">
    <aside class="sidebar"><div class="brand"><div class="brand-mark"><i class="bi bi-shield-check fs-5"></i></div><div><div class="fw-semibold">HR Seva</div><div class="small text-muted-3">Super Admin</div></div></div><div class="sidebar-scroll"></div></aside>
    <main class="content">
      <header class="topbar"><div class="topbar-inner d-flex align-items-center gap-2"><button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar"><i class="bi bi-list"></i></button><div class="me-auto"><div class="fw-semibold">Face Attendance Sheet</div><div class="small text-muted-3">View client-wise date-wise face attendance records.</div></div><button class="btn theme-icon-btn" id="themeToggle" type="button"><i class="bi bi-moon" id="themeIcon"></i></button></div></header>
      <div class="container-fluid py-4 px-3 px-lg-4">
        <div class="glass p-3">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4"><label class="form-label fw-semibold">Date</label><input class="form-control" id="attendanceDate" type="date"></div>
            <div class="col-12 col-md-4"><label class="form-label fw-semibold">Employee</label><select class="form-select" id="employeeIdFilter"><option value="">All employees</option></select></div>
            <div class="col-12 col-md-4"><button class="btn btn-primary w-100" id="btnLoadSheet" type="button"><i class="bi bi-search"></i> Load Sheet</button></div>
          </div>
          <div class="table-responsive mt-3">
            <table class="table table-hover align-middle mb-0">
              <thead><tr><th>Employee ID</th><th>Employee Name</th><th>Department</th><th>Designation</th><th>Date</th><th>IN</th><th>OUT</th><th>Hours</th><th>Status</th><th>IN Status</th><th>OUT Status</th><th>Remarks</th><th>Action</th></tr></thead>
              <tbody id="attendanceSheetBody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>
  <div class="modal fade" id="attendanceEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Attendance</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="attendanceEditForm" class="row g-3">
            <input type="hidden" id="editAttendanceId">
            <div class="col-12"><label class="form-label fw-semibold">Date</label><input class="form-control" id="editAttendanceDate" type="date" required></div>
            <div class="col-6"><label class="form-label fw-semibold">IN Time</label><input class="form-control" id="editInTime" type="time" step="1"></div>
            <div class="col-6"><label class="form-label fw-semibold">OUT Time</label><input class="form-control" id="editOutTime" type="time" step="1"></div>
            <div class="col-6"><label class="form-label fw-semibold">Hours</label><input class="form-control" id="editHours" type="number" min="0" step="0.01"></div>
            <div class="col-6"><label class="form-label fw-semibold">Status</label><input class="form-control" id="editAttendanceStatus" type="text"></div>
            <div class="col-6"><label class="form-label fw-semibold">IN Status</label><input class="form-control" id="editInStatus" type="text"></div>
            <div class="col-6"><label class="form-label fw-semibold">OUT Status</label><input class="form-control" id="editOutStatus" type="text"></div>
            <div class="col-12"><label class="form-label fw-semibold">Remarks</label><textarea class="form-control" id="editRemarks" rows="3"></textarea></div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="btnSaveAttendanceEdit">Save Changes</button>
        </div>
      </div>
    </div>
  </div>
  <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar"><div class="offcanvas-header"><div class="d-flex align-items-center gap-2"><div class="brand-mark u-brand-mark-36"><i class="bi bi-shield-check"></i></div><div><div class="fw-semibold" id="mobileSidebarLabel">HR Seva</div><div class="small text-muted-3">Super Admin</div></div></div><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div><div class="offcanvas-body"></div></div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/app-common.js"></script>
  <script src="../assets/js/face-attendance.js"></script>
</body>
</html>
