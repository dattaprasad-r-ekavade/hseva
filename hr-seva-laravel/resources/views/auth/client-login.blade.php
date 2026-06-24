<div class="auth-wrap">
    <div class="card auth-card">
      <div class="row g-0">

        <!-- LEFT (Brand / Info) -->
        <div class="col-lg-6 d-none d-lg-block">
          <div class="auth-left h-100 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex align-items-center gap-3 mb-4">
                <div class="brand-mark">
                  <i class="bi bi-shield-check fs-4"></i>
                </div>
                <div>
                  <div class="fw-semibold fs-5">HR Seva</div>
                  <div class="small-muted">Client Access</div>
                </div>
              </div>

              <div class="mb-3">
                <span class="badge-soft">
                  <i class="bi bi-lock"></i> Secure client login
                </span>
              </div>

              <h1 class="fw-semibold lh-sm mb-3 u-fs-2rem">
                HR, Payroll &amp; Compliance - Simplified
              </h1>

              <p class="small-muted mb-4">
                Manage employees, attendance, payroll, and compliance - all in one dashboard.
              </p>

              <div class="d-grid gap-2">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-check2-circle"></i><span class="small-muted">Employee &amp; Attendance Management</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-check2-circle"></i><span class="small-muted">Payroll &amp; Salary Sheets</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-check2-circle"></i><span class="small-muted">Payslip Downloads</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-check2-circle"></i><span class="small-muted">PF / ESIC Tracking</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-check2-circle"></i><span class="small-muted">Compliance Challans</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-check2-circle"></i><span class="small-muted">Downloadable Reports</span>
                </div>
              </div>

              <p class="small-muted mt-4 mb-0">Built for growing businesses everything you need.</p>
            </div>

            <div class="pt-4">
              <div class="small-muted">Need assistance? Our HR experts are just a call away.</div>
              <div class="small-muted">HR Seva Support Team | <span id="yr"></span></div>
            </div>
          </div>
        </div>

        <!-- RIGHT (Login Form) -->
        <div class="col-lg-6">
          <div class="auth-right">
            <div class="d-flex align-items-center justify-content-between mb-4">
              <div class="d-lg-none">
                <div class="fw-semibold">HR Seva</div>
                <div class="text-secondary small">Client Access</div>
              </div>
              <div class="d-flex align-items-center gap-2 ms-auto">
                <span class="text-secondary small">
                  <i class="bi bi-shield-lock"></i> Secure
                </span>
                <a href="/" class="auth-home-btn" aria-label="Back to landing page" title="Back to landing page">
                  <i class="bi bi-house-door"></i>
                </a>
              </div>
            </div>

            <h2 class="fw-semibold mb-1">Client Login</h2>
            <p class="text-secondary mb-4">Login using your assigned User Name / Email and Password.</p>

            <!-- Replace action with PHP endpoint later -->
            <form id="loginForm" novalidate>

              <div id="loginMsg" class="alert d-none" role="alert"></div>

              <div class="mb-3">
                <label class="form-label">User Name / Email</label>
                <input type="text" class="form-control" id="userId" placeholder="Enter user name or email" required>
                <div class="invalid-feedback">Please enter User Name / Email.</div>
              </div>

              <div class="mb-2">
                <label class="form-label">Password</label>
                <div class="input-group">
                  <input type="password" class="form-control" id="userPassword" placeholder="Enter password" required>
                  <button class="btn btn-outline-secondary" type="button" id="togglePass" aria-label="Show password">
                    <i class="bi bi-eye"></i>
                  </button>
                  <div class="invalid-feedback">Please enter Password.</div>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center my-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="remember">
                  <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <a href="#" class="small d-none" data-bs-toggle="modal" data-bs-target="#forgotModal">Forgot password?</a>
              </div>

              <button class="btn btn-primary w-100 py-2 fw-semibold" type="submit">
                Login <i class="bi bi-arrow-right-short"></i>
              </button>

              <div class="text-center text-secondary small mt-3">
                By logging in, you agree to portal usage terms.
              </div>

              <hr class="my-4">

              <div class="d-grid gap-2 d-none">
                <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#supportModal">
                  <i class="bi bi-headset"></i> Contact Support
                </button>
              </div>

            </form>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Forgot Password Modal -->
  <div class="modal fade" id="forgotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content u-modal-radius-16">
        <div class="modal-header">
          <h5 class="modal-title">Reset password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-secondary small mb-3">
            Enter your registered email. We will send reset instructions.
          </p>
          <label class="form-label">Registered Email</label>
          <input type="email" class="form-control" placeholder="Enter registered email">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Send Link</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Support Modal -->
  <div class="modal fade" id="supportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content u-modal-radius-16">
        <div class="modal-header">
          <h5 class="modal-title">Support</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info mb-3 u-radius-14" role="alert">
            Contact your HR agency for access issues / password reset.
          </div>
          <div class="d-grid gap-2">
            <a class="btn btn-outline-primary" href="tel:+910000000000">
              <i class="bi bi-telephone"></i> Call Support
            </a>
            <a class="btn btn-outline-success" href="https://wa.me/910000000000" target="_blank" rel="noopener">
              <i class="bi bi-whatsapp"></i> WhatsApp Support
            </a>
            <a class="btn btn-outline-dark" href="mailto:support@yourcompany.com">
              <i class="bi bi-envelope"></i> Email Support
            </a>
          </div>
          <p class="text-secondary small mt-3 mb-0">
            (Replace phone/email with your actual support details.)
          </p>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/app-common.js"></script>