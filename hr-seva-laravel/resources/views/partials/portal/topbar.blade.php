<header class="topbar">
  <div class="topbar-inner d-flex align-items-center gap-2">
    <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
      <i class="bi bi-list"></i>
    </button>
    @if (!empty($topbarView))
      @include($topbarView)
    @else
      <div class="me-auto header-meta">
        <h4 class="mb-0">{{ $pageTitle ?? $title ?? '' }}</h4>
        @if (!empty($pageSubtitle))
          <div class="text-muted">{{ $pageSubtitle }}</div>
        @endif
      </div>
    @endif
    <button class="btn theme-icon-btn" id="themeToggle" type="button" aria-label="Toggle theme">
      <i class="bi bi-moon" id="themeIcon"></i>
    </button>
    @if (!empty($showNotifications))
      <button class="btn profile-icon-btn" type="button" aria-label="Notifications">
        <i class="bi bi-bell"></i>
      </button>
    @endif
    <div class="dropdown">
      <button class="btn profile-icon-btn" data-bs-toggle="dropdown" aria-label="Account menu">
        <i class="bi bi-person-circle"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="{{ $portal === 'super-admin' ? url('super-admin/super-admin-profile.html') : url('client/client-profile.html') }}"><i class="bi bi-building me-2"></i>Profile</a></li>
        <li><a class="dropdown-item" href="#"><i class="bi bi-headset me-2"></i>Support</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="{{ $portal === 'super-admin' ? url('super-admin/super-admin-logout.html') : url('client/client-logout.html') }}"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</header>
