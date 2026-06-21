<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Monthly Attendance Report | Super Admin | HR Seva</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../assets/css/app-common.css" rel="stylesheet">
  <link href="../assets/css/face-attendance.css" rel="stylesheet">
</head>
<body data-face-page="report">
  <div class="app">
    <aside class="sidebar"><div class="brand"><div class="brand-mark"><i class="bi bi-shield-check fs-5"></i></div><div><div class="fw-semibold">HR Seva</div><div class="small text-muted-3">Super Admin</div></div></div><div class="sidebar-scroll"></div></aside>
    <main class="content">
      <header class="topbar"><div class="topbar-inner d-flex align-items-center gap-2"><button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar"><i class="bi bi-list"></i></button><div class="me-auto"><div class="fw-semibold">Monthly Attendance Report</div><div class="small text-muted-3">Export monthly face attendance reports for the selected client.</div></div><button class="btn theme-icon-btn" id="themeToggle" type="button"><i class="bi bi-moon" id="themeIcon"></i></button></div></header>
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
    </main>
  </div>
  <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar"><div class="offcanvas-header"><div class="d-flex align-items-center gap-2"><div class="brand-mark u-brand-mark-36"><i class="bi bi-shield-check"></i></div><div><div class="fw-semibold" id="mobileSidebarLabel">HR Seva</div><div class="small text-muted-3">Super Admin</div></div></div><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div><div class="offcanvas-body"></div></div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/app-common.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js"></script>
  <script src="../assets/js/face-attendance.js"></script>
</body>
</html>
