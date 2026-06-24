<div class="content-wrap">
      <div class="page-max">
        <div id="msg" class="alert d-none"></div>

        <div class="card shadow-sm border-0 mb-3">
          <div class="card-body">
            <div class="row g-3 align-items-end">
              <div class="col-lg-8">
                <label class="form-label">Role Name</label>
                <input id="roleName" class="form-control" placeholder="e.g. Accountant Access / HR Access / Manager Access">
                <div class="small text-muted mt-2" id="roleCodeInfo">Creating new role</div>
              </div>
              <div class="col-lg-4 text-lg-end d-flex justify-content-lg-end gap-2">
                <button id="btnResetRole" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
                <button id="btnSaveRole" class="btn btn-primary"><i class="bi bi-save"></i> Create Role</button>
              </div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h6 class="mb-0">Role Permissions</h6>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" id="btnAll">Allow All</button>
                <button class="btn btn-outline-secondary btn-sm" id="btnNone">Deny All</button>
              </div>
            </div>
            <div class="row g-3" id="permGrid"></div>
          </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h6 class="mb-0">Role List</h6>
              <div class="text-muted small"><span id="roleCount">0</span> records</div>
            </div>
            <div class="table-responsive">
              <table class="table align-middle mb-0" data-no-datetime="true">
                <thead class="table-light">
                  <tr>
                    <th>Sr. No</th>
                    <th>Role Code</th>
                    <th>Role Name</th>
                    <th>Enabled Modules</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody id="roleTbody"></tbody>
              </table>
            </div>
            <div class="text-center text-muted py-3 d-none" id="roleEmpty">No roles yet.</div>
          </div>
        </div>

        <div class="card shadow-sm border-0">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h6 class="mb-0">Assign Staff Role Access</h6>
              <div class="small text-muted" id="staffEditInfo">Create staff login account</div>
            </div>
            <div class="row g-3 align-items-end mb-3">
              <div class="col-md-4">
                <label class="form-label">Employee</label>
                <select id="staffEmpId" class="form-select"></select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Username</label>
                <input id="staffUsername" class="form-control" placeholder="login username">
              </div>
              <div class="col-md-2">
                <label class="form-label">Password</label>
                <input id="staffPassword" type="password" class="form-control" placeholder="required on create">
              </div>
              <div class="col-md-2">
                <label class="form-label">Role</label>
                <select id="staffRoleCode" class="form-select"></select>
              </div>
              <div class="col-md-1">
                <label class="form-label">Status</label>
                <select id="staffStatus" class="form-select">
                  <option value="Active">Active</option>
                  <option value="Inactive">Inactive</option>
                </select>
              </div>
            </div>
            <div class="d-flex gap-2 mb-3">
              <button id="btnSaveStaff" class="btn btn-primary"><i class="bi bi-save"></i> Save Staff Access</button>
              <button id="btnResetStaff" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-3">
              <h6 class="mb-0">Staff Accounts</h6>
              <div class="text-muted small"><span id="staffCount">0</span> records</div>
            </div>
            <div class="table-responsive">
              <table class="table align-middle mb-0" data-no-datetime="true">
                <thead class="table-light">
                  <tr>
                    <th>Sr. No</th>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody id="staffTbody"></tbody>
              </table>
            </div>
            <div class="text-center text-muted py-3 d-none" id="staffEmpty">No staff access accounts yet.</div>
          </div>
        </div>
      </div>
    </div>

@push('modals')
</div>
@endpush
