<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Scan Attendance Settings | HR Seva</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../assets/css/app-common.css" rel="stylesheet">
  <link href="../assets/css/face-attendance.css" rel="stylesheet">
</head>
<body data-face-page="settings">
  <div class="app">
    <aside class="sidebar"><div class="brand"><div class="brand-mark"><i class="bi bi-shield-check fs-5"></i></div><div><div class="fw-semibold">HR Seva</div><div class="small text-muted-3">Loading user...</div></div></div><div class="sidebar-scroll"></div></aside>
    <main class="content">
      <header class="topbar"><div class="topbar-inner d-flex align-items-center gap-2"><button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar"><i class="bi bi-list"></i></button><div class="me-auto"><div class="fw-semibold">Scan Attendance Settings</div><div class="small text-muted-3">Define IN/OUT rules, matching threshold, and model location.</div></div><button class="btn theme-icon-btn" id="themeToggle" type="button"><i class="bi bi-moon" id="themeIcon"></i></button></div></header>
      <div class="container-fluid py-4 px-3 px-lg-4">
        <div class="glass p-3">
          <form id="settingsForm" class="row g-3">
            <div class="col-12 col-md-4"><label class="form-label fw-semibold">IN allowed from</label><input class="form-control" id="inAllowedFrom" type="time" required></div>
            <div class="col-12 col-md-4"><label class="form-label fw-semibold">IN allowed till</label><input class="form-control" id="inAllowedTill" type="time" required></div>
            <div class="col-12 col-md-4"><label class="form-label fw-semibold">Late mark after</label><input class="form-control" id="lateMarkAfter" type="time" required></div>
            <div class="col-12 col-md-4"><label class="form-label fw-semibold">OUT allowed from</label><input class="form-control" id="outAllowedFrom" type="time" required></div>
            <div class="col-12 col-md-4"><label class="form-label fw-semibold">OUT allowed till</label><input class="form-control" id="outAllowedTill" type="time" required></div>
            <div class="col-12 col-md-4"><label class="form-label fw-semibold">Grace time (minutes)</label><input class="form-control" id="graceTime" type="number" min="0"></div>
            <div class="col-12 col-md-4"><label class="form-label fw-semibold">Face match threshold</label><input class="form-control" id="faceMatchThreshold" type="number" min="0.1" max="1.5" step="0.01"></div>
            <div class="col-12 col-md-4"><label class="form-label fw-semibold">Auto capture interval (seconds)</label><input class="form-control" id="autoCaptureSeconds" type="number" min="1" max="20"></div>
            <div class="col-12 col-md-4"><label class="form-label fw-semibold">Recommended scan distance (cm)</label><input class="form-control" id="scanDistanceCm" type="number" min="20" max="150"></div>
            <div class="col-12 col-md-4"><label class="form-label fw-semibold">Timezone</label><input class="form-control" id="timezone" type="text" placeholder="Asia/Kolkata"></div>
            <div class="col-12"><label class="form-label fw-semibold">Model URL</label><input class="form-control" id="modelUrl" type="text" placeholder="https://.../face-api.js/models"></div>
            <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Save Settings</button></div>
          </form>
          <div id="pageStatus" class="alert alert-info mt-3 mb-0">Load settings, adjust values, then save.</div>
        </div>
      </div>
    </main>
  </div>
  <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar"><div class="offcanvas-header"><div class="d-flex align-items-center gap-2"><div class="brand-mark u-brand-mark-36"><i class="bi bi-shield-check"></i></div><div><div class="fw-semibold" id="mobileSidebarLabel">HR Seva</div><div class="small text-muted-3">Client</div></div></div><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div><div class="offcanvas-body"></div></div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/app-common.js"></script>
  <script src="../assets/js/face-attendance.js"></script>
</body>
</html>
