<div class="content-wrap">
      <div class="page-max">
        <div id="msg" class="alert d-none"></div>
        <div class="card shadow-sm border-0 mb-3">
          <div class="card-body">
            <div class="row g-3 align-items-end">
              <div class="col-md-8">
                <label class="form-label">Create Access Type Name</label>
                <input id="newAccessTypeName" class="form-control" placeholder="e.g. HR Executive Access">
              </div>
              <div class="col-md-4 text-md-end">
                <button id="btnCreateType" class="btn btn-outline-primary"><i class="bi bi-plus-lg"></i> Save As New Type</button>
              </div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h6 class="mb-0">Module Permissions</h6>
              <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm" id="btnSaveAccess"><i class="bi bi-save"></i> Save Access</button>
                <button class="btn btn-outline-secondary btn-sm" id="btnAll">Allow All</button>
                <button class="btn btn-outline-secondary btn-sm" id="btnNone">Deny All</button>
              </div>
            </div>
            <div class="small text-muted-3 mb-2" id="accessTargetInfo"></div>
            <div class="row g-3" id="permGrid"></div>
          </div>
        </div>

        <div class="card shadow-sm border-0 mt-3">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h6 class="mb-0">Access Type List</h6>
              <div class="text-muted small"><span id="accessTypeCount">0</span> records</div>
            </div>
            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Sr. No</th>
                    <th>Access Type</th>
                    <th>Enabled Modules</th>
                    <th>System</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody id="accessTypeTbody"></tbody>
              </table>
            </div>
            <div class="text-center text-muted py-3 d-none" id="accessTypeEmpty">No access types yet.</div>
          </div>
        </div>
      </div>
    </div>

@push('modals')
</div>
@endpush
