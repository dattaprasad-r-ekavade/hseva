<div class="container-fluid py-4 px-3 px-lg-4">
        <div id="typeMsg" class="alert d-none"></div>

        <div class="row g-3 mb-3">
          <div class="col-12 col-md-4">
            <div class="glass p-3">
              <div class="small text-muted-3">Active types</div>
              <div class="fs-4 fw-semibold" id="activeTypeCount">0</div>
              <div class="small text-muted-3 mt-2">Visible in employee add/edit forms.</div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="glass p-3">
              <div class="small text-muted-3">Inactive types</div>
              <div class="fs-4 fw-semibold" id="inactiveTypeCount">0</div>
              <div class="small text-muted-3 mt-2">Hidden from new entries but kept for history.</div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="glass p-3">
              <div class="small text-muted-3">Total types</div>
              <div class="fs-4 fw-semibold" id="totalTypeCount">0</div>
              <div class="small text-muted-3 mt-2">Tenant-specific employee type master.</div>
            </div>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-12 col-xl-8">
            <div class="glass p-3 p-lg-4">
              <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <div>
                  <div class="fw-semibold">Employee Type Master</div>
                  <div class="small text-muted-3">Use this list to standardize employment type selection across employee records and imports.</div>
                </div>
                <div class="d-flex gap-2">
                  <button class="btn btn-outline-secondary" type="button" id="reloadTypesBtn"><i class="bi bi-arrow-repeat me-1"></i>Reload</button>
                  <button class="btn btn-primary" type="button" id="openCreateTypeBtn"><i class="bi bi-plus-circle me-1"></i>Add Type</button>
                </div>
              </div>

              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" data-no-srno="true" data-no-datetime="true">
                  <thead>
                    <tr>
                      <th>Code</th>
                      <th>Label</th>
                      <th>Active</th>
                      <th>Order</th>
                      <th class="text-end">Action</th>
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
                  <div class="fw-semibold">Active Form Preview</div>
                  <div class="small text-muted-3">This matches the order shown in Employee Master dropdowns.</div>
                </div>
                <span class="badge-soft"><i class="bi bi-person-vcard"></i> Live</span>
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
    

  <div class="modal fade" id="employeeTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content u-modal-radius-16">
        <div class="modal-header">
          <h5 class="modal-title" id="employeeTypeModalTitle">Add Employee Type</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="employeeTypeForm">
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Code</label>
                <input class="form-control text-uppercase" id="typeCode" list="employeeTypeCodeSuggestions" required maxlength="24" placeholder="Example: FULL_TIME">
                <datalist id="employeeTypeCodeSuggestions">
                  <option value="FULL_TIME"></option>
                  <option value="PART_TIME"></option>
                  <option value="CONTRACT"></option>
                  <option value="INTERN"></option>
                  <option value="CONSULTANT"></option>
                  <option value="TEMPORARY"></option>
                </datalist>
              </div>
              <div class="col-md-8">
                <label class="form-label">Label</label>
                <input class="form-control" id="typeLabel" required placeholder="Full-time">
              </div>
              <div class="col-md-6">
                <label class="form-label">Sort Order</label>
                <input class="form-control" id="typeSortOrder" type="number" min="0" step="1" value="0">
              </div>
              <div class="col-md-6 d-flex align-items-end">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="typeActive" checked>
                  <label class="form-check-label" for="typeActive">Active</label>
                </div>
              </div>
            </div>
            <div class="small text-muted-3 mt-3">Keep labels clean because they are stored directly on employee records.</div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary" id="saveTypeBtn"><i class="bi bi-check2-circle me-1"></i>Save Type</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endpush
