<div class="container-fluid px-3 px-lg-4 py-4">
        <div class="mx-auto d-grid gap-4">
          <div id="otMsg" class="d-none"></div>

          <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-3"><div class="glass p-4 ot-stat"><div class="label">Total Entries</div><div id="statEntries" class="value">0</div></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="glass p-4 ot-stat"><div class="label">Total Hours</div><div id="statHours" class="value">0.00</div></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="glass p-4 ot-stat"><div class="label">Total Amount</div><div id="statAmount" class="value">Rs 0.00</div></div></div>
            <div class="col-12 col-md-6 col-xl-3"><div class="glass p-4 ot-stat"><div class="label">This Month</div><div id="statMonth" class="value">0.00 hrs</div></div></div>
          </div>

          <div id="managePanel" class="row g-3">
            <div class="col-12 col-xl-8">
              <div class="glass p-4 p-lg-5 h-100">
                <div class="mb-4">
                  <h2 class="ot-section-title">Create OT Entry</h2>
                  <p class="ot-section-copy mt-1 mb-0">Choose employee, date, time range, and per-hour rate. Hours and amount are calculated instantly.</p>
                </div>
                <form id="otForm">
                  <div class="row g-3">
                    <label class="col-12 col-md-6">
                      <span class="form-label fw-semibold">Employee</span>
                      <select id="employee" class="form-select"></select>
                    </label>
                    <label class="col-12 col-md-6">
                      <span class="form-label fw-semibold">Date</span>
                      <input id="otDate" type="date" class="form-control">
                    </label>
                    <label class="col-12 col-md-6 col-xl-3">
                      <span class="form-label fw-semibold">Start Time</span>
                      <div class="ot-time-row">
                        <select id="startHour" class="form-select" aria-label="Start hour"></select>
                        <select id="startMinute" class="form-select" aria-label="Start minute"></select>
                        <select id="startPeriod" class="form-select" aria-label="Start AM or PM"><option>AM</option><option>PM</option></select>
                      </div>
                    </label>
                    <label class="col-12 col-md-6 col-xl-3">
                      <span class="form-label fw-semibold">End Time</span>
                      <div class="ot-time-row">
                        <select id="endHour" class="form-select" aria-label="End hour"></select>
                        <select id="endMinute" class="form-select" aria-label="End minute"></select>
                        <select id="endPeriod" class="form-select" aria-label="End AM or PM"><option>AM</option><option>PM</option></select>
                      </div>
                    </label>
                    <label class="col-12 col-md-6 col-xl-3">
                      <span class="form-label fw-semibold">Total Hours</span>
                      <input id="totalHours" type="number" class="form-control" readonly>
                    </label>
                    <label class="col-12 col-md-6 col-xl-3">
                      <span class="form-label fw-semibold">OT Rate / Hour</span>
                      <input id="rate" type="number" min="0" step="0.01" class="form-control" placeholder="150">
                    </label>
                    <label class="col-12 col-md-6">
                      <span class="form-label fw-semibold">Total OT Amount</span>
                      <input id="totalAmount" type="number" class="form-control" readonly>
                    </label>
                    <label class="col-12">
                      <span class="form-label fw-semibold">Notes</span>
                      <textarea id="notes" rows="3" class="form-control" placeholder="Optional note"></textarea>
                    </label>
                  </div>
                  <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Save OT Entry</button>
                  </div>
                </form>
              </div>
            </div>

            <div class="col-12 col-xl-4">
              <div class="glass p-4 p-lg-5 h-100">
                <div class="mb-4">
                  <h2 class="ot-section-title">Live Calculation</h2>
                  <p class="ot-section-copy mt-1 mb-0">End time earlier than start time is treated as overnight OT.</p>
                </div>
                <div class="d-grid gap-3">
                  <div class="ot-preview"><div class="label">Working Hours</div><div id="previewHours" class="value">0.00 hrs</div></div>
                  <div class="ot-preview"><div class="label">OT Amount</div><div id="previewAmount" class="value">Rs 0.00</div></div>
                </div>
              </div>
            </div>
          </div>

          <div class="glass p-4 p-lg-5">
            <div class="mb-4">
              <h2 class="ot-section-title">Overtime Register</h2>
              <p class="ot-section-copy mt-1 mb-0">Saved employee overtime records.</p>
            </div>
            <div class="overflow-x-auto">
              <table class="table align-middle mb-0" data-no-srno="true" data-no-datetime="true">
                <thead>
                  <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Hours</th>
                    <th>Rate</th>
                    <th>Amount</th>
                    <th>Notes</th>
                    <th>Created By</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody id="otTableBody"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

@push('modals')
</div>

  
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
@endpush
