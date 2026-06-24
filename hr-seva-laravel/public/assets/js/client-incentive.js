const $ = (id) => document.getElementById(id);
const htmlEl = document.documentElement;
const KEY_AUTH = "hr_auth_session_v1";
const KEY_SUPERADMIN_CLIENT_ID = "hr_superadmin_selected_client_id_v1";
const API_EMP = "/api/employees?activeOnly=1";
const API_INCENTIVES = "/api/incentives";
const API_INCENTIVE_CLEAR = "/api/incentives/clear";

let incentiveRows = [];
let employeeRows = [];

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
function todayIso(){
  const now = new Date();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2, "0");
  const d = String(now.getDate()).padStart(2, "0");
  return `${y}-${m}-${d}`;
}
function money(value){
  return Number(value || 0).toLocaleString("en-IN", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function fmtDateTime(value){
  const d = new Date(value || "");
  return Number.isNaN(d.getTime()) ? "-" : d.toLocaleString();
}
function esc(value){
  return String(value ?? "").replace(/[&<>"']/g, (ch) => (
    {"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[ch]
  ));
}

async function fetchJson(url, options){
  const res = await fetch(url, options);
  const data = await res.json();
  if(!res.ok) throw new Error(data?.detail || "Request failed");
  return data;
}

function setScopeState(){
  const scoped = hasClientContext();
  const notice = $("clientScopeNotice");
  const form = $("incentiveForm");
  if(notice){
    notice.classList.toggle("d-none", scoped);
    notice.textContent = isSuperAdminPage() ? "Select a client from the top client picker to manage incentives." : "";
  }
  if(form){
    Array.from(form.querySelectorAll("input, select, textarea, button")).forEach((el) => { el.disabled = !scoped; });
  }
  if($("btnClearAll")) $("btnClearAll").disabled = !scoped;
  if(!scoped && $("incentiveTable")){
    $("incentiveTable").innerHTML = `<tr><td colspan="7" class="text-center text-muted-3 py-4">Select a client to view incentive records.</td></tr>`;
  }
}

function renderEmployeeOptions(){
  const select = $("empId");
  if(!select) return;
  const current = select.value || "";
  select.innerHTML = '<option value="">Select employee</option>' + employeeRows.map((row) => {
    const empId = String(row.id || row.empId || "").toUpperCase();
    const name = String(row.name || row.empName || empId);
    return `<option value="${esc(empId)}">${esc(name)} (${esc(empId)})</option>`;
  }).join("");
  if(current) select.value = current;
}

function renderRows(){
  const tbody = $("incentiveTable");
  if(!tbody || !hasClientContext()) return;
  if(!incentiveRows.length){
    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted-3 py-4">No incentives created yet.</td></tr>`;
    if($("recordCount")) $("recordCount").textContent = "0";
    if($("totalAmount")) $("totalAmount").textContent = "Rs 0.00";
    return;
  }
  const totalAmount = incentiveRows.reduce((sum, row) => sum + Number(row.amount || 0), 0);
  if($("recordCount")) $("recordCount").textContent = String(incentiveRows.length);
  if($("totalAmount")) $("totalAmount").textContent = `Rs ${money(totalAmount)}`;
  tbody.innerHTML = incentiveRows.map((row, idx) => `
    <tr>
      <td>${idx + 1}</td>
      <td><div class="fw-semibold">${esc(row.employeeName || "-")}</div><div class="small text-muted-3">${esc(row.empId || "-")}</div></td>
      <td>${esc(row.incentiveDate || "-")}</td>
      <td class="fw-semibold text-end">Rs ${money(row.amount || 0)}</td>
      <td>${esc(row.remarks || "-")}</td>
      <td>${fmtDateTime(row.createdAt)}</td>
      <td class="text-end"><button class="btn btn-sm btn-outline-danger" type="button" onclick="deleteIncentiveRow('${String(row.id || "")}')"><i class="bi bi-trash"></i></button></td>
    </tr>
  `).join("");
}

async function loadEmployees(){
  const data = await fetchJson(API_EMP, { headers: { Accept: "application/json" } });
  employeeRows = Array.isArray(data.rows) ? data.rows : [];
  renderEmployeeOptions();
}

async function loadRows(){
  const data = await fetchJson(API_INCENTIVES, { headers: { Accept: "application/json" } });
  incentiveRows = Array.isArray(data.rows) ? data.rows : [];
  renderRows();
}

async function saveRow(event){
  event.preventDefault();
  const empId = String($("empId")?.value || "").toUpperCase();
  const incentiveDate = String($("incentiveDate")?.value || todayIso());
  const amount = Number($("amount")?.value || 0);
  const remarks = String($("remarks")?.value || "").trim();
  if(!empId) return alert("Select employee.");
  if(!incentiveDate) return alert("Date is required.");
  if(!Number.isFinite(amount) || amount <= 0) return alert("Enter a valid incentive amount.");
  const submitBtn = $("incentiveForm")?.querySelector('button[type="submit"]');
  const doneProcessing = window.HRCommon?.setProcessingState?.(submitBtn, {
    busyText: "Saving...",
    message: "Please wait, we are saving the incentive entry."
  });
  try {
    await fetchJson(API_INCENTIVES, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ empId, incentiveDate, amount, remarks })
    });
    $("amount").value = "";
    $("remarks").value = "";
    $("incentiveDate").value = todayIso();
    await loadRows();
    doneProcessing?.("Incentive saved successfully.", false);
  } catch (e){
    doneProcessing?.(e?.message || "Unable to save incentive.", true);
    alert(e?.message || "Unable to save incentive.");
  }
}

window.deleteIncentiveRow = async function(id){
  if(!id) return;
  if(!confirm("Delete this incentive entry?")) return;
  await fetchJson(`${API_INCENTIVES}/${encodeURIComponent(id)}`, { method: "DELETE" });
  await loadRows();
};

async function clearAll(){
  if(!confirm("Clear all incentive history?")) return;
  await fetchJson(API_INCENTIVE_CLEAR, { method: "POST" });
  incentiveRows = [];
  renderRows();
}

document.addEventListener("DOMContentLoaded", async () => {
  try {
    if($("incentiveDate") && !$("incentiveDate").value) $("incentiveDate").value = todayIso();
    setScopeState();
    if(hasClientContext()){
      await loadEmployees();
      await loadRows();
    }
    $("incentiveForm")?.addEventListener("submit", saveRow);
    $("btnClearAll")?.addEventListener("click", clearAll);
    window.addEventListener("hr:client-changed", async () => {
      setScopeState();
      if($("incentiveDate")) $("incentiveDate").value = todayIso();
      if(hasClientContext()){
        await loadEmployees();
        await loadRows();
      }
    });
  } catch (e){
    alert(e.message || "Unable to load incentive module.");
  }
});
