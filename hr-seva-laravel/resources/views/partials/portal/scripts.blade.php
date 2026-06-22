<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@foreach ($cdnScripts ?? [] as $script)
  <script src="{{ $script }}"></script>
@endforeach
<script src="{{ asset('assets/js/app-common.js') }}"></script>
@foreach ($scripts ?? [] as $script)
  <script src="{{ asset('assets/js/' . $script) }}{{ str_contains($script, '?') ? '' : '' }}"></script>
@endforeach
@stack('scripts')
