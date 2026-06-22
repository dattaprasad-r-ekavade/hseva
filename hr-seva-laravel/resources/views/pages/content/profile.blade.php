<div class="container-fluid py-4 px-3 px-lg-4">

        <!-- Profile header -->
        <div class="row g-3">
          <div class="col-12">
            <div class="glass p-3">
              <div class="d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                  <div class="avatar" id="avatarTxt">MI</div>
                  <div>
                    <div class="fw-semibold fs-5" id="headerCompany">-</div>
                    <div class="small text-muted-3" id="headerLocation">-</div>
                  </div>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                  <span class="pill"><i class="bi bi-shield-check"></i> Client</span>
                  <span class="pill"><i class="bi bi-file-earmark-text"></i> Payslip/Returns</span>
                  <span class="pill"><i class="bi bi-geo-alt"></i> Address saved</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Main form -->
        <div class="row g-3 mt-0">

          <!-- Company details -->
          <div class="col-12 col-xl-7">
            <div class="glass p-3 h-100">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-semibold">Company Details</div>
                <span class="pill"><i class="bi bi-buildings"></i> Master</span>
              </div>

              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label">Company Name *</label>
                  <input class="form-control" id="companyName" required />
                  <div class="small text-muted-3 mt-1">Used in payslips, returns, registers.</div>
                </div>

                <div class="col-12">
                  <label class="form-label">Company Address *</label>
                  <textarea class="form-control" id="companyAddress" rows="4" required></textarea>
                </div>

                <div class="col-12 col-md-6">
                  <label class="form-label">City</label>
                  <input class="form-control" id="city" placeholder="Bicholim" />
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">State</label>
                  <input class="form-control" id="state" placeholder="Goa" />
                </div>

                <div class="col-12 col-md-6">
                  <label class="form-label">Pincode</label>
                  <input class="form-control" id="pincode" placeholder="403504" />
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Country</label>
                  <input class="form-control" id="country" value="India" />
                </div>

                <div class="col-12">
                  <div class="glass-soft p-3">
                    <div class="fw-semibold mb-2">Branding (Optional)</div>
                    <div class="row g-3">
                      <div class="col-12 col-md-6">
                        <label class="form-label">Company Logo</label>
                        <input class="form-control" type="file" accept="image/*" />
                        <div class="small text-muted-3 mt-1">(Backend: store logo + show on payslip PDF)</div>
                      </div>
                      <div class="col-12 col-md-6">
                        <label class="form-label">Website</label>
                        <input class="form-control" id="website" placeholder="https://example.com" />
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <!-- Statutory + contacts -->
          <div class="col-12 col-xl-5">
            <div class="row g-3">

              <div class="col-12">
                <div class="glass p-3">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="fw-semibold">Statutory IDs</div>
                    <span class="pill"><i class="bi bi-file-earmark-lock"></i> PF/ESI/Tax</span>
                  </div>

                  <div class="row g-3">
                    <div class="col-12">
                      <label class="form-label">CIN / LLPIN / Reg. No</label>
                      <input class="form-control" id="regNo" />
                    </div>

                    <div class="col-12 col-md-6">
                      <label class="form-label">PAN</label>
                      <input class="form-control" id="pan" />
                    </div>
                    <div class="col-12 col-md-6">
                      <label class="form-label">TAN</label>
                      <input class="form-control" id="tan" />
                    </div>

                    <div class="col-12">
                      <label class="form-label">GSTIN</label>
                      <input class="form-control" id="gstin" />
                    </div>

                    <div class="col-12">
                      <label class="form-label">PF Establishment ID (Optional)</label>
                      <input class="form-control" id="pfEstId" placeholder="PF Establishment ID" />
                    </div>

                    <div class="col-12">
                      <label class="form-label">ESIC Employer Code (Optional)</label>
                      <input class="form-control" id="esicCode" placeholder="ESIC Employer Code" />
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <div class="glass p-3">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="fw-semibold">Contact Details</div>
                    <span class="pill"><i class="bi bi-telephone"></i> Support</span>
                  </div>

                  <div class="row g-3">
                    <div class="col-12 col-md-6">
                      <label class="form-label">Primary Contact Name</label>
                      <input class="form-control" id="contactName" placeholder="HR / Owner name" />
                    </div>
                    <div class="col-12 col-md-6">
                      <label class="form-label">Primary Contact No</label>
                      <input class="form-control" id="contactNo" placeholder="Enter contact number" />
                    </div>

                    <div class="col-12 col-md-6">
                      <label class="form-label">Email</label>
                      <input class="form-control" id="email" type="email" placeholder="hr@company.com" />
                    </div>
                    <div class="col-12 col-md-6">
                      <label class="form-label">Alt Contact No</label>
                      <input class="form-control" id="altContactNo" placeholder="Optional" />
                    </div>

                    <div class="col-12">
                      <label class="form-label">Notes</label>
                      <textarea class="form-control" id="notes" rows="3" placeholder="Any notes for agency..."></textarea>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>

        </div>

        <!-- Sticky actions -->
        <div class="sticky-actions mt-3">
          <div class="d-flex justify-content-end gap-2">
            <button class="btn btn-outline-secondary" id="btnValidate" type="button">
              <i class="bi bi-shield-exclamation"></i> Validate
            </button>
            <button class="btn btn-primary" id="btnSave" type="button">
              <i class="bi bi-check2-circle"></i> Save Changes
            </button>
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
