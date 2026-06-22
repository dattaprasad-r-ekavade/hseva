const $ = (id) => document.getElementById(id);
const viewHtmlEl = document.documentElement;
const VIEW_KEY_AUTH = "hr_auth_session_v1";
const VIEW_KEY_SUPERADMIN_CLIENT_ID = "hr_superadmin_selected_client_id_v1";
const VIEW_API_LOANS = "/api/loans";

function applyViewTheme(theme){
  viewHtmlEl.setAttribute("data-bs-theme", theme);
  localStorage.setItem("hr_portal_theme", theme);
  if ($("themeIcon")) $("themeIcon").className = theme === "dark" ? "bi bi-sun" : "bi bi-moon";
}
applyViewTheme(localStorage.getItem("hr_portal_theme") || "light");
$("themeToggle")?.addEventListener("click", () => {
  const current = viewHtmlEl.getAttribute("data-bs-theme") || "light";
  applyViewTheme(current === "dark" ? "light" : "dark");
});

function safeParseView(value, fallback = null){
  try { return JSON.parse(value); } catch(_e){ return fallback; }
}
function viewAuth(){
  return safeParseView(sessionStorage.getItem(VIEW_KEY_AUTH), null);
}
function isViewSuperAdminPage(){
  return String(window.location.pathname || "").toLowerCase().includes("/super-admin/");
}
function hasViewClientContext(){
  const auth = viewAuth();
  const tokenClientId = Number(auth?.user?.clientId || 0);
  if(tokenClientId > 0) return true;
  if(isViewSuperAdminPage()) return Number(localStorage.getItem(VIEW_KEY_SUPERADMIN_CLIENT_ID) || 0) > 0;
  return false;
}
function escView(value){
  return String(value ?? "").replace(/[&<>"']/g, (ch) => (
    {"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[ch]
  ));
}
function moneyView(value){
  return Number(value || 0).toLocaleString("en-IN", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function labelOrDash(value){
  const text = String(value ?? "").trim();
  return text || "-";
}
function fmtDateTimeView(value){
  const d = new Date(value || "");
  return Number.isNaN(d.getTime()) ? "-" : d.toLocaleString();
}
function fmtMonthView(value){
  const match = /^(\d{4})-(\d{2})$/.exec(String(value || ""));
  if(!match) return value || "-";
  return new Date(Number(match[1]), Number(match[2]) - 1, 1).toLocaleString("en-IN", { month: "short", year: "numeric" });
}
function backHref(){
  return isViewSuperAdminPage() ? "super-admin-loan.html" : "client-loan.html";
}
async function fetchViewJson(url, options){
  const res = await fetch(url, options);
  const raw = await res.text();
  const data = raw ? safeParseView(raw, {}) : {};
  if(!res.ok) throw new Error(data?.detail || `Request failed (${res.status})`);
  return data;
}
function renderPairs(targetId, rows){
  const target = $(targetId);
  if(!target) return;
  target.innerHTML = rows.map((row) => `
    <div class="col-12 col-md-6">
      <div class="glass p-3 h-100">
        <div class="small text-muted-3">${escView(row.label)}</div>
        <div class="fw-semibold mt-1">${row.value}</div>
      </div>
    </div>
  `).join("");
}
function renderHistory(rows){
  const tbody = $("deductionHistory");
  if(!tbody) return;
  if(!Array.isArray(rows) || !rows.length){
    tbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted-3 py-4">No EMI deductions recorded yet.</td></tr>`;
    return;
  }
  tbody.innerHTML = rows.map((row) => `
    <tr>
      <td>${escView(row.period || fmtMonthView(`${row.deductionYear}-${String(row.deductionMonth || "").padStart(2, "0")}`))}</td>
      <td class="text-end">Rs ${moneyView(row.deductedAmount || 0)}</td>
      <td class="text-end">Rs ${moneyView(row.balanceAfter || 0)}</td>
    </tr>
  `).join("");
}
function renderLoan(row){
  if($("loanHeading")) $("loanHeading").textContent = `${labelOrDash(row.employeeName)} Loan Details`;
  if($("loanSub")) $("loanSub").textContent = `${labelOrDash(row.loanType)} | Status: ${labelOrDash(row.status)}`;
  if($("topStatus")) $("topStatus").textContent = labelOrDash(row.status);
  if($("topStatus")) $("topStatus").className = "badge text-bg-light border";
  renderPairs("employeeDetailGrid", [
    { label: "Employee Name", value: escView(labelOrDash(row.employeeName)) },
    { label: "Employee ID", value: escView(labelOrDash(row.empId)) },
    { label: "Department", value: escView(labelOrDash(row.dept)) },
    { label: "Property / Branch", value: escView(labelOrDash(row.propertyBranch)) },
    { label: "Designation", value: escView(labelOrDash(row.designation)) }
  ]);
  renderPairs("loanDetailGrid", [
    { label: "Loan Type", value: escView(labelOrDash(row.loanType)) },
    { label: "Requested Amount", value: `Rs ${moneyView(row.requestedAmount || 0)}` },
    { label: "Reason", value: escView(labelOrDash(row.reason)) },
    { label: "Request Date", value: escView(labelOrDash(row.requestDate)) },
    { label: "Required Date", value: escView(labelOrDash(row.requiredDate)) },
    { label: "Repayment Type", value: escView(labelOrDash(row.repaymentType === "one_time" ? "One-time" : "EMI")) },
    { label: "EMI Start Month", value: escView(labelOrDash(row.emiStartPeriod || fmtMonthView(`${row.emiStartYear}-${String(row.emiStartMonth || "").padStart(2, "0")}`))) },
    { label: "EMI Amount", value: `Rs ${moneyView(row.emiAmount || 0)}` },
    { label: "Number of Installments", value: escView(labelOrDash(row.installmentCount)) },
    { label: "Remarks", value: escView(labelOrDash(row.remarks)) }
  ]);
  renderPairs("recoveryDetailGrid", [
    { label: "Loan Amount", value: `Rs ${moneyView(row.requestedAmount || 0)}` },
    { label: "Paid Amount", value: `Rs ${moneyView(row.paidAmount || 0)}` },
    { label: "Balance Amount", value: `Rs ${moneyView(row.balanceAmount || 0)}` },
    { label: "EMI Amount", value: `Rs ${moneyView(row.emiAmount || 0)}` },
    { label: "Status", value: escView(labelOrDash(row.status)) },
    { label: "Created On", value: escView(fmtDateTimeView(row.createdAt)) }
  ]);
  renderHistory(row.deductionHistory || []);
  if($("btnEdit")) $("btnEdit").href = `${backHref()}?edit=${encodeURIComponent(String(row.id || ""))}`;
}

document.addEventListener("DOMContentLoaded", async () => {
  try {
    if(!hasViewClientContext()){
      if($("pageMsg")){
        $("pageMsg").className = "alert alert-warning";
        $("pageMsg").textContent = isViewSuperAdminPage()
          ? "Select a client from the top client picker to view loan details."
          : "Loan details are not available right now.";
      }
      return;
    }
    const loanId = new URLSearchParams(window.location.search).get("id");
    if(!loanId) throw new Error("Loan ID is missing.");
    const data = await fetchViewJson(`${VIEW_API_LOANS}/${encodeURIComponent(loanId)}`, { headers: { Accept: "application/json" } });
    renderLoan(data.row || {});
  } catch (e){
    if($("pageMsg")){
      $("pageMsg").className = "alert alert-danger";
      $("pageMsg").textContent = e?.message || "Unable to load loan details.";
    } else {
      alert(e?.message || "Unable to load loan details.");
    }
  }
});
