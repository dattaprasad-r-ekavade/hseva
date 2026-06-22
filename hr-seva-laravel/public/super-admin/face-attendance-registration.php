<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Employee Face Registration | Super Admin | HR Seva</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../assets/css/app-common.css" rel="stylesheet">
  <link href="../assets/css/face-attendance.css" rel="stylesheet">
</head>
<body data-face-page="register">
  <div class="app">
    <aside class="sidebar"><div class="brand"><div class="brand-mark"><i class="bi bi-shield-check fs-5"></i></div><div><div class="fw-semibold">HR Seva</div><div class="small text-muted-3">Super Admin</div></div></div><div class="sidebar-scroll"></div></aside>
    <main class="content">
      <header class="topbar"><div class="topbar-inner d-flex align-items-center gap-2"><button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar"><i class="bi bi-list"></i></button><div class="me-auto"><div class="fw-semibold">Employee Face Registration</div><div class="small text-muted-3">Register one face template per employee for the selected client.</div></div><button class="btn theme-icon-btn" id="themeToggle" type="button"><i class="bi bi-moon" id="themeIcon"></i></button></div></header>
      <div class="container-fluid py-4 px-3 px-lg-4">
        <div class="row g-3">
          <div class="col-12 col-xl-7">
            <div class="glass p-3">
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <label class="form-label fw-semibold">Employee</label>
                  <select class="form-select" id="employeeId"></select>
                </div>
                <div class="col-12 col-md-6 d-flex align-items-end">
                  <button class="btn btn-primary w-100" id="btnRegisterFace" type="button"><i class="bi bi-camera-video"></i> Capture & Register Face</button>
                </div>
              </div>
              <div class="camera-shell mt-3">
                <video id="cameraVideo" autoplay muted playsinline></video>
                <div class="camera-overlay"><div class="camera-guide"></div></div>
              </div>
            </div>
          </div>
          <div class="col-12 col-xl-5">
            <div class="glass p-3 h-100">
              <div id="pageStatus" class="alert alert-info mb-0">Select a client from the top picker, then register employee faces.</div>
            </div>
          </div>
          <div class="col-12">
            <div class="glass p-3">
              <div class="fw-semibold mb-3">Registered Faces</div>
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead><tr><th>Employee ID</th><th>Employee Name</th><th>Department</th><th>Designation</th><th>Face</th><th>Updated</th><th>Action</th></tr></thead>
                  <tbody id="registrationTableBody"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
  <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar"><div class="offcanvas-header"><div class="d-flex align-items-center gap-2"><div class="brand-mark u-brand-mark-36"><i class="bi bi-shield-check"></i></div><div><div class="fw-semibold" id="mobileSidebarLabel">HR Seva</div><div class="small text-muted-3">Super Admin</div></div></div><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div><div class="offcanvas-body"></div></div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/app-common.js"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
  <script src="../assets/js/face-attendance.js"></script>
</body>
</html>
