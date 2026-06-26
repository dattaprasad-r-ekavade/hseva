/* HR Seva portal auth, navigation, RBAC, and UI bootstrap */
(function () {
  "use strict";

  var cfg = window.HRCommon && window.HRCommon.cfg ? window.HRCommon.cfg : {};
  var KEY_AUTH = cfg.KEY_AUTH || "hr_auth_session_v1";
  var KEY_SUPERADMIN_CLIENT_ID = cfg.KEY_SUPERADMIN_CLIENT_ID || "hr_superadmin_selected_client_id_v1";
  var KEY_SUPERADMIN_CLIENT_LABEL = cfg.KEY_SUPERADMIN_CLIENT_LABEL || "hr_superadmin_selected_client_label_v1";
  var KEY_SALARY_DATA_VERSION = cfg.KEY_SALARY_DATA_VERSION || "hr_salary_data_version_v1";
  var TENANT_CACHE_KEYS = cfg.TENANT_CACHE_KEYS || [];
  var TENANT_CACHE_PREFIXES = cfg.TENANT_CACHE_PREFIXES || [];
  var SALARY_CACHE_KEYS = cfg.SALARY_CACHE_KEYS || [];
  function getAuthSession() {
    try {
      var raw = sessionStorage.getItem(KEY_AUTH);
      if (!raw) {
        try { localStorage.removeItem(KEY_AUTH); } catch (_e2) {}
        return null;
      }
      return JSON.parse(raw || "null");
    } catch (_e) {
      return null;
    }
  }
  function getPathPage() {
    var path = (window.location.pathname || "").toLowerCase();
    var page = path.split("/").pop() || "";
    var bodyKey = "";
    try {
      bodyKey = String(document.body && document.body.getAttribute("data-page-key") || "").toLowerCase();
    } catch (_e) {}
    if (bodyKey) page = bodyKey;
    return { path: path, page: page };
  }
  function isPublicPage(page) {
    return page === "client-login.html" || page === "client-logout.html" || page === "super-admin-login.html" || page === "super-admin-logout.html";
  }
  function installCustomAlert() {
    var page = getPathPage().page;
    if (isPublicPage(page)) return;
    window.alert = function (message) {
      window.HRCommon.showAlert(message, "HR Seva");
    };
  }
  function isSuperAdminFolder() {
    return getPathPage().path.indexOf("/super-admin/") >= 0;
  }
  function isClientFolder() {
    return getPathPage().path.indexOf("/client/") >= 0;
  }
  function selectedSuperAdminClientId() {
    var id = Number(localStorage.getItem(KEY_SUPERADMIN_CLIENT_ID) || 0);
    return Number.isFinite(id) && id > 0 ? id : 0;
  }
  function effectiveTenantClientId() {
    var auth = getAuthSession();
    var authClientId = auth && auth.user ? Number(auth.user.clientId || 0) : 0;
    var role = String((auth && auth.user && auth.user.role) || "").toLowerCase();
    if (authClientId > 0) return authClientId;
    if (role === "super_admin") {
      var selected = selectedSuperAdminClientId();
      if (selected > 0) return selected;
    }
    if (isSuperAdminFolder()) return selectedSuperAdminClientId();
    return 0;
  }
  function clientLoginPagePath() {
    return isSuperAdminFolder() ? "../client/client-login.html" : "client-login.html";
  }
  function installTenantFetch() {
    if (window.__hrTenantFetchWrapped) return;
    var originalFetch = window.fetch.bind(window);
    window.fetch = function (input, init) {
      var reqInit = init ? Object.assign({}, init) : {};
      var url = "";
      if (typeof input === "string") url = input;
      else if (input && typeof input.url === "string") url = input.url;
      var lower = String(url || "").toLowerCase();
      var isApi = /(^|\/|\.\.\/)api\//.test(lower);
      var isAuthRoute =
        /(^|\/|\.\.\/)api\/auth\/login/.test(lower) ||
        /(^|\/|\.\.\/)api\/auth\/forgot/.test(lower);
      if (isApi && !isAuthRoute) {
        var auth = getAuthSession();
        var token = auth && auth.token ? String(auth.token) : "";
        var clientId = effectiveTenantClientId();
        var headers = new Headers(reqInit.headers || (input && input.headers) || {});
        if (token) {
          headers.set("Authorization", "Bearer " + token);
        }
        if (clientId > 0) {
          headers.set("X-Client-Id", String(clientId));
        }
        reqInit.headers = headers;
      }
      return originalFetch(input, reqInit).then(function (res) {
        if (res && res.status === 403) {
          var authNow = getAuthSession();
          var roleNow = String((authNow && authNow.user && authNow.user.role) || "").toLowerCase();
          if (roleNow === "client") {
            res.clone().json().then(function (errData) {
              var detail = String((errData && errData.detail) || "").toLowerCase();
              if (detail.indexOf("subscription expired") >= 0) {
                try {
                  localStorage.removeItem(KEY_AUTH);
                  sessionStorage.removeItem(KEY_AUTH);
                } catch (_e) {}
                window.location.replace(clientLoginPagePath());
              }
            }).catch(function () {});
          }
        }
        return res;
      });
    };
    window.__hrTenantFetchWrapped = true;
  }
  async function initSuperAdminClientPicker() {
    if (!isSuperAdminFolder()) return;
    var topbar = document.querySelector(".topbar .topbar-inner");
    if (!topbar || document.getElementById("hrClientPickerWrap")) return;

    var wrap = document.createElement("div");
    wrap.id = "hrClientPickerWrap";
    wrap.className = "hr-client-picker-wrap me-2";
    wrap.innerHTML = [
      '<div class="hr-client-picker-field input-group input-group-sm">',
      '  <input id="hrClientPickerInput" type="text" class="form-control hr-client-picker-input" list="hrClientPickerList" placeholder="Search Client Name">',
      '  <button id="hrClientPickerClear" class="btn btn-outline-secondary hr-client-picker-clear" type="button" aria-label="Clear selection"><i class="bi bi-x-lg"></i></button>',
      '  <button id="hrClientPickerSearch" class="btn btn-outline-secondary hr-client-picker-btn" type="button" aria-label="Search client"><i class="bi bi-search"></i></button>',
      '  <datalist id="hrClientPickerList"></datalist>',
      '</div>',
      '<span id="hrClientPickerHint" class="d-none"></span>'
    ].join("");

    var themeBtn = topbar.querySelector("#themeToggle");
    if (themeBtn && themeBtn.parentElement === topbar) topbar.insertBefore(wrap, themeBtn);
    else topbar.appendChild(wrap);

    var input = document.getElementById("hrClientPickerInput");
    var list = document.getElementById("hrClientPickerList");
    var searchBtn = document.getElementById("hrClientPickerSearch");
    var clearBtn = document.getElementById("hrClientPickerClear");
    var hint = document.getElementById("hrClientPickerHint");
    var pickerField = wrap.querySelector(".hr-client-picker-field");

    var clients = [];
    function optionText(c) {
      return String(c.companyName || "Client");
    }
    function renderHint(client) {
      if (!hint) return;
      hint.textContent = "";
      hint.classList.add("d-none");
    }
    function resetInputStable() {
      if (!input) return;
      input.value = "";
      input.placeholder = "Search Client Name";
    }
    function setSelection(client, shouldReload) {
      var prevClientId = selectedSuperAdminClientId();
      var nextClientId = client && Number(client.id) > 0 ? Number(client.id) : 0;
      var changed = prevClientId !== nextClientId;
      if (client && Number(client.id) > 0) {
        var id = Number(client.id);
        localStorage.setItem(KEY_SUPERADMIN_CLIENT_ID, String(id));
        localStorage.setItem(KEY_SUPERADMIN_CLIENT_LABEL, optionText(client));
        if (input) {
          input.value = optionText(client);
          input.placeholder = "Search Client Name";
        }
        if (pickerField) pickerField.classList.add("hr-picked");
        renderHint(client);
        window.dispatchEvent(new CustomEvent("hr:selected-client-changed", { detail: { clientId: id, client: client } }));
      } else {
        localStorage.removeItem(KEY_SUPERADMIN_CLIENT_ID);
        localStorage.removeItem(KEY_SUPERADMIN_CLIENT_LABEL);
        resetInputStable();
        if (pickerField) pickerField.classList.remove("hr-picked");
        renderHint(null);
        window.dispatchEvent(new CustomEvent("hr:selected-client-changed", { detail: { clientId: 0, client: null } }));
      }
      if (changed && window.HRCommon && typeof window.HRCommon.clearTenantScopedCaches === "function") {
        window.HRCommon.clearTenantScopedCaches();
      }
      if (shouldReload) window.location.reload();
    }
    function findByInput(raw) {
      var value = String(raw || "").trim();
      if (!value) return null;
      var idOnly = value.match(/^\d+$/);
      if (idOnly) {
        var byId = Number(idOnly[0]);
        for (var i = 0; i < clients.length; i++) if (Number(clients[i].id) === byId) return clients[i];
      }
      var lower = value.toLowerCase();
      for (var j = 0; j < clients.length; j++) if (optionText(clients[j]).toLowerCase() === lower) return clients[j];
      for (var k = 0; k < clients.length; k++) if (String(clients[k].companyName || "").toLowerCase() === lower) return clients[k];
      for (var m = 0; m < clients.length; m++) if (String(clients[m].userId || "").toLowerCase() === lower) return clients[m];
      for (var n = 0; n < clients.length; n++) {
        var compound = (String(clients[n].companyName || "") + " " + String(clients[n].userId || "") + " " + String(clients[n].id || "")).toLowerCase();
        if (compound.indexOf(lower) >= 0) return clients[n];
      }
      return null;
    }
    function applyExistingSelection() {
      var selectedId = selectedSuperAdminClientId();
      if (selectedId <= 0) {
        resetInputStable();
        renderHint(null);
        return;
      }
      for (var i = 0; i < clients.length; i++) {
        if (Number(clients[i].id) === selectedId) {
          if (input) {
            input.value = optionText(clients[i]);
            input.placeholder = "Search Client Name";
          }
          if (pickerField) pickerField.classList.add("hr-picked");
          renderHint(clients[i]);
          return;
        }
      }
      resetInputStable();
      if (pickerField) pickerField.classList.remove("hr-picked");
    }

    function commitSearch() {
      var found = findByInput(input.value);
      if (input.value.trim() === "") {
        setSelection(null, true);
        return;
      }
      if (found) setSelection(found, true);
    }

    input.addEventListener("change", commitSearch);
    input.addEventListener("keydown", function (ev) {
      if (ev.key !== "Enter") return;
      ev.preventDefault();
      commitSearch();
    });
    if (searchBtn) searchBtn.addEventListener("click", commitSearch);
    if (clearBtn) clearBtn.addEventListener("click", function () { setSelection(null, true); });

    var apiBases = ["/api"];
    for (var b = 0; b < apiBases.length; b++) {
      var base = apiBases[b];
      try {
        var res = await fetch(base + "/clients", { cache: "no-store", headers: { Accept: "application/json" } });
        if (!res.ok) continue;
        var data = await res.json();
        clients = Array.isArray(data.rows) ? data.rows.slice() : [];
        clients.sort(function (a, z) {
          return String(a.companyName || "").localeCompare(String(z.companyName || ""));
        });
        list.innerHTML = clients.map(function (c) {
          return '<option value="' + optionText(c).replace(/"/g, "&quot;") + '"></option>';
        }).join("");
        applyExistingSelection();
        return;
      } catch (_e) {
        // try next base
      }
    }
    renderHint(null);
  }

  function enforceAuth() {
    var p = getPathPage();
    var path = p.path;
    var page = p.page;
    var loginPage = path.indexOf("/super-admin/") >= 0 ? "super-admin-login.html" : "client-login.html";
    if (isPublicPage(page)) return true;

    var auth = getAuthSession();
    if (!auth || !auth.token) {
      // Prevent stale page flash before redirect.
      try { document.documentElement.style.visibility = "hidden"; } catch (_e) {}
      window.location.replace(loginPage);
      return false;
    }
    return true;
  }
  function enforceAccess() {
    var p = getPathPage();
    var path = p.path;
    var page = p.page;
    var map = {
      "index.html": "dashboard",
      "client-module.html": "clientModule",
      "client-employee-master.html": "employeeMaster",
      "client-employee-types.html": "employeeType",
      "client-payroll-calc.html": "salarySheet",
      "client-payslips.html": "payslips",
      "client-compliance-calendar.html": "compliance",
      "client-attendance.html": "attendance",
      "client-attendance-statuses.html": "attendanceStatus",
      "scan-attendance.php": "attendance",
      "my-face-attendance.php": "attendance",
      "face-attendance-registration.php": "attendance",
      "face-attendance-settings.php": "attendance",
      "face-attendance-sheet.php": "attendance",
      "monthly-attendance-report.php": "attendance",
      "client-shift-roster.html": "shiftRoster",
      "my-shift-roster.html": "shiftRoster",
      "client-leave.html": "leaveManagement",
      "client-overtime.html": "overtime",
      "client-fnf.html": "fnf",
      "client-advance-salary.html": "advanceSalary",
      "client-loan.html": "loan",
      "client-view-loan.html": "loan",
      "client-gratuity.html": "gratuity",
      "client-bonus.html": "bonus",
      "client-incentive.html": "incentive",
      "client-pf-sheet.html": "pfSheet",
      "client-pf-return.html": "pfReturn",
      "client-esic-sheet.html": "esicSheet",
      "client-esic-return.html": "esicReturn",
      "client-ecr-sheet.html": "ecrSheet",
      "client-control.html": "controlPage",
      "client-profile.html": "companyProfile",
      "client-subscriptions.html": "subscriptions",
      "client-billing.html": "billing",
      "client-invoices.html": "invoices",
      "client-roles.html": "accessControl",
      "client-access-control.html": "accessControl",
      "super-admin-module.html": "clientModule",
      "super-admin-employee-master.html": "employeeMaster",
      "super-admin-employee-types.html": "employeeType",
      "super-admin-payroll-calc.html": "salarySheet",
      "super-admin-payslips.html": "payslips",
      "super-admin-compliance-calendar.html": "compliance",
      "super-admin-attendance.html": "attendance",
      "super-admin-attendance-statuses.html": "attendanceStatus",
      "face-attendance-registration.php": "attendance",
      "face-attendance-settings.php": "attendance",
      "face-attendance-sheet.php": "attendance",
      "monthly-attendance-report.php": "attendance",
      "super-admin-shift-roster.html": "shiftRoster",
      "super-admin-leave.html": "leaveManagement",
      "super-admin-overtime.html": "overtime",
      "super-admin-fnf.html": "fnf",
      "super-admin-advance-salary.html": "advanceSalary",
      "super-admin-loan.html": "loan",
      "super-admin-view-loan.html": "loan",
      "super-admin-gratuity.html": "gratuity",
      "super-admin-bonus.html": "bonus",
      "super-admin-incentive.html": "incentive",
      "super-admin-pf-sheet.html": "pfSheet",
      "super-admin-pf-return.html": "pfReturn",
      "super-admin-esic-sheet.html": "esicSheet",
      "super-admin-esic-return.html": "esicReturn",
      "super-admin-ecr-sheet.html": "ecrSheet",
      "super-admin-control.html": "controlPage",
      "super-admin-smtp-control.html": "accessControl",
      "super-admin-profile.html": "companyProfile",
      "super-admin-subscriptions.html": "subscriptions",
      "super-admin-billing.html": "billing",
      "super-admin-invoices.html": "invoices",
      "super-admin-enquiries.html": "subscriptions",
      "super-admin-access-control.html": "accessControl",
      "super-admin-roles.html": "accessControl"
    };
    var key = map[page];
    if (!key) return;
    var auth = getAuthSession();
    if (!auth || !auth.user) return;
    var role = String(auth.user.role || "").toLowerCase();
    if (!auth.user.clientId) return; // non-client/admin users can access all pages
    var perms = auth.user.permissions;
    if (!perms || typeof perms !== "object") return;
    var isSuperAdminFolder = path.indexOf("/super-admin/") >= 0;
    var clientHome = isSuperAdminFolder ? "../client/index.html" : "index.html";
    if (perms[key] === false) {
      window.location.replace(clientHome);
    }
  }
  function enforceSuperAdminPages() {
    var auth = getAuthSession();
    if (!auth || !auth.user) return;
    var role = String(auth.user.role || "").toLowerCase();
    var p = getPathPage();
    var path = p.path;
    var page = p.page;
    var isSuperAdminFolder = path.indexOf("/super-admin/") >= 0;
    var clientHome = isSuperAdminFolder ? "../client/index.html" : "index.html";
    var isClientFolder = path.indexOf("/client/") >= 0;
    if (isClientFolder && (page === "super-admin-billing.html" || page === "super-admin-subscriptions.html")) {
      return;
    }
    var adminOnlyPages = {
      "super-admin-dashboard.html": true,
      "super-admin-billing.html": true,
      "super-admin-subscriptions.html": true,
      "super-admin-enquiries.html": true,
      "super-admin-shift-roster.html": true,
      "super-admin-smtp-control.html": true,
      "super-admin-attendance-statuses.html": true,
      "super-admin-employee-types.html": true,
      "client-module.html": true,
      "client-access-control.html": true,
      "super-admin-module.html": true,
      "super-admin-access-control.html": true,
      "super-admin-roles.html": true
    };
    if (adminOnlyPages[page] && role === "client") {
      window.location.replace(clientHome);
    }
  }
  function applyClientSidebarIdentity() {
    if (!isClientFolder()) return;
    var auth = getAuthSession();
    if (!auth || !auth.user) return;

    var clientName = String(auth.user.username || auth.user.name || "Client").trim() || "Client";
    var sidebarBrand = document.querySelector("aside.sidebar .brand");
    if (sidebarBrand) {
      var title = sidebarBrand.querySelector(".fw-semibold");
      var subtitle = sidebarBrand.querySelector(".small, .text-muted-3");
      if (title) title.textContent = "HR Seva";
      if (subtitle) subtitle.textContent = clientName;
    }

    var mobileLabel = document.getElementById("mobileSidebarLabel");
    if (mobileLabel) {
      mobileLabel.textContent = "HR Seva";
      var mobileWrap = mobileLabel.parentElement;
      if (mobileWrap) {
        var mobileSub = mobileWrap.querySelector(".small, .text-muted-3");
        if (mobileSub) mobileSub.textContent = clientName;
      }
    }
  }
  function applySuperAdminSidebarIdentity() {
    if (!isSuperAdminFolder()) return;

    var sidebarBrand = document.querySelector("aside.sidebar .brand");
    if (sidebarBrand) {
      var title = sidebarBrand.querySelector(".fw-semibold");
      var subtitle = sidebarBrand.querySelector(".small, .text-muted-3");
      if (title) title.textContent = "HR Seva";
      if (subtitle) subtitle.textContent = "Super Admin";
    }

    var mobileLabel = document.getElementById("mobileSidebarLabel");
    if (mobileLabel) {
      mobileLabel.textContent = "HR Seva";
      var mobileWrap = mobileLabel.parentElement;
      if (mobileWrap) {
        var mobileSub = mobileWrap.querySelector(".small, .text-muted-3");
        if (mobileSub) mobileSub.textContent = "Super Admin";
      }
    }
  }
  function applyAccessVisibility() {
    var auth = getAuthSession();
    if (!auth || !auth.user || !auth.user.clientId) return;
    var role = String(auth.user.role || "").toLowerCase();
    var perms = auth.user.permissions;
    if (!perms || typeof perms !== "object") return;

    var links = document.querySelectorAll(".sidebar-scroll a.nav-link[data-perm], #mobileSidebar a.nav-link[data-perm]");
    links.forEach(function (a) {
      var key = String(a.getAttribute("data-perm") || "");
      if (!key) return;
      if (perms[key] === false) a.classList.add("d-none");
    });

    var linkMap = {
      "index.html": "dashboard",
      "client-module.html": "clientModule",
      "client-employee-master.html": "employeeMaster",
      "client-employee-types.html": "employeeType",
      "client-payroll-calc.html": "salarySheet",
      "client-payslips.html": "payslips",
      "client-compliance-calendar.html": "compliance",
      "client-attendance.html": "attendance",
      "client-attendance-statuses.html": "attendanceStatus",
      "scan-attendance.php": "attendance",
      "my-face-attendance.php": "attendance",
      "face-attendance-registration.php": "attendance",
      "face-attendance-settings.php": "attendance",
      "face-attendance-sheet.php": "attendance",
      "monthly-attendance-report.php": "attendance",
      "client-shift-roster.html": "shiftRoster",
      "my-shift-roster.html": "shiftRoster",
      "client-leave.html": "leaveManagement",
      "client-overtime.html": "overtime",
      "client-fnf.html": "fnf",
      "client-advance-salary.html": "advanceSalary",
      "client-loan.html": "loan",
      "client-view-loan.html": "loan",
      "client-gratuity.html": "gratuity",
      "client-bonus.html": "bonus",
      "client-incentive.html": "incentive",
      "client-pf-sheet.html": "pfSheet",
      "client-pf-return.html": "pfReturn",
      "client-esic-sheet.html": "esicSheet",
      "client-esic-return.html": "esicReturn",
      "client-ecr-sheet.html": "ecrSheet",
      "client-control.html": "controlPage",
      "client-profile.html": "companyProfile",
      "client-subscriptions.html": "subscriptions",
      "client-billing.html": "billing",
      "client-invoices.html": "invoices",
      "client-roles.html": "accessControl",
      "client-access-control.html": "accessControl",
      "super-admin-module.html": "clientModule",
      "super-admin-employee-master.html": "employeeMaster",
      "super-admin-employee-types.html": "employeeType",
      "super-admin-payroll-calc.html": "salarySheet",
      "super-admin-payslips.html": "payslips",
      "super-admin-compliance-calendar.html": "compliance",
      "super-admin-attendance.html": "attendance",
      "super-admin-attendance-statuses.html": "attendanceStatus",
      "face-attendance-registration.php": "attendance",
      "face-attendance-settings.php": "attendance",
      "face-attendance-sheet.php": "attendance",
      "monthly-attendance-report.php": "attendance",
      "super-admin-shift-roster.html": "shiftRoster",
      "super-admin-leave.html": "leaveManagement",
      "super-admin-overtime.html": "overtime",
      "super-admin-fnf.html": "fnf",
      "super-admin-advance-salary.html": "advanceSalary",
      "super-admin-loan.html": "loan",
      "super-admin-view-loan.html": "loan",
      "super-admin-gratuity.html": "gratuity",
      "super-admin-bonus.html": "bonus",
      "super-admin-incentive.html": "incentive",
      "super-admin-pf-sheet.html": "pfSheet",
      "super-admin-pf-return.html": "pfReturn",
      "super-admin-esic-sheet.html": "esicSheet",
      "super-admin-esic-return.html": "esicReturn",
      "super-admin-ecr-sheet.html": "ecrSheet",
      "super-admin-control.html": "controlPage",
      "super-admin-smtp-control.html": "accessControl",
      "super-admin-profile.html": "companyProfile",
      "super-admin-subscriptions.html": "subscriptions",
      "super-admin-billing.html": "billing",
      "super-admin-invoices.html": "invoices",
      "super-admin-access-control.html": "accessControl",
      "super-admin-roles.html": "accessControl"
    };

    // Keep admin-only modules hidden for client users even if permission payload is incorrect.
    if (role === "client") {
      ["client-module.html", "client-access-control.html", "super-admin-module.html", "super-admin-access-control.html"].forEach(function (href) {
        var links = document.querySelectorAll('a[href="' + href + '"]');
        Array.prototype.forEach.call(links, function (link) {
          if (link.classList.contains("nav-link")) link.style.display = "none";
          var li = link.closest("li");
          if (li && li.parentElement && li.parentElement.classList.contains("dropdown-menu")) li.style.display = "none";
          var tile = link.closest(".tile");
          if (tile) {
            var tileCol = tile.closest(".col-12, .col-md-6, .col-xl-4, .col-xl-3, .col-lg-3, .col-md-4, .col-md-3");
            if (tileCol) tileCol.style.display = "none";
            tile.style.display = "none";
          } else if (!link.classList.contains("nav-link")) {
            link.style.display = "none";
          }
        });
      });
    }
    if (role === "employee") {
      ["face-attendance-registration.php", "face-attendance-settings.php", "face-attendance-sheet.php", "monthly-attendance-report.php", "client-attendance-statuses.html", "client-module.html", "client-access-control.html"].forEach(function (href) {
        var links = document.querySelectorAll('a[href="' + href + '"]');
        Array.prototype.forEach.call(links, function (link) {
          if (link.classList.contains("nav-link")) link.style.display = "none";
          var li = link.closest("li");
          if (li && li.parentElement && li.parentElement.classList.contains("dropdown-menu")) li.style.display = "none";
          var tile = link.closest(".tile");
          if (tile) tile.style.display = "none";
        });
      });
    }

    Object.keys(linkMap).forEach(function (href) {
      var key = linkMap[href];
      if (perms[key] !== false) return;
      var selector = 'a[href="' + href + '"]';
      var links = document.querySelectorAll(selector);
      Array.prototype.forEach.call(links, function (link) {
        // Hide nav links
        if (link.classList.contains("nav-link")) {
          link.style.display = "none";
        }
        // Hide dropdown menu item wrapper
        var li = link.closest("li");
        if (li && li.parentElement && li.parentElement.classList.contains("dropdown-menu")) {
          li.style.display = "none";
        }
        // Hide dashboard tiles/cards that link to denied modules
        var tile = link.closest(".tile");
        if (tile) {
          var tileCol = tile.closest(".col-12, .col-md-6, .col-xl-4, .col-xl-3, .col-lg-3, .col-md-4, .col-md-3");
          if (tileCol) tileCol.style.display = "none";
          tile.style.display = "none";
        }
        // Hide standalone links/buttons
        if (!tile && !link.classList.contains("nav-link")) {
          link.style.display = "none";
        }
      });
    });
  }

  function ensureSuperAdminRolesLink() {
    if (!isSuperAdminFolder()) return;
    var rolesHref = "super-admin-roles.html";
    var oldRolesHref = "../client/client-roles.html";
    var addAfterAccessControl = function (link) {
      if (!link || !link.parentElement) return;
      var parent = link.parentElement;
      var existing = parent.querySelector('a.nav-link[href="' + rolesHref + '"]') || parent.querySelector('a.nav-link[href="' + oldRolesHref + '"]');
      if (existing) {
        existing.href = rolesHref;
        return;
      }
      var rolesLink = document.createElement("a");
      rolesLink.className = "nav-link";
      rolesLink.href = rolesHref;
      rolesLink.innerHTML = '<i class="bi bi-person-badge"></i> Roles';
      if (link.nextSibling) parent.insertBefore(rolesLink, link.nextSibling);
      else parent.appendChild(rolesLink);
    };
    var settingsLinks = document.querySelectorAll('a.nav-link[href="super-admin-access-control.html"]');
    Array.prototype.forEach.call(settingsLinks, addAfterAccessControl);

    var mobileBody = document.querySelector("#mobileSidebar .offcanvas-body");
    if (mobileBody && !mobileBody.querySelector('a.nav-link[href="' + rolesHref + '"]')) {
      var anchor = mobileBody.querySelector('a.nav-link[href="super-admin-access-control.html"]') || mobileBody.querySelector('a.nav-link[href="super-admin-control.html"]');
      if (anchor && anchor.parentElement) {
        var existingMobile = mobileBody.querySelector('a.nav-link[href="' + oldRolesHref + '"]');
        if (existingMobile) {
          existingMobile.href = rolesHref;
          return;
        }
        var mobileRoles = document.createElement("a");
        mobileRoles.className = "nav-link";
        mobileRoles.href = rolesHref;
        mobileRoles.innerHTML = '<i class="bi bi-person-badge"></i> Roles';
        if (anchor.nextSibling) anchor.parentElement.insertBefore(mobileRoles, anchor.nextSibling);
        else anchor.parentElement.appendChild(mobileRoles);
      }
    }
  }

  function normalizeSuperAdminSidebar() {
    if (document.querySelector('.sidebar-scroll[data-server-nav="1"]')) return;
    if (!isSuperAdminFolder()) return;
    var p = getPathPage();
    var page = String(p.page || "");
    var isPublic = page === "super-admin-login.html" || page === "super-admin-logout.html";
    if (isPublic) return;

    var links = [
      { section: "Main", href: "super-admin-dashboard.html", icon: "bi-speedometer2", label: "Super Dashboard" },
      { section: "Main", href: "index.html", icon: "bi-grid", label: "Client Dashboard" },
      { section: "Main", href: "super-admin-shift-roster.html", icon: "bi-calendar-week", label: "Shift / Roster" },
      { section: "Main", href: "super-admin-module.html", icon: "bi-building", label: "Client Module" },
      { section: "Main", href: "super-admin-employee-master.html", icon: "bi-people", label: "Employee Master" },
      { section: "Main", href: "super-admin-employee-types.html", icon: "bi-person-vcard", label: "Employee Type" },
      { section: "Statutory", href: "super-admin-payroll-calc.html", icon: "bi-calculator", label: "Salary Sheet" },
      { section: "Statutory", href: "super-admin-payslips.html", icon: "bi-receipt", label: "Payslip Generator" },
      { section: "Statutory", href: "super-admin-compliance-calendar.html", icon: "bi-calendar3", label: "Compliance challan" },
      { section: "Attendance and Leave", href: "super-admin-attendance.html", icon: "bi-calendar2-check", label: "Attendance Sheet" },
      { section: "Attendance and Leave", href: "scan-attendance.php", icon: "bi-camera-video", label: "Scan Attendance" },
      { section: "Attendance and Leave", href: "face-attendance-registration.php", icon: "bi-person-bounding-box", label: "Employee Face Registration" },
      { section: "Attendance and Leave", href: "face-attendance-settings.php", icon: "bi-sliders2", label: "Scan Attendance Settings" },
      { section: "Attendance and Leave", href: "face-attendance-sheet.php", icon: "bi-table", label: "Face Attendance Sheet" },
      { section: "Attendance and Leave", href: "monthly-attendance-report.php", icon: "bi-file-earmark-bar-graph", label: "Monthly Attendance Report" },
      { section: "Attendance and Leave", href: "super-admin-attendance-statuses.html", icon: "bi-ui-checks-grid", label: "Attendance Status" },
      { section: "Attendance and Leave", href: "super-admin-leave.html", icon: "bi-calendar2-check", label: "Leave Management" },
      { section: "Attendance and Leave", href: "super-admin-overtime.html", icon: "bi-clock-history", label: "Overtime" },
      { section: "Returns & Sheets", href: "super-admin-fnf.html", icon: "bi-clipboard-check", label: "FNF" },
      { section: "Returns & Sheets", href: "super-admin-advance-salary.html", icon: "bi-cash-coin", label: "Advance Salary" },
      { section: "Returns & Sheets", href: "super-admin-loan.html", icon: "bi-bank", label: "Loan" },
      { section: "Returns & Sheets", href: "super-admin-gratuity.html", icon: "bi-award", label: "Gratuity" },
      { section: "Returns & Sheets", href: "super-admin-bonus.html", icon: "bi-cash-stack", label: "Bonus" },
      { section: "Returns & Sheets", href: "super-admin-incentive.html", icon: "bi-gift", label: "Incentive" },
      { section: "Returns & Sheets", href: "super-admin-pf-sheet.html", icon: "bi-file-earmark-spreadsheet", label: "PF / ECR Sheet" },
      { section: "Returns & Sheets", href: "super-admin-pf-return.html", icon: "bi-cloud-upload", label: "PF Return" },
      { section: "Returns & Sheets", href: "super-admin-esic-sheet.html", icon: "bi-file-earmark-spreadsheet", label: "ESIC Sheet" },
      { section: "Returns & Sheets", href: "super-admin-esic-return.html", icon: "bi-cloud-upload", label: "ESIC Return" },
      { section: "Settings", href: "super-admin-control.html", icon: "bi-sliders", label: "Control Page" },
      { section: "Settings", href: "super-admin-smtp-control.html", icon: "bi-envelope-gear", label: "SMTP Control" },
      { section: "Settings", href: "super-admin-access-control.html", icon: "bi-shield-lock", label: "Access Control" },
      { section: "Settings", href: "super-admin-roles.html", icon: "bi-person-badge", label: "Roles" },
      { section: "Account", href: "super-admin-profile.html", icon: "bi-building", label: "Company Profile" },
      { section: "Account", href: "super-admin-enquiries.html", icon: "bi-chat-left-text", label: "Enquiries" },
      { section: "Account", href: "super-admin-subscriptions.html", icon: "bi-card-checklist", label: "Subscriptions" },
      { section: "Account", href: "super-admin-billing.html", icon: "bi-receipt-cutoff", label: "Billing & Invoice" },
      { section: "Account", href: "super-admin-logout.html", icon: "bi-box-arrow-right", label: "Logout" }
    ];
    var sections = ["Main", "Statutory", "Attendance and Leave", "Returns & Sheets", "Settings", "Account"];
    var sectionClass = {
      "Main": "nav-section-title",
      "Statutory": "nav-section-title",
      "Attendance and Leave": "nav-section-title",
      "Returns & Sheets": "nav-section-title",
      "Settings": "nav-section-title",
      "Account": "nav-section-title"
    };
    var mobileSectionClass = {
      "Main": "nav-section-title",
      "Statutory": "nav-section-title mt-3",
      "Attendance and Leave": "nav-section-title mt-3",
      "Returns & Sheets": "nav-section-title mt-3",
      "Settings": "nav-section-title mt-3",
      "Account": "nav-section-title mt-3"
    };
    var activeHref = page;
    if (page === "super-admin-view-loan.html") activeHref = "super-admin-loan.html";

    var buildNavHtml = function (mobile) {
      return sections.map(function (sec) {
        var cls = mobile ? mobileSectionClass[sec] : sectionClass[sec];
        var rows = links.filter(function (x) { return x.section === sec; });
        var nav = rows.map(function (x) {
          var active = activeHref === x.href ? " active" : "";
          return '<a class="nav-link' + active + '" href="' + x.href + '"><i class="bi ' + x.icon + '"></i> ' + x.label + '</a>';
        }).join("");
        return '<div class="' + cls + '">' + sec + '</div><nav class="nav flex-column gap-1">' + nav + '</nav>';
      }).join("");
    };

    var desktopScroll = document.querySelector("aside.sidebar .sidebar-scroll");
    if (desktopScroll) desktopScroll.innerHTML = buildNavHtml(false);

    var mobileBody = document.querySelector("#mobileSidebar .offcanvas-body");
    if (mobileBody) mobileBody.innerHTML = buildNavHtml(true);
  }

  function normalizeClientSidebar() {
    if (document.querySelector('.sidebar-scroll[data-server-nav="1"]')) return;
    if (!isClientFolder()) return;
    var p = getPathPage();
    var page = String(p.page || "");
    var isPublic = page === "client-login.html" || page === "client-logout.html";
    if (isPublic) return;

    var links = [
      { section: "Main", href: "index.html", icon: "bi-grid", label: "Dashboard" },
      { section: "Main", href: "client-module.html", icon: "bi-building", label: "Client Module" },
      { section: "Main", href: "client-shift-roster.html", icon: "bi-calendar-week", label: "Shift / Roster" },
      { section: "Main", href: "client-employee-master.html", icon: "bi-people", label: "Employee Master" },
      { section: "Main", href: "client-employee-types.html", icon: "bi-person-vcard", label: "Employee Type" },
      { section: "Statutory", href: "client-payroll-calc.html", icon: "bi-calculator", label: "Salary Sheet" },
      { section: "Statutory", href: "client-payslips.html", icon: "bi-receipt", label: "Payslip Generator" },
      { section: "Statutory", href: "client-compliance-calendar.html", icon: "bi-calendar3", label: "Compliance challan" },
      { section: "Attendance and Leave", href: "client-attendance.html", icon: "bi-calendar2-check", label: "Attendance Sheet" },
      { section: "Attendance and Leave", href: "scan-attendance.php", icon: "bi-camera-video", label: "Scan Attendance" },
      { section: "Attendance and Leave", href: "my-face-attendance.php", icon: "bi-person-check", label: "My Attendance" },
      { section: "Attendance and Leave", href: "face-attendance-registration.php", icon: "bi-person-bounding-box", label: "Employee Face Registration" },
      { section: "Attendance and Leave", href: "face-attendance-settings.php", icon: "bi-sliders2", label: "Scan Attendance Settings" },
      { section: "Attendance and Leave", href: "face-attendance-sheet.php", icon: "bi-table", label: "Face Attendance Sheet" },
      { section: "Attendance and Leave", href: "monthly-attendance-report.php", icon: "bi-file-earmark-bar-graph", label: "Monthly Attendance Report" },
      { section: "Attendance and Leave", href: "client-attendance-statuses.html", icon: "bi-ui-checks-grid", label: "Attendance Status" },
      { section: "Attendance and Leave", href: "client-leave.html", icon: "bi-calendar2-check", label: "Leave Management" },
      { section: "Attendance and Leave", href: "client-overtime.html", icon: "bi-clock-history", label: "Overtime" },
      { section: "Returns & Sheets", href: "client-fnf.html", icon: "bi-clipboard-check", label: "FNF" },
      { section: "Returns & Sheets", href: "client-advance-salary.html", icon: "bi-cash-coin", label: "Advance Salary" },
      { section: "Returns & Sheets", href: "client-loan.html", icon: "bi-bank", label: "Loan" },
      { section: "Returns & Sheets", href: "client-gratuity.html", icon: "bi-award", label: "Gratuity" },
      { section: "Returns & Sheets", href: "client-bonus.html", icon: "bi-cash-stack", label: "Bonus" },
      { section: "Returns & Sheets", href: "client-incentive.html", icon: "bi-gift", label: "Incentive" },
      { section: "Returns & Sheets", href: "client-pf-sheet.html", icon: "bi-file-earmark-spreadsheet", label: "PF / ECR Sheet" },
      { section: "Returns & Sheets", href: "client-pf-return.html", icon: "bi-cloud-upload", label: "PF Return" },
      { section: "Returns & Sheets", href: "client-esic-sheet.html", icon: "bi-file-earmark-spreadsheet", label: "ESIC Sheet" },
      { section: "Returns & Sheets", href: "client-esic-return.html", icon: "bi-cloud-upload", label: "ESIC Return" },
      { section: "Settings", href: "client-control.html", icon: "bi-sliders", label: "Control Page" },
      { section: "Settings", href: "client-roles.html", icon: "bi-shield-lock", label: "Roles" },
      { section: "Account", href: "client-profile.html", icon: "bi-building", label: "Company Profile" },
      { section: "Account", href: "client-subscriptions.html", icon: "bi-card-checklist", label: "Subscriptions" },
      { section: "Account", href: "client-billing.html", icon: "bi-receipt-cutoff", label: "Billing & Invoice" },
      { section: "Account", href: "client-logout.html", icon: "bi-box-arrow-right", label: "Logout" }
    ];
    var sections = ["Main", "Statutory", "Attendance and Leave", "Returns & Sheets", "Settings", "Account"];
    var sectionClass = {
      "Main": "nav-section-title",
      "Statutory": "nav-section-title",
      "Attendance and Leave": "nav-section-title",
      "Returns & Sheets": "nav-section-title",
      "Settings": "nav-section-title",
      "Account": "nav-section-title"
    };
    var mobileSectionClass = {
      "Main": "nav-section-title",
      "Statutory": "nav-section-title mt-3",
      "Attendance and Leave": "nav-section-title mt-3",
      "Returns & Sheets": "nav-section-title mt-3",
      "Settings": "nav-section-title mt-3",
      "Account": "nav-section-title mt-3"
    };
    var activeHref = page;
    if (page === "employee-profile.html") activeHref = "client-employee-master.html";
    else if (page === "my-shift-roster.html") activeHref = "client-shift-roster.html";
    else if (page === "client-invoices.html") activeHref = "client-billing.html";
    else if (page === "client-access-control.html") activeHref = "client-roles.html";
    else if (page === "client-view-loan.html") activeHref = "client-loan.html";

    var buildNavHtml = function (mobile) {
      return sections.map(function (sec) {
        var cls = mobile ? mobileSectionClass[sec] : sectionClass[sec];
        var rows = links.filter(function (x) { return x.section === sec; });
        var nav = rows.map(function (x) {
          var active = activeHref === x.href ? " active" : "";
          return '<a class="nav-link' + active + '" href="' + x.href + '"><i class="bi ' + x.icon + '"></i> ' + x.label + '</a>';
        }).join("");
        return '<div class="' + cls + '">' + sec + '</div><nav class="nav flex-column gap-1">' + nav + '</nav>';
      }).join("");
    };

    var desktopScroll = document.querySelector("aside.sidebar .sidebar-scroll");
    if (desktopScroll) desktopScroll.innerHTML = buildNavHtml(false);

    var mobileBody = document.querySelector("#mobileSidebar .offcanvas-body");
    if (mobileBody) mobileBody.innerHTML = buildNavHtml(true);
  }

  function hasSrHeader(table) {
    var firstTh = table.querySelector("thead tr th");
    if (!firstTh) return false;
    var txt = (firstTh.textContent || "").trim().toLowerCase();
    return txt === "#" || txt === "sr no" || txt === "sr. no" || txt === "sr no." || txt === "sr";
  }

  function renderSrNo(table) {
    if (!table || table.dataset.noSrno === "true") return;
    if (table.classList.contains("cal-table") || table.classList.contains("salary-preview-table") || table.classList.contains("ps-grid")) return;
    if (table.dataset.srnoRendering === "1") return;
    table.dataset.srnoRendering = "1";

    var theadRow = table.querySelector("thead tr");
    var tbody = table.querySelector("tbody");
    if (!theadRow || !tbody) {
      table.dataset.srnoRendering = "0";
      return;
    }

    var manualSrHeader = hasSrHeader(table);
    if (!manualSrHeader) {
      var th = document.createElement("th");
      th.textContent = "Sr. No";
      theadRow.insertBefore(th, theadRow.firstElementChild || null);
      table.dataset.srnoAutoMode = "true";
    } else {
      table.dataset.srnoAutoMode = "false";
      Array.prototype.forEach.call(tbody.querySelectorAll("tr"), function (tr) {
        var firstTd = tr.querySelector("td");
        var tds = tr.querySelectorAll("td");
        if (!tds.length) return;
        if (tds.length === 1 && tds[0].hasAttribute("colspan")) return;
        if (firstTd && firstTd.classList.contains("srno-auto-cell")) firstTd.remove();
      });
    }

    Array.prototype.forEach.call(tbody.querySelectorAll("tr"), function (tr, idx) {
      var tds = tr.querySelectorAll("td");
      if (!tds.length) return;
      if (tds.length === 1 && tds[0].hasAttribute("colspan")) return;

      var firstTd = tr.querySelector("td");
      var firstText = firstTd ? String(firstTd.textContent || "").trim() : "";
      var hasNumericFirst = /^[0-9]+$/.test(firstText);
      if (firstTd && (firstTd.classList.contains("srno-auto-cell") || hasNumericFirst)) {
        var expected = String(idx + 1);
        if ((firstTd.textContent || "").trim() !== expected) firstTd.textContent = expected;
      } else {
        var td = document.createElement("td");
        td.className = "srno-auto-cell fw-semibold";
        td.textContent = String(idx + 1);
        tr.insertBefore(td, tr.firstElementChild || null);
      }
    });
    table.dataset.srnoRendering = "0";
  }

  function initAutoSrNo() {
    var tables = document.querySelectorAll("table.table");
    Array.prototype.forEach.call(tables, function (table) {
      renderSrNo(table);
      var tbody = table.querySelector("tbody");
      if (!tbody || tbody.dataset.srnoObserved === "true") return;
      var obs = new MutationObserver(function () { renderSrNo(table); });
      obs.observe(tbody, { childList: true, subtree: false });
      tbody.dataset.srnoObserved = "true";
    });
  }

  function hasDateTimeHeader(table) {
    var heads = table.querySelectorAll("thead tr th");
    for (var i = 0; i < heads.length; i++) {
      var txt = String(heads[i].textContent || "").trim().toLowerCase();
      if (txt === "date & time" || txt === "date/time" || txt === "datetime") return true;
    }
    return false;
  }

  function nowDateTimeText() {
    return new Date().toLocaleString("en-IN");
  }

  function isLeaveDetailsTable(table) {
    if (!table || !table.querySelector) return false;
    return !!table.querySelector("tbody#leaveDetailsBody");
  }
  function isBillingTable(table) {
    if (!table || !table.querySelector) return false;
    if (table.querySelector("tbody#billingTbody")) return true;
    var heads = table.querySelectorAll("thead tr th");
    var hasInvoice = false;
    var hasBillingMonth = false;
    var hasDueDate = false;
    for (var i = 0; i < heads.length; i++) {
      var txt = String(heads[i].textContent || "").trim().toLowerCase();
      if (txt === "invoice no") hasInvoice = true;
      if (txt === "billing month") hasBillingMonth = true;
      if (txt === "due date") hasDueDate = true;
    }
    return hasInvoice && hasBillingMonth && hasDueDate;
  }

  function sourceDateTimeFromRow(table, tr, strictTimestampOnly) {
    if (!table || !tr) return "";
    var ths = table.querySelectorAll("thead tr th");
    var tds = tr.querySelectorAll("td");
    if (!ths.length || !tds.length) return "";

    var bestIdx = -1;
    var bestScore = -1;
    function scoreHeader(txt) {
      var t = String(txt || "").trim().toLowerCase();
      if (!t) return -1;
      if (t.indexOf("created on") >= 0 || t.indexOf("generated on") >= 0) return 100;
      if (t.indexOf("created") >= 0 || t.indexOf("generated") >= 0) return 90;
      if (t.indexOf("updated") >= 0) return 80;
      if (strictTimestampOnly) return -1;
      if (t.indexOf("date & time") >= 0 || t.indexOf("date/time") >= 0 || t.indexOf("datetime") >= 0) return 70;
      if (t.indexOf("date") >= 0 || t.indexOf("time") >= 0) return 60;
      return -1;
    }

    for (var i = 0; i < ths.length; i++) {
      var s = scoreHeader(ths[i].textContent || "");
      if (s > bestScore) {
        bestScore = s;
        bestIdx = i;
      }
    }
    if (bestIdx < 0 || bestIdx >= tds.length) return "";
    return String(tds[bestIdx].textContent || "").trim();
  }

  function formatDateDMY(value) {
    var raw = String(value || "").trim();
    if (!raw) return "";

    // Prefer native parse first for ambiguous slash dates like 3/6/2026
    // so we can normalize consistently to DD/MM/YYYY.
    var parsed = new Date(raw);
    if (!Number.isNaN(parsed.getTime())) {
      return new Intl.DateTimeFormat("en-GB", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric"
      }).format(parsed);
    }

    // yyyy-mm-dd -> dd/mm/yyyy
    var iso = raw.match(/(\d{4})-(\d{1,2})-(\d{1,2})/);
    if (iso) {
      var y = iso[1];
      var m = String(iso[2]).padStart(2, "0");
      var d = String(iso[3]).padStart(2, "0");
      return d + "/" + m + "/" + y;
    }

    // d/m/yyyy or dd/mm/yyyy -> dd/mm/yyyy
    var dmy = raw.match(/(\d{1,2})\/(\d{1,2})\/(\d{4})/);
    if (dmy) {
      var dd = String(dmy[1]).padStart(2, "0");
      var mm = String(dmy[2]).padStart(2, "0");
      var yy = dmy[3];
      return dd + "/" + mm + "/" + yy;
    }

    return raw;
  }

  function renderDateTimeColumn(table) {
    if (!table) return;
    if (table.dataset.noDatetime === "true" || isLeaveDetailsTable(table) || isBillingTable(table)) {
      var noDtHead = table.querySelectorAll("thead tr th.dt-auto-head");
      Array.prototype.forEach.call(noDtHead, function (th) { th.remove(); });
      var noDtCells = table.querySelectorAll("tbody tr td.dt-auto-cell");
      Array.prototype.forEach.call(noDtCells, function (td) { td.remove(); });
      return;
    }
    if (table.classList.contains("cal-table") || table.id === "calTable") return;
    if (table.dataset.dtRendering === "1") return;
    table.dataset.dtRendering = "1";
    try {
      var theadRow = table.querySelector("thead tr");
      var tbody = table.querySelector("tbody");
      if (!theadRow || !tbody) return;

      var targetIndex = 2; // 3rd column
      var thNodes = theadRow.querySelectorAll("th");
      var dtHead = null;
      var hadExistingDateHead = false;
      var sourceDateColIndex = -1;
      for (var h = 0; h < thNodes.length; h++) {
        var htxt = String(thNodes[h].textContent || "").trim().toLowerCase();
        if (htxt === "date & time" || htxt === "date/time" || htxt === "datetime" || thNodes[h].classList.contains("dt-auto-head")) {
          dtHead = thNodes[h];
          hadExistingDateHead = true;
          sourceDateColIndex = h;
          break;
        }
      }
      if (!dtHead) {
        dtHead = document.createElement("th");
        dtHead.textContent = "Date & Time";
        dtHead.className = "dt-auto-head";
      } else {
        dtHead.classList.add("dt-auto-head");
      }
      var headRef = theadRow.children[targetIndex] || null;
      if (headRef !== dtHead) theadRow.insertBefore(dtHead, headRef);

      Array.prototype.forEach.call(tbody.querySelectorAll("tr"), function (tr) {
        var cells = tr.querySelectorAll("td");
        if (!cells.length) return;

        if (cells.length === 1 && cells[0].hasAttribute("colspan")) {
          // Increase colspan only when a brand-new Date & Time column is injected.
          if (!hadExistingDateHead) {
            var span = Number(cells[0].getAttribute("colspan") || 1);
            cells[0].setAttribute("colspan", String(Math.max(1, span + 1)));
          }
          return;
        }

        var dtCell = tr.querySelector("td.dt-auto-cell");
        if (!dtCell && hadExistingDateHead && sourceDateColIndex >= 0 && sourceDateColIndex < tr.children.length) {
          var sourceCell = tr.children[sourceDateColIndex];
          if (sourceCell && sourceCell.tagName === "TD") {
            dtCell = sourceCell;
            dtCell.classList.add("dt-auto-cell");
            if (!String(dtCell.getAttribute("data-captured") || "").trim()) {
              var rawExisting = String(dtCell.textContent || "").trim();
              if (rawExisting) dtCell.setAttribute("data-captured", rawExisting);
            }
          }
        }
        if (!dtCell) {
          dtCell = document.createElement("td");
          dtCell.className = "dt-auto-cell";
        }
        var rowRef = tr.children[targetIndex] || null;
        if (rowRef !== dtCell) tr.insertBefore(dtCell, rowRef);
        var captured = String(dtCell.getAttribute("data-captured") || "").trim();
        if (!captured) {
          // For auto-injected Date & Time, use only real timestamp columns
          // (Created/Generated/Updated), not generic date fields like DOJ.
          var fromRow = sourceDateTimeFromRow(table, tr, !hadExistingDateHead);
          captured = fromRow || "";
          dtCell.setAttribute("data-captured", captured);
        }
        dtCell.textContent = formatDateDMY(captured || "");
      });
    } finally {
      table.dataset.dtRendering = "0";
    }
  }

  function initAutoDateTimeColumn() {
    var tables = document.querySelectorAll("table.table");
    Array.prototype.forEach.call(tables, function (table) {
      if (table.dataset.noDatetime === "true" || isLeaveDetailsTable(table) || isBillingTable(table)) return;
      if (table.classList.contains("cal-table") || table.id === "calTable") return;
      renderDateTimeColumn(table);
      var tbody = table.querySelector("tbody");
      if (!tbody || tbody.dataset.dtObserved === "true") return;
      var obs = new MutationObserver(function () { renderDateTimeColumn(table); });
      obs.observe(tbody, { childList: true, subtree: false });
      tbody.dataset.dtObserved = "true";
    });
  }

  function applyBorderedTables(root) {
    var scope = root && root.querySelectorAll ? root : document;
    var tables = scope.querySelectorAll("table");
    Array.prototype.forEach.call(tables, function (table) {
      if (!table.classList.contains("table-bordered")) table.classList.add("table-bordered");
      if (table.classList.contains("borderless")) table.classList.remove("borderless");
    });
  }

  function initBorderedTables() {
    applyBorderedTables(document);
    if (window.__hrBorderedTablesObserved) return;
    var obs = new MutationObserver(function (mutations) {
      for (var i = 0; i < mutations.length; i++) {
        var m = mutations[i];
        if (!m || !m.addedNodes) continue;
        for (var j = 0; j < m.addedNodes.length; j++) {
          var n = m.addedNodes[j];
          if (!n || n.nodeType !== 1) continue;
          if (n.matches && n.matches("table")) {
            applyBorderedTables(n.parentElement || document);
          } else if (n.querySelectorAll) {
            applyBorderedTables(n);
          }
        }
      }
    });
    obs.observe(document.body || document.documentElement, { childList: true, subtree: true });
    window.__hrBorderedTablesObserved = true;
  }

  function centerActionColumns(root) {
    var scope = root && root.querySelectorAll ? root : document;
    var tables = scope.querySelectorAll("table");
    Array.prototype.forEach.call(tables, function (table) {
      var headRow = table.querySelector("thead tr");
      if (!headRow) return;
      var ths = headRow.querySelectorAll("th");
      var actionIdx = -1;
      for (var i = 0; i < ths.length; i++) {
        var txt = String(ths[i].textContent || "").trim().toLowerCase();
        if (txt === "action" || txt === "actions") { actionIdx = i; break; }
      }
      if (actionIdx < 0) return;

      var actionTh = ths[actionIdx];
      if (actionTh) {
        actionTh.classList.remove("text-end", "text-start");
        actionTh.classList.add("text-center", "action-col");
      }

      var rows = table.querySelectorAll("tbody tr");
      Array.prototype.forEach.call(rows, function (tr) {
        var tds = tr.querySelectorAll("td");
        if (!tds.length) return;
        if (actionIdx >= tds.length) return;
        var td = tds[actionIdx];
        if (!td) return;
        td.classList.remove("text-end", "text-start");
        td.classList.add("text-center", "action-col");
      });
    });
  }

  function initCenterActionColumns() {
    centerActionColumns(document);
    if (window.__hrActionColsObserved) return;
    var obs = new MutationObserver(function (mutations) {
      for (var i = 0; i < mutations.length; i++) {
        var m = mutations[i];
        if (!m || !m.addedNodes) continue;
        for (var j = 0; j < m.addedNodes.length; j++) {
          var n = m.addedNodes[j];
          if (!n || n.nodeType !== 1) continue;
          if (n.matches && n.matches("table")) centerActionColumns(n.parentElement || document);
          else if (n.querySelectorAll) centerActionColumns(n);
        }
      }
    });
    obs.observe(document.body || document.documentElement, { childList: true, subtree: true });
    window.__hrActionColsObserved = true;
  }

  function applyGlobalTheme(theme) {
    var next = theme === "dark" ? "dark" : "light";
    document.documentElement.setAttribute("data-bs-theme", next);
    try { localStorage.setItem("hr_portal_theme", next); } catch (_e) {}
    var icons = document.querySelectorAll("#themeIcon");
    Array.prototype.forEach.call(icons, function (icon) {
      if (!icon) return;
      icon.className = next === "dark" ? "bi bi-sun" : "bi bi-moon";
    });
  }

  function initGlobalThemeToggle() {
    if (window.__hrThemeToggleBound) return;
    var saved = "light";
    try { saved = String(localStorage.getItem("hr_portal_theme") || "light").toLowerCase(); } catch (_e) {}
    applyGlobalTheme(saved === "dark" ? "dark" : "light");
    // Capture-phase listener ensures one consistent toggle even when
    // page-level scripts also attach their own theme handlers.
    document.addEventListener("click", function (ev) {
      var target = ev && ev.target && ev.target.closest
        ? ev.target.closest("#themeToggle, .theme-icon-btn, #themeIcon")
        : null;
      if (!target) return;
      if (ev.preventDefault) ev.preventDefault();
      if (ev.stopImmediatePropagation) ev.stopImmediatePropagation();
      if (ev.stopPropagation) ev.stopPropagation();
      var cur = String(document.documentElement.getAttribute("data-bs-theme") || "light").toLowerCase();
      applyGlobalTheme(cur === "dark" ? "light" : "dark");
    }, true);
    window.__hrThemeToggleBound = true;
  }

  function inferActionType(btn) {
    if (!btn) return "action";
    var txt = (
      (btn.getAttribute("title") || "") + " " +
      (btn.getAttribute("aria-label") || "") + " " +
      (btn.textContent || "")
    ).toLowerCase();
    var cls = String(btn.className || "").toLowerCase();
    if (txt.indexOf("delete") >= 0 || txt.indexOf("remove") >= 0 || txt.indexOf("clear") >= 0 || cls.indexOf("danger") >= 0) return "delete";
    if (txt.indexOf("edit") >= 0 || txt.indexOf("update") >= 0 || cls.indexOf("warning") >= 0) return "edit";
    if (txt.indexOf("view") >= 0 || txt.indexOf("preview") >= 0 || txt.indexOf("open") >= 0) return "view";
    if (txt.indexOf("download") >= 0 || txt.indexOf("export") >= 0 || txt.indexOf("csv") >= 0 || txt.indexOf("xlsx") >= 0 || txt.indexOf("excel") >= 0 || cls.indexOf("success") >= 0 || cls.indexOf("primary") >= 0) return "download";
    if (txt.indexOf("print") >= 0) return "print";
    if (txt.indexOf("approve") >= 0 || txt.indexOf("accept") >= 0) return "approve";
    if (txt.indexOf("reject") >= 0 || txt.indexOf("decline") >= 0) return "reject";
    return "action";
  }

  function actionIconClass(type) {
    if (type === "delete") return "bi-trash";
    if (type === "edit") return "bi-pencil";
    if (type === "view") return "bi-eye";
    if (type === "download") return "bi-file-earmark-excel";
    if (type === "print") return "bi-printer";
    if (type === "approve") return "bi-check2";
    if (type === "reject") return "bi-x-lg";
    return "bi-three-dots";
  }

  function actionBtnClass(type) {
    if (type === "delete" || type === "reject") return "btn-outline-danger";
    if (type === "edit" || type === "download") return "btn-outline-secondary";
    return "btn-outline-primary";
  }

  function normalizeActionButtons(root) {
    var scope = root && root.querySelectorAll ? root : document;
    var tables = scope.querySelectorAll("table");
    Array.prototype.forEach.call(tables, function (table) {
      var headRow = table.querySelector("thead tr");
      if (!headRow) return;
      var ths = headRow.querySelectorAll("th");
      var actionIdx = -1;
      for (var i = 0; i < ths.length; i++) {
        var txt = String(ths[i].textContent || "").trim().toLowerCase();
        if (txt === "action" || txt === "actions") { actionIdx = i; break; }
      }
      if (actionIdx < 0) return;
      var rows = table.querySelectorAll("tbody tr");
      Array.prototype.forEach.call(rows, function (tr) {
        var tds = tr.querySelectorAll("td");
        if (!tds.length || actionIdx >= tds.length) return;
        var cell = tds[actionIdx];
        if (!cell) return;
        cell.classList.add("action-col");
        var buttons = cell.querySelectorAll("button.btn, a.btn");
        if (!buttons.length) return;
        var wrap = cell.querySelector(".hr-action-btns");
        if (!wrap) {
          var existingWrap = cell.querySelector(".btn-group, .hr-action-pill-group, .d-inline-flex, .d-flex");
          if (existingWrap) {
            wrap = existingWrap;
          } else {
            wrap = document.createElement("div");
            while (cell.firstChild) wrap.appendChild(cell.firstChild);
            cell.appendChild(wrap);
          }
        }
        wrap.className = "btn-group hr-action-btns";
        buttons = wrap.querySelectorAll("button.btn, a.btn");
        Array.prototype.forEach.call(buttons, function (btn) {
          var type = inferActionType(btn);
          btn.classList.remove(
            "btn-outline-primary",
            "btn-outline-secondary",
            "btn-outline-success",
            "btn-outline-danger",
            "btn-primary",
            "btn-secondary",
            "btn-success",
            "btn-danger",
            "me-1",
            "me-2",
            "ms-1",
            "ms-2",
            "mx-1",
            "mx-2",
            "rounded",
            "rounded-pill",
            "hr-action-btn",
            "hr-action-view",
            "hr-action-download",
            "hr-action-edit",
            "hr-action-delete",
            "hr-action-print",
            "hr-action-approve",
            "hr-action-reject",
            "hr-action-action"
          );
          btn.classList.add("btn", "btn-sm", actionBtnClass(type), "hr-action-btn", "hr-action-" + type);
          var label = String(btn.getAttribute("title") || btn.getAttribute("aria-label") || btn.textContent || type).trim();
          btn.setAttribute("title", label);
          btn.setAttribute("aria-label", label);
          btn.innerHTML = '<i class="bi ' + actionIconClass(type) + '"></i>';
        });
      });
    });
  }

  function initNormalizeActionButtons() {
    normalizeActionButtons(document);
    if (window.__hrActionBtnsObserved) return;
    var obs = new MutationObserver(function (mutations) {
      for (var i = 0; i < mutations.length; i++) {
        var m = mutations[i];
        if (!m || !m.addedNodes) continue;
        for (var j = 0; j < m.addedNodes.length; j++) {
          var n = m.addedNodes[j];
          if (!n || n.nodeType !== 1) continue;
          if (n.matches && n.matches("table")) normalizeActionButtons(n.parentElement || document);
          else if (n.querySelectorAll) normalizeActionButtons(n);
        }
      }
    });
    obs.observe(document.body || document.documentElement, { childList: true, subtree: true });
    window.__hrActionBtnsObserved = true;
  }

  function normalizeCardsToGlass(root) {
    var scope = root && root.querySelectorAll ? root : document;
    var cards = scope.querySelectorAll(".card.shadow-sm.border-0");
    Array.prototype.forEach.call(cards, function (card) {
      card.classList.remove("card", "shadow-sm", "border-0");
      if (!card.classList.contains("glass")) card.classList.add("glass");
      if (!card.classList.contains("p-3")) card.classList.add("p-3");
      var bodies = card.querySelectorAll(":scope > .card-body");
      Array.prototype.forEach.call(bodies, function (body) {
        body.classList.add("p-0");
      });
    });
  }

  function initNormalizeCardsToGlass() {
    normalizeCardsToGlass(document);
    if (window.__hrCardsGlassObserved) return;
    var obs = new MutationObserver(function (mutations) {
      for (var i = 0; i < mutations.length; i++) {
        var m = mutations[i];
        if (!m || !m.addedNodes) continue;
        for (var j = 0; j < m.addedNodes.length; j++) {
          var n = m.addedNodes[j];
          if (!n || n.nodeType !== 1) continue;
          if (n.matches && n.matches(".card.shadow-sm.border-0")) normalizeCardsToGlass(n.parentElement || document);
          else if (n.querySelectorAll) normalizeCardsToGlass(n);
        }
      }
    });
    obs.observe(document.body || document.documentElement, { childList: true, subtree: true });
    window.__hrCardsGlassObserved = true;
  }

  function syncSidebarActiveNav() {
    var p = getPathPage();
    var currentPage = p.page;
    var aliasMap = {
      "client-view-loan.html": "client-loan.html",
      "super-admin-view-loan.html": "super-admin-loan.html"
    };
    if (aliasMap[currentPage]) currentPage = aliasMap[currentPage];
    if (!currentPage) {
      if (document.body) document.body.classList.add("hr-nav-ready");
      return;
    }
    var links = document.querySelectorAll(".sidebar a.nav-link[href], .offcanvas a.nav-link[href]");
    if (!links.length) {
      if (document.body) document.body.classList.add("hr-nav-ready");
      return;
    }

    Array.prototype.forEach.call(links, function (link) {
      var rawHref = String(link.getAttribute("href") || "").trim();
      if (!rawHref || rawHref === "#") {
        link.classList.remove("active");
        return;
      }
      var cleanHref = rawHref.split("#")[0].split("?")[0];
      var hrefPage = cleanHref.split("/").pop() || "";
      if (!hrefPage) {
        link.classList.remove("active");
        return;
      }
      if (hrefPage.toLowerCase() === currentPage) link.classList.add("active");
      else link.classList.remove("active");
    });

    if (document.body) document.body.classList.add("hr-nav-ready");
  }

  window.HRCommon.applyAutoSrNo = initAutoSrNo;
  function bindCommonActions() {
    var printBtns = document.querySelectorAll('[data-action="print-page"]');
    Array.prototype.forEach.call(printBtns, function (btn) {
      if (btn.dataset.boundClick === "true") return;
      btn.addEventListener("click", function () { window.print(); });
      btn.dataset.boundClick = "true";
    });
  }

  function setRealtimeMonthYearDefaults() {
    var now = new Date();
    var currentMonthNum = now.getMonth() + 1;
    var currentYear = String(now.getFullYear());

    function pickMonthValue(monthEl) {
      if (!monthEl) return "";
      var exact = String(currentMonthNum);
      var padded = String(currentMonthNum).padStart(2, "0");
      var opts = monthEl.options || [];
      for (var i = 0; i < opts.length; i++) {
        var v = String(opts[i].value || "").trim();
        if (v === exact || v === padded) return v;
      }
      return "";
    }

    function hasYearOption(yearEl, y) {
      var opts = yearEl.options || [];
      for (var i = 0; i < opts.length; i++) {
        var v = String(opts[i].value || opts[i].textContent || "").trim();
        if (v === y) return true;
      }
      return false;
    }

    function ensureYearOption(yearEl, y) {
      if (!yearEl || hasYearOption(yearEl, y)) return;
      var opt = document.createElement("option");
      opt.value = y;
      opt.textContent = y;
      yearEl.insertBefore(opt, yearEl.firstChild || null);
    }

    function applyPair(monthId, yearId) {
      var monthEl = document.getElementById(monthId);
      var yearEl = document.getElementById(yearId);
      if (!monthEl || !yearEl) return;

      var monthSelected = monthEl.options && monthEl.selectedIndex >= 0 ? monthEl.options[monthEl.selectedIndex] : null;
      var yearSelected = yearEl.options && yearEl.selectedIndex >= 0 ? yearEl.options[yearEl.selectedIndex] : null;

      var monthValue = String(monthEl.value || "").trim();
      var yearValue = String(yearEl.value || "").trim();

      var setMonth = !monthValue || (monthSelected && monthSelected.disabled);
      var setYear = !yearValue || (yearSelected && yearSelected.disabled);

      if (setYear) ensureYearOption(yearEl, currentYear);
      if (setMonth) {
        var targetMonth = pickMonthValue(monthEl);
        if (targetMonth) monthEl.value = targetMonth;
      }
      if (setYear) yearEl.value = currentYear;
    }

    applyPair("monthSel", "yearSel");
    applyPair("bulkMonth", "bulkYear");
  }
  function ensureSuperAdminHeaderActions() {
    if (!isSuperAdminFolder()) return;
    var topbar = document.querySelector(".topbar .topbar-inner");
    if (!topbar) return;

    // Remove only heavy notification dropdown widgets (keep compact bell).
    var legacyNotif = topbar.querySelectorAll('.dropdown .icon-btn[aria-label="Notifications"]');
    Array.prototype.forEach.call(legacyNotif, function (btn) {
      var dd = btn.closest(".dropdown");
      if (dd && dd.parentElement) dd.parentElement.removeChild(dd);
    });

    // Reuse existing controls to avoid breaking page-specific JS listeners.
    var themeBtn = topbar.querySelector("#themeToggle");
    if (!themeBtn) {
      themeBtn = document.createElement("button");
      themeBtn.type = "button";
      themeBtn.id = "themeToggle";
      themeBtn.className = "btn theme-icon-btn";
      themeBtn.setAttribute("aria-label", "Toggle theme");
      themeBtn.innerHTML = '<i class="bi bi-moon" id="themeIcon"></i>';
      topbar.appendChild(themeBtn);
    }

    var bellBtn = topbar.querySelector('.profile-icon-btn[aria-label="Notifications"]');
    if (!bellBtn) {
      bellBtn = document.createElement("button");
      bellBtn.type = "button";
      bellBtn.className = "btn profile-icon-btn";
      bellBtn.setAttribute("aria-label", "Notifications");
      bellBtn.innerHTML = '<i class="bi bi-bell"></i>';
      topbar.appendChild(bellBtn);
    }

    var accountBtn = topbar.querySelector('button[aria-label="Account menu"]');
    var accountDrop = accountBtn ? accountBtn.closest(".dropdown") : null;
    if (!accountDrop) {
      accountDrop = document.createElement("div");
      accountDrop.className = "dropdown";
      accountDrop.innerHTML = [
        '<button class="btn profile-icon-btn" data-bs-toggle="dropdown" aria-label="Account menu">',
        '  <i class="bi bi-person-circle"></i>',
        '</button>',
        '<ul class="dropdown-menu dropdown-menu-end">',
        '  <li><a class="dropdown-item" href="super-admin-profile.html"><i class="bi bi-building me-2"></i>Profile</a></li>',
        '  <li><a class="dropdown-item" href="#"><i class="bi bi-headset me-2"></i>Support</a></li>',
        '  <li><hr class="dropdown-divider"></li>',
        '  <li><a class="dropdown-item" href="super-admin-logout.html"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>',
        '</ul>'
      ].join("");
      topbar.appendChild(accountDrop);
    }

    var mobileToggle = topbar.querySelector('button[data-bs-target="#mobileSidebar"]');
    var titleBlock = topbar.querySelector(".me-auto");
    var picker = topbar.querySelector("#hrClientPickerWrap");

    // Remove extra direct header action buttons (export/generate/bulk etc).
    Array.prototype.slice.call(topbar.children).forEach(function (child) {
      if (child === mobileToggle || child === titleBlock || child === picker || child === themeBtn || child === bellBtn || child === accountDrop) return;
      if (child.classList && child.classList.contains("dropdown")) return;
      var isActionBtn = child.matches && child.matches("button.btn, a.btn, .icon-btn");
      if (isActionBtn) topbar.removeChild(child);
    });

    // Ensure right-side order.
    if (themeBtn.parentElement === topbar && bellBtn.parentElement === topbar && accountDrop.parentElement === topbar) {
      topbar.appendChild(themeBtn);
      topbar.appendChild(bellBtn);
      topbar.appendChild(accountDrop);
    }
  }
  function ensureClientHeaderActions() {
    if (!isClientFolder()) return;
    var p = getPathPage();
    if (isPublicPage(p.page)) return;

    var topbar = document.querySelector(".topbar .topbar-inner");
    if (!topbar) return;

    // Remove legacy notification dropdown/button widgets.
    var legacyNotif = topbar.querySelectorAll('.dropdown .icon-btn[aria-label="Notifications"], .icon-btn[aria-label="Notifications"]');
    Array.prototype.forEach.call(legacyNotif, function (btn) {
      var dd = btn.closest(".dropdown");
      if (dd && dd.parentElement) dd.parentElement.removeChild(dd);
      else if (btn.parentElement) btn.parentElement.removeChild(btn);
    });

    var themeBtn = topbar.querySelector("#themeToggle");
    if (!themeBtn) {
      themeBtn = document.createElement("button");
      themeBtn.type = "button";
      themeBtn.id = "themeToggle";
      themeBtn.className = "btn theme-icon-btn";
      themeBtn.setAttribute("aria-label", "Toggle theme");
      themeBtn.innerHTML = '<i class="bi bi-moon" id="themeIcon"></i>';
      topbar.appendChild(themeBtn);
    }

    var isPayslipsPage = String(p.page || "").toLowerCase() === "client-payslips.html";
    var bellBtn = topbar.querySelector('.profile-icon-btn[aria-label="Notifications"]');
    if (!isPayslipsPage && !bellBtn) {
      bellBtn = document.createElement("button");
      bellBtn.type = "button";
      bellBtn.className = "btn profile-icon-btn";
      bellBtn.setAttribute("aria-label", "Notifications");
      bellBtn.innerHTML = '<i class="bi bi-bell"></i>';
      topbar.appendChild(bellBtn);
    }
    if (isPayslipsPage && bellBtn && bellBtn.parentElement) {
      bellBtn.parentElement.removeChild(bellBtn);
      bellBtn = null;
    }

    var accountBtn = topbar.querySelector('button[aria-label="Account menu"]');
    var accountDrop = accountBtn ? accountBtn.closest(".dropdown") : null;
    if (!accountDrop) {
      accountDrop = document.createElement("div");
      accountDrop.className = "dropdown";
      accountDrop.innerHTML = [
        '<button class="btn profile-icon-btn" data-bs-toggle="dropdown" aria-label="Account menu">',
        '  <i class="bi bi-person-circle"></i>',
        '</button>',
        '<ul class="dropdown-menu dropdown-menu-end">',
        '  <li><a class="dropdown-item" href="client-profile.html"><i class="bi bi-building me-2"></i>Profile</a></li>',
        '  <li><a class="dropdown-item" href="#"><i class="bi bi-headset me-2"></i>Support</a></li>',
        '  <li><hr class="dropdown-divider"></li>',
        '  <li><a class="dropdown-item" href="client-logout.html"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>',
        '</ul>'
      ].join("");
      topbar.appendChild(accountDrop);
    }

    var mobileToggle = topbar.querySelector('button[data-bs-target="#mobileSidebar"]');
    var titleBlock = topbar.querySelector(".me-auto");
    var clientPicker = topbar.querySelector("#hrClientPickerWrap");

    // Remove extra direct action buttons from header (Export/Bulk/Generate etc.).
    Array.prototype.slice.call(topbar.children).forEach(function (child) {
      if (child === mobileToggle || child === titleBlock || child === clientPicker || child === themeBtn || child === bellBtn || child === accountDrop) return;
      if (child.classList && child.classList.contains("dropdown")) return;
      var isActionBtn = child.matches && child.matches("button.btn, a.btn, .icon-btn");
      if (isActionBtn) topbar.removeChild(child);
    });

    topbar.appendChild(themeBtn);
    if (bellBtn) topbar.appendChild(bellBtn);
    topbar.appendChild(accountDrop);
  }
  function initClientHeaderQuickTools() {
    if (!isClientFolder()) return;
    var p = getPathPage();
    if (isPublicPage(p.page)) return;

    var topbar = document.querySelector(".topbar .topbar-inner");
    if (!topbar) return;
    if (document.getElementById("monthSelect")) return; // keep page-specific controls (dashboard)
    if (document.getElementById("hrClientQuickTools")) return;

    var themeBtn = topbar.querySelector("#themeToggle");
    var wrap = document.createElement("div");
    wrap.id = "hrClientQuickTools";
    wrap.className = "hr-client-header-tools d-none d-md-flex align-items-center gap-2 me-2";

    var now = new Date();
    var y = now.getFullYear();
    var m = now.getMonth() + 1;
    var storageKey = "hr_client_topbar_period_v1";
    var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

    var options = [];
    for (var i = 1; i <= 12; i++) {
      var val = String(y) + "-" + String(i).padStart(2, "0");
      options.push('<option value="' + val + '">' + months[i - 1] + " " + y + "</option>");
    }

    wrap.innerHTML = [
      '<select class="form-select hr-month-select" id="hrTopbarMonthSelect" aria-label="Month period">',
      options.join(""),
      "</select>",
      '<div class="dropdown">',
      '  <button class="btn hr-quick-actions-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">',
      '    <i class="bi bi-lightning-charge"></i> Quick Actions',
      "  </button>",
      '  <ul class="dropdown-menu dropdown-menu-end hr-quick-actions-menu">',
      '    <li><a class="dropdown-item" href="scan-attendance.php"><i class="bi bi-camera-video me-2"></i>Scan Attendance</a></li>',
      '    <li><a class="dropdown-item" href="client-attendance.html"><i class="bi bi-calendar2-check me-2"></i>Attendance</a></li>',
      '    <li><a class="dropdown-item" href="client-shift-roster.html"><i class="bi bi-calendar-week me-2"></i>Shift Roster</a></li>',
      '    <li><a class="dropdown-item" href="client-payroll-calc.html"><i class="bi bi-calculator me-2"></i>Salary Sheet</a></li>',
      '    <li><a class="dropdown-item" href="client-payslips.html"><i class="bi bi-receipt me-2"></i>Payslips</a></li>',
      '    <li><a class="dropdown-item" href="client-pf-sheet.html"><i class="bi bi-file-earmark-spreadsheet me-2"></i>PF Sheet</a></li>',
      '    <li><a class="dropdown-item" href="client-esic-sheet.html"><i class="bi bi-file-earmark-spreadsheet me-2"></i>ESIC Sheet</a></li>',
      "  </ul>",
      "</div>"
    ].join("");

    if (themeBtn && themeBtn.parentElement === topbar) topbar.insertBefore(wrap, themeBtn);
    else topbar.appendChild(wrap);

    var monthSel = document.getElementById("hrTopbarMonthSelect");
    if (!monthSel) return;
    var selected = "";
    try { selected = String(localStorage.getItem(storageKey) || "").trim(); } catch (_e) {}
    if (!selected || !/^\d{4}-\d{2}$/.test(selected)) {
      selected = String(y) + "-" + String(m).padStart(2, "0");
    }
    if (monthSel.querySelector('option[value="' + selected + '"]')) monthSel.value = selected;

    function emitPeriodChanged(period) {
      var parts = String(period || "").split("-");
      if (parts.length !== 2) return;
      var yy = Number(parts[0]);
      var mm = Number(parts[1]);
      if (!yy || !mm || mm < 1 || mm > 12) return;
      var label = months[mm - 1] + " " + yy;
      try { localStorage.setItem(storageKey, period); } catch (_e) {}
      try {
        window.dispatchEvent(new CustomEvent("hr:period-changed", {
          detail: { period: period, year: yy, month: mm, label: label }
        }));
      } catch (_e) {}
    }

    emitPeriodChanged(monthSel.value);
    monthSel.addEventListener("change", function () {
      emitPeriodChanged(monthSel.value);
    });
  }

  function bindAuthNavigationGuards() {
    if (window.__hrAuthNavGuardBound) return;

    // Handles browser back/forward cache restores after logout.
    window.addEventListener("pageshow", function () {
      enforceAuth();
    });

    // Handles explicit history navigation after session is cleared.
    window.addEventListener("popstate", function () {
      enforceAuth();
    });

    window.__hrAuthNavGuardBound = true;
  }

  function disableBFCacheForProtectedPages() {
    var p = getPathPage();
    if (isPublicPage(p.page)) return;
    if (window.__hrNoBFCacheBound) return;
    // No-op unload handler discourages BFCache restore in many browsers.
    window.addEventListener("unload", function () {});
    window.__hrNoBFCacheBound = true;
  }

  function pageNeedsSalaryRefreshReload() {
    var p = getPathPage();
    var page = String(p.page || "").toLowerCase();
    var dependent = {
      "client-payroll-calc.html": true,
      "super-admin-payroll-calc.html": true,
      "client-payslips.html": true,
      "super-admin-payslips.html": true,
      "client-pf-sheet.html": true,
      "super-admin-pf-sheet.html": true,
      "client-esic-sheet.html": true,
      "super-admin-esic-sheet.html": true,
      "client-ecr-sheet.html": true,
      "super-admin-ecr-sheet.html": true,
      "employee-profile.html": true
    };
    return dependent[page] === true;
  }

  function bindSalaryDataVersionSync() {
    if (window.__hrSalarySyncBound) return;
    var onChanged = function () {
      try { window.HRCommon.clearSalaryDependentCaches(); } catch (_e) {}
      if (pageNeedsSalaryRefreshReload()) {
        setTimeout(function () { window.location.reload(); }, 50);
      }
    };
    window.addEventListener("storage", function (ev) {
      if (!ev || ev.key !== KEY_SALARY_DATA_VERSION) return;
      onChanged();
    });
    window.addEventListener("hr:salary-data-changed", onChanged);
    window.__hrSalarySyncBound = true;
  }

  function setHeaderLoadingState(isLoading) {
    var p = getPathPage();
    if (isPublicPage(p.page)) return;
    if (!document.body) return;
    if (isLoading) document.body.classList.add("hr-header-loading");
    else document.body.classList.remove("hr-header-loading");
  }
  function setClientContentLoadingState(isLoading) {
    var p = getPathPage();
    if (isPublicPage(p.page)) return;
    if (!isClientFolder()) return;
    if (!document.body) return;
    if (isLoading) document.body.classList.add("hr-content-loading");
    else document.body.classList.remove("hr-content-loading");
  }

  function clearOrphanedBootstrapBackdrops() {
    if (!document.body) return;
    var hasOpenModal = !!document.querySelector(".modal.show");
    var hasOpenOffcanvas = !!document.querySelector(".offcanvas.show");
    if (!hasOpenModal) {
      document.querySelectorAll(".modal-backdrop").forEach(function (el) { el.remove(); });
    }
    if (!hasOpenOffcanvas) {
      document.querySelectorAll(".offcanvas-backdrop").forEach(function (el) { el.remove(); });
    }
    if (!hasOpenModal && !hasOpenOffcanvas) {
      document.body.classList.remove("modal-open");
      document.body.style.removeProperty("overflow");
      document.body.style.removeProperty("padding-right");
    }
  }

  async function bootstrapSharedUi() {
    setHeaderLoadingState(true);
    setClientContentLoadingState(true);
    try {
      installCustomAlert();
      enforceAuth();
      disableBFCacheForProtectedPages();
      bindAuthNavigationGuards();
      bindSalaryDataVersionSync();
      initGlobalThemeToggle();
      enforceSuperAdminPages();
      enforceAccess();
      normalizeSuperAdminSidebar();
      normalizeClientSidebar();
      applySuperAdminSidebarIdentity();
      applyClientSidebarIdentity();
      applyAccessVisibility();
      initNormalizeCardsToGlass();
      await initSuperAdminClientPicker();
      ensureSuperAdminHeaderActions();
      ensureClientHeaderActions();
      ensureSuperAdminRolesLink();
      initClientHeaderQuickTools();
      // Re-apply after header normalization so newly injected theme icons/buttons
      // always reflect current mode.
      applyGlobalTheme(String(document.documentElement.getAttribute("data-bs-theme") || "light"));
      syncSidebarActiveNav();
      initCenterActionColumns();
      initNormalizeActionButtons();
      initBorderedTables();
      initAutoSrNo();
      initAutoDateTimeColumn();
      setRealtimeMonthYearDefaults();
      bindCommonActions();
      clearOrphanedBootstrapBackdrops();
      window.HRCommon.syncControlSettings();
    } finally {
      setHeaderLoadingState(false);
      setClientContentLoadingState(false);
      clearOrphanedBootstrapBackdrops();
    }
  }

  // Run once immediately to avoid first-paint flicker from hardcoded active classes.
  syncSidebarActiveNav();

  installTenantFetch();
  setClientContentLoadingState(true);
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () { bootstrapSharedUi(); });
  } else {
    bootstrapSharedUi();
  }
  window.addEventListener("pageshow", clearOrphanedBootstrapBackdrops);
  window.addEventListener("focus", clearOrphanedBootstrapBackdrops);
})();

