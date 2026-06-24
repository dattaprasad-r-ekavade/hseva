(function(){
  "use strict";

  const $ = (id) => document.getElementById(id);
  const API_OT = "/api/overtime";
  const API_EMPLOYEES = "/api/employees";
  const auth = (() => {
    try { return JSON.parse(sessionStorage.getItem("hr_auth_session_v1") || "null"); } catch(_e){ return null; }
  })();
  const role = String(auth?.user?.role || "").toLowerCase();
  const isEmployee = role === "employee";
  const state = { employees: [], rows: [] };

  function safeNum(v){ const n = Number(v); return Number.isFinite(n) ? n : 0; }
  function esc(v){ return String(v ?? "").replace(/[&<>"']/g, (ch) => ({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[ch])); }
  function inr(v){ return "Rs " + safeNum(v).toLocaleString("en-IN", { maximumFractionDigits: 2, minimumFractionDigits: 2 }); }
  function today(){ return new Date().toISOString().slice(0,10); }
  function fmtDate(v){
    const d = new Date(`${v || ""}T00:00:00`);
    return Number.isNaN(d.getTime()) ? "-" : d.toLocaleDateString("en-IN", { day:"2-digit", month:"short", year:"numeric" });
  }
  function pad2(v){ return String(v).padStart(2, "0"); }
  function showMsg(text, ok){
    const box = $("otMsg");
    if(!box) return;
    box.className = `alert ${ok ? "alert-success" : "alert-danger"} mb-0`;
    box.textContent = text;
    box.classList.remove("d-none");
    clearTimeout(box._timer);
    box._timer = setTimeout(() => box.classList.add("d-none"), 3500);
  }
  async function fetchJson(url, opts){
    const res = await fetch(url, { cache:"no-store", ...(opts || {}) });
    let data = null;
    try { data = await res.json(); } catch(_e){}
    if(!res.ok) throw new Error(data?.detail || `Request failed (${res.status})`);
    return data || {};
  }
  function timeMinutes(value){
    const m = /^([01]\d|2[0-3]):([0-5]\d)$/.exec(String(value || ""));
    if(!m) return null;
    return (Number(m[1]) * 60) + Number(m[2]);
  }
  function time12To24(prefix){
    const hour = Number($(`${prefix}Hour`)?.value || 0);
    const minute = Number($(`${prefix}Minute`)?.value || 0);
    const period = String($(`${prefix}Period`)?.value || "").toUpperCase();
    if(hour < 1 || hour > 12 || minute < 0 || minute > 59 || !["AM","PM"].includes(period)) return "";
    let h = hour % 12;
    if(period === "PM") h += 12;
    return `${pad2(h)}:${pad2(minute)}`;
  }
  function formatTime12(value){
    const m = /^([01]\d|2[0-3]):([0-5]\d)$/.exec(String(value || ""));
    if(!m) return String(value || "-");
    const h24 = Number(m[1]);
    const hour = h24 % 12 || 12;
    const period = h24 >= 12 ? "PM" : "AM";
    return `${hour}:${m[2]} ${period}`;
  }
  function populateTimeSelects(){
    const hourOptions = `<option value="">HH</option>` + Array.from({ length: 12 }, (_, i) => {
      const h = i + 1;
      return `<option value="${h}">${h}</option>`;
    }).join("");
    const minuteOptions = `<option value="">MM</option>` + Array.from({ length: 60 }, (_, i) => `<option value="${i}">${pad2(i)}</option>`).join("");
    ["startHour","endHour"].forEach((id) => { if($(id)) $(id).innerHTML = hourOptions; });
    ["startMinute","endMinute"].forEach((id) => { if($(id)) $(id).innerHTML = minuteOptions; });
  }
  function resetTimeSelects(){
    ["startHour","startMinute","endHour","endMinute"].forEach((id) => { if($(id)) $(id).value = ""; });
    if($("startPeriod")) $("startPeriod").value = "PM";
    if($("endPeriod")) $("endPeriod").value = "PM";
  }
  function calcHours(startTime, endTime){
    const start = timeMinutes(startTime);
    const endRaw = timeMinutes(endTime);
    if(start === null || endRaw === null) return 0;
    let end = endRaw;
    if(end <= start) end += 24 * 60;
    return Math.round(((end - start) / 60) * 100) / 100;
  }
  function refreshCalc(){
    const hours = calcHours(time12To24("start"), time12To24("end"));
    const rate = safeNum($("rate")?.value);
    const amount = Math.round(hours * rate * 100) / 100;
    if($("totalHours")) $("totalHours").value = hours ? hours.toFixed(2) : "";
    if($("totalAmount")) $("totalAmount").value = amount ? amount.toFixed(2) : "";
    if($("previewHours")) $("previewHours").textContent = hours ? `${hours.toFixed(2)} hrs` : "0.00 hrs";
    if($("previewAmount")) $("previewAmount").textContent = inr(amount);
  }
  async function loadEmployees(){
    if(isEmployee) return;
    const data = await fetchJson(API_EMPLOYEES);
    state.employees = Array.isArray(data.rows) ? data.rows : [];
    if($("employee")){
      $("employee").innerHTML = `<option value="">Select employee</option>` + state.employees
        .filter((r) => String(r.status || "").toLowerCase() !== "inactive")
        .map((r) => `<option value="${esc(r.id)}">${esc(r.id)} - ${esc(r.name)}</option>`)
        .join("");
    }
  }
  async function loadRows(){
    const data = await fetchJson(API_OT);
    state.rows = Array.isArray(data.rows) ? data.rows : [];
    renderStats(data.stats || {});
    renderRows();
  }
  function renderStats(stats){
    const totalHours = safeNum(stats.totalHours);
    const monthHours = safeNum(stats.monthHours);
    if($("statEntries")) $("statEntries").textContent = String(stats.entries || 0);
    if($("statHours")) $("statHours").textContent = totalHours.toFixed(2);
    if($("statAmount")) $("statAmount").textContent = inr(stats.totalAmount || 0);
    if($("statMonth")) $("statMonth").textContent = `${monthHours.toFixed(2)} hrs`;
  }
  function renderRoleMode(){
    if($("managePanel")) $("managePanel").classList.toggle("d-none", isEmployee);
    if($("pageTitle")) $("pageTitle").textContent = isEmployee ? "Overtime View" : "Overtime Module";
    if($("pageSub")) $("pageSub").textContent = isEmployee ? "View your recorded overtime hours and amount." : "Record overtime hours and calculate OT amount automatically.";
  }
  function renderRows(){
    const tbody = $("otTableBody");
    if(!tbody) return;
    if(!state.rows.length){
      tbody.innerHTML = `<tr><td colspan="9" class="text-center text-muted-3 py-4">No overtime entries found.</td></tr>`;
      return;
    }
    tbody.innerHTML = state.rows.map((r) => `
      <tr>
        <td><div class="fw-semibold">${esc(r.employeeName)}</div><div class="small text-muted-3">${esc(r.empId)}</div></td>
        <td>${esc(fmtDate(r.otDate))}</td>
        <td><span class="ot-chip">${esc(formatTime12(r.startTime))} - ${esc(formatTime12(r.endTime))}</span></td>
        <td class="fw-semibold">${safeNum(r.totalHours).toFixed(2)}</td>
        <td>${esc(inr(r.rate))}</td>
        <td class="fw-semibold">${esc(inr(r.amount))}</td>
        <td>${esc(r.notes || "-")}</td>
        <td class="small text-muted-3">${esc(r.createdBy || "-")}</td>
        <td class="text-end">${isEmployee ? "" : `<button class="btn btn-sm btn-outline-danger" type="button" data-delete="${esc(r.id)}"><i class="bi bi-trash"></i></button>`}</td>
      </tr>`).join("");
  }
  async function submitOt(ev){
    ev.preventDefault();
    refreshCalc();
    const payload = {
      empId: $("employee")?.value || "",
      otDate: $("otDate")?.value || "",
      startTime: time12To24("start"),
      endTime: time12To24("end"),
      rate: safeNum($("rate")?.value),
      notes: $("notes")?.value || ""
    };
    const hours = calcHours(payload.startTime, payload.endTime);
    if(!payload.empId) return showMsg("Select employee first.", false);
    if(!payload.otDate) return showMsg("Enter OT date.", false);
    if(hours <= 0) return showMsg("Enter a valid start and end time.", false);
    if(payload.rate < 0) return showMsg("OT rate cannot be negative.", false);
    try{
      await fetchJson(API_OT, {
        method:"POST",
        headers:{ "Content-Type":"application/json" },
        body:JSON.stringify(payload)
      });
      $("otForm")?.reset();
      if($("otDate")) $("otDate").value = today();
      resetTimeSelects();
      refreshCalc();
      await loadRows();
      showMsg("Overtime entry saved successfully.", true);
    } catch(e){
      showMsg(e.message || "Failed to save overtime entry.", false);
    }
  }
  async function deleteRow(id){
    if(!id || !confirm("Delete this overtime entry?")) return;
    try{
      await fetchJson(`${API_OT}/${encodeURIComponent(id)}`, { method:"DELETE" });
      await loadRows();
      showMsg("Overtime entry deleted.", true);
    } catch(e){
      showMsg(e.message || "Failed to delete overtime entry.", false);
    }
  }
  async function init(){
    renderRoleMode();
    populateTimeSelects();
    resetTimeSelects();
    if($("otDate")) $("otDate").value = today();
    ["startHour","startMinute","startPeriod","endHour","endMinute","endPeriod","rate"].forEach((id) => {
      $(id)?.addEventListener("input", refreshCalc);
      $(id)?.addEventListener("change", refreshCalc);
    });
    $("otForm")?.addEventListener("submit", submitOt);
    $("otTableBody")?.addEventListener("click", (ev) => {
      const btn = ev.target.closest("[data-delete]");
      if(btn) deleteRow(btn.getAttribute("data-delete"));
    });
    refreshCalc();
    try{
      await loadEmployees();
      await loadRows();
    } catch(e){
      showMsg(e.message || "Failed to load overtime data.", false);
    }
  }

  document.addEventListener("DOMContentLoaded", init);
})();
