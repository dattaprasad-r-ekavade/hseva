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
              <div id="pageStatus" class="alert alert-info mb-0">Camera will start automatically. Ask the employee to look straight at the webcam.</div>
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

@push('modals')
</div>
  </div><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
@endpush
