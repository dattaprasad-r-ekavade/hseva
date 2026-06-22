<div class="container-fluid py-4 px-3 px-lg-4">

        <!-- Header card -->
        <div class="row g-3">
          <div class="col-12">
            <div class="glass p-3">
              <div class="d-flex flex-column flex-lg-row gap-2 align-items-lg-center justify-content-between">
                <div>
                  <div class="section-title">Company Controls</div>
                  <div class="small-hint">These values will be used in Payroll, PF/ESI sheets, ECR, ESIC return, FNF and Gratuity calculations.</div>
                  <div class="mt-2 d-flex flex-wrap gap-2">
                    <span class="pill"><i class="bi bi-shield-check"></i> Client-scoped</span>
                    <span class="pill"><i class="bi bi-calculator"></i> Used in auto-calculation</span>
                    <span class="pill"><i class="bi bi-filetype-xlsx"></i> Excel aligned</span>
                  </div>
                </div>
                <div class="d-flex gap-2">
                  <button class="btn btn-outline-secondary" id="btnResetDemo" type="button">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset Demo
                  </button>
                  <button class="btn btn-outline-secondary" id="btnExportJson" type="button">
                    <i class="bi bi-braces"></i> Export JSON
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 3 blocks like your Excel -->
        <div class="row g-3 mt-0">

          <!-- Left: PF/ESI/PT/LWF -->
          <div class="col-12 col-xl-6">
            <div class="glass p-3 h-100">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-semibold">Statutory Settings</div>
                <span class="pill"><i class="bi bi-gear"></i> PF / ESI / PT / LWF</span>
              </div>

              <div class="table-responsive">
                <table class="table align-middle mb-0">
                  <tbody>
                    <tr>
                      <td class="fw-semibold">PF Employee %</td>
                      <td class="u-w-180">
                        <div class="input-group">
                          <input class="form-control text-end" id="pfEmpPct" type="number" step="0.01">
                          <span class="input-group-text">%</span>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td class="fw-semibold">ESI Employee %</td>
                      <td>
                        <div class="input-group">
                          <input class="form-control text-end" id="esiEmpPct" type="number" step="0.01">
                          <span class="input-group-text">%</span>
                        </div>
                      </td>
                    </tr>

                    <tr>
                      <td class="fw-semibold">ESI Wage Limit (Monthly)</td>
                      <td>
                        <div class="input-group">
                          <span class="input-group-text">Rs </span>
                          <input class="form-control text-end" id="esiWageLimit" type="number" step="1">
                        </div>
                      </td>
                    </tr>

                    <tr id="ptEnabledRow">
                      <td class="fw-semibold">PT (Monthly) Enabled (Yes/No)</td>
                      <td>
                        <select class="form-select" id="ptEnabled">
                          <option>Yes</option>
                          <option>No</option>
                        </select>
                      </td>
                    </tr>
                    <tr id="ptMonthlyRow">
                      <td class="fw-semibold">PT (Monthly)</td>
                      <td>
                        <div class="input-group">
                          <span class="input-group-text">Rs </span>
                          <input class="form-control text-end" id="ptMonthly" type="number" step="1">
                        </div>
                      </td>
                    </tr>

                    <tr>
                      <td class="fw-semibold">PF Wage Cap Enabled (Yes/No)</td>
                      <td>
                        <select class="form-select" id="pfWageCapEnabled">
                          <option>Yes</option>
                          <option>No</option>
                        </select>
                      </td>
                    </tr>

                    <tr>
                      <td class="fw-semibold">PF Wage Cap Amount</td>
                      <td>
                        <div class="input-group">
                          <span class="input-group-text">Rs </span>
                          <input class="form-control text-end" id="pfWageCapAmount" type="number" step="1">
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td class="fw-semibold">PF Wage % When ESI Applicable</td>
                      <td>
                        <div class="input-group">
                          <input class="form-control text-end" id="pfOnEsiPct" type="number" step="0.01">
                          <span class="input-group-text">%</span>
                        </div>
                      </td>
                    </tr>

                    <tr id="lwfEnabledRow">
                      <td class="fw-semibold">LWF Enabled (Yes/No)</td>
                      <td>
                        <select class="form-select" id="lwfEnabled">
                          <option>Yes</option>
                          <option>No</option>
                        </select>
                      </td>
                    </tr>

                    <tr id="lwfEmpRow">
                      <td class="fw-semibold">LWF Employee (Amount)</td>
                      <td>
                        <div class="input-group">
                          <span class="input-group-text">Rs </span>
                          <input class="form-control text-end" id="lwfEmpAmt" type="number" step="1">
                        </div>
                      </td>
                    </tr>

                    <tr id="lwfErRow">
                      <td class="fw-semibold">LWF Employer (Amount)</td>
                      <td>
                        <div class="input-group">
                          <span class="input-group-text">Rs </span>
                          <input class="form-control text-end" id="lwfErAmt" type="number" step="1">
                        </div>
                      </td>
                    </tr>

                    <tr id="lwfMonthRow">
                      <td class="fw-semibold">LWF Applicable Month (1-12; 0=monthly)</td>
                      <td>
                        <input class="form-control text-end" id="lwfMonth" type="number" min="0" max="12" step="1">
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="small-hint mt-2">
                Tip: If PF Wage Cap Enabled = Yes, PF wages will be capped at the amount (example: 15000).
              </div>
            </div>
          </div>

          <!-- Right-Center: CTC Add-on -->
          <div class="col-12 col-xl-6">
            <div class="glass p-3 h-100">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-semibold">CTC Add-on</div>
                <span class="pill"><i class="bi bi-plus-circle"></i> Employer Cost</span>
              </div>

              <div class="table-responsive">
                <table class="table align-middle mb-0" data-no-datetime="true">
                  <thead>
                    <tr>
                      <th>Type</th>
                      <th>Add-on</th>
                      <th class="text-end u-w-130">Value</th>
                      <th class="text-end u-w-120">Action</th>
                    </tr>
                  </thead>
                  <tbody id="deductionRowsBody"></tbody>
                </table>
              </div>

              <div class="row g-2 mt-2">
                <div class="col-4">
                  <select class="form-select" id="deductionType">
                    <option value="percent">%</option>
                    <option value="amount">Rs</option>
                  </select>
                </div>
                <div class="col-4">
                  <input class="form-control" id="deductionName" type="text" placeholder="Add-on name">
                </div>
                <div class="col-4">
                  <div class="input-group">
                    <input class="form-control text-end" id="deductionValue" type="number" step="0.01" min="0" placeholder="0.00">
                    <span class="input-group-text" id="deductionValueSuffix">%</span>
                  </div>
                </div>
              </div>
              <div class="d-flex gap-2 mt-2">
                <button class="btn btn-sm btn-primary" type="button" id="btnDeductionAdd">Add</button>
                <button class="btn btn-sm btn-outline-secondary d-none" type="button" id="btnDeductionCancelEdit">Cancel Edit</button>
              </div>

              <div class="glass-soft p-3 mt-3">
                <div class="d-flex justify-content-between">
                  <div class="small-hint">Configured add-ons</div>
                  <div class="fw-semibold" id="deductionTotalAmt">0 % rows | Rs 0.00 fixed</div>
                </div>
                <div class="small-hint mt-1">Use this for employer-side CTC items like PF Employer %, ESI Employer %, insurance, bonus provision, or other company-paid costs.</div>
              </div>
            </div>
          </div>

          <!-- Right: Company Details -->
          <div class="col-12 col-xl-6">
            <div class="glass p-3 h-100">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-semibold">Company Details</div>
                <span class="pill"><i class="bi bi-buildings"></i> Master</span>
              </div>

              <div class="table-responsive">
                <table class="table align-middle mb-0">
                  <tbody>
                    <tr>
                      <td class="fw-semibold">Company Name</td>
                      <td class="u-w-55p">
                        <input class="form-control" id="companyName" />
                      </td>
                    </tr>
                    <tr>
                      <td class="fw-semibold">Company Address</td>
                      <td>
                        <textarea class="form-control" id="companyAddress" rows="3"></textarea>
                      </td>
                    </tr>
                    <tr>
                      <td class="fw-semibold">Company CIN / LLPIN / Reg. No</td>
                      <td><input class="form-control" id="companyRegNo" /></td>
                    </tr>
                    <tr>
                      <td class="fw-semibold">Company PAN ID</td>
                      <td><input class="form-control" id="companyPAN" /></td>
                    </tr>
                    <tr>
                      <td class="fw-semibold">Company TAN ID</td>
                      <td><input class="form-control" id="companyTAN" /></td>
                    </tr>
                    <tr>
                      <td class="fw-semibold">Company GSTIN</td>
                      <td><input class="form-control" id="companyGSTIN" /></td>
                    </tr>
                    <tr>
                      <td class="fw-semibold">Company Contact No</td>
                      <td><input class="form-control" id="companyContact" /></td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="small-hint mt-2">
                These details will appear in Payslip, PF/ESI sheets and reports.
              </div>
            </div>
          </div>

          <!-- Middle: Gross Distribution -->
          <div class="col-12 col-xl-6">
            <div class="glass p-3 h-100">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-semibold">Gross Distribution (All %)</div>
                <span class="pill"><i class="bi bi-pie-chart"></i> Gross Split</span>
              </div>

              <div class="table-responsive">
                <table class="table align-middle mb-0" data-no-datetime="true">
                  <thead>
                    <tr>
                      <th>Component</th>
                      <th class="text-end u-w-130">%</th>
                      <th class="text-end u-w-120">Action</th>
                    </tr>
                  </thead>
                  <tbody id="ctcRowsBody"></tbody>
                </table>
              </div>

              <div class="row g-2 mt-2">
                <div class="col-7">
                  <input class="form-control" id="ctcCompName" type="text" placeholder="Component name (e.g. Basic)">
                </div>
                <div class="col-5">
                  <div class="input-group">
                    <input class="form-control text-end" id="ctcCompPct" type="number" step="0.01" min="0" placeholder="0.00">
                    <span class="input-group-text">%</span>
                  </div>
                </div>
              </div>
              <div class="d-flex gap-2 mt-2">
                <button class="btn btn-sm btn-primary" type="button" id="btnCtcAdd">Add</button>
                <button class="btn btn-sm btn-outline-secondary d-none" type="button" id="btnCtcCancelEdit">Cancel Edit</button>
              </div>

              <div class="glass-soft p-3 mt-3">
                <div class="d-flex justify-content-between">
                  <div class="small-hint">Total (all gross components)</div>
                  <div class="fw-semibold" id="ctcTotalPct">100%</div>
                </div>
                <div class="small-hint mt-1">Use Add/Edit/Delete to manage components. Total should be 100%.</div>
              </div>

            </div>
          </div>

        </div>

        <div class="row g-3 mt-0">
          <div class="col-12">
            <div class="glass p-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-semibold">Bonus Module Control</div>
                <span class="pill"><i class="bi bi-cash-stack"></i> Editable formula defaults</span>
              </div>
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label fw-semibold">Bonus Module Enabled</label>
                  <select class="form-select" id="bonusEnabled">
                    <option>Yes</option>
                    <option>No</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-semibold">Minimum Wages</label>
                  <input class="form-control text-end" id="bonusMinimumWage" type="number" min="0" step="0.01">
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-semibold">Months</label>
                  <input class="form-control text-end" id="bonusMultiplierMonths" type="number" min="0" step="0.01">
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-semibold">Bonus %</label>
                  <input class="form-control text-end" id="bonusPercent" type="number" min="0" max="100" step="0.01">
                </div>
                <div class="col-12">
                  <div class="small text-muted-3">Formula: Minimum wages x Months x Bonus % = Bonus. These values become the default sheet values and can still be edited on the Bonus page.</div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="glass p-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-semibold">Gratuity Module Control</div>
                <span class="pill"><i class="bi bi-award"></i> One mode active at a time</span>
              </div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-check gratuity-mode-card">
                    <input class="form-check-input" type="radio" name="gratuityMode" id="gratuityModeAfter5" checked>
                    <span>
                      <span class="fw-semibold d-block">After completion of 5yr</span>
                      <span class="small text-muted-3">Final gratuity = ((Basic + DA) x 15 x Years) / 26</span>
                    </span>
                  </label>
                </div>
                <div class="col-md-6">
                  <label class="form-check gratuity-mode-card">
                    <input class="form-check-input" type="radio" name="gratuityMode" id="gratuityModeMonthly">
                    <span>
                      <span class="fw-semibold d-block">Monthly</span>
                      <span class="small text-muted-3">Monthly estimate = Basic x 4.81% for CTC calculation</span>
                    </span>
                  </label>
                </div>
                <div class="col-md-4" id="gratuityMinYearsWrap">
                  <label class="form-label fw-semibold">Minimum Service Years</label>
                  <input class="form-control text-end" id="gratuityMinYears" type="number" min="0" step="0.01">
                </div>
                <div class="col-12">
                  <div class="small text-muted-3">This value is used in gratuity eligibility validation. Example: if set to 5, generation requires service years to be more than 5 in `After completion` mode.</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sticky actions (mobile friendly) -->
        <div class="sticky-actions mt-3">
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div class="small text-muted-3">
              Storage: <span id="storageMode" class="fw-semibold">Loading...</span>
              <span class="mx-2">|</span>
              Last saved: <span id="lastSaved" class="fw-semibold">-</span>
            </div>
            <div class="d-flex justify-content-end gap-2">
              <button class="btn btn-outline-secondary" id="btnValidate" type="button">
                <i class="bi bi-shield-exclamation"></i> Validate
              </button>
              <button class="btn btn-primary" id="btnSave" type="button">
                <i class="bi bi-check2-circle"></i> Save Changes
              </button>
            </div>
          </div>
        </div>

        <div class="text-center small text-muted-3 mt-4">
          &copy; <span id="yr"></span> HR Compliance Portal
        </div>
      </div>

@push('modals')
</div>

  <!-- Mobile Sidebar -->
  
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
@endpush
