<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
  <div class="offcanvas-header">
    <div class="d-flex align-items-center gap-2">
      <div class="brand-mark"><i class="bi bi-shield-check fs-5"></i></div>
      <div>
        <div class="fw-semibold" id="mobileSidebarLabel">HR Seva</div>
        <div class="small text-muted-3">{{ $portal === 'super-admin' ? 'Super Admin' : 'Loading user...' }}</div>
      </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body"></div>
</div>
