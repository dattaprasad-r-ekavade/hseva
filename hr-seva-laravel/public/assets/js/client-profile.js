document.getElementById("yr").textContent = new Date().getFullYear();

  const htmlEl = document.documentElement;
  const themeToggle = document.getElementById("themeToggle");
  const themeIcon = document.getElementById("themeIcon");
  const themeText = document.getElementById("themeText");

  function applyTheme(theme){
    htmlEl.setAttribute("data-bs-theme", theme);
    localStorage.setItem("hr_portal_theme", theme);
    const isDark = theme === "dark";
    themeIcon.className = isDark ? "bi bi-sun" : "bi bi-moon";
    if (themeText) themeText.textContent = "";
  }
  applyTheme(localStorage.getItem("hr_portal_theme") || "light");
  themeToggle.addEventListener("click", () => {
    const current = htmlEl.getAttribute("data-bs-theme") || "light";
    applyTheme(current === "dark" ? "light" : "dark");
  });

  const API_PROFILE = "/api/profile";
  const API_PROFILE_RESET = "/api/profile/reset";
  const API_CONTROL = "/api/control";
  const API_CONTROL_RESET = "/api/control/reset";
  const KEY_PROFILE = "hr_client_profile_v1";
  const KEY_CONTROL = "hr_client_control_v1";

  function el(id){ return document.getElementById(id); }
  function safeParse(s){ try { return JSON.parse(s); } catch(_e){ return null; } }

  const DEFAULTS = {
    companyName: "",
    companyAddress: "",
    city: "",
    state: "",
    pincode: "",
    country: "",
    website: "",
    regNo: "",
    pan: "",
    tan: "",
    gstin: "",
    pfEstId: "",
    esicCode: "",
    contactName: "",
    contactNo: "",
    email: "",
    altContactNo: "",
    notes: ""
  };

  const F = {
    companyName: el("companyName"),
    companyAddress: el("companyAddress"),
    city: el("city"),
    state: el("state"),
    pincode: el("pincode"),
    country: el("country"),
    website: el("website"),
    regNo: el("regNo"),
    pan: el("pan"),
    tan: el("tan"),
    gstin: el("gstin"),
    pfEstId: el("pfEstId"),
    esicCode: el("esicCode"),
    contactName: el("contactName"),
    contactNo: el("contactNo"),
    email: el("email"),
    altContactNo: el("altContactNo"),
    notes: el("notes"),
  };

  function initials(str){
    const parts = String(str || "").split(/\s+/).filter(Boolean);
    const a = (parts[0] || "C")[0];
    const b = (parts[1] || parts[0] || "O")[0];
    return (a + b).toUpperCase();
  }

  function setHeader(){
    const name = (F.companyName.value || "").trim();
    el("headerCompany").textContent = name || "Company";
    el("avatarTxt").textContent = initials(name || "Company");
    el("headerLocation").textContent = [F.city.value, F.state.value].filter(Boolean).join(", ") || "-";
  }

  function setLastSaved(v){
    const n = el("lastSavedSide");
    if(n) n.textContent = v ? new Date(v).toLocaleString() : "-";
  }

  function toPayload(){
    const payload = {};
    Object.keys(DEFAULTS).forEach((k) => payload[k] = (F[k]?.value ?? "").toString());
    return payload;
  }

  function fillForm(data){
    const use = Object.assign({}, DEFAULTS, data || {});
    Object.keys(DEFAULTS).forEach((k) => {
      if(F[k]) F[k].value = use[k] ?? DEFAULTS[k];
    });
    setHeader();
    setLastSaved(use.__lastSaved || null);
  }

  function validate(){
    const issues = [];
    if(!(F.companyName.value || "").trim()) issues.push("Company Name is required");
    if(!(F.companyAddress.value || "").trim()) issues.push("Company Address is required");
    const pin = (F.pincode.value || "").trim();
    if(pin && !/^\d{6}$/.test(pin)) issues.push("Pincode should be 6 digits");
    const gst = (F.gstin.value || "").trim();
    if(gst && gst.length < 10) issues.push("GSTIN looks too short");
    const pan = (F.pan.value || "").trim();
    if(pan && pan.length < 8) issues.push("PAN looks too short");
    if(issues.length){
      alert("Please fix:\n\n- " + issues.join("\n- "));
      return false;
    }
    alert("Validation passed");
    return true;
  }

  async function apiGetProfile(){
    const res = await fetch(API_PROFILE, { cache: "no-store" });
    if(!res.ok) throw new Error("GET /api/profile failed");
    return await res.json();
  }

  async function apiSaveProfile(payload){
    const res = await fetch(API_PROFILE, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    if(!res.ok) throw new Error("PUT /api/profile failed");
    return await res.json();
  }

  async function apiResetProfile(){
    const res = await fetch(API_PROFILE_RESET, { method: "POST" });
    if(!res.ok) throw new Error("POST /api/profile/reset failed");
    return await res.json();
  }

  async function apiPost(path, payload){
    const res = await fetch(path, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: payload ? JSON.stringify(payload) : "{}"
    });
    if(!res.ok) throw new Error(`POST ${path} failed`);
    return await res.json();
  }

  async function resetAllData(){
    if(!confirm("This will clear all portal data (employees, leaves, attendance, salary sheets, PF/ESIC/ECR, FNF, payslips, compliance tasks) and reset profile/control defaults.\n\nContinue?")) return;
    const btn = el("btnResetAll");
    const prev = btn ? btn.innerHTML : "";
    if(btn){ btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Resetting...'; }
    try {
      // Reset all server-side modules
      await Promise.allSettled([
        apiPost("/api/employees/clear"),
        apiPost("/api/leaves/clear"),
        apiPost("/api/attendance/clear"),
        apiPost("/api/payroll/clear"),
        apiPost("/api/pf-sheet/clear"),
        apiPost("/api/pf-return/clear"),
        apiPost("/api/esic-sheet/clear"),
        apiPost("/api/esic-return/clear"),
        apiPost("/api/ecr-sheet/clear"),
        apiPost("/api/fnf/clear"),
        apiPost("/api/payslips/clear"),
        apiPost("/api/compliance/tasks/clear"),
        apiPost(API_CONTROL_RESET),
        apiPost(API_PROFILE_RESET)
      ]);

      // Clear browser-side cache keys but keep theme + auth session
      const keepTheme = localStorage.getItem("hr_portal_theme");
      const keepAuth = sessionStorage.getItem("hr_auth_session_v1");
      localStorage.clear();
      if(keepTheme !== null) localStorage.setItem("hr_portal_theme", keepTheme);
      if(keepAuth !== null) sessionStorage.setItem("hr_auth_session_v1", keepAuth);

      alert("All data has been reset successfully.");
      window.location.reload();
    } catch(_e){
      alert("Reset all failed. Please try again.");
    } finally {
      if(btn){ btn.disabled = false; btn.innerHTML = prev; }
    }
  }

  async function save(){
    if(!validate()) return;
    const payload = toPayload();
    try {
      const saved = await apiSaveProfile(payload);
      localStorage.setItem(KEY_PROFILE, JSON.stringify(saved));
      fillForm(saved);
      alert("Saved in API (SQLite)");
    } catch(_e){
      payload.__lastSaved = new Date().toISOString();
      localStorage.setItem(KEY_PROFILE, JSON.stringify(payload));
      fillForm(payload);
      alert("Saved in browser localStorage (offline mode)");
    }
  }

  async function loadFromControl(){
    let c = null;
    try {
      const res = await fetch(API_CONTROL, { cache: "no-store" });
      if(res.ok) c = await res.json();
    } catch(_e) {}
    if(!c){
      const raw = localStorage.getItem(KEY_CONTROL);
      c = raw ? safeParse(raw) : null;
    }
    if(!c){
      alert("No Control data found.");
      return;
    }
    F.companyName.value = c.companyName || F.companyName.value;
    F.companyAddress.value = c.companyAddress || F.companyAddress.value;
    F.regNo.value = c.companyRegNo || F.regNo.value;
    F.pan.value = c.companyPAN || F.pan.value;
    F.tan.value = c.companyTAN || F.tan.value;
    F.gstin.value = c.companyGSTIN || F.gstin.value;
    F.contactNo.value = c.companyContact || F.contactNo.value;
    setHeader();
    alert("Loaded from Control");
  }

  async function resetProfile(){
    try {
      const data = await apiResetProfile();
      localStorage.setItem(KEY_PROFILE, JSON.stringify(data));
      fillForm(data);
      alert("Reset from API defaults");
    } catch(_e){
      localStorage.removeItem(KEY_PROFILE);
      fillForm(DEFAULTS);
      alert("Reset to local defaults");
    }
  }

  el("btnSave").addEventListener("click", save);
  el("btnSaveTop").addEventListener("click", save);
  el("btnValidate").addEventListener("click", validate);
  el("btnLoadFromControl")?.addEventListener("click", loadFromControl);
  el("btnResetDemo")?.addEventListener("click", resetProfile);
  el("btnResetAll")?.addEventListener("click", resetAllData);
  ["companyName","city","state"].forEach((id) => el(id).addEventListener("input", setHeader));

  (async function init(){
    try {
      const data = await apiGetProfile();
      localStorage.setItem(KEY_PROFILE, JSON.stringify(data));
      fillForm(data);
    } catch(_e){
      const local = safeParse(localStorage.getItem(KEY_PROFILE));
      fillForm(local || DEFAULTS);
    }
  })();
