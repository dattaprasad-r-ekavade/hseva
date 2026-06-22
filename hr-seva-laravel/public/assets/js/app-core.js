/* HR Seva core utilities and shared constants */
(function () {
  "use strict";

  var KEY_AUTH = "hr_auth_session_v1";
  var KEY_SUPERADMIN_CLIENT_ID = "hr_superadmin_selected_client_id_v1";
  var KEY_SUPERADMIN_CLIENT_LABEL = "hr_superadmin_selected_client_label_v1";
  var KEY_SALARY_DATA_VERSION = "hr_salary_data_version_v1";
  var TENANT_CACHE_KEYS = [
    "hr_client_control_v1",
    "hr_client_profile_v1",
    "hr_client_employees_v1",
    "hr_emp_extra_v1",
    "hr_client_leaves_v1",
    "hr_client_attendance_daily_v1",
    "hr_client_attendance_notes_v1",
    "hr_client_attendance_half_days_v1",
    "hr_client_attendance_sheets_v1",
    "hr_client_payroll_overrides_v2",
    "hr_client_pf_sheet_history_v1",
    "hr_client_pf_returns_v1",
    "hr_client_esic_sheets_v1",
    "hr_client_esic_returns_v1",
    "hr_client_ecr_sheets_v1",
    "hr_client_payslips_v1",
    "hr_salary_sheet_files_v2",
    "hr_salary_sheet_files_v1",
    "fnf_data",
    "gratuity_data",
    "bonus_data",
    "incentive_data",
    "loan_data"
  ];
  var TENANT_CACHE_PREFIXES = [
    "hr_client_",
    "hr_emp_",
    "hr_salary_sheet_files_",
    "hr_control_updated_at",
    "hr_client_dash_cache_v1"
  ];
  var SALARY_CACHE_KEYS = [
    "hr_salary_sheet_files_v2",
    "hr_salary_sheet_files_v1",
    "hr_client_payslips_v1",
    "hr_client_pf_sheets_v1",
    "hr_client_esic_sheets_v1",
    "hr_client_ecr_sheets_v1"
  ];

  if (!window.HRCommon) {
    window.HRCommon = {};
  }

  window.HRCommon.safeParse = function (value, fallback) {
    try {
      return JSON.parse(value);
    } catch (_e) {
      return fallback === undefined ? null : fallback;
    }
  };

  window.HRCommon.num = function (value, fallback) {
    var n = Number(value);
    if (Number.isNaN(n)) return fallback === undefined ? 0 : fallback;
    return n;
  };

  window.HRCommon.money = function (value) {
    return Number(value || 0).toLocaleString("en-IN", { maximumFractionDigits:0 });
  };
  window.HRCommon.nativeAlert = window.alert.bind(window);
  window.HRCommon.ensureAlertModal = function () {
    if (document.getElementById("hrAlertModal")) return document.getElementById("hrAlertModal");
    var wrapper = document.createElement("div");
    wrapper.innerHTML = ''
      + '<div class="modal fade hr-alert-modal" id="hrAlertModal" tabindex="-1" aria-hidden="true">'
      + '  <div class="modal-dialog modal-dialog-centered">'
      + '    <div class="modal-content">'
      + '      <div class="modal-header">'
      + '        <h5 class="modal-title">HR Seva</h5>'
      + '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>'
      + '      </div>'
      + '      <div class="modal-body" id="hrAlertMessage"></div>'
      + '      <div class="modal-footer justify-content-end">'
      + '        <button type="button" class="btn btn-hr-alert" data-bs-dismiss="modal">OK</button>'
      + '      </div>'
      + '    </div>'
      + '  </div>'
      + '</div>';
    document.body.appendChild(wrapper.firstElementChild);
    return document.getElementById("hrAlertModal");
  };
  window.HRCommon.showAlert = function (message, title) {
    var text = String(message == null ? "" : message);
    var modalEl = window.HRCommon.ensureAlertModal();
    var titleEl = modalEl ? modalEl.querySelector(".modal-title") : null;
    var bodyEl = document.getElementById("hrAlertMessage");
    if (titleEl) titleEl.textContent = String(title || "HR Seva");
    if (bodyEl) bodyEl.textContent = text;
    if (window.bootstrap && typeof window.bootstrap.Modal === "function") {
      window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
      return;
    }
    window.HRCommon.nativeAlert(text);
  };
  window.HRCommon.setProcessingState = function (buttons, options) {
    var btns = Array.isArray(buttons) ? buttons : [buttons];
    var opts = options && typeof options === "object" ? options : {};
    var message = String(opts.message || "Please wait, we are processing your request.");
    var busyText = String(opts.busyText || "Processing...");
    var target = opts.statusEl || null;
    if (!target) {
      for (var i = 0; i < btns.length; i++) {
        var btn = btns[i];
        if (!btn || !btn.parentNode) continue;
        var holder = btn.parentNode.querySelector(".hr-processing-status");
        if (!holder) {
          holder = document.createElement("div");
          holder.className = "hr-processing-status small mt-2";
          holder.style.color = "var(--bs-secondary-color, #6c757d)";
          holder.style.minHeight = "1.25rem";
          btn.parentNode.appendChild(holder);
        }
        target = holder;
        break;
      }
    }
    var snapshots = btns.map(function (btn) {
      return {
        btn: btn,
        html: btn ? btn.innerHTML : "",
        disabled: !!(btn && btn.disabled)
      };
    });
    btns.forEach(function (btn) {
      if (!btn) return;
      btn.disabled = true;
      btn.setAttribute("aria-busy", "true");
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + busyText;
    });
    if (target) target.textContent = message;
    return function (doneMessage, isError) {
      snapshots.forEach(function (snap) {
        if (!snap.btn) return;
        snap.btn.disabled = snap.disabled;
        snap.btn.removeAttribute("aria-busy");
        snap.btn.innerHTML = snap.html;
      });
      if (target) {
        target.textContent = String(doneMessage || "");
        target.style.color = isError ? "var(--bs-danger, #dc3545)" : "var(--bs-secondary-color, #6c757d)";
      }
    };
  };
  window.HRCommon.clearSalaryDependentCaches = function () {
    for (var i = 0; i < SALARY_CACHE_KEYS.length; i++) {
      try { localStorage.removeItem(SALARY_CACHE_KEYS[i]); } catch (_e) {}
    }
  };
  window.HRCommon.clearTenantScopedCaches = function () {
    for (var i = 0; i < TENANT_CACHE_KEYS.length; i++) {
      try { localStorage.removeItem(TENANT_CACHE_KEYS[i]); } catch (_e) {}
    }
    try {
      for (var j = localStorage.length - 1; j >= 0; j--) {
        var key = String(localStorage.key(j) || "");
        if (!key) continue;
        var shouldClear = false;
        for (var p = 0; p < TENANT_CACHE_PREFIXES.length; p++) {
          if (key.indexOf(TENANT_CACHE_PREFIXES[p]) === 0) {
            shouldClear = true;
            break;
          }
        }
        if (shouldClear) localStorage.removeItem(key);
      }
    } catch (_e) {}
    window.HRCommon.clearSalaryDependentCaches();
  };
  window.HRCommon.bumpSalaryDataVersion = function (reason) {
    window.HRCommon.clearSalaryDependentCaches();
    var stamp = String(Date.now());
    try { localStorage.setItem(KEY_SALARY_DATA_VERSION, stamp); } catch (_e) {}
    try {
      window.dispatchEvent(new CustomEvent("hr:salary-data-changed", {
        detail: { at: stamp, reason: String(reason || "") }
      }));
    } catch (_e) {}
  };

  window.HRCommon.syncControlSettings = async function () {
    var KEY_CONTROL = "hr_client_control_v1";
    var apiBases = ["/api", "/backend/api.php?path=/api"];
    for (var i = 0; i < apiBases.length; i++) {
      var base = apiBases[i];
      try {
        var res = await fetch(base + "/control", { cache: "no-store", headers: { Accept: "application/json" } });
        if (!res.ok) continue;
        var data = await res.json();
        if (data && typeof data === "object") {
          localStorage.setItem(KEY_CONTROL, JSON.stringify(data));
          window.dispatchEvent(new CustomEvent("hr:control-updated", { detail: data }));
        }
        return;
      } catch (_e) {
        // ignore and try fallback base
      }
    }
  };

  window.HRCommon.cfg = {
    KEY_AUTH: KEY_AUTH,
    KEY_SUPERADMIN_CLIENT_ID: KEY_SUPERADMIN_CLIENT_ID,
    KEY_SUPERADMIN_CLIENT_LABEL: KEY_SUPERADMIN_CLIENT_LABEL,
    KEY_SALARY_DATA_VERSION: KEY_SALARY_DATA_VERSION,
    TENANT_CACHE_KEYS: TENANT_CACHE_KEYS,
    TENANT_CACHE_PREFIXES: TENANT_CACHE_PREFIXES,
    SALARY_CACHE_KEYS: SALARY_CACHE_KEYS
  };
})();
