const $ = (id) => document.getElementById(id);
const htmlEl = document.documentElement;
const KEY_AUTH = "hr_auth_session_v1";
const KEY_SUPERADMIN_CLIENT_ID = "hr_superadmin_selected_client_id_v1";
const API_EMP = "/api/employees?activeOnly=1";
const API_LOANS = "/api/loans";

const state = {
  employees: [],
  loans: [],
  editingId: "",
  auth: null
};

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
  if (state.auth) return state.auth;
  state.auth = safeParse(sessionStorage.getItem(KEY_AUTH), null);
  return state.auth;
}
function selectedClientId(){
  return Number(localStorage.getItem(KEY_SUPERADMIN_CLIENT_ID) || 0);
}
function isSuperAdminPage(){
  return String(window.location.pathname || "").toLowerCase().includes("/super-admin/");
}
function currentRole(){
  return String(authSession()?.user?.role || "").toLowerCase();
}
function hasClientContext(){
  const auth = authSession();
  const tokenClientId = Number(auth?.user?.clientId || 0);
  if(tokenClientId > 0) return true;
  if(isSuperAdminPage()) return selectedClientId() > 0;
  return false;
}
function canDeleteLoan(){
  const role = currentRole();
  return role === "client" || role === "super_admin";
}
function canManageLoan(){
  return currentRole() !== "employee";
}
function todayIso(){
  const now = new Date();
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")}`;
}
function currentMonthIso(){
  const now = new Date();
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`;
}
function money(value){
  return Number(value || 0).toLocaleString("en-IN", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function esc(value){
  return String(value ?? "").replace(/[&<>"']/g, (ch) => (
    {"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[ch]
  ));
}
function formatStatusLabel(status){
  const key = String(status || "").trim().toLowerCase().replace(/[_-]+/g, " ");
  if(!key) return "-";
  const labels = {
    active: "Active",
    closed: "Closed",
    pending: "Pending",
    disbursed: "Disbursed",
    scheduled: "Scheduled",
    deducted: "Deducted",
    inactive: "Inactive",
    approved: "Approved",
    rejected: "Rejected",
  };
  return labels[key] || key.replace(/\b\w/g, (ch) => ch.toUpperCase());
}
function fmtDateTime(value){
  const d = new Date(value || "");
  return Number.isNaN(d.getTime()) ? "-" : d.toLocaleString();
}
function fmtMonth(value){
  const match = /^(\d{4})-(\d{2})$/.exec(String(value || ""));
  if(!match) return value || "-";
  const d = new Date(Number(match[1]), Number(match[2]) - 1, 1);
  return d.toLocaleString("en-IN", { month: "short", year: "numeric" });
}
function loanViewPage(){
  return isSuperAdminPage() ? "super-admin-view-loan.html" : "client-view-loan.html";
}
async function fetchJson(url, options){
  const res = await fetch(url, options);
  const raw = await res.text();
  const data = raw ? safeParse(raw, {}) : {};
  if(!res.ok) throw new Error(data?.detail || `Request failed (${res.status})`);
  return data;
}
function showScopeState(){
  const scoped = hasClientContext();
  const notice = $("loanScopeNotice");
  const form = $("loanForm");
  const clear = $("btnReset");
  if(notice){
    notice.classList.toggle("d-none", scoped);
    notice.textContent = isSuperAdminPage() ? "Select a client from the top client picker to manage loans." : "";
  }
  if(form){
    Array.from(form.querySelectorAll("input, select, textarea, button")).forEach((el) => {
      if(el.id === "btnReset") return;
      el.disabled = !scoped || !canManageLoan();
    });
  }
  if(clear) clear.disabled = !scoped;
  if(!scoped && $("loanTable")){
    $("loanTable").innerHTML = `<tr><td colspan="8" class="text-center text-muted-3 py-4">Select a client to view loan records.</td></tr>`;
  }
}
function selectedEmployee(){
  const empId = String($("empId")?.value || "").toUpperCase();
  return state.employees.find((row) => String(row.id || row.empId || "").toUpperCase() === empId) || null;
}
function fillEmployeeSnapshot(employee){
  $("employeeCode").value = employee ? String(employee.id || employee.empId || "").toUpperCase() : "";
  $("department").value = employee ? String(employee.dept || employee.department || "") : "";
  $("propertyBranch").value = employee ? String(employee.propertyBranch || employee.branch || employee.address || "") : "";
  $("designation").value = employee ? String(employee.desig || employee.designation || "") : "";
}
function renderEmployeeOptions(){
  const select = $("empId");
  if(!select) return;
  const current = select.value || "";
  select.innerHTML = '<option value="">Select employee</option>' + state.employees.map((row) => {
    const empId = String(row.id || row.empId || "").toUpperCase();
    const name = String(row.name || row.empName || empId);
    return `<option value="${esc(empId)}">${esc(name)} (${esc(empId)})</option>`;
  }).join("");
  if(current) select.value = current;
  fillEmployeeSnapshot(selectedEmployee());
}
function updateInstallmentCount(){
  const repaymentType = String($("repaymentType")?.value || "emi").toLowerCase();
  const requestedAmount = Number($("requestedAmount")?.value || 0);
  const emiAmountInput = $("emiAmount");
  const installmentInput = $("installmentCount");
  const emiStartMonth = $("emiStartMonth");
  if(emiStartMonth && !emiStartMonth.value) emiStartMonth.value = currentMonthIso();
  if(repaymentType === "one_time"){
    if(emiAmountInput) emiAmountInput.value = requestedAmount > 0 ? requestedAmount.toFixed(2) : "";
    if(installmentInput) installmentInput.value = requestedAmount > 0 ? "1" : "";
    if(emiAmountInput) emiAmountInput.readOnly = true;
    if(installmentInput) installmentInput.readOnly = true;
    return;
  }
  if(emiAmountInput) emiAmountInput.readOnly = true;
  if(installmentInput) installmentInput.readOnly = false;
  const installments = Number(installmentInput?.value || 0);
  if(requestedAmount > 0 && installments > 0 && emiAmountInput){
    emiAmountInput.value = (requestedAmount / installments).toFixed(2);
  } else if(emiAmountInput && repaymentType === "emi") {
    emiAmountInput.value = "";
  }
}
function setFormMode(isEditing){
  const title = $("formTitle");
  const copy = $("formCopy");
  const submit = $("btnSubmit");
  if(title) title.textContent = isEditing ? "Edit Loan" : "Add Loan";
  if(copy) copy.textContent = isEditing
    ? "Update the loan entry. Core schedule fields lock automatically once payroll deductions start."
    : "Create employee loan requests with repayment setup and automatic payroll recovery.";
  if(submit) submit.innerHTML = isEditing
    ? '<i class="bi bi-save2 me-2"></i>Update Loan'
    : '<i class="bi bi-plus-circle me-2"></i>Submit Loan';
}
function resetForm(keepEmployee){
  $("loanId").value = "";
  state.editingId = "";
  $("loanForm")?.reset();
  $("requestDate").value = todayIso();
  $("requiredDate").value = todayIso();
  $("emiStartMonth").value = currentMonthIso();
  $("repaymentType").value = "emi";
  if(!keepEmployee) $("empId").value = "";
  fillEmployeeSnapshot(selectedEmployee());
  updateInstallmentCount();
  setFormMode(false);
}
function collectPayload(){
  const empId = String($("empId")?.value || "").toUpperCase();
  const repaymentType = String($("repaymentType")?.value || "emi").toLowerCase();
  const emiStart = String($("emiStartMonth")?.value || currentMonthIso());
  const match = /^(\d{4})-(\d{2})$/.exec(emiStart);
  if(!match) throw new Error("EMI start month is required.");
  return {
    empId,
    loanType: String($("loanType")?.value || "").trim(),
    requestedAmount: Number($("requestedAmount")?.value || 0),
    reason: String($("reason")?.value || "").trim(),
    requestDate: String($("requestDate")?.value || todayIso()),
    requiredDate: String($("requiredDate")?.value || todayIso()),
    repaymentType,
    emiStartYear: Number(match[1]),
    emiStartMonth: Number(match[2]),
    emiAmount: Number($("emiAmount")?.value || 0),
    installmentCount: Number($("installmentCount")?.value || 0),
    remarks: String($("remarks")?.value || "").trim()
  };
}
function validatePayload(payload){
  if(!payload.empId) throw new Error("Select employee.");
  if(!payload.loanType) throw new Error("Select loan type.");
  if(!Number.isFinite(payload.requestedAmount) || payload.requestedAmount <= 0) throw new Error("Requested amount must be greater than 0.");
  if(!payload.requestDate) throw new Error("Request date is required.");
  if(!payload.requiredDate) throw new Error("Required date is required.");
  if(!payload.repaymentType) throw new Error("Repayment type is required.");
  if(!Number.isFinite(payload.emiAmount) || payload.emiAmount <= 0) throw new Error("EMI amount must be greater than 0.");
  if(!Number.isFinite(payload.installmentCount) || payload.installmentCount <= 0) throw new Error("Number of installments must be greater than 0.");
}
function renderStats(){
  const total = state.loans.reduce((sum, row) => sum + Number(row.requestedAmount || 0), 0);
  const paid = state.loans.reduce((sum, row) => sum + Number(row.paidAmount || 0), 0);
  const balance = state.loans.reduce((sum, row) => sum + Number(row.balanceAmount || 0), 0);
  const active = state.loans.filter((row) => String(row.status || "").toLowerCase() !== "closed").length;
  if($("statActive")) $("statActive").textContent = String(active);
  if($("statDisbursed")) $("statDisbursed").textContent = `Rs ${money(total)}`;
  if($("statRecovered")) $("statRecovered").textContent = `Rs ${money(paid)}`;
  if($("statBalance")) $("statBalance").textContent = `Rs ${money(balance)}`;
}
function renderRows(){
  const tbody = $("loanTable");
  if(!tbody || !hasClientContext()) return;
  if(!state.loans.length){
    tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted-3 py-4">No loans created yet.</td></tr>`;
    if($("recordCount")) $("recordCount").textContent = "0";
    return;
  }
  if($("recordCount")) $("recordCount").textContent = String(state.loans.length);
  tbody.innerHTML = state.loans.map((row) => {
    const canDelete = canDeleteLoan();
    return `
      <tr>
        <td><div class="fw-semibold">${esc(row.employeeName || "-")}</div><div class="small text-muted-3">${esc(row.empId || "-")}</div></td>
        <td>${esc(row.loanType || "-")}</td>
        <td class="text-end fw-semibold">Rs ${money(row.requestedAmount || 0)}</td>
        <td class="text-end">Rs ${money(row.paidAmount || 0)}</td>
        <td class="text-end text-warning-emphasis">Rs ${money(row.balanceAmount || 0)}</td>
        <td class="text-end">Rs ${money(row.emiAmount || 0)}</td>
        <td><span class="badge text-bg-light border">${esc(formatStatusLabel(row.status))}</span></td>
        <td class="text-end">
          <div class="d-inline-flex flex-wrap justify-content-end gap-2">
            <button class="btn btn-sm btn-outline-primary" type="button" data-action="view" data-id="${esc(row.id || "")}">View</button>
            ${canManageLoan() ? `<button class="btn btn-sm btn-outline-secondary" type="button" data-action="edit" data-id="${esc(row.id || "")}">Edit</button>` : ""}
            ${canDelete ? `<button class="btn btn-sm btn-outline-danger" type="button" data-action="delete" data-id="${esc(row.id || "")}">Delete</button>` : ""}
          </div>
        </td>
      </tr>
    `;
  }).join("");
  bindTableActions();
}
function bindTableActions(){
  document.querySelectorAll('[data-action="view"]').forEach((btn) => {
    btn.onclick = () => {
      const id = String(btn.getAttribute("data-id") || "");
      if(id) window.location.href = `${loanViewPage()}?id=${encodeURIComponent(id)}`;
    };
  });
  document.querySelectorAll('[data-action="edit"]').forEach((btn) => {
    btn.onclick = () => {
      const id = String(btn.getAttribute("data-id") || "");
      const row = state.loans.find((item) => String(item.id || "") === id);
      if(row) fillForm(row);
    };
  });
  document.querySelectorAll('[data-action="delete"]').forEach((btn) => {
    btn.onclick = async () => {
      const id = String(btn.getAttribute("data-id") || "");
      if(!id) return;
      if(!confirm("Delete this loan entry?")) return;
      try {
        await fetchJson(`${API_LOANS}/${encodeURIComponent(id)}`, { method: "DELETE" });
        if(state.editingId === id) resetForm(false);
        await loadLoans();
      } catch (e){
        alert(e?.message || "Unable to delete loan.");
      }
    };
  });
}
function fillForm(row){
  state.editingId = String(row.id || "");
  $("loanId").value = state.editingId;
  $("empId").value = String(row.empId || "");
  fillEmployeeSnapshot(selectedEmployee() || {
    id: row.empId,
    dept: row.dept,
    address: row.propertyBranch,
    desig: row.designation
  });
  $("loanType").value = String(row.loanType || "");
  $("requestedAmount").value = Number(row.requestedAmount || 0) > 0 ? Number(row.requestedAmount || 0).toFixed(2) : "";
  $("reason").value = String(row.reason || "");
  $("requestDate").value = String(row.requestDate || todayIso());
  $("requiredDate").value = String(row.requiredDate || todayIso());
  $("repaymentType").value = String(row.repaymentType || "emi").toLowerCase();
  $("emiStartMonth").value = row.emiStartYear && row.emiStartMonth
    ? `${row.emiStartYear}-${String(row.emiStartMonth).padStart(2, "0")}`
    : currentMonthIso();
  $("emiAmount").value = Number(row.emiAmount || 0) > 0 ? Number(row.emiAmount || 0).toFixed(2) : "";
  $("installmentCount").value = Number(row.installmentCount || 0) > 0 ? String(row.installmentCount) : "";
  $("remarks").value = String(row.remarks || "");
  updateInstallmentCount();
  setFormMode(true);
  window.scrollTo({ top: 0, behavior: "smooth" });
}
async function loadEmployees(){
  const data = await fetchJson(API_EMP, { headers: { Accept: "application/json" } });
  state.employees = Array.isArray(data.rows) ? data.rows : [];
  renderEmployeeOptions();
}
async function loadLoans(){
  const data = await fetchJson(API_LOANS, { headers: { Accept: "application/json" } });
  state.loans = Array.isArray(data.rows) ? data.rows : [];
  renderStats();
  renderRows();
}
async function handleSubmit(event){
  event.preventDefault();
  if(!canManageLoan()) return;
  const wasEditing = !!state.editingId;
  const submitBtn = $("btnSubmit");
  const doneProcessing = window.HRCommon?.setProcessingState?.(submitBtn, {
    busyText: wasEditing ? "Updating..." : "Saving...",
    message: wasEditing ? "Please wait, we are updating the loan entry." : "Please wait, we are saving the loan entry."
  });
  try {
    const payload = collectPayload();
    validatePayload(payload);
    const method = wasEditing ? "PUT" : "POST";
    const url = wasEditing ? `${API_LOANS}/${encodeURIComponent(state.editingId)}` : API_LOANS;
    await fetchJson(url, {
      method,
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    const keepEmployee = true;
    resetForm(keepEmployee);
    await loadLoans();
    doneProcessing?.(wasEditing ? "Loan updated successfully." : "Loan saved successfully.", false);
  } catch (e){
    doneProcessing?.(e?.message || "Unable to save loan.", true);
    alert(e?.message || "Unable to save loan.");
  }
}
function initFromQuery(){
  const id = new URLSearchParams(window.location.search).get("edit");
  if(!id) return;
  const row = state.loans.find((item) => String(item.id || "") === String(id));
  if(row) fillForm(row);
}

document.addEventListener("DOMContentLoaded", async () => {
  try {
    if($("requestDate") && !$("requestDate").value) $("requestDate").value = todayIso();
    if($("requiredDate") && !$("requiredDate").value) $("requiredDate").value = todayIso();
    if($("emiStartMonth") && !$("emiStartMonth").value) $("emiStartMonth").value = currentMonthIso();
    showScopeState();
    if(hasClientContext()){
      await loadEmployees();
      await loadLoans();
      initFromQuery();
    }
    $("empId")?.addEventListener("change", () => fillEmployeeSnapshot(selectedEmployee()));
    $("repaymentType")?.addEventListener("change", updateInstallmentCount);
    $("requestedAmount")?.addEventListener("input", updateInstallmentCount);
    $("installmentCount")?.addEventListener("input", updateInstallmentCount);
    $("loanForm")?.addEventListener("submit", handleSubmit);
    $("btnReset")?.addEventListener("click", () => resetForm(false));
    window.addEventListener("hr:client-changed", async () => {
      showScopeState();
      resetForm(false);
      if(hasClientContext()){
        await loadEmployees();
        await loadLoans();
      }
    });
  } catch (e){
    alert(e?.message || "Unable to load loan module.");
  }
});
