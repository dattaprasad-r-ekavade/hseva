<aside class="sidebar">
  <div class="brand">
    <div class="brand-mark"><i class="bi bi-shield-check fs-5"></i></div>
    <div>
      <div class="fw-semibold">HR Seva</div>
      <div class="small text-muted-3">{{ $portal === 'super-admin' ? 'Super Admin' : 'Loading user...' }}</div>
    </div>
  </div>
  <div class="sidebar-scroll" data-server-nav="1">
    @foreach (($navigation ?? []) as $section)
      <div class="nav-section-title">{{ $section['title'] }}</div>
      <nav class="nav flex-column gap-1">
        @foreach ($section['items'] as $item)
          <a class="nav-link{{ ($pageKey ?? '') === ($item['page'] ?? '') ? ' active' : '' }}"
             href="{{ $item['href'] }}"
             data-perm="{{ $item['permission'] ?? '' }}">
            <i class="bi {{ $item['icon'] }}"></i> {{ $item['label'] }}
          </a>
        @endforeach
      </nav>
    @endforeach
  </div>
</aside>
