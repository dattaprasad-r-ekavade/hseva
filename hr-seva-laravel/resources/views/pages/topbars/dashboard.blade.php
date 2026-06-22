<div class="me-auto">
  <div class="fw-semibold">Dashboard</div>
  <div class="small text-muted-3">Welcome back, <span id="companyName">-</span></div>
</div>
<div class="d-none d-md-flex align-items-center gap-2">
  <select class="form-select u-minw-160" id="monthSelect">
    <option value="" selected>Loading...</option>
  </select>
</div>
<div class="dropdown">
  <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
    <i class="bi bi-lightning-charge"></i> Quick Actions
  </button>
  <ul class="dropdown-menu dropdown-menu-end">
    @if ($portal === 'super-admin')
      <li><a class="dropdown-item" href="{{ url('super-admin/super-admin-shift-roster.html') }}"><i class="bi bi-calendar-week me-2"></i>Shift Roster</a></li>
      <li><a class="dropdown-item" href="{{ url('super-admin/scan-attendance.php') }}"><i class="bi bi-camera-video me-2"></i>Scan Attendance</a></li>
      <li><a class="dropdown-item" href="{{ url('super-admin/super-admin-attendance.html') }}"><i class="bi bi-calendar2-check me-2"></i>Update Attendance</a></li>
      <li><a class="dropdown-item" href="{{ url('super-admin/super-admin-payslips.html') }}"><i class="bi bi-download me-2"></i>Download Payslip</a></li>
      <li><a class="dropdown-item" href="{{ url('super-admin/super-admin-ecr-sheet.html') }}"><i class="bi bi-file-earmark-text me-2"></i>Open ECR Sheet</a></li>
      <li><a class="dropdown-item" href="{{ url('super-admin/super-admin-pf-return.html') }}"><i class="bi bi-cloud-upload me-2"></i>PF Return</a></li>
      <li><a class="dropdown-item" href="{{ url('super-admin/super-admin-esic-return.html') }}"><i class="bi bi-cloud-upload me-2"></i>ESIC Return</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="{{ url('super-admin/super-admin-profile.html') }}"><i class="bi bi-building me-2"></i>Company Profile</a></li>
    @else
      <li><a class="dropdown-item" href="{{ url('client/client-shift-roster.html') }}"><i class="bi bi-calendar-week me-2"></i>Shift Roster</a></li>
      <li><a class="dropdown-item" href="{{ url('client/scan-attendance.php') }}"><i class="bi bi-camera-video me-2"></i>Scan Attendance</a></li>
      <li><a class="dropdown-item" href="{{ url('client/client-attendance.html') }}"><i class="bi bi-calendar2-check me-2"></i>Update Attendance</a></li>
      <li><a class="dropdown-item" href="{{ url('client/client-payslips.html') }}"><i class="bi bi-download me-2"></i>Download Payslip</a></li>
      <li><a class="dropdown-item" href="{{ url('client/client-ecr-sheet.html') }}"><i class="bi bi-file-earmark-text me-2"></i>Open ECR Sheet</a></li>
      <li><a class="dropdown-item" href="{{ url('client/client-pf-return.html') }}"><i class="bi bi-cloud-upload me-2"></i>PF Return</a></li>
      <li><a class="dropdown-item" href="{{ url('client/client-esic-return.html') }}"><i class="bi bi-cloud-upload me-2"></i>ESIC Return</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="{{ url('client/client-profile.html') }}"><i class="bi bi-building me-2"></i>Company Profile</a></li>
    @endif
  </ul>
</div>
