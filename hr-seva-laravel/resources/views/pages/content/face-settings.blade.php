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

@push('modals')
</div>
  </div><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
@endpush
