<!-- Page body -->
      <div class="container-fluid py-4 px-3 px-lg-4">

        <!-- KPI row -->
        <div class="row g-3">
          <div class="col-12 col-md-6 col-xl-3">
            <div class="glass p-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="small text-muted-3">Total Employees</div>
                  <div class="fs-3 fw-semibold" id="kpiEmployees">-</div>
                </div>
                <div class="brand-mark u-brand-mark-44"><i class="bi bi-people fs-5"></i></div>
              </div>
              <div class="small text-muted-3 mt-2">Active in selected month</div>
            </div>
          </div>

          <div class="col-12 col-md-6 col-xl-3">
            <div class="glass p-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="small text-muted-3">Avg Paid Days</div>
                  <div class="fs-3 fw-semibold" id="kpiPaidDays">-</div>
                </div>
                <div class="brand-mark u-brand-mark-44"><i class="bi bi-calendar2-check fs-5"></i></div>
              </div>
              <div class="small text-muted-3 mt-2">Attendance summary</div>
            </div>
          </div>

          <div class="col-12 col-md-6 col-xl-3">
            <div class="glass p-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="small text-muted-3">Gross Payroll</div>
                  <div class="fs-3 fw-semibold" id="kpiGross">-</div>
                </div>
                <div class="brand-mark u-brand-mark-44"><i class="bi bi-cash-stack fs-5"></i></div>
              </div>
              <div class="small text-muted-3 mt-2">Earned gross total</div>
            </div>
          </div>

          <div class="col-12 col-md-6 col-xl-3">
            <div class="glass p-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="small text-muted-3">Total Deductions</div>
                  <div class="fs-3 fw-semibold" id="kpiDed">-</div>
                </div>
                <div class="brand-mark u-brand-mark-44"><i class="bi bi-receipt-cutoff fs-5"></i></div>
              </div>
              <div class="small text-muted-3 mt-2">PF/ESI + others</div>
            </div>
          </div>
        </div>

        <div class="row g-3 mt-1">
          <div class="col-12 col-xl-5">
            <div class="glass p-3">
              <div class="fw-semibold mb-1">Your Current Subscription</div>
              <div class="small text-muted-3 mb-3">Active plan mapped by Super Admin</div>
              <div id="currentPlanBox" class="glass-soft p-3">
                <div class="fw-semibold" id="curPlanName">-</div>
                <div class="small text-muted-3 mt-1" id="curPlanMeta">No subscription plan assigned.</div>
                <div class="small text-muted-3 mt-2" id="curPlanFeatures"></div>
              </div>
            </div>
          </div>
          <div class="col-12 col-xl-7">
            <div class="glass p-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div>
                  <div class="fw-semibold">Other Available Plans</div>
                  <div class="small text-muted-3">Contact Super Admin to switch your plan</div>
                </div>
              </div>
              <div class="table-responsive">
                  <table class="table align-middle mb-0" data-no-datetime="true">
                  <thead>
                    <tr>
                      <th>Plan</th>
                      <th>Duration</th>
                      <th class="text-end">Amount</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody id="plansTbody"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Sheet Workflow -->
        <div class="row g-3 mt-1">
          <div class="col-12">
            <div class="glass p-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div>
                  <div class="fw-semibold">Sheet Workflow</div>
                  <div class="small text-muted-3">Attendance -> Salary -> Payslip</div>
                </div>
                <span class="badge-soft" id="monthBadgeWorkflowDash">-</span>
              </div>

              <div class="row g-2">
                <div class="col-12 col-md-6 col-xl-4">
                  <a class="tile" href="scan-attendance.php">
                    <div class="glass-soft p-3 h-100">
                      <div class="d-flex align-items-center gap-3">
                        <div class="icon"><i class="bi bi-camera-video fs-5"></i></div>
                        <div>
                          <div class="fw-semibold">Scan Attendance</div>
                          <div class="small text-muted-3">Open camera and mark IN / OUT instantly</div>
                        </div>
                        <i class="bi bi-arrow-right-short ms-auto fs-4"></i>
                      </div>
                    </div>
                  </a>
                </div>

                <div class="col-12 col-md-6 col-xl-4">
                  <a class="tile" href="client-attendance.html">
                    <div class="glass-soft p-3 h-100">
                      <div class="d-flex align-items-center gap-3">
                        <div class="icon"><i class="bi bi-calendar2-check fs-5"></i></div>
                        <div>
                          <div class="fw-semibold">Attendance Sheet</div>
                          <div class="small text-muted-3">Generate monthly attendance first</div>
                        </div>
                        <i class="bi bi-arrow-right-short ms-auto fs-4"></i>
                      </div>
                    </div>
                  </a>
                </div>

                <div class="col-12 col-md-6 col-xl-4">
                  <a class="tile" href="client-payroll-calc.html">
                    <div class="glass-soft p-3 h-100">
                      <div class="d-flex align-items-center gap-3">
                        <div class="icon"><i class="bi bi-calculator fs-5"></i></div>
                        <div>
                          <div class="fw-semibold">Salary Sheet</div>
                          <div class="small text-muted-3">Prepare salary from attendance</div>
                        </div>
                        <i class="bi bi-arrow-right-short ms-auto fs-4"></i>
                      </div>
                    </div>
                  </a>
                </div>

                <div class="col-12 col-md-6 col-xl-4">
                  <a class="tile" href="client-payslips.html">
                    <div class="glass-soft p-3 h-100">
                      <div class="d-flex align-items-center gap-3">
                        <div class="icon"><i class="bi bi-receipt-cutoff fs-5"></i></div>
                        <div>
                          <div class="fw-semibold">Payslip</div>
                          <div class="small text-muted-3">Generate after salary sheet</div>
                        </div>
                        <i class="bi bi-arrow-right-short ms-auto fs-4"></i>
                      </div>
                    </div>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Returns & Sheets tiles -->
        <div class="row g-3 mt-1">
          <div class="col-12">
            <div class="glass p-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div>
                  <div class="fw-semibold">Returns & Sheets</div>
                  <div class="small text-muted-3">Open monthly compliance sheets/returns</div>
                </div>
                <span class="badge-soft" id="monthBadge2">-</span>
              </div>

              <div class="row g-2">
                <div class="col-12 col-md-6 col-xl-4">
                  <a class="tile" href="client-fnf.html">
                    <div class="glass-soft p-3 h-100">
                      <div class="d-flex align-items-center gap-3">
                        <div class="icon"><i class="bi bi-clipboard-check fs-5"></i></div>
                        <div>
                          <div class="fw-semibold">FNF</div>
                          <div class="small text-muted-3">Final settlement tracker</div>
                        </div>
                        <i class="bi bi-arrow-right-short ms-auto fs-4"></i>
                      </div>
                    </div>
                  </a>
                </div>

                <div class="col-12 col-md-6 col-xl-4">
                  <a class="tile" href="client-pf-sheet.html">
                    <div class="glass-soft p-3 h-100">
                      <div class="d-flex align-items-center gap-3">
                        <div class="icon"><i class="bi bi-file-earmark-spreadsheet fs-5"></i></div>
                        <div>
                          <div class="fw-semibold">PF Sheet</div>
                          <div class="small text-muted-3">PF wages & contribution</div>
                        </div>
                        <i class="bi bi-arrow-right-short ms-auto fs-4"></i>
                      </div>
                    </div>
                  </a>
                </div>

                <div class="col-12 col-md-6 col-xl-4">
                  <a class="tile" href="client-pf-return.html">
                    <div class="glass-soft p-3 h-100">
                      <div class="d-flex align-items-center gap-3">
                        <div class="icon"><i class="bi bi-cloud-upload fs-5"></i></div>
                        <div>
                          <div class="fw-semibold">PF Return</div>
                          <div class="small text-muted-3">Upload / status tracking</div>
                        </div>
                        <i class="bi bi-arrow-right-short ms-auto fs-4"></i>
                      </div>
                    </div>
                  </a>
                </div>

                <div class="col-12 col-md-6 col-xl-4">
                  <a class="tile" href="client-esic-sheet.html">
                    <div class="glass-soft p-3 h-100">
                      <div class="d-flex align-items-center gap-3">
                        <div class="icon"><i class="bi bi-file-earmark-spreadsheet fs-5"></i></div>
                        <div>
                          <div class="fw-semibold">ESIC Sheet</div>
                          <div class="small text-muted-3">ESI wages & contribution</div>
                        </div>
                        <i class="bi bi-arrow-right-short ms-auto fs-4"></i>
                      </div>
                    </div>
                  </a>
                </div>

                <div class="col-12 col-md-6 col-xl-4">
                  <a class="tile" href="client-esic-return.html">
                    <div class="glass-soft p-3 h-100">
                      <div class="d-flex align-items-center gap-3">
                        <div class="icon"><i class="bi bi-cloud-upload fs-5"></i></div>
                        <div>
                          <div class="fw-semibold">ESIC Return</div>
                          <div class="small text-muted-3">Monthly return filing</div>
                        </div>
                        <i class="bi bi-arrow-right-short ms-auto fs-4"></i>
                      </div>
                    </div>
                  </a>
                </div>

                <div class="col-12 col-md-6 col-xl-4">
                  <a class="tile" href="client-ecr-sheet.html">
                    <div class="glass-soft p-3 h-100">
                      <div class="d-flex align-items-center gap-3">
                        <div class="icon"><i class="bi bi-file-earmark-text fs-5"></i></div>
                        <div>
                          <div class="fw-semibold">ECR Sheet</div>
                          <div class="small text-muted-3">ECR export / autofill</div>
                        </div>
                        <i class="bi bi-arrow-right-short ms-auto fs-4"></i>
                      </div>
                    </div>
                  </a>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- Middle row -->
        <div class="row g-3 mt-1">
          <div class="col-12 col-xl-7">
            <div class="glass p-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div>
                  <div class="fw-semibold">Compliance Alerts</div>
                  <div class="small text-muted-3">Upcoming deadlines & tasks</div>
                </div>
                <a class="btn btn-outline-secondary btn-sm" href="client-compliance-calendar.html">
                  View Calendar <i class="bi bi-arrow-right-short"></i>
                </a>
              </div>

              <div class="table-responsive">
                <table class="table align-middle mb-0">
                  <thead>
                    <tr>
                      <th class="u-minw-160">Due Date</th>
                      <th>Task</th>
                      <th class="u-minw-140">Status</th>
                      <th class="text-end u-minw-140">Action</th>
                    </tr>
                  </thead>
                  <tbody id="alertsBody"></tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="col-12 col-xl-5">
            <div class="row g-3">
              <div class="col-12">
                <div class="glass p-3">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                      <div class="fw-semibold">This Month Summary</div>
                      <div class="small text-muted-3">PF/ESI applicability overview</div>
                    </div>
                    <span class="badge-soft" id="monthBadge">-</span>
                  </div>

                  <div class="row g-2">
                    <div class="col-6">
                      <div class="glass-soft p-3">
                        <div class="small text-muted-3">PF Employees</div>
                        <div class="fs-4 fw-semibold" id="pfCount">-</div>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="glass-soft p-3">
                        <div class="small text-muted-3">ESI Employees</div>
                        <div class="fs-4 fw-semibold" id="esiCount">-</div>
                      </div>
                    </div>
                    <div class="col-12">
                      <div class="glass-soft p-3">
                        <div class="d-flex justify-content-between">
                          <div>
                            <div class="small text-muted-3">Net Payable (Total)</div>
                            <div class="fs-4 fw-semibold" id="netTotal">-</div>
                          </div>
                          <div class="text-end">
                            <div class="small text-muted-3">Payslips</div>
                            <div class="fs-4 fw-semibold" id="payslipCount">-</div>
                          </div>
                        </div>
                        <div class="small text-muted-3 mt-2">
                          Download from <a href="client-payslips.html" class="text-decoration-underline">Payslips</a>
                        </div>
                      </div>
                    </div>
                  </div>

                </div>
              </div>

            </div>
          </div>
        </div>

        <div class="text-center small text-muted-3 mt-4">
          &copy; <span id="yr"></span> HR Compliance Portal
        </div>

      </div>

@push('modals')
</div>

  <!-- Mobile Sidebar Offcanvas -->
  
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
@endpush
