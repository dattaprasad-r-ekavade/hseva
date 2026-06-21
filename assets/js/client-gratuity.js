const $ = (id) => document.getElementById(id);
const htmlEl = document.documentElement;
const KEY_AUTH = "hr_auth_session_v1";
const KEY_SUPERADMIN_CLIENT_ID = "hr_superadmin_selected_client_id_v1";
const API_CONTROL = "/api/control";
const API_EMP = "/api/employees";
const API_GEN = "/api/gratuity/generate";
const API_LIST = "/api/gratuity/sheets";
const API_CLEAR = "/api/gratuity/clear";

let gratuityRows = [];
let employeeById = new Map();
let controlState = { gratuityMode: "after_5yr", gratuityMinYears: 5 };
let empSelectTs = null;

function applyTheme(theme){
  htmlEl.setAttribute("data-bs-theme", theme);
  localStorage.setItem("hr_portal_theme", theme);
  if ($("themeIcon")) $("themeIcon").className = theme === "dark" ? "bi bi-sun" : "bi bi-moon";
}
applyTheme(localStorage.getItem("hr_portal_theme") || "light");
$("themeToggle")?.addEventListener("click", () => {
  const current = htmlEl.getAttribute("data-bs-theme") || "light";
  applyTheme(current === "dark" ? "light" : "dark");
});

function safeParse(value, fallback = null){
  try { return JSON.parse(value); } catch(_e){ return fallback; }
}
function money(value){
  return Number(value || 0).toLocaleString("en-IN", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function fmtDateTime(value){
  const d = new Date(value || "");
  return Number.isNaN(d.getTime()) ? "-" : d.toLocaleString();
}
function yearsFromDoj(doj){
  const raw = String(doj || "").trim();
  if(!raw) return 0;
  const date = new Date(raw);
  if(Number.isNaN(date.getTime())) return 0;
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  if(diffMs <= 0) return 0;
  return Math.max(0, diffMs / (365.25 * 24 * 60 * 60 * 1000));
}
function authSession(){
  return safeParse(sessionStorage.getItem(KEY_AUTH), null);
}
function selectedClientId(){
  return Number(localStorage.getItem(KEY_SUPERADMIN_CLIENT_ID) || 0);
}
function isSuperAdminPage(){
  return String(window.location.pathname || "").toLowerCase().includes("/super-admin/");
}
function hasClientContext(){
  const auth = authSession();
  const tokenClientId = Number(auth?.user?.clientId || 0);
  if(tokenClientId > 0) return true;
  if(isSuperAdminPage()) return selectedClientId() > 0;
  return false;
}
function activeMode(){
  return String(controlState?.gratuityMode || "after_5yr").toLowerCase() === "monthly" ? "monthly" : "after_5yr";
}
function minServiceYears(){
  const raw = Number(controlState?.gratuityMinYears ?? 5);
  return Number.isFinite(raw) && raw >= 0 ? raw : 5;
}
function currentMonth(){
  return String(new Date().getMonth() + 1);
}
function currentYear(){
  return String(new Date().getFullYear());
}

function syncModeUi(){
  const mode = activeMode();
  const monthly = mode === "monthly";
  if($("modeBadge")) $("modeBadge").textContent = monthly ? "Monthly" : "After completion of 5yr";
  if($("modeFormula")) $("modeFormula").textContent = monthly
    ? "Monthly estimate = Basic x 4.81%"
    : "Final gratuity = ((Basic + DA) x 15 x Years) / 26";
  if($("employeeWrap")) $("employeeWrap").style.display = monthly ? "none" : "";
  if($("yearsWrap")) $("yearsWrap").style.display = monthly ? "none" : "";
  if($("monthWrap")) $("monthWrap").style.display = monthly ? "" : "none";
  if($("yearWrap")) $("yearWrap").style.display = monthly ? "" : "none";
  if($("yearsHint")) $("yearsHint").textContent = `Years auto-fetch from employee DOJ and must be more than ${String(minServiceYears()).replace(/\.0+$/,"")}.`;
  if(monthly){
    if($("monthInput")) $("monthInput").value = $("monthInput").value || currentMonth();
    if($("yearInput")) $("yearInput").value = $("yearInput").value || currentYear();
  }
}

function setScopeState(){
  const scoped = hasClientContext();
  const notice = $("clientScopeNotice");
  const form = $("gratuityForm");
  const clearBtn = $("btnClearAll");
  if(notice){
    notice.classList.toggle("d-none", scoped);
    notice.textContent = isSuperAdminPage()
      ? "Select a client from the top client picker to view or generate gratuity data."
      : "";
  }
  if(form){
    Array.from(form.querySelectorAll("input, select, button")).forEach((el) => { el.disabled = !scoped; });
  }
  if(clearBtn) clearBtn.disabled = !scoped;
  if(!scoped){
    if($("gratuityTable")) $("gratuityTable").innerHTML = `<tr><td colspan="8" class="text-center text-muted-3 py-4">Select a client to view gratuity records.</td></tr>`;
    if($("previewWrap")) $("previewWrap").classList.add("d-none");
  }
}

async function fetchJson(url, options){
  const res = await fetch(url, options);
  const data = await res.json();
  if(!res.ok) throw new Error(data?.detail || "Request failed");
  return data;
}

async function loadControl(){
  const data = await fetchJson(API_CONTROL, { headers: { Accept: "application/json" } });
  controlState = data || { gratuityMode: "after_5yr", gratuityMinYears: 5 };
  syncModeUi();
}

async function loadEmployees(){
  const data = await fetchJson(`${API_EMP}?activeOnly=1`, { headers: { Accept: "application/json" } });
  const rows = Array.isArray(data.rows) ? data.rows : [];
  employeeById = new Map(rows.map((r) => [String(r.id || "").toUpperCase(), r]));
  if($("empSelect")){
    $("empSelect").innerHTML = `<option value="">Search employee...</option>` + rows.map((r) => {
      const id = String(r.id || "").toUpperCase();
      const name = String(r.name || "");
      return `<option value="${id}">${id} - ${name}</option>`;
    }).join("");
  }
  if(empSelectTs){ empSelectTs.destroy(); empSelectTs = null; }
  if($("empSelect")){
    empSelectTs = new TomSelect("#empSelect", {
      create: false,
      sortField: { field: "text", direction: "asc" },
      maxOptions: 300,
      placeholder: "Search by Emp ID or Name..."
    });
  }
}

function autofillYearsFromEmployee(){
  const empId = String($("empSelect")?.value || "").toUpperCase();
  const emp = employeeById.get(empId);
  if(!emp || !$("yearsInput")) return;
  const years = yearsFromDoj(emp.doj || emp.joiningDate || "");
  $("yearsInput").value = years > 0 ? years.toFixed(2) : "";
}

function renderStats(){
  if($("statTotal")) $("statTotal").textContent = String(gratuityRows.length);
  if($("statMonthly")) $("statMonthly").textContent = String(gratuityRows.filter((r) => String(r.mode || "") === "monthly").length);
  if($("statFiveYear")) $("statFiveYear").textContent = String(gratuityRows.filter((r) => String(r.mode || "") === "after_5yr").length);
  if($("statAmount")) {
    const total = gratuityRows.reduce((sum, r) => sum + Number(r.totalAmount ?? r.gratuityAmount ?? 0), 0);
    $("statAmount").textContent = `Rs ${money(total)}`;
  }
}

function renderPreview(sheet){
  if(!$("previewWrap") || !$("previewTable")) return;
  const rows = Array.isArray(sheet?.rows) ? sheet.rows : [];
  if(!rows.length){
    $("previewWrap").classList.add("d-none");
    return;
  }
  $("previewWrap").classList.remove("d-none");
  if($("previewTitle")){
    $("previewTitle").textContent = activeMode() === "monthly"
      ? `Monthly preview for ${sheet.period || "-"}`
      : `Preview for ${sheet.employeeName || sheet.empId || "-"}`;
  }
  $("previewTable").innerHTML = rows.map((row, idx) => `
    <tr>
      <td>${idx + 1}</td>
      <td><div class="fw-semibold">${row.employeeName || "-"}</div><div class="small text-muted-3">${row.empId || "-"}</div></td>
      <td>Rs ${money(row.basic || 0)}</td>
      <td>Rs ${money(row.da || 0)}</td>
      <td class="fw-semibold">Rs ${money(row.gratuityAmount || 0)}</td>
    </tr>
  `).join("");
}

function renderSingleResult(sheet){
  if(!$("latestResult")) return;
  if(!sheet){
    $("latestResult").classList.add("d-none");
    return;
  }
  $("latestResult").classList.remove("d-none");
  if(activeMode() === "monthly"){
    $("latestResult").innerHTML = `
      <div class="fw-semibold mb-1">${sheet.period || "-"}</div>
      <div class="small text-muted-3 mb-2">${sheet.modeLabel || "-"}</div>
      <div class="row g-2">
        <div class="col-md-4"><div class="glass-soft p-2">Employees<br><span class="fw-semibold">${sheet.rows?.length || 0}</span></div></div>
        <div class="col-md-4"><div class="glass-soft p-2">Total Amount<br><span class="fw-semibold">Rs ${money(sheet.totalAmount || 0)}</span></div></div>
        <div class="col-md-4"><div class="glass-soft p-2">Generated On<br><span class="fw-semibold">${fmtDateTime(sheet.generatedAt)}</span></div></div>
      </div>
    `;
    return;
  }
  $("latestResult").innerHTML = `
    <div class="fw-semibold mb-1">${sheet.employeeName || sheet.empId || "-"}</div>
    <div class="small text-muted-3 mb-2">${sheet.modeLabel || "-"}</div>
    <div class="row g-2">
      <div class="col-md-4"><div class="glass-soft p-2">Basic<br><span class="fw-semibold">Rs ${money(sheet.basic || 0)}</span></div></div>
      <div class="col-md-4"><div class="glass-soft p-2">DA<br><span class="fw-semibold">Rs ${money(sheet.da || 0)}</span></div></div>
      <div class="col-md-4"><div class="glass-soft p-2">Gratuity<br><span class="fw-semibold">Rs ${money(sheet.gratuityAmount || 0)}</span></div></div>
    </div>
  `;
}

function renderRows(){
  renderStats();
  if(!hasClientContext()) return;
  if(!gratuityRows.length){
    $("gratuityTable").innerHTML = `<tr><td colspan="8" class="text-center text-muted-3 py-4">No gratuity records generated yet.</td></tr>`;
    return;
  }
  $("gratuityTable").innerHTML = gratuityRows.map((row, idx) => {
    const ref = row.mode === "monthly"
      ? `${row.period || "-"}`
      : `<div class="fw-semibold">${row.employeeName || "-"}</div><div class="small text-muted-3">${row.empId || "-"}</div>`;
    const periodYears = row.mode === "monthly"
      ? (row.period || "-")
      : (Number(row.years || 0) > 0 ? `${Number(row.years).toFixed(2)} years` : "-");
    const rowsCount = row.mode === "monthly" ? Number(row.rowCount || 0) : 1;
    const total = row.mode === "monthly" ? Number(row.totalAmount || 0) : Number(row.gratuityAmount || 0);
    return `
      <tr>
        <td>${idx + 1}</td>
        <td>${ref}</td>
        <td>${row.modeLabel || "-"}</td>
        <td>${periodYears}</td>
        <td>${rowsCount}</td>
        <td class="fw-semibold">Rs ${money(total)}</td>
        <td>${fmtDateTime(row.generatedAt)}</td>
        <td class="text-end d-flex justify-content-end gap-1">
          <button class="btn btn-sm btn-outline-primary" type="button" onclick="viewGratuityRow('${String(row.id || "")}')"><i class="bi bi-eye"></i></button>
          <button class="btn btn-sm btn-outline-danger" type="button" onclick="deleteGratuityRow('${String(row.id || "")}')"><i class="bi bi-trash"></i></button>
        </td>
      </tr>
    `;
  }).join("");
}

async function loadRows(){
  const data = await fetchJson(API_LIST, { headers: { Accept: "application/json" } });
  gratuityRows = Array.isArray(data.rows) ? data.rows : [];
  renderRows();
}

window.viewGratuityRow = async function(id){
  const row = gratuityRows.find((x) => String(x.id || "") === String(id || ""));
  try {
    if(!id) throw new Error("Gratuity record id is missing.");
    const data = await fetchJson(`${API_LIST}/${encodeURIComponent(id)}`, { headers: { Accept: "application/json" } });
    const sheet = data.sheet || null;
    if(!sheet) throw new Error("Gratuity record details are not available.");
    if(sheet.rows){
      renderPreview(sheet);
    } else {
      renderPreview({ rows: [sheet], employeeName: sheet.employeeName, empId: sheet.empId });
    }
    renderSingleResult(sheet);
    $("previewWrap")?.scrollIntoView({ behavior: "smooth", block: "start" });
    return;
  } catch (err) {
    if(row){
      renderSingleResult(row);
      if(String(row.mode || "") === "monthly"){
        $("previewWrap")?.classList.add("d-none");
      } else {
        renderPreview({ rows: [row], employeeName: row.employeeName, empId: row.empId });
      }
      $("latestResult")?.scrollIntoView({ behavior: "smooth", block: "start" });
      alert((err && err.message ? err.message + " Showing saved summary instead." : "Showing saved summary instead."));
      return;
    }
    alert(err?.message || "Unable to open gratuity record.");
  }
};

window.deleteGratuityRow = async function(id){
  if(!id || !confirm("Delete this gratuity record?")) return;
  await fetchJson(`${API_LIST}/${encodeURIComponent(id)}`, { method: "DELETE" });
  await loadRows();
  if($("previewWrap")) $("previewWrap").classList.add("d-none");
};

$("gratuityForm")?.addEventListener("submit", async (e) => {
  e.preventDefault();
  if(!hasClientContext()) return alert("Select a client first.");
  let payload = {};
  if(activeMode() === "monthly"){
    const month = Number($("monthInput")?.value || 0);
    const year = Number($("yearInput")?.value || 0);
    if(month < 1 || month > 12 || year < 2000) return alert("Enter valid month and year.");
    payload = { month, year };
  } else {
    const empId = String($("empSelect")?.value || "").toUpperCase();
    const years = Number($("yearsInput")?.value || 0);
    if(!empId) return alert("Select employee.");
    const minYears = minServiceYears();
    if(years <= minYears) return alert(`Years of service must be more than ${String(minYears).replace(/\.0+$/,"")}.`);
    payload = { empId, years };
  }
  const submitBtn = $("gratuityForm")?.querySelector('button[type="submit"]');
  const doneProcessing = window.HRCommon?.setProcessingState?.(submitBtn, {
    busyText: "Generating...",
    message: "Please wait, we are preparing the gratuity record."
  });
  try {
    const data = await fetchJson(API_GEN, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    const sheet = data.sheet || null;
    if(sheet){
      renderSingleResult(sheet);
      renderPreview(sheet.rows ? sheet : { rows: [sheet], employeeName: sheet.employeeName, empId: sheet.empId });
      doneProcessing?.("Gratuity record generated successfully.", false);
    }
    if(activeMode() !== "monthly"){
      if(empSelectTs) empSelectTs.clear(true);
      if($("yearsInput")) $("yearsInput").value = "";
    }
    await loadRows();
  } catch (err) {
    doneProcessing?.(err?.message || "Unable to generate gratuity record.", true);
    alert(err?.message || "Unable to generate gratuity record.");
  }
});

$("empSelect")?.addEventListener("change", autofillYearsFromEmployee);
$("btnClearAll")?.addEventListener("click", async () => {
  if(!hasClientContext()) return;
  if(!confirm("Clear all gratuity records for this client?")) return;
  await fetchJson(API_CLEAR, { method: "POST" });
  gratuityRows = [];
  renderRows();
  renderSingleResult(null);
  if($("previewWrap")) $("previewWrap").classList.add("d-none");
});

window.addEventListener("hr:selected-client-changed", () => {
  setScopeState();
  window.location.reload();
});

(async function init(){
  setScopeState();
  if(!hasClientContext()){
    syncModeUi();
    renderStats();
    return;
  }
  try {
    if($("monthInput")) $("monthInput").value = currentMonth();
    if($("yearInput")) $("yearInput").value = currentYear();
    await loadControl();
    await loadEmployees();
    await loadRows();
  } catch (e){
    alert(e.message || "Unable to load gratuity module.");
  }
})();
