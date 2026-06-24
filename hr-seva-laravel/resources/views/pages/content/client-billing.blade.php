<div class="container-fluid py-4 px-3 px-lg-4">
      <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
          <div class="glass p-3 metric-card">
            <div class="label">Paid</div>
                  <div class="value" id="paidAmount">-</div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="glass p-3 metric-card">
            <div class="label">Pending</div>
                  <div class="value" id="pendingAmount">-</div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="glass p-3 metric-card">
            <div class="label">Total</div>
                  <div class="value" id="totalAmount">-</div>
          </div>
        </div>
      </div>

      <div class="glass p-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div>
            <div class="fw-semibold">Billing History</div>
            <div class="small text-muted-3">Plan: <span id="billPlanName">-</span></div>
          </div>
          <button class="btn btn-primary btn-sm" id="btnRenewSubscription" type="button">
            <i class="bi bi-arrow-repeat"></i> Renew Subscription
          </button>
        </div>
        <div class="table-responsive table-wrap">
          <table class="table align-middle mb-0" data-no-datetime="true">
            <thead>
              <tr>
                <th>Sr. No</th>
                <th>Invoice No</th>
                <th>Billing Month</th>
                <th class="text-end">Amount</th>
                <th class="text-end">GST (18%)</th>
                <th class="text-end">Total</th>
                <th>Status</th>
                <th>Due Date</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody id="billingTbody">
              <tr><td colspan="9" class="text-center text-muted-3 py-3">Loading billing records...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

@push('modals')
</div>



<div class="modal fade" id="clientRenewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Renew Client Subscription</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="clientRenewForm" class="modal-body needs-validation" novalidate>
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
            <input class="form-control" id="renewPlanName" readonly>
          </div>
          <div class="col-md-6">
            <label class="form-label">Status</label>
            <input class="form-control" id="renewStatus" readonly>
          </div>
          <div class="col-md-4">
            <label class="form-label">Start Date</label>
            <input type="text" class="form-control" id="renewStartDate" readonly>
          </div>
          <div class="col-md-4">
            <label class="form-label">End Date</label>
            <input type="text" class="form-control" id="renewEndDate" readonly>
          </div>
          <div class="col-md-4">
            <label class="form-label">Renewal Date</label>
            <input type="text" class="form-control" id="renewRenewalDate" readonly>
          </div>
          <div class="col-md-6">
            <label class="form-label">Amount</label>
            <input type="text" class="form-control" id="renewAmount" readonly>
          </div>
          <div class="col-md-6">
            <label class="form-label">Notes</label>
            <input class="form-control" id="renewNotes" value="Renewed by Client">
          </div>
        </div>
      </form>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" form="clientRenewForm" type="submit">Renew Subscription</button>
      </div>
    </div>
  </div>
</div>
@endpush
