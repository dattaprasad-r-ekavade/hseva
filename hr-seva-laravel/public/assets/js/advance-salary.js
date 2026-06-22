(function () {
  "use strict";

  const $ = (id) => document.getElementById(id);
  const API_ADVANCES = "/api/advances";
  const API_HISTORY = "/api/advances/history";
  const API_EMPLOYEES = "/api/employees";
  const API_ELIGIBILITY = "/api/advances/eligibility";
  const auth = (() => {
    try {
      return JSON.parse(sessionStorage.getItem("hr_auth_session_v1") || "null");
    } catch (_e) {
      return null;
    }
  })();
  const user = auth && auth.user ? auth.user : {};
  const role = String(user.role || "").toLowerCase();
  const isEmployee = role === "employee";
  const state = {
    employees: [],
    advances: [],
    history: [],
    eligibility: null
  };

  function safeNum(value) {
    const num = Number(value);
    return Number.isFinite(num) ? num : 0;
  }
  function inr(value) {
    return "Rs " + safeNum(value).toLocaleString("en-IN", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  function esc(value) {
    return String(value ?? "").replace(/[&<>"']/g, (ch) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", "\"": "&quot;", "'": "&#39;" }[ch]));
  }
  function today() {
    return new Date().toISOString().slice(0, 10);
  }
  function monthLabel(period) {
    const match = /^(\d{4})-(\d{2})$/.exec(String(period || ""));
    if (!match) return String(period || "-");
    const dt = new Date(Number(match[1]), Number(match[2]) - 1, 1);
    return dt.toLocaleString("en-IN", { month: "short", year: "numeric" });
  }
  function dateToPeriod(dateValue) {
    const match = /^(\d{4})-(\d{2})-\d{2}$/.exec(String(dateValue || ""));
    return match ? `${match[1]}-${match[2]}` : "";
  }
  function showMsg(text, ok) {
    const box = $("advanceMsg");
    if (!box) return;
    box.className = `alert ${ok ? "alert-success" : "alert-danger"} mb-0`;
    box.textContent = text;
    box.classList.remove("d-none");
    clearTimeout(box._timer);
    box._timer = setTimeout(() => box.classList.add("d-none"), 4000);
  }
  async function fetchJson(url, options) {
    const res = await fetch(url, { cache: "no-store", ...(options || {}) });
    const raw = await res.text();
    let data = {};
    if (raw) {
      try {
        data = JSON.parse(raw);
      } catch (_e) {
        throw new Error("Server returned an invalid response.");
      }
    }
    if (!res.ok) {
      throw new Error(data?.detail || `${res.status}`);
    }
    return data;
  }
  function setMetric(id, value) {
    if ($(id)) $(id).textContent = value;
  }
  function renderEligibility() {
    const info = state.eligibility;
    setMetric("periodValue", info ? monthLabel(`${info.year}-${String(info.month).padStart(2, "0")}`) : "-");
    setMetric("presentDaysValue", info ? String(info.presentDays) : "0");
    setMetric("monthlyGrossValue", info ? inr(info.monthlyGross) : inr(0));
    setMetric("perDaySalaryValue", info ? inr(info.perDaySalary) : inr(0));
    setMetric("eligibleSalaryValue", info ? inr(info.eligibleSalary) : inr(0));
    setMetric("existingAdvanceValue", info ? inr(info.existingMonthAdvance) : inr(0));
    setMetric("remainingEligibleValue", info ? inr(info.remainingEligible) : inr(0));
    if ($("eligibilityHint")) {
      if (!info) {
        $("eligibilityHint").textContent = isEmployee
          ? "Attendance-based advance details are visible to HR/Admin while payroll deductions remain visible below."
          : "Select employee and advance date to calculate attendance-based eligible salary.";
      } else {
        $("eligibilityHint").textContent = `Present days counted up to ${info.date}. Only amount up to ${inr(info.remainingEligible)} can be generated now.`;
      }
    }
    if ($("amount")) {
      $("amount").max = info ? String(info.remainingEligible) : "";
    }
  }
  function renderStats() {
    const active = state.advances.filter((row) => String(row.status || "").toLowerCase() !== "closed");
    const outstanding = state.advances.reduce((sum, row) => sum + safeNum(row.remainingBalance), 0);
    const disbursed = state.advances.reduce((sum, row) => sum + safeNum(row.amount), 0);
    const deductions = state.history.reduce((sum, row) => sum + safeNum(row.deductedAmount), 0);
    setMetric("statActive", String(active.length));
    setMetric("statOutstanding", inr(outstanding));
    setMetric("statDisbursed", inr(disbursed));
    setMetric("statRecovered", inr(deductions));
  }
  function renderAdvances() {
    if (!$("advanceTableBody")) return;
    const rows = state.advances;
    $("advanceTableBody").innerHTML = rows.length ? rows.map((row) => `
      <tr>
        <td class="fw-semibold">${esc(row.empId)}</td>
        <td>${esc(row.employeeName)}</td>
        <td>${esc(monthLabel(`${row.attendanceYear}-${String(row.attendanceMonth).padStart(2, "0")}`))}</td>
        <td>${esc(String(row.presentDays))}</td>
        <td>${esc(inr(row.eligibleSalary))}</td>
        <td>${esc(inr(row.amount))}</td>
        <td><span class="advance-chip">${esc(row.status)}</span></td>
        <td class="text-center">${renderAdvanceAction(row)}</td>
      </tr>`).join("") : `<tr><td colspan="8" class="text-center text-muted-3 py-4">No advances found.</td></tr>`;
  }
  function renderAdvanceAction(row) {
    if (isEmployee) return `<span class="text-muted-3">-</span>`;
    const deducted = roundAmount(row.deductedAmount || 0);
    if (deducted > 0) {
      return `<span class="text-muted-3">Locked</span>`;
    }
    return `<button type="button" class="btn btn-outline-danger btn-sm" data-advance-delete="${esc(row.id)}">Delete</button>`;
  }
  function renderOutstanding() {
    if (!$("outstandingBody")) return;
    const rows = state.advances.filter((row) => safeNum(row.remainingBalance) > 0);
    $("outstandingBody").innerHTML = rows.length ? rows.map((row) => `
      <tr>
        <td class="fw-semibold">${esc(row.empId)}</td>
        <td>${esc(row.employeeName)}</td>
        <td>${esc(monthLabel(`${row.attendanceYear}-${String(row.attendanceMonth).padStart(2, "0")}`))}</td>
        <td>${esc(inr(row.amount))}</td>
        <td>${esc(inr(row.deductedAmount))}</td>
        <td class="fw-semibold text-warning-emphasis">${esc(inr(row.remainingBalance))}</td>
      </tr>`).join("") : `<tr><td colspan="6" class="text-center text-muted-3 py-4">No outstanding advances.</td></tr>`;
  }
  function renderHistory() {
    if (!$("historyBody")) return;
    const rows = state.history;
    $("historyBody").innerHTML = rows.length ? rows.map((row) => `
      <tr>
        <td class="fw-semibold">${esc(row.empId)}</td>
        <td>${esc(row.employeeName)}</td>
        <td>${esc(monthLabel(row.period))}</td>
        <td>${esc(inr(row.scheduledAmount))}</td>
        <td>${esc(inr(row.deductedAmount))}</td>
        <td>${esc(inr(row.balanceAfter))}</td>
        <td><span class="advance-chip">${esc(row.status)}</span></td>
      </tr>`).join("") : `<tr><td colspan="7" class="text-center text-muted-3 py-4">No deduction history.</td></tr>`;
  }
  function renderRoleMode() {
    if ($("managePanel")) $("managePanel").classList.toggle("d-none", isEmployee);
    if ($("pageTitle")) $("pageTitle").textContent = isEmployee ? "Advance Salary View" : "Advance Salary Module";
    if ($("pageSub")) $("pageSub").textContent = isEmployee
      ? "View your advance records and payroll deductions."
      : "Generate attendance-based advances and let payroll deduct them automatically.";
  }
  function render() {
    renderRoleMode();
    renderEligibility();
    renderStats();
    renderAdvances();
    renderOutstanding();
    renderHistory();
    bindAdvanceActions();
  }
  function bindAdvanceActions() {
    document.querySelectorAll("[data-advance-delete]").forEach((btn) => {
      btn.addEventListener("click", async function () {
        const id = String(btn.getAttribute("data-advance-delete") || "");
        if (!id) return;
        if (!confirm("Delete this advance salary entry?")) return;
        btn.disabled = true;
        try {
          await fetchJson(`${API_ADVANCES}/${encodeURIComponent(id)}`, { method: "DELETE" });
          await loadData();
          await loadEligibility();
          showMsg("Advance salary entry deleted successfully.", true);
        } catch (e) {
          showMsg(e.message || "Failed to delete advance salary entry.", false);
        } finally {
          btn.disabled = false;
        }
      });
    });
  }
  async function loadEmployees() {
    if (isEmployee) return;
    const data = await fetchJson(API_EMPLOYEES);
    state.employees = Array.isArray(data.rows) ? data.rows : [];
    if ($("employee")) {
      $("employee").innerHTML = `<option value="">Select employee</option>` + state.employees
        .filter((row) => String(row.status || "").toLowerCase() !== "inactive")
        .map((row) => `<option value="${esc(row.id)}">${esc(row.id)} - ${esc(row.name)}</option>`)
        .join("");
    }
  }
  async function loadData() {
    const [advances, history] = await Promise.all([
      fetchJson(API_ADVANCES),
      fetchJson(API_HISTORY)
    ]);
    state.advances = Array.isArray(advances.rows) ? advances.rows : [];
    state.history = Array.isArray(history.rows) ? history.rows : [];
    render();
  }
  async function loadEligibility() {
    if (isEmployee) return;
    const empId = $("employee")?.value || "";
    const date = $("disbursedOn")?.value || today();
    state.eligibility = null;
    if (!empId) {
      renderEligibility();
      return;
    }
    try {
      const query = `${API_ELIGIBILITY}?empId=${encodeURIComponent(empId)}&date=${encodeURIComponent(date)}`;
      const data = await fetchJson(query);
      state.eligibility = data.row || null;
    } catch (e) {
      state.eligibility = null;
      showMsg(e.message || "Unable to calculate eligibility.", false);
    }
    renderEligibility();
  }
  async function submitAdvance(ev) {
    ev.preventDefault();
    const empId = $("employee")?.value || "";
    const amount = roundAmount($("amount")?.value || 0);
    const disbursedOn = $("disbursedOn")?.value || today();
    const notes = $("notes")?.value || "";
    if (!empId) return showMsg("Select employee first.", false);
    if (!state.eligibility) return showMsg("Calculate attendance-based eligibility first.", false);
    if (amount <= 0) return showMsg("Advance amount must be greater than 0.", false);
    if (amount > safeNum(state.eligibility.remainingEligible)) {
      return showMsg("Advance amount cannot exceed the calculated salary on present attendance.", false);
    }
    try {
      await fetchJson(API_ADVANCES, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ empId, amount, disbursedOn, notes })
      });
      $("advanceForm")?.reset();
      if ($("disbursedOn")) $("disbursedOn").value = today();
      state.eligibility = null;
      await loadEmployees();
      await loadData();
      await loadEligibility();
      showMsg("Attendance-based advance generated successfully.", true);
    } catch (e) {
      showMsg(e.message || "Failed to generate advance.", false);
    }
  }
  function roundAmount(value) {
    return Math.round(safeNum(value) * 100) / 100;
  }
  async function init() {
    if ($("disbursedOn")) $("disbursedOn").value = today();
    $("advanceForm")?.addEventListener("submit", submitAdvance);
    $("employee")?.addEventListener("change", loadEligibility);
    $("disbursedOn")?.addEventListener("change", loadEligibility);
    render();
    try {
      await loadEmployees();
      await loadData();
      await loadEligibility();
    } catch (e) {
      showMsg(e.message || "Failed to load advance salary module data.", false);
    }
  }

  document.addEventListener("DOMContentLoaded", init);
})();
