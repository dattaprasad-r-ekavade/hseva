<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{{ $title ?? 'HR Seva' }}</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('assets/css/app-common.css') }}" rel="stylesheet">
</head>
<body data-portal="{{ $portal }}" data-page-key="{{ $pageKey }}">
  @include($contentView)
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  @if (!empty($loadAppCommon))
    <script src="{{ asset('assets/js/app-core.js') }}"></script>
    <script src="{{ asset('assets/js/app-portal.js') }}"></script>
    <script src="{{ asset('assets/js/app-common.js') }}"></script>
  @endif
  <script src="{{ asset('assets/js/' . $script) }}"></script>
</body>
</html>
