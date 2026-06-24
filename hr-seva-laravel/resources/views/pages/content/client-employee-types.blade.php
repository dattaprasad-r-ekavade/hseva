<div class="container-fluid py-4 px-3 px-lg-4">
        <div id="typeMsg" class="alert d-none"></div>

        <div class="row g-3 mb-3">
          <div class="col-12 col-md-4">
            <div class="glass p-3">
              <div class="small text-muted-3">Active types</div>
              <div class="fs-4 fw-semibold" id="activeTypeCount">0</div>
              <div class="small text-muted-3 mt-2">Shown in employee add/edit forms.</div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="glass p-3">
              <div class="small text-muted-3">Inactive types</div>
              <div class="fs-4 fw-semibold" id="inactiveTypeCount">0</div>
              <div class="small text-muted-3 mt-2">Retained only for existing records.</div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="glass p-3">
              <div class="small text-muted-3">Total types</div>
              <div class="fs-4 fw-semibold" id="totalTypeCount">0</div>
              <div class="small text-muted-3 mt-2">Configured by Super Admin.</div>
            </div>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-12 col-xl-8">
            <div class="glass p-3 p-lg-4">
              <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <div>
                  <div class="fw-semibold">Configured Employee Types</div>
                  <div class="small text-muted-3">This list is read-only here and is used by Employee Master dropdowns.</div>
                </div>
                <button class="btn btn-outline-secondary" type="button" id="reloadTypesBtn"><i class="bi bi-arrow-repeat me-1"></i>Reload</button>
              </div>

              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" data-no-srno="true" data-no-datetime="true">
                  <thead>
                    <tr>
                      <th>Code</th>
                      <th>Label</th>
                      <th>Active</th>
                      <th>Order</th>
                    </tr>
                  </thead>
                  <tbody id="typeTableBody"></tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="col-12 col-xl-4">
            <div class="glass p-3 p-lg-4 h-100">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                  <div class="fw-semibold">Dropdown Preview</div>
                  <div class="small text-muted-3">Matches the order currently used in Employee Master.</div>
                </div>
                <span class="badge-soft"><i class="bi bi-person-vcard"></i> Active</span>
              </div>
              <div class="d-flex flex-wrap gap-2" id="typePreview"></div>
            </div>
          </div>
        </div>
      </div>

@push('modals')
</div>

  
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
@endpush
