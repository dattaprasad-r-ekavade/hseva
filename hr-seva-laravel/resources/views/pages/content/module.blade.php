<div class="content-wrap">
      <div class="page-max">
        <div class="card shadow-sm border-0 mb-3">
          <div class="card-body">
            <div class="row g-3 align-items-end">
              <div class="col-md-6">
                <label class="form-label">Search Client</label>
                <input class="form-control" id="searchInput" placeholder="Search by company name, PAN, GSTIN or contact no">
              </div>
              <div class="col-md-6 text-md-end">
                <button class="btn btn-primary" type="button" id="btnAddClient" data-bs-toggle="modal" data-bs-target="#clientModal">
                  <i class="bi bi-plus-lg"></i> Add Client
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h6 class="mb-0">Client Master</h6>
              <div class="text-muted small" id="resultCount">-</div>
            </div>
            <div class="table-responsive">
              <table class="table align-middle mb-0" data-no-datetime="true">
                <thead class="table-light">
                  <tr>
                    <th>Company Name</th>
                    <th>User ID</th>
                    <th>Company Contact No</th>
                    <th>Company PAN ID</th>
                    <th>Company GSTIN</th>
                    <th>Company CIN / LLPIN / Reg. No</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody id="clientTbody"></tbody>
              </table>
            </div>
            <div class="text-center text-muted py-4 d-none" id="emptyState">No clients yet. Click <span class="fw-semibold">Add Client</span>.</div>
          </div>
        </div>
      </div>
    </div>

@push('modals')
</div>


    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  

<div class="modal fade" id="clientModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="clientModalTitle">Add Client</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form class="modal-body needs-validation" novalidate id="clientForm">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Company Name</label>
            <input class="form-control" id="companyName" required>
            <div class="invalid-feedback">Company Name is required.</div>
          </div>
          <div class="col-12">
            <label class="form-label">Company Address</label>
            <textarea class="form-control" id="companyAddress" rows="3"></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Company CIN / LLPIN / Reg. No</label>
            <input class="form-control" id="companyRegNo">
          </div>
          <div class="col-md-6">
            <label class="form-label">Company Contact No</label>
            <input class="form-control" id="companyContactNo">
          </div>
          <div class="col-md-6">
            <label class="form-label">userId</label>
            <input class="form-control" id="userId" required>
            <div class="invalid-feedback">userId is required.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">userPassword</label>
            <input class="form-control" id="userPassword" type="password" required>
            <div class="invalid-feedback">userPassword is required.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Subscription Type</label>
            <select class="form-select" id="subscriptionPlanId" required></select>
            <div class="invalid-feedback">Subscription Type is required.</div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Company PAN ID</label>
            <input class="form-control text-uppercase" id="companyPAN">
          </div>
          <div class="col-md-4">
            <label class="form-label">Company TAN ID</label>
            <input class="form-control text-uppercase" id="companyTAN">
          </div>
          <div class="col-md-4">
            <label class="form-label">Company GSTIN</label>
            <input class="form-control text-uppercase" id="companyGSTIN">
          </div>
        </div>
      </form>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit" form="clientForm">Save Client</button>
      </div>
    </div>
  </div>
</div>
@endpush
