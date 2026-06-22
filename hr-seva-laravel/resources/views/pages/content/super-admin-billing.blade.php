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
            <div class="fw-semibold">Client Bills & PDF Bills</div>
            <div class="small text-muted-3">Live from Subscriptions page (real-time)</div>
          </div>
        </div>
        <div class="table-responsive table-wrap">
          <table class="table align-middle mb-0" data-no-datetime="true">
            <thead>
              <tr>
                <th>Sr. No</th>
                <th>Client</th>
                <th>User ID</th>
                <th>Invoice No</th>
                <th>Billing Month</th>
                <th class="text-end">Amount</th>
                <th class="text-end">GST (18%)</th>
                <th class="text-end">Total</th>
                <th>Status</th>
                <th>Due Date</th>
                <th class="text-end">PDF Bill</th>
              </tr>
            </thead>
            <tbody id="billingTbody">
              <tr><td colspan="11" class="text-center text-muted-3 py-3">Loading billing records...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

@push('modals')
</div>


    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
@endpush
