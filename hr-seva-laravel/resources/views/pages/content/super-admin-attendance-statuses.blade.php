<div class="container-fluid py-4 px-3 px-lg-4">
        <div id="statusMsg" class="alert d-none"></div>

        <div class="row g-3 mb-3">
          <div class="col-12 col-md-6 col-xl-3">
            <div class="glass p-3">
              <div class="small text-muted-3">Active statuses</div>
              <div class="fs-4 fw-semibold" id="activeCount">0</div>
              <div class="small text-muted-3 mt-2">Visible in attendance status pickers.</div>
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
              <div class="small text-muted-3 mt-2">Reduce payable days in payroll logic.</div>
            </div>
          </div>
          <div class="col-12 col-md-6 col-xl-3">
            <div class="glass p-3">
              <div class="small text-muted-3">Note required</div>
              <div class="fs-4 fw-semibold" id="noteCount">0</div>
              <div class="small text-muted-3 mt-2">Ask for reason while marking attendance.</div>
            </div>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-12 col-xl-8">
            <div class="glass p-3 p-lg-4">
              <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <div>
                  <div class="fw-semibold">Status Master</div>
                  <div class="small text-muted-3">Manage built-in or custom attendance codes here. Use paid or unpaid carefully because payroll relies on that behavior.</div>
                </div>
                <div class="d-flex gap-2">
                  <button class="btn btn-outline-secondary" type="button" id="reloadStatusesBtn"><i class="bi bi-arrow-repeat me-1"></i>Reload</button>
                  <button class="btn btn-primary" type="button" id="openCreateStatusBtn"><i class="bi bi-plus-circle me-1"></i>Add Status</button>
                </div>
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
                      <th class="text-end">Action</th>
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
                  <div class="fw-semibold">Attendance Legend Preview</div>
                  <div class="small text-muted-3">This preview mirrors what users see in attendance pages.</div>
                </div>
                <span class="badge-soft"><i class="bi bi-ui-checks-grid"></i> Live</span>
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
    

  <div class="modal fade" id="attendanceStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content u-modal-radius-16">
        <div class="modal-header">
          <h5 class="modal-title" id="attendanceStatusModalTitle">Add Attendance Status</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="attendanceStatusForm">
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Code</label>
                <input class="form-control text-uppercase" id="statusCode" list="attendanceStatusCodeSuggestions" required maxlength="12" placeholder="Example: HD">
                <datalist id="attendanceStatusCodeSuggestions">
                  <option value="P"></option>
                  <option value="A"></option>
                  <option value="WO"></option>
                  <option value="CL"></option>
                  <option value="SL"></option>
                  <option value="EL"></option>
                  <option value="LOP"></option>
                  <option value="HD"></option>
                  <option value="OD"></option>
                  <option value="WFH"></option>
                </datalist>
              </div>
              <div class="col-md-8">
                <label class="form-label">Full Label</label>
                <input class="form-control" id="statusFullLabel" required placeholder="Present">
              </div>
              <div class="col-12">
                <div class="small text-muted-3">You can use built-in codes or custom ones like `HD`, `OD`, `WFH`.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Short Label</label>
                <input class="form-control" id="statusShortLabel" required placeholder="P">
              </div>
              <div class="col-md-6">
                <label class="form-label">Button Style</label>
                <select class="form-select" id="statusButtonClass">
                  <option value="btn-outline-success">Green</option>
                  <option value="btn-outline-danger">Red</option>
                  <option value="btn-outline-secondary">Gray</option>
                  <option value="btn-outline-primary">Blue</option>
                  <option value="btn-outline-info">Sky</option>
                  <option value="btn-outline-dark">Dark</option>
                  <option value="btn-outline-warning">Amber</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Paid / Unpaid</label>
                <select class="form-select" id="statusPaid">
                  <option value="paid">Paid</option>
                  <option value="unpaid">Unpaid</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Sort Order</label>
                <input class="form-control" id="statusSortOrder" type="number" min="0" step="1" value="0">
              </div>
              <div class="col-md-4 d-flex align-items-end">
                <div class="w-100 vstack gap-2">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="statusActive" checked>
                    <label class="form-check-label" for="statusActive">Active</label>
                  </div>
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="statusNoteRequired">
                    <label class="form-check-label" for="statusNoteRequired">Note Required</label>
                  </div>
                </div>
              </div>
            </div>
            <div class="small text-muted-3 mt-3">Paid statuses count in payroll payable days. Unpaid statuses work like Loss of Pay.</div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary" id="saveStatusBtn"><i class="bi bi-check2-circle me-1"></i>Save Status</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endpush
