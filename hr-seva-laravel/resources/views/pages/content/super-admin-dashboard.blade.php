<div class="container-fluid py-4 px-3 px-lg-4">
        <div id="dashMsg" class="alert d-none"></div>

        <div class="row g-3 mb-3">
          <div class="col-12 col-md-6 col-xl-3">
            <div class="glass p-3">
              <div class="small text-muted-3">Total Clients</div>
              <div class="fs-3 fw-semibold" id="kpiClients">0</div>
              <div class="small text-muted-3">Configured in Client Module</div>
            </div>
          </div>
          <div class="col-12 col-md-6 col-xl-3">
            <div class="glass p-3">
              <div class="small text-muted-3">Client Users</div>
              <div class="fs-3 fw-semibold" id="kpiUsers">0</div>
              <div class="small text-muted-3">Unique login users</div>
            </div>
          </div>
          <div class="col-12 col-md-6 col-xl-3">
            <div class="glass p-3">
              <div class="small text-muted-3">Access Types</div>
              <div class="fs-3 fw-semibold" id="kpiAccessTypes">0</div>
              <div class="small text-muted-3">System + custom types</div>
            </div>
          </div>
          <div class="col-12 col-md-6 col-xl-3">
            <div class="glass p-3">
              <div class="small text-muted-3">Custom Types</div>
              <div class="fs-3 fw-semibold" id="kpiCustomTypes">0</div>
              <div class="small text-muted-3">Created by Super Admin</div>
            </div>
          </div>
        </div>
        <div class="row g-3 mb-3 widget-grid" id="shiftWidgetCards"></div>

        <div class="row g-3">
          <div class="col-12 col-xl-8">
            <div class="glass p-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div>
                  <div class="fw-semibold">Client Overview</div>
                  <div class="small text-muted-3">Latest client users and assigned access type</div>
                </div>
                <a class="btn btn-outline-secondary btn-sm" href="super-admin-module.html">Manage Clients</a>
              </div>
              <div class="table-responsive">
                <table class="table align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Sr No</th>
                      <th>Company</th>
                      <th>userId</th>
                      <th>Access Type</th>
                      <th>Contact</th>
                      <th>Updated</th>
                    </tr>
                  </thead>
                  <tbody id="clientsTbody"></tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="col-12 col-xl-4">
            <div class="glass p-3 h-100">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div>
                  <div class="fw-semibold">Access Type Catalog</div>
                  <div class="small text-muted-3">Permission templates</div>
                </div>
                <a class="btn btn-outline-secondary btn-sm" href="super-admin-access-control.html">Open</a>
              </div>
              <ul class="list-group list-group-flush" id="typeList"></ul>
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
