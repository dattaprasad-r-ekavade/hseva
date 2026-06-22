<div class="container-fluid py-4 px-3 px-lg-4">
        <div id="smtpMsg" class="alert d-none"></div>

        <div class="row g-3 mb-3">
          <div class="col-12 col-md-6 col-xl-3">
            <div class="glass p-3">
              <div class="small text-muted-3">SMTP status</div>
              <div class="fs-4 fw-semibold" id="smtpStatusText">Disabled</div>
              <div class="small text-muted-3 mt-2">Turns all project email flows on or off.</div>
            </div>
          </div>
          <div class="col-12 col-md-6 col-xl-3">
            <div class="glass p-3">
              <div class="small text-muted-3">Mail host</div>
              <div class="fs-5 fw-semibold" id="smtpHostText">-</div>
              <div class="small text-muted-3 mt-2">Current SMTP endpoint in use.</div>
            </div>
          </div>
          <div class="col-12 col-md-6 col-xl-3">
            <div class="glass p-3">
              <div class="small text-muted-3">From email</div>
              <div class="fs-5 fw-semibold" id="smtpFromText">-</div>
              <div class="small text-muted-3 mt-2">Sender identity used by the app.</div>
            </div>
          </div>
          <div class="col-12 col-md-6 col-xl-3">
            <div class="glass p-3">
              <div class="small text-muted-3">Last saved</div>
              <div class="fs-5 fw-semibold" id="smtpSavedText">-</div>
              <div class="small text-muted-3 mt-2">Latest stored SMTP settings update.</div>
            </div>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-12 col-xl-7">
            <div class="glass p-3 p-lg-4 h-100">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                  <div class="fw-semibold">SMTP Settings</div>
                  <div class="small text-muted-3">Use your Hostinger mailbox details here. Leave password empty to keep the saved password unchanged.</div>
                  <div class="small text-muted-3">Hostinger usually requires <strong>From Email</strong> to match <strong>SMTP Username</strong>. If they are different, the system will save the username as the sender email automatically.</div>
                </div>
                <span class="badge-soft"><i class="bi bi-envelope-paper"></i> Email Core</span>
              </div>

              <form id="smtpForm" class="row g-3" novalidate>
                <div class="col-12">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="smtpEnabled">
                    <label class="form-check-label" for="smtpEnabled">Enable project email sending</label>
                  </div>
                </div>
                <div class="col-md-8">
                  <label class="form-label">SMTP Host</label>
                  <input id="smtpHost" class="form-control" placeholder="smtp.hostinger.com">
                </div>
                <div class="col-md-4">
                  <label class="form-label">SMTP Port</label>
                  <input id="smtpPort" class="form-control" type="number" min="1" placeholder="465">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Encryption</label>
                  <select id="smtpEncryption" class="form-select">
                    <option value="ssl">SSL</option>
                    <option value="tls">TLS</option>
                    <option value="">None</option>
                  </select>
                </div>
                <div class="col-md-8">
                  <label class="form-label">SMTP Username</label>
                  <input id="smtpUsername" class="form-control" placeholder="support@yourdomain.com">
                </div>
                <div class="col-md-12">
                  <label class="form-label">SMTP Password</label>
                  <input id="smtpPassword" class="form-control" type="password" placeholder="Enter new password only when you want to change it">
                  <div class="small text-muted-3 mt-1" id="smtpPasswordHint">Saved password status unknown.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">From Email</label>
                  <input id="smtpFromEmail" class="form-control" type="email" placeholder="support@yourdomain.com">
                </div>
                <div class="col-md-6">
                  <label class="form-label">From Name</label>
                  <input id="smtpFromName" class="form-control" placeholder="HR Seva">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Reply-To Email</label>
                  <input id="smtpReplyTo" class="form-control" type="email" placeholder="optional">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Admin Notification Emails</label>
                  <input id="smtpAdminEmails" class="form-control" placeholder="admin1@domain.com, admin2@domain.com">
                </div>
                <div class="col-12 d-flex flex-wrap gap-2 justify-content-end">
                  <button class="btn btn-primary" type="submit" id="smtpSaveBtn"><i class="bi bi-check2-circle me-1"></i>Save Settings</button>
                </div>
              </form>
            </div>
          </div>

          <div class="col-12 col-xl-5">
            <div class="glass p-3 p-lg-4 h-100">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                  <div class="fw-semibold">Send Test Email</div>
                  <div class="small text-muted-3">Use this after saving your SMTP details to confirm Hostinger delivery is working.</div>
                </div>
                <span class="badge-soft"><i class="bi bi-send-check"></i> Verify</span>
              </div>
              <form id="smtpTestForm" class="vstack gap-3" novalidate>
                <div>
                  <label class="form-label">Test Recipient Email</label>
                  <input id="smtpTestEmail" class="form-control" type="email" placeholder="you@example.com" required>
                </div>
                <div class="small text-muted-3">The test email uses the current saved SMTP settings and writes the result into email logs.</div>
                <div class="d-flex gap-2 justify-content-end">
                  <button class="btn btn-outline-secondary" type="button" id="smtpReloadBtn"><i class="bi bi-arrow-repeat me-1"></i>Reload</button>
                  <button class="btn btn-primary" type="submit" id="smtpTestBtn"><i class="bi bi-envelope-check me-1"></i>Send Test Email</button>
                </div>
              </form>
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
