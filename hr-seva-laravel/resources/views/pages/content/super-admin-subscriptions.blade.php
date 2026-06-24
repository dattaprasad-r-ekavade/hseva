<div class="container-fluid py-4 px-3 px-lg-4">
        <div id="subMsg" class="alert d-none"></div>
        <div class="glass p-3">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="mb-0">Subscription Plans</h6>
            <button class="btn btn-primary btn-sm" id="btnAddSubscription" data-bs-toggle="modal" data-bs-target="#subscriptionModal"><i class="bi bi-plus-lg"></i> Add Plan</button>
          </div>
          <div class="table-responsive">
            <table class="table align-middle mb-0" data-no-datetime="true">
              <thead>
                <tr>
                  <th>Sr. No</th>
                  <th>Plan</th>
                  <th>Access Type</th>
                  <th>Duration (Months)</th>
                  <th class="text-end">Amount</th>
                  <th>Status</th>
                  <th>Features</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody id="subsTbody"></tbody>
            </table>
          </div>
        </div>

        <div class="glass p-3 mt-3">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="mb-0">Client Subscription Renewals</h6>
            <span class="badge-soft" id="clientSubsCount">-</span>
          </div>
          <div class="table-responsive">
            <table class="table align-middle mb-0" data-no-datetime="true">
              <thead>
                <tr>
                  <th>Sr. No</th>
                  <th>Client</th>
                  <th>User ID</th>
                  <th>Plan</th>
                  <th>Start</th>
                  <th>End</th>
                  <th>Renewal</th>
                  <th>Status</th>
                  <th class="text-end">Amount</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody id="clientSubsTbody"></tbody>
            </table>
          </div>
        </div>
      </div>

@push('modals')
</div>

  
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    

  <div class="modal fade" id="subscriptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="subModalTitle">Add Plan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form id="subForm" class="modal-body needs-validation" novalidate>
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Plan Name</label>
              <input class="form-control" id="subPlanName" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Access Type</label>
              <select class="form-select" id="subAccessTypeCode" required></select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Duration (Months)</label>
              <input type="number" min="1" step="1" class="form-control" id="subDurationMonths" required value="12">
            </div>
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select class="form-select" id="subStatus" required>
                <option>Active</option>
                <option>Inactive</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Amount</label>
              <input type="number" min="0" step="1" class="form-control" id="subAmount" required>
            </div>
            <div class="col-md-12">
              <label class="form-label">Features</label>
              <textarea class="form-control" id="subNotes" rows="2"></textarea>
            </div>
          </div>
        </form>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" form="subForm" type="submit">Save</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="renewSubscriptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Renew Client Subscription</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form id="renewSubForm" class="modal-body needs-validation" novalidate>
          <input type="hidden" id="renewClientId">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Client</label>
              <input class="form-control" id="renewClientName" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label">User ID</label>
              <input class="form-control" id="renewUserId" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label">Plan</label>
              <select class="form-select" id="renewPlanId" required></select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select class="form-select" id="renewStatus" required>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Start Date</label>
              <input type="date" class="form-control" id="renewStartDate" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">End Date</label>
              <input type="date" class="form-control" id="renewEndDate" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Renewal Date</label>
              <input type="date" class="form-control" id="renewRenewalDate" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Amount</label>
              <input type="number" min="0" step="1" class="form-control" id="renewAmount" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Notes</label>
              <input class="form-control" id="renewNotes" placeholder="Renewed from Super Admin">
            </div>
          </div>
        </form>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" form="renewSubForm" type="submit">Renew Subscription</button>
        </div>
      </div>
    </div>
  </div>
@endpush
