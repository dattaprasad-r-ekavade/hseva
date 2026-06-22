<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{{ $title ?? 'HR Seva' }}</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('assets/css/app-common.css') }}" rel="stylesheet">
  @foreach ($styles ?? [] as $style)
    <link href="{{ asset('assets/css/' . $style) }}" rel="stylesheet">
  @endforeach
  @stack('styles')
</head>
<body
  @class(array_filter([$bodyClass ?? null]))
  data-portal="{{ $portal }}"
  data-page-key="{{ $pageKey }}"
  @foreach ($bodyAttrs ?? [] as $attr => $value) {{ $attr }}="{{ $value }}" @endforeach
>
  <div class="app d-flex">
    @include('partials.portal.sidebar')
    <main class="content flex-grow-1">
      @include('partials.portal.topbar')
      @include($contentView)
    </main>
  </div>
  @include('partials.portal.mobile-sidebar')
  @stack('modals')
  @include('partials.portal.scripts')
</body>
</html>
