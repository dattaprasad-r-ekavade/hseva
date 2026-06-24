<div class="container-fluid py-4 px-3 px-lg-4">
        <div id="statusMsg" class="alert d-none"></div>

        <div class="row g-3 mb-3">
          <div class="col-12 col-md-6 col-xl-3">
            <div class="glass p-3">
              <div class="small text-muted-3">Active statuses</div>
              <div class="fs-4 fw-semibold" id="activeCount">0</div>
              <div class="small text-muted-3 mt-2">Visible in attendance marking.</div>
            </div>
          </div>
          <div class="col-12 col-md-6 col-xl-3">
            <div class="glass p-3">
              <div class="small text-muted-3">Paid statuses</div>
              <div class="fs-4 fw-semibold" id="paidCount">0</div>
              <div class="small text-muted-3 mt-2">Count toward payable days.</div>
            </div>
          </div>
          <div class="col-12 col-md-6 col-xl-3">
            <div class="glass p-3">
              <div class="small text-muted-3">Unpaid statuses</div>
              <div class="fs-4 fw-semibold" id="unpaidCount">0</div>
              <div class="small text-muted-3 mt-2">Reduce payable days.</div>
            </div>
          </div>
          <div class="col-12 col-md-6 col-xl-3">
            <div class="glass p-3">
              <div class="small text-muted-3">Note required</div>
              <div class="fs-4 fw-semibold" id="noteCount">0</div>
              <div class="small text-muted-3 mt-2">Statuses that require a note.</div>
            </div>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-12 col-xl-8">
            <div class="glass p-3 p-lg-4">
              <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <div>
                  <div class="fw-semibold">Configured Statuses</div>
                  <div class="small text-muted-3">These statuses are controlled by Super Admin and apply across attendance marking.</div>
                </div>
                <button class="btn btn-outline-secondary" type="button" id="reloadStatusesBtn"><i class="bi bi-arrow-repeat me-1"></i>Reload</button>
              </div>

              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" data-no-srno="true" data-no-datetime="true">
                  <thead>
                    <tr>
                      <th>Code</th>
                      <th>Label</th>
                      <th>Paid</th>
                      <th>Note</th>
                      <th>Active</th>
                      <th>Order</th>
                    </tr>
                  </thead>
                  <tbody id="statusTableBody"></tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="col-12 col-xl-4">
            <div class="glass p-3 p-lg-4 h-100">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                  <div class="fw-semibold">Legend Preview</div>
                  <div class="small text-muted-3">This preview matches the attendance screen legend and status picker labels.</div>
                </div>
                <span class="badge-soft"><i class="bi bi-ui-checks-grid"></i> Active</span>
              </div>
              <div class="d-flex flex-wrap gap-2" id="statusPreview"></div>
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
