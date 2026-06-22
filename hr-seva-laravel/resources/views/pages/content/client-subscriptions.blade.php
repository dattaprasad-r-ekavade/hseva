<div class="container-fluid py-4 px-3 px-lg-4">
      <div class="row g-3">
        <div class="col-12 col-xl-5">
          <div class="glass p-3 p-lg-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div>
                <div class="small text-muted-3">Current Plan</div>
                <h5 class="mb-1 mt-1" id="curPlanName">-</h5>
              </div>
              <div class="brand-mark u-brand-mark-44">
                <i class="bi bi-stars fs-5"></i>
              </div>
            </div>
            <div class="glass-soft p-3 plan-highlight">
              <div class="small text-muted-3" id="curPlanMeta">No subscription plan assigned.</div>
              <div class="small mt-2" id="curPlanFeatures"></div>
            </div>
            <div class="small text-muted-3 mt-3">
              Contact Super Admin to upgrade or change your subscription.
            </div>
          </div>
        </div>
        <div class="col-12 col-xl-7">
          <div class="glass p-3 p-lg-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div>
                <div class="fw-semibold">Other Available Plans</div>
                <div class="small text-muted-3">Compare plan duration and amount</div>
              </div>
              <span class="badge-soft" id="plansCount">0 plans</span>
            </div>
            <div class="table-responsive plans-table-wrap">
              <table class="table align-middle mb-0 plans-table" data-no-datetime="true">
                <thead>
                  <tr>
                    <th>Sr. No</th>
                    <th>Plan Name</th>
                    <th>Duration</th>
                    <th class="text-end">Amount</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody id="plansTbody">
                  <tr><td colspan="5" class="text-center text-muted-3 py-3">Loading plans...</td></tr>
                </tbody>
              </table>
            </div>
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
