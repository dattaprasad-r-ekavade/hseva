const themeRoot = document.documentElement;
function applyTheme(theme){
  if(!themeRoot) return;
  themeRoot.setAttribute("data-bs-theme", theme);
  localStorage.setItem("hr_portal_theme", theme);
  const icon = document.getElementById("themeIcon");
  const text = document.getElementById("themeText");
  const isDark = theme === "dark";
  if(icon) icon.className = isDark ? "bi bi-sun" : "bi bi-moon";
  if(text) text.textContent = isDark ? "Light" : "Dark";
}
applyTheme(localStorage.getItem("hr_portal_theme") || "light");
document.getElementById("themeToggle")?.addEventListener("click", () => {
  const current = themeRoot.getAttribute("data-bs-theme") || "light";
  applyTheme(current === "dark" ? "light" : "dark");
});

const API_BASES = ["/api"];
const KEY_CONTROL = "hr_client_control_v1";
const KEY_EMP_EXTRA = "hr_emp_extra_v1";
const KEY_LEAVES = "hr_client_leaves_v1";
const KEY_EMP_LOCAL = "hr_client_employees_v1";
const KEY_SALARY_DATA_VERSION = "hr_salary_data_version_v1";
let editId = null;
let employees = [];
let employeeTypes = [];
let leaveEntries = [];
let notifications = [];
let clientControl = { year: 2026, month: 2, pfEmpPct: 12, esiEmpPct: 0.75, esiWageLimitMonthly: 21000, ptEnabled: "Yes", ptMonthly: 200, pfCapEnabled: true, pfCapAmount: 15000, pfOnEsiPct: 70, lwfEnabled: "Yes", lwfEmpAmt: 20, basicPct: 40, hraPct: 40, convPct: 5, eduPct: 2, daPct: 0, daPctBasic: 0, specialPct: 13, ctcSplitRows: [], ctcAddonRows: [] };
let empExtras = {};

function specialPct(){ return Number(clientControl.specialPct || 0); }
function inr(n){ return "Rs " + Number(n || 0).toLocaleString("en-IN"); }
function normalizeAlias(name){ return String(name || "").trim().toLowerCase().replace(/\s+/g, " "); }
function getCtcRowsNormalized(){
  const dynamicRows = Array.isArray(clientControl.ctcSplitRows) ? clientControl.ctcSplitRows : [];
  if(dynamicRows.length){
    const rows = dynamicRows
      .map((r) => ({ name: String(r?.name || "").trim(), pct: Number(r?.pct || 0) }))
      .filter((r) => r.name && r.pct > 0);
    if(rows.length) return rows;
  }
  return [
    { name: "Basic", pct: Number(clientControl.basicPct || 0) },
    { name: "HRA", pct: Number(clientControl.hraPct || 0) },
    { name: "Conveyance", pct: Number(clientControl.convPct || 0) },
    { name: "DA", pct: Number(clientControl.daPct || 0) },
    { name: "Educational Allowance", pct: Number(clientControl.eduPct || 0) },
    { name: "Special Allowance", pct: Number(clientControl.specialPct || 0) }
  ].filter((r) => r.pct > 0);
}
function calcComponents(baseCtc){
  const base = Number(baseCtc || 0);
  const rows = getCtcRowsNormalized();
  const totalPct = rows.reduce((s, r) => s + Number(r.pct || 0), 0);
  const amounts = rows.map((r) => ({
    name: r.name,
    pct: Number(r.pct || 0),
    amount: Math.round(base * (totalPct > 0 ? Number(r.pct || 0) / totalPct : 0))
  }));
  const byAlias = new Map(amounts.map((r) => [normalizeAlias(r.name), r.amount]));
  const basic = Number(byAlias.get("basic") || 0);
  const hra = Number(byAlias.get("hra") || 0);
  const conv = Number(byAlias.get("conveyance") || byAlias.get("conv") || 0);
  const edu = Number(byAlias.get("educational allowance") || byAlias.get("education allowance") || byAlias.get("edu") || 0);
  const da = Number(byAlias.get("da") || 0);
  const special = Number(byAlias.get("special allowance") || byAlias.get("special") || 0);
  return { basic, hra, conv, edu, da, special, rows: amounts };
}
function getCtcAddonRowsNormalized(){
  const rows = Array.isArray(clientControl.ctcAddonRows) ? [...clientControl.ctcAddonRows] : [];
  const hasPfEmployer = rows.some((r) => normalizeAlias(r?.name) === "pf employer %" || String(r?.code || "").trim() === "pfEmployerPct");
  const hasEsiEmployer = rows.some((r) => normalizeAlias(r?.name) === "esi employer %" || String(r?.code || "").trim() === "esiEmployerPct");
  if(!hasPfEmployer && Number(clientControl.pfErPct || 0) > 0){
    rows.push({ code: "pfEmployerPct", name: "PF Employer %", type: "percent", value: Number(clientControl.pfErPct || 0) });
  }
  if(!hasEsiEmployer && Number(clientControl.esiErPct || 0) > 0){
    rows.push({ code: "esiEmployerPct", name: "ESI Employer %", type: "percent", value: Number(clientControl.esiErPct || 0) });
  }
  return rows.map((r) => ({
    code: String(r?.code || "").trim(),
    name: String(r?.name || "").trim(),
    type: String(r?.type || "").trim().toLowerCase() === "amount" ? "amount" : "percent",
    value: Number(r?.value ?? r?.amount ?? 0)
  })).filter((r) => r.name && Number(r.value || 0) > 0);
}
function calcCtcFigures(grossMonthly){
  const gross = Number(grossMonthly || 0);
  const rows = getCtcAddonRowsNormalized();
  const addonMonthly = rows.reduce((sum, row) => {
    if(row.type === "amount") return sum + Number(row.value || 0);
    const isPfEmployer = row.code === "pfEmployerPct" || normalizeAlias(row.name) === "pf employer %";
    const isEsiEmployer = row.code === "esiEmployerPct" || normalizeAlias(row.name) === "esi employer %";
    const esiLimit = Number(clientControl.esiWageLimitMonthly || 0);
    const esiEligible = esiLimit > 0 ? gross <= esiLimit : true;
    const pfCapAmount = Number(clientControl.pfCapAmount || 0);
    const pfOnEsiPct = Number(clientControl.pfOnEsiPct || 0);
    const statutoryBase = esiEligible ? (gross * pfOnEsiPct / 100) : gross;
    const contributionBase = (isPfEmployer || isEsiEmployer)
      ? (esiEligible
          ? statutoryBase
          : (isPfEmployer ? (pfCapAmount > 0 ? Math.min(gross, pfCapAmount) : gross) : gross))
      : gross;
    const pfBase = isPfEmployer
      ? (esiEligible
          ? (gross * pfOnEsiPct / 100)
          : (pfCapAmount > 0 ? Math.min(gross, pfCapAmount) : gross))
      : gross;
    if(isEsiEmployer && !esiEligible) return sum;
    return sum + ((isPfEmployer ? pfBase : contributionBase) * Number(row.value || 0) / 100);
  }, 0);
  const ctcMonthly = gross + addonMonthly;
  const ctcYearly = ctcMonthly * 12;
  return { grossMonthly: gross, addonMonthly, ctcMonthly, ctcYearly };
}
function statutoryPreviewForBase(baseCtc){
  const base = Number(baseCtc || 0);
  const comp = calcComponents(base);
  const esiLimit = Number(clientControl.esiWageLimitMonthly || 0);
  const esiEligible = esiLimit > 0 ? base <= esiLimit : false;
  const pfBase = Number(comp.basic || 0) + (Number(comp.basic || 0) * Number(clientControl.daPctBasic || 0) / 100);
  const statutoryBase = esiEligible
    ? (base * Number(clientControl.pfOnEsiPct || 0) / 100)
    : base;
  const pfWages = esiEligible
    ? statutoryBase
    : (clientControl.pfCapEnabled && base > esiLimit ? Number(clientControl.pfCapAmount || 15000) : pfBase);
  const pfEmpAmt = (pfWages * Number(clientControl.pfEmpPct || 0)) / 100;
  const esiEmpAmt = esiEligible ? (statutoryBase * Number(clientControl.esiEmpPct || 0)) / 100 : 0;
  return { pfEmpAmt, esiEmpAmt, esiEligible, statutoryBase };
}
function pfApplicable(status){ return String(status).toLowerCase() === "active"; }
function esiApplicable(baseCtc){ return Number(baseCtc || 0) <= Number(clientControl.esiWageLimitMonthly || 0); }
function toUi(r){ return { empId: String(r.id || "").toUpperCase(), empName: String(r.name || ""), doj: String(r.doj || ""), dept: String(r.dept || ""), designation: String(r.desig || ""), employmentType: String(r.type || "Full-time"), status: String(r.status || "Active"), baseCtc: Number(r.baseCtc || 0), pf: String(r.pf || ""), esi: String(r.esi || ""), uan: String(r.uan || ""), esiNo: String(r.esiNo || ""), mobile: String(r.mobile || ""), email: String(r.email || ""), pfNo: String(r.pfNo || ""), bankName: String(r.bankName || ""), bankAc: String(r.bankAc || ""), ifsc: String(r.ifsc || ""), aadharNo: String(r.aadharNo || ""), panCard: String(r.panCard || ""), address: String(r.address || "") }; }
function toApi(r){ return { id: String(r.empId || "").toUpperCase(), name: String(r.empName || ""), doj: String(r.doj || ""), dept: String(r.dept || ""), desig: String(r.designation || ""), type: String(r.employmentType || "Full-time"), status: String(r.status || "Active"), pf: String(r.pf || (pfApplicable(r.status) ? "Yes" : "No")), esi: String(r.esi || (esiApplicable(r.baseCtc) ? "Yes" : "No")), baseCtc: Number(r.baseCtc || 0), mobile: String(r.mobile || ""), email: String(r.email || ""), uan: String(r.uan || ""), esiNo: String(r.esiNo || ""), pfNo: String(r.pfNo || ""), bankName: String(r.bankName || ""), bankAc: String(r.bankAc || ""), ifsc: String(r.ifsc || ""), aadharNo: String(r.aadharNo || ""), panCard: String(r.panCard || ""), address: String(r.address || "") }; }
function safeParse(s){ try { return JSON.parse(s); } catch(_e){ return null; } }
function loadEmployeesLocal(){ const rows = safeParse(localStorage.getItem(KEY_EMP_LOCAL)); return Array.isArray(rows) ? rows : []; }
function saveEmployeesLocal(rows){ localStorage.setItem(KEY_EMP_LOCAL, JSON.stringify(Array.isArray(rows) ? rows : [])); }
function saveEmpExtras(){ localStorage.setItem(KEY_EMP_EXTRA, JSON.stringify(empExtras)); }
function fmtDays(v){ const n = Number(v || 0); return Number.isInteger(n) ? String(n) : n.toFixed(1); }
function fileToDataUrl(file){
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(String(reader.result || ""));
    reader.onerror = () => reject(new Error("Failed to read attachment."));
    reader.readAsDataURL(file);
  });
}
function openPdfDataUrl(dataUrl){
  try {
    const byteString = atob(String(dataUrl).split(',')[1] || '');
    const mimeString = String(dataUrl).split(',')[0].split(':')[1]?.split(';')[0] || 'application/pdf';
    const ab = new ArrayBuffer(byteString.length);
    const ia = new Uint8Array(ab);
    for(let i = 0; i < byteString.length; i++) ia[i] = byteString.charCodeAt(i);
    const blob = new Blob([ab], { type: mimeString });
    const blobUrl = URL.createObjectURL(blob);
    const w = window.open(blobUrl, "_blank");
    if(!w) alert("Popup blocked. Please allow popups for this site.");
    setTimeout(() => URL.revokeObjectURL(blobUrl), 30000);
  } catch(_e){
    window.open(dataUrl, "_blank");
  }
}

async function apiFetch(path, options = {}){
  const errors = [];
  for(const base of API_BASES){
    const url = `${base}${path}`;
    try {
      const res = await fetch(url, options);
      if(res.status === 404 || res.status === 405){ errors.push(`${url}:${res.status}`); continue; }
      return res;
    } catch (e){ errors.push(`${url}:${e}`); }
  }
  throw new Error("API unavailable " + errors.join(" | "));
}

async function loadControl(){
  const toControlModel = (d) => {
    const byName = {};
    if(Array.isArray(d?.ctcSplitRows)){
      d.ctcSplitRows.forEach((r) => {
        const k = String(r?.key || "").trim();
        if(k) byName[k] = Number(r?.pct ?? 0);
      });
    }
    const pickNum = (primary, fallback, def) => {
      if(primary !== undefined && primary !== null && primary !== "") return Number(primary);
      if(fallback !== undefined && fallback !== null && fallback !== "") return Number(fallback);
      return Number(def);
    };
    const pickStr = (primary, fallback, def) => {
      if(primary !== undefined && primary !== null && primary !== "") return String(primary);
      if(fallback !== undefined && fallback !== null && fallback !== "") return String(fallback);
      return String(def);
    };
    const basic = pickNum(d?.ctcBasicPct, byName.ctcBasicPct, 40);
    const hra = pickNum(d?.ctcHraPct, byName.ctcHraPct, 40);
    const conv = pickNum(d?.ctcConvPct, byName.ctcConvPct, 5);
    const edu = pickNum(d?.ctcEduPct, byName.ctcEduPct, 2);
    const da = pickNum(d?.ctcDaPct, byName.ctcDaPct, 0);
    const special = pickNum(d?.ctcSpecialPct, byName.ctcSpecialPct, 13);
    const ctcSplitRows = Array.isArray(d?.ctcSplitRows)
      ? d.ctcSplitRows.map((r) => ({ name: String(r?.name || r?.key || ""), pct: pickNum(r?.pct, undefined, 0) })).filter((r) => r.name)
      : [];
    return {
      year: new Date().getFullYear(),
      month: new Date().getMonth() + 1,
      pfEmpPct: pickNum(d?.pfEmpPct ?? d?.pfEmployeePct, undefined, 12),
      pfErPct: pickNum(d?.pfErPct ?? d?.pfEmployerPct, undefined, 13),
      esiEmpPct: pickNum(d?.esiEmpPct ?? d?.esiEmployeePct, undefined, 0.75),
      esiErPct: pickNum(d?.esiErPct ?? d?.esiEmployerPct, undefined, 3.25),
      esiWageLimitMonthly: pickNum(d?.esiWageLimit, undefined, 21000),
      ptEnabled: pickStr(d?.ptEnabled, undefined, "Yes"),
      ptMonthly: pickNum(d?.ptMonthly, undefined, 200),
      pfCapEnabled: String(d?.pfWageCapEnabled ?? "Yes").toLowerCase() === "yes",
      pfCapAmount: pickNum(d?.pfWageCapAmount, undefined, 15000),
      pfOnEsiPct: pickNum(d?.pfOnEsiPct, undefined, 70),
      lwfEnabled: pickStr(d?.lwfEnabled, undefined, "Yes"),
      lwfEmpAmt: pickNum(d?.lwfEmpAmt ?? d?.lwfEmployeeAmt, undefined, 20),
      basicPct: basic,
      hraPct: hra,
      convPct: conv,
      eduPct: edu,
      daPct: da,
      daPctBasic: pickNum(d?.daPctBasic ?? d?.daPctOfBasic, undefined, 0),
      specialPct: special,
      ctcSplitRows,
      ctcAddonRows: Array.isArray(d?.ctcAddonRows) ? d.ctcAddonRows : []
    };
  };
  try {
    const res = await apiFetch("/control");
    if(!res.ok) return;
    const d = await res.json();
    localStorage.setItem(KEY_CONTROL, JSON.stringify(d));
    clientControl = toControlModel(d);
  } catch (_e) {
    const d = safeParse(localStorage.getItem(KEY_CONTROL));
    if(d && typeof d === "object"){
      clientControl = toControlModel(d);
    }
  }
}
async function fetchEmployees(){
  try {
    const res = await apiFetch("/employees");
    if(!res.ok) throw new Error("Load failed");
    const data = await res.json();
    employees = Array.isArray(data.rows) ? data.rows.map(toUi) : [];
    const syncRows = [];
    for(const e of employees){
      const id = String(e.empId || "").toUpperCase();
      if(!id) continue;
      const ex = empExtras[id] || {};
      ex.pfNo = ex.pfNo || e.pfNo || "";
      ex.bankName = ex.bankName || e.bankName || "";
      ex.bankAc = ex.bankAc || e.bankAc || "";
      ex.ifsc = ex.ifsc || e.ifsc || "";
      ex.aadharNo = ex.aadharNo || e.aadharNo || "";
      ex.panCard = ex.panCard || e.panCard || "";
      ex.address = ex.address || e.address || "";
      empExtras[id] = ex;
      if((!e.bankAc && ex.bankAc) || (!e.pfNo && ex.pfNo) || (!e.bankName && ex.bankName) || (!e.ifsc && ex.ifsc) || (!e.aadharNo && ex.aadharNo) || (!e.panCard && ex.panCard) || (!e.address && ex.address)){
        syncRows.push({ ...e, pfNo: ex.pfNo || "", bankName: ex.bankName || "", bankAc: ex.bankAc || "", ifsc: ex.ifsc || "", aadharNo: ex.aadharNo || "", panCard: ex.panCard || "", address: ex.address || "" });
      }
    }
    for(const r of syncRows){
      try {
        const up = await apiFetch(`/employees/${encodeURIComponent(r.empId)}`, { method: "PUT", headers: { "Content-Type": "application/json" }, body: JSON.stringify(toApi(r)) });
        if(!up.ok) break;
      } catch(_e){ break; }
    }
    saveEmpExtras();
    saveEmployeesLocal(employees);
  } catch(_e){
    employees = loadEmployeesLocal();
  }
}
async function fetchEmployeeTypes(){
  try {
    const res = await apiFetch("/employee-types?activeOnly=1");
    if(!res.ok) throw new Error("Employee type load failed");
    const data = await res.json();
    employeeTypes = Array.isArray(data.rows) ? data.rows : [];
  } catch(_e){
    employeeTypes = [
      { code: "FULL_TIME", label: "Full-time", sortOrder: 10, isActive: true },
      { code: "PART_TIME", label: "Part-time", sortOrder: 20, isActive: true },
      { code: "CONTRACT", label: "Contract", sortOrder: 30, isActive: true },
      { code: "INTERN", label: "Intern", sortOrder: 40, isActive: true }
    ];
  }
  syncEmploymentTypeOptions();
}
function syncEmploymentTypeOptions(selectedValue){
  const select = document.getElementById("employmentType");
  if(!select) return;
  const current = String(selectedValue ?? select.value ?? "").trim();
  const options = employeeTypes.slice().sort((a,b) => Number(a.sortOrder || 0) - Number(b.sortOrder || 0) || String(a.label || "").localeCompare(String(b.label || "")));
  const optionHtml = ['<option value="">Select employee type</option>']
    .concat(options.map((row) => `<option value="${String(row.label || "").replace(/"/g, "&quot;")}">${String(row.label || "")}</option>`));
  select.innerHTML = optionHtml.join("");
  if(current && !options.some((row) => String(row.label || "") === current)){
    const opt = document.createElement("option");
    opt.value = current;
    opt.textContent = `${current} (legacy)`;
    select.appendChild(opt);
  }
  select.value = current || "";
}
async function createEmployee(r){
  try {
    const res = await apiFetch("/employees", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(toApi(r)) });
    if(!res.ok) throw new Error(await res.text());
  } catch(_e){
    const local = loadEmployeesLocal();
    const id = String(r.empId || "").toUpperCase();
    if(local.some(x => String(x.empId || "").toUpperCase() === id)) throw new Error("Employee ID already exists");
    local.push({ ...r, empId: id });
    saveEmployeesLocal(local);
  }
}
async function updateEmployee(r){
  try {
    const res = await apiFetch(`/employees/${encodeURIComponent(r.empId)}`, { method: "PUT", headers: { "Content-Type": "application/json" }, body: JSON.stringify(toApi(r)) });
    if(!res.ok) throw new Error(await res.text());
  } catch(_e){
    const local = loadEmployeesLocal();
    const id = String(r.empId || "").toUpperCase();
    const idx = local.findIndex(x => String(x.empId || "").toUpperCase() === id);
    if(idx < 0) throw new Error("Employee not found");
    local[idx] = { ...local[idx], ...r, empId: id };
    saveEmployeesLocal(local);
  }
}
async function removeEmployee(id){
  try {
    const res = await apiFetch(`/employees/${encodeURIComponent(id)}`, { method: "DELETE" });
    if(!res.ok) throw new Error(await res.text());
  } catch(_e){
    const local = loadEmployeesLocal().filter(x => String(x.empId || "").toUpperCase() !== String(id || "").toUpperCase());
    saveEmployeesLocal(local);
  }
}
async function clearGeneratedSalaryApis(){
  const paths = ["/payroll/clear", "/payslips/clear", "/pf-sheet/clear", "/esic-sheet/clear", "/ecr-sheet/clear"];
  for(const path of paths){
    try {
      const res = await apiFetch(path, { method: "POST" });
      if(!res.ok){
        // best-effort only
      }
    } catch(_e){
      // ignore fallback failures
    }
  }
}
async function invalidateSalaryDependentData(reason){
  await clearGeneratedSalaryApis();
  if(window.HRCommon && typeof window.HRCommon.bumpSalaryDataVersion === "function"){
    window.HRCommon.bumpSalaryDataVersion(reason || "employee-salary-update");
    return;
  }
  // Fallback when shared bootstrap is unavailable.
  [
    "hr_salary_sheet_files_v2",
    "hr_salary_sheet_files_v1",
    "hr_client_payslips_v1",
    "hr_client_pf_sheets_v1",
    "hr_client_esic_sheets_v1",
    "hr_client_ecr_sheets_v1"
  ].forEach((k) => localStorage.removeItem(k));
  const stamp = String(Date.now());
  localStorage.setItem(KEY_SALARY_DATA_VERSION, stamp);
  window.dispatchEvent(new CustomEvent("hr:salary-data-changed", { detail: { at: stamp, reason: String(reason || "") } }));
}
function normalizeLeaveItem(r){
  return {
    empId: String(r.empId || r.emp_id || "").toUpperCase(),
    leaveType: String(r.leaveType || r.leave_type || r.type || "").toUpperCase(),
    status: String(r.status || ""),
    days: Number(r.days || 0)
  };
}
async function loadLeaves(){
  try {
    const res = await apiFetch("/leaves");
    if(!res.ok) throw new Error("leave load failed");
    const data = await res.json();
    leaveEntries = Array.isArray(data.rows) ? data.rows.map(normalizeLeaveItem) : [];
  } catch (_e){
    const local = safeParse(localStorage.getItem(KEY_LEAVES)) || [];
    leaveEntries = Array.isArray(local) ? local.map(normalizeLeaveItem) : [];
  }
}
function getLeaveStats(empId){
  const extra = empExtras[empId] || {};
  const alloc = {
    cl: Number(extra.leaveAlloc?.cl || 0),
    sl: Number(extra.leaveAlloc?.sl || 0),
    el: Number(extra.leaveAlloc?.el || 0)
  };
  const used = { CL: 0, SL: 0, EL: 0, LOP: 0 };
  leaveEntries.forEach((x) => {
    if(String(x.empId).toUpperCase() !== String(empId).toUpperCase()) return;
    if(String(x.status || "").toLowerCase() === "rejected") return;
    if(!used.hasOwnProperty(x.leaveType)) return;
    used[x.leaveType] += Number(x.days || 0);
  });
  return {
    clBal: Math.max(0, alloc.cl - used.CL),
    slBal: Math.max(0, alloc.sl - used.SL),
    elBal: Math.max(0, alloc.el - used.EL),
    leavesTaken: used.CL + used.SL + used.EL,
    lopTaken: used.LOP
  };
}

function renderNotifs(listEl, emptyEl, dotEl){
  const unread = notifications.filter(n => n.unread).length;
  if(dotEl) dotEl.style.display = unread > 0 ? "block" : "none";
  if(!listEl) return;
  if(!notifications.length){ if(emptyEl) emptyEl.style.display = "block"; listEl.innerHTML = ""; return; }
  if(emptyEl) emptyEl.style.display = "none";
  listEl.innerHTML = notifications.map(n => `<button type="button" class="list-group-item list-group-item-action d-flex gap-2 py-3" onclick="openNotif(${n.id})"><div style="width:10px;">${n.unread ? '<span class="badge rounded-pill text-bg-primary">&nbsp;</span>' : ''}</div><div class="flex-grow-1"><div class="d-flex justify-content-between"><div class="fw-semibold">${n.title}</div><div class="text-muted small">${n.time}</div></div><div class="text-muted small">${n.desc}</div></div></button>`).join("");
}
function syncNotifUI(){ renderNotifs(document.getElementById("notifListDesktop"), document.getElementById("notifEmptyDesktop"), document.getElementById("notifDotDesktop")); }
window.openNotif = function(id){ const n = notifications.find(x => x.id === id); if(!n) return; n.unread = false; syncNotifUI(); };
document.getElementById("markAllReadBtnDesktop")?.addEventListener("click", () => { notifications = notifications.map(n => ({ ...n, unread: false })); syncNotifUI(); });

const empTbody = document.getElementById("empTbody");
const emptyState = document.getElementById("emptyState");
const resultCount = document.getElementById("resultCount");
const form = document.getElementById("empForm");
const modalEl = document.getElementById("addEmployeeModal");
const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
const modalTitle = modalEl.querySelector(".modal-title");
const submitBtn = document.querySelector('button[form="empForm"]');

function fillSummary(){
  const m = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
  document.getElementById("controlMonthLabel").textContent = `${m[clientControl.month - 1]} ${clientControl.year}`;
  const splitRows = getCtcRowsNormalized();
  const top = splitRows.slice(0, 2).map((r) => `${r.name} ${Number(r.pct || 0)}%`).join(" | ");
  const rest = splitRows.slice(2).map((r) => `${r.name} ${Number(r.pct || 0)}%`).join(" | ");
  document.getElementById("ctcSplitLabel").textContent = top || "Gross split not set";
  document.getElementById("ctcSplitSub").textContent = rest || "Set Gross Distribution in Control page";
  document.getElementById("esiLimitLabel").textContent = inr(clientControl.esiWageLimitMonthly);
  document.getElementById("pfCapLabel").textContent = clientControl.pfCapEnabled ? `Enabled | ${inr(clientControl.pfCapAmount)}` : "Disabled";
}

function refreshDeptOptions(){
  const sel = document.getElementById("filterDept");
  const keep = sel.value;
  const vals = [...new Set(employees.map(x => x.dept).filter(Boolean))].sort((a,b)=>a.localeCompare(b));
  sel.innerHTML = '<option value="">All</option>' + vals.map(v => `<option>${v}</option>`).join("");
  if(vals.includes(keep)) sel.value = keep;
}

function renderTable(){
  const q = (document.getElementById("searchInput").value || "").toLowerCase().trim();
  const fd = document.getElementById("filterDept").value;
  const fs = document.getElementById("filterStatus").value;
  const list = employees.filter(e => { const t = `${e.empId} ${e.empName} ${e.dept} ${e.designation} ${e.employmentType} ${e.status}`.toLowerCase(); if(q && !t.includes(q)) return false; if(fd && e.dept !== fd) return false; if(fs && e.status !== fs) return false; return true; });
  empTbody.innerHTML = list.map((e, idx) => {
    const hasDoc = !!(empExtras[e.empId]?.attachmentDataUrl);
    return `<tr><td>${idx + 1}</td><td class="fw-semibold">${e.empId}</td><td>${e.empName}</td><td class="nowrap">${e.doj || "-"}</td><td>${e.dept || "-"}</td><td>${e.designation || "-"}</td><td class="nowrap">${e.employmentType}</td><td><span class="badge ${e.status === "Active" ? "text-bg-success" : "text-bg-secondary"}">${e.status}</span></td><td class="text-end">${Number(e.baseCtc || 0).toLocaleString("en-IN")}</td><td><button class="btn btn-outline-secondary btn-sm" onclick="viewEmpDoc('${e.empId}')" ${hasDoc ? "" : "disabled"}><i class="bi bi-file-earmark-pdf"></i></button></td><td class="text-center"><div class="btn-group"><button class="btn btn-outline-primary btn-sm" title="View" aria-label="View" onclick="viewEmpProfile('${e.empId}')"><i class="bi bi-eye"></i></button><button class="btn btn-outline-secondary btn-sm" title="Edit" aria-label="Edit" onclick="editEmp('${e.empId}')"><i class="bi bi-pencil"></i></button><button class="btn btn-outline-danger btn-sm" title="Delete" aria-label="Delete" onclick="deleteEmp('${e.empId}')"><i class="bi bi-trash"></i></button></div></td></tr>`;
  }).join("");
  emptyState.classList.toggle("d-none", list.length !== 0);
  resultCount.textContent = `${list.length} record${list.length === 1 ? "" : "s"}`;
}

const prevCtcRows = document.getElementById("prevCtcRows");
const prevPfEmp = document.getElementById("prevPfEmp");
const prevEsiEmp = document.getElementById("prevEsiEmp");
const prevEsiEmpRow = document.getElementById("prevEsiEmpRow");
const prevPt = document.getElementById("prevPt");
const prevLwfEmp = document.getElementById("prevLwfEmp");
const prevCtcDist = document.getElementById("prevCtcDist");
const prevPf = document.getElementById("prevPf");
const prevEsi = document.getElementById("prevEsi");
const leaveTakenPreview = document.getElementById("leaveTakenPreview");
const lopTakenPreview = document.getElementById("lopTakenPreview");
const ctcMonthlyInput = document.getElementById("ctcMonthly");
const ctcYearlyInput = document.getElementById("ctcYearly");
function syncPreview(){
  const base = Number(document.getElementById("baseCtc").value || 0);
  const st = document.getElementById("status").value || "Active";
  const c = calcComponents(base);
  const stat = statutoryPreviewForBase(base);
  if(prevCtcRows){
    const statutoryNodes = Array.from(prevCtcRows.children);
    const dynamicRowsHtml = c.rows.map((r) => (
      `<div class="col-6 d-flex justify-content-between"><span class="text-muted">${r.name}</span><span class="fw-semibold">${inr(r.amount)}</span></div>`
    )).join("");
    prevCtcRows.innerHTML = dynamicRowsHtml;
    statutoryNodes.forEach((node) => prevCtcRows.appendChild(node));
  }
  if(prevPfEmp) prevPfEmp.textContent = inr(Math.round(stat.pfEmpAmt));
  if(prevEsiEmpRow) prevEsiEmpRow.style.display = stat.esiEligible ? "" : "none";
  if(prevEsiEmp) prevEsiEmp.textContent = stat.esiEligible ? inr(Math.round(stat.esiEmpAmt)) : "-";
  if(prevPt) prevPt.textContent = String(clientControl.ptEnabled || "Yes").toLowerCase() === "yes" ? inr(clientControl.ptMonthly || 0) : "Disabled";
  if(prevLwfEmp) prevLwfEmp.textContent = String(clientControl.lwfEnabled || "Yes").toLowerCase() === "yes" ? inr(clientControl.lwfEmpAmt || 0) : "Disabled";
  if(prevCtcDist){
    const dyn = getCtcRowsNormalized().map((r) => `${r.name} ${Number(r.pct || 0)}%`).join(" | ");
    prevCtcDist.textContent = `Gross Distribution (All %): ${dyn}`;
  }
  if(prevPf) prevPf.textContent = pfApplicable(st) ? "Yes" : "No";
  if(prevEsi) prevEsi.textContent = esiApplicable(base) ? "Yes" : "No";
  const ctc = calcCtcFigures(base);
  if(ctcMonthlyInput) ctcMonthlyInput.value = ctc.ctcMonthly > 0 ? String(Math.round(ctc.ctcMonthly)) : "";
  if(ctcYearlyInput) ctcYearlyInput.value = ctc.ctcYearly > 0 ? String(Math.round(ctc.ctcYearly)) : "";
}
function syncLeaveTakenPreview(){
  const empId = String(document.getElementById("empId").value || "").trim().toUpperCase();
  const lv = empId ? getLeaveStats(empId) : { leavesTaken: 0, lopTaken: 0 };
  if(leaveTakenPreview) leaveTakenPreview.value = fmtDays(lv.leavesTaken);
  if(lopTakenPreview) lopTakenPreview.value = fmtDays(lv.lopTaken);
}
function clearForm(){ form.reset(); form.classList.remove("was-validated"); editId = null; modalTitle.textContent = "Add Employee"; submitBtn.textContent = "Save Employee"; document.getElementById("empId").readOnly = false; document.getElementById("empAttachmentPdf").value = ""; syncEmploymentTypeOptions("Full-time"); syncPreview(); syncLeaveTakenPreview(); }

window.editEmp = function(empId){
  const e = employees.find(x => x.empId === empId);
  if(!e) return;
  editId = e.empId;
  modalTitle.textContent = "Edit Employee";
  submitBtn.textContent = "Update Employee";
  document.getElementById("empId").value = e.empId;
  document.getElementById("empId").readOnly = true;
  document.getElementById("empName").value = e.empName;
  document.getElementById("doj").value = e.doj || "";
  document.getElementById("dept").value = e.dept || "";
  document.getElementById("designation").value = e.designation || "";
  syncEmploymentTypeOptions(e.employmentType || "Full-time");
  document.getElementById("status").value = e.status || "Active";
  document.getElementById("baseCtc").value = Number(e.baseCtc || 0);
  document.getElementById("uan").value = e.uan || "";
  document.getElementById("esiNo").value = e.esiNo || "";
  document.getElementById("mobile").value = e.mobile || "";
  document.getElementById("email").value = e.email || "";
  const ex = empExtras[e.empId] || {};
  document.getElementById("empAddress").value = ex.address || "";
  document.getElementById("aadharNo").value = ex.aadharNo || "";
  document.getElementById("panCard").value = ex.panCard || "";
  document.getElementById("bankName").value = ex.bankName || "";
  document.getElementById("pfNo").value = ex.pfNo || "";
  document.getElementById("bankAc").value = ex.bankAc || "";
  document.getElementById("ifsc").value = ex.ifsc || "";
  document.getElementById("emergencyName1").value = ex.emergencyName1 || ex.emergencyName || "";
  document.getElementById("emergencyPhone1").value = ex.emergencyPhone1 || ex.emergencyPhone || "";
  document.getElementById("emergencyRelation1").value = ex.emergencyRelation1 || ex.emergencyRelation || "";
  document.getElementById("emergencyName2").value = ex.emergencyName2 || "";
  document.getElementById("emergencyPhone2").value = ex.emergencyPhone2 || "";
  document.getElementById("emergencyRelation2").value = ex.emergencyRelation2 || "";
  const lv = empExtras[e.empId]?.leaveAlloc || {};
  document.getElementById("leaveCl").value = Number(lv.cl || 0);
  document.getElementById("leaveSl").value = Number(lv.sl || 0);
  document.getElementById("leaveEl").value = Number(lv.el || 0);
  syncPreview();
  syncLeaveTakenPreview();
  modal.show();
};

window.deleteEmp = async function(empId){
  if(!confirm(`Delete ${empId}?`)) return;
  try { await removeEmployee(empId); delete empExtras[empId]; saveEmpExtras(); await fetchEmployees(); refreshDeptOptions(); renderTable(); await invalidateSalaryDependentData("employee-deleted"); }
  catch (e) { alert("Delete failed: " + (e.message || e)); }
};
window.viewEmpDoc = function(empId){
  const doc = empExtras[empId]?.attachmentDataUrl;
  if(!doc){ alert("No PDF attached for this employee."); return; }
  openPdfDataUrl(doc);
};
window.viewEmpProfile = function(empId){
  const id = String(empId || "").trim().toUpperCase();
  if(!id) return;
  window.location.href = `employee-profile.html?empId=${encodeURIComponent(id)}`;
};

form.addEventListener("submit", async (event) => {
  event.preventDefault();
  event.stopPropagation();
  if(!form.checkValidity()){ form.classList.add("was-validated"); return; }
  const attachmentFile = document.getElementById("empAttachmentPdf").files?.[0];
  if(attachmentFile && attachmentFile.type !== "application/pdf" && !attachmentFile.name.toLowerCase().endsWith(".pdf")){
    alert("Attachment must be a PDF file.");
    return;
  }
  const row = { empId: document.getElementById("empId").value.trim().toUpperCase(), empName: document.getElementById("empName").value.trim(), doj: document.getElementById("doj").value, dept: document.getElementById("dept").value, designation: document.getElementById("designation").value.trim(), employmentType: document.getElementById("employmentType").value, status: document.getElementById("status").value, baseCtc: Number(document.getElementById("baseCtc").value || 0), uan: document.getElementById("uan").value.trim(), esiNo: document.getElementById("esiNo").value.trim(), mobile: document.getElementById("mobile").value.trim(), email: document.getElementById("email").value.trim() };
  const prev = editId ? employees.find((x) => String(x.empId || "").toUpperCase() === String(editId || "").toUpperCase()) : null;
  const salaryChanged = !editId || Number(prev?.baseCtc || 0) !== Number(row.baseCtc || 0);
  const address = String(document.getElementById("empAddress").value || "").trim();
  const aadharNo = String(document.getElementById("aadharNo").value || "").trim();
  const panCard = String(document.getElementById("panCard").value || "").trim();
  const bankName = String(document.getElementById("bankName").value || "").trim();
  const pfNo = String(document.getElementById("pfNo").value || "").trim();
  const bankAc = String(document.getElementById("bankAc").value || "").trim();
  const ifsc = String(document.getElementById("ifsc").value || "").trim();
  const emergencyName1 = String(document.getElementById("emergencyName1").value || "").trim();
  const emergencyPhone1 = String(document.getElementById("emergencyPhone1").value || "").trim();
  const emergencyRelation1 = String(document.getElementById("emergencyRelation1").value || "").trim();
  const emergencyName2 = String(document.getElementById("emergencyName2").value || "").trim();
  const emergencyPhone2 = String(document.getElementById("emergencyPhone2").value || "").trim();
  const emergencyRelation2 = String(document.getElementById("emergencyRelation2").value || "").trim();
  row.pfNo = pfNo;
  row.bankName = bankName;
  row.bankAc = bankAc;
  row.ifsc = ifsc;
  row.aadharNo = aadharNo;
  row.panCard = panCard;
  row.address = address;
  const leaveAlloc = {
    cl: Number(document.getElementById("leaveCl").value || 0),
    sl: Number(document.getElementById("leaveSl").value || 0),
    el: Number(document.getElementById("leaveEl").value || 0)
  };
  try {
    if(editId) await updateEmployee(row);
    else await createEmployee(row);
    const existing = empExtras[row.empId] || {};
    existing.leaveAlloc = leaveAlloc;
    existing.address = address;
    existing.aadharNo = aadharNo;
    existing.panCard = panCard;
    existing.bankName = bankName;
    existing.pfNo = pfNo;
    existing.bankAc = bankAc;
    existing.ifsc = ifsc;
    existing.emergencyName1 = emergencyName1;
    existing.emergencyPhone1 = emergencyPhone1;
    existing.emergencyRelation1 = emergencyRelation1;
    existing.emergencyName2 = emergencyName2;
    existing.emergencyPhone2 = emergencyPhone2;
    existing.emergencyRelation2 = emergencyRelation2;
    existing.emergencyName = emergencyName1;
    existing.emergencyPhone = emergencyPhone1;
    existing.emergencyRelation = emergencyRelation1;
    if(attachmentFile){
      existing.attachmentName = attachmentFile.name;
      existing.attachmentDataUrl = await fileToDataUrl(attachmentFile);
      empExtras[row.empId] = existing;
      saveEmpExtras();
    } else if(!empExtras[row.empId]) {
      empExtras[row.empId] = existing;
      saveEmpExtras();
    }
    await fetchEmployees();
    refreshDeptOptions();
    renderTable();
    if(salaryChanged) await invalidateSalaryDependentData(editId ? "employee-salary-updated" : "employee-created");
    clearForm();
    modal.hide();
  } catch (e) {
    alert("Save failed: " + (e.message || e));
  }
}, false);

function buildExportRows(){
    return employees.map(e => {
      const extra = empExtras[e.empId] || {};
      return {
        Emp_ID: e.empId || "",
        Employee_Name: e.empName || "",
        DOJ: e.doj || "",
        Department: e.dept || "",
        Designation: e.designation || "",
        Emp_Type: e.employmentType || "",
        Status: e.status || "",
        Gross_Monthly: Number(e.baseCtc || 0),
        UAN: e.uan || "",
        PF_No: extra.pfNo || `PF-${e.empId}`,
        ESI_No: e.esiNo || "",
        Bank_Name: extra.bankName || "",
        Bank_AC: extra.bankAc || "",
        IFSC: extra.ifsc || "",
        Aadhar_Card: extra.aadharNo || "",
        PAN_Card: extra.panCard || "",
        Address: extra.address || "",
        Mobile: e.mobile || "",
        Email: e.email || ""
      };
    });
}

async function exportXlsx(){
  if(!Array.isArray(employees) || !employees.length){
    try {
      await fetchEmployees();
    } catch(_e){}
  }
  const rows = buildExportRows();
  if(!rows.length){
    alert("No employee data available to export.");
    return;
  }
  const note = [
    ["Employee Master Export"],
    ["This sheet contains the current employee master data. You can review or edit it and import it back if needed."],
    ["Employee ID must stay unique. Required fields for re-import: Employee ID, Employee Name, DOJ, Department, Designation, Employment Type, Status, Gross Monthly."],
    ["Emp_Type values should match Employee Type master. Status values: Active | Inactive."],
    ["Attachment PDFs and per-employee leave allocation are maintained separately in the app."],
    []
  ];
  const ws = XLSX.utils.aoa_to_sheet(note);
  XLSX.utils.sheet_add_json(ws, rows, { origin: "A8", skipHeader: false });
  const colCount = Object.keys(rows[0] || {}).length;
  const rowStart = 8;
  const rowEnd = rowStart + rows.length;
  const mkCol = (n) => { let s = ""; while(n > 0){ const m = (n - 1) % 26; s = String.fromCharCode(65 + m) + s; n = Math.floor((n - 1) / 26); } return s; };
  if(colCount > 0 && rows.length){
    ws["!autofilter"] = { ref: `A${rowStart}:${mkCol(colCount)}${rowEnd}` };
    ws["!cols"] = Array.from({ length: colCount }, () => ({ wch: 18 }));
    for(let r = rowStart; r <= rowEnd; r++){
      for(let c = 1; c <= colCount; c++){
        const ref = `${mkCol(c)}${r}`;
        if(!ws[ref]) ws[ref] = { t: "s", v: "" };
        ws[ref].s = {
          border: { top:{style:"thin", color:{rgb:"FF000000"}}, bottom:{style:"thin", color:{rgb:"FF000000"}}, left:{style:"thin", color:{rgb:"FF000000"}}, right:{style:"thin", color:{rgb:"FF000000"}} },
          fill: r === rowStart ? { patternType: "solid", fgColor: { rgb: "FF0B1F3A" } } : undefined,
          font: r === rowStart ? { bold: true, color: { rgb: "FFFFFFFF" } } : undefined
        };
      }
    }
  }
  const wb = XLSX.utils.book_new();
  const instructionsRows = [
    ["Column", "Description", "Required", "Valid Values / Format"],
    ["Employee ID", "Unique employee code", "Yes", "EMP001 format"],
    ["Employee Name", "Employee full name", "Yes", "Text"],
    ["DOJ", "Date of joining", "Yes", "YYYY-MM-DD"],
    ["Department", "Department", "Yes", "HR/Sales/Accounts/Operations/Tech/Testing"],
    ["Designation", "Designation", "Yes", "Text"],
    ["Employment Type", "Employment category", "Yes", "Full-time/Part-time/Contract/Intern"],
    ["Status", "Current status", "Yes", "Active/Inactive"],
    ["Gross Monthly", "Gross monthly salary", "Yes", "Number"],
    ["UAN/PF/ESI", "Statutory IDs", "No", "Text"],
    ["Bank Name/Account/IFSC", "Bank details", "No", "Text"],
    ["Aadhar/PAN", "KYC details", "No", "Text"],
    ["Address", "Employee address", "No", "Text"],
    ["Mobile/Email", "Contact details", "No", "Text"]
  ];
  const wsHelp = XLSX.utils.aoa_to_sheet(instructionsRows);
  wsHelp["!cols"] = [{ wch: 40 }, { wch: 44 }, { wch: 12 }, { wch: 48 }];
  XLSX.utils.book_append_sheet(wb, ws, "Employee_Master");
  XLSX.utils.book_append_sheet(wb, wsHelp, "Instructions");
  XLSX.writeFileXLSX(wb, "employee_master_export.xlsx", { cellStyles: true, bookType: "xlsx" });
}

function parseCsvLine(line){
  const out = [];
  let cur = "";
  let inQuotes = false;
  for(let i = 0; i < line.length; i++){
    const ch = line[i];
    if(ch === '"'){
      if(inQuotes && line[i + 1] === '"'){ cur += '"'; i++; }
      else inQuotes = !inQuotes;
      continue;
    }
    if(ch === "," && !inQuotes){ out.push(cur); cur = ""; continue; }
    cur += ch;
  }
  out.push(cur);
  return out.map(x => x.trim());
}

function getCell(obj, names){
  for(const n of names){
    if(Object.prototype.hasOwnProperty.call(obj, n)) return obj[n];
    const k = Object.keys(obj).find(x => String(x).toLowerCase() === String(n).toLowerCase());
    if(k) return obj[k];
  }
  return "";
}

function parseImportNumber(v){
  if(v === null || v === undefined || v === "") return 0;
  const cleaned = String(v).replace(/,/g, "").trim();
  const n = Number(cleaned);
  return Number.isFinite(n) ? n : 0;
}

function parseImportDate(v){
  if(v === null || v === undefined || v === "") return "";
  if(typeof v === "number" && Number.isFinite(v)){
    const parsed = XLSX.SSF.parse_date_code(v);
    if(parsed && parsed.y && parsed.m && parsed.d){
      return `${String(parsed.y).padStart(4, "0")}-${String(parsed.m).padStart(2, "0")}-${String(parsed.d).padStart(2, "0")}`;
    }
  }
  const text = String(v).trim();
  const normalized = text.replace(/\//g, "-");
  if(/^\d{4}-\d{2}-\d{2}$/.test(normalized)) return normalized;
  const dm = normalized.match(/^(\d{1,2})-(\d{1,2})-(\d{4})$/);
  if(dm){
    const dd = String(dm[1]).padStart(2, "0");
    const mm = String(dm[2]).padStart(2, "0");
    const yy = String(dm[3]);
    return `${yy}-${mm}-${dd}`;
  }
  return text;
}

async function importSpreadsheetFile(file){
  const buffer = await file.arrayBuffer();
  const wb = XLSX.read(buffer, { type: "array" });
  const preferred = wb.Sheets["Add_Employee_Import"] ? "Add_Employee_Import" : (wb.Sheets["Add_Employee_Sample"] ? "Add_Employee_Sample" : wb.SheetNames[0]);
  const ws = wb.Sheets[preferred];
  const grid = XLSX.utils.sheet_to_json(ws, { header: 1, defval: "" });
  let headerRow = -1;
  for(let i = 0; i < Math.min(grid.length, 50); i++){
    const row = Array.isArray(grid[i]) ? grid[i].map((x) => String(x || "").trim().toLowerCase()) : [];
    if(row.includes("emp_id") && row.includes("employee_name")){
      headerRow = i;
      break;
    }
  }
  const rows = headerRow >= 0
    ? XLSX.utils.sheet_to_json(ws, { defval: "", range: headerRow })
    : XLSX.utils.sheet_to_json(ws, { defval: "" });
  if(!rows.length){ alert("Import file has no data rows."); return; }

  const existing = new Set(employees.map(e => e.empId));
  const prevSalary = new Map(employees.map((e) => [String(e.empId || "").toUpperCase(), Number(e.baseCtc || 0)]));
  let salaryChanged = false;
  let ok = 0;
  let fail = 0;
  for(const r of rows){
    const empId = String(getCell(r, ["Emp_ID","Employee_ID","ID"])).trim().toUpperCase();
    const empName = String(getCell(r, ["Employee_Name","Name"])).trim();
    if(!empId || !empName){ fail++; continue; }

    const row = {
      empId,
      empName,
      doj: parseImportDate(getCell(r, ["DOJ"])),
      dept: String(getCell(r, ["Department","Dept"])).trim(),
      designation: String(getCell(r, ["Designation"])).trim(),
      employmentType: String(getCell(r, ["Emp_Type","Emp type","Employment_Type","Employment Type"])).trim() || "Full-time",
      status: String(getCell(r, ["Status"])).trim() || "Active",
      baseCtc: parseImportNumber(getCell(r, ["Gross_Monthly","Gross Monthly","Base_CTC_Monthly","Base CTC Monthly"])),
      pf: String(getCell(r, ["PF_Applicable"])).trim() || undefined,
      esi: String(getCell(r, ["ESI_Applicable"])).trim() || undefined,
      uan: String(getCell(r, ["UAN"])).trim(),
      esiNo: String(getCell(r, ["ESI_No"])).trim(),
      pfNo: String(getCell(r, ["PF_No"])).trim(),
      bankName: String(getCell(r, ["Bank_Name","Bank Name"])).trim(),
      bankAc: String(getCell(r, ["Bank_AC"])).trim(),
      ifsc: String(getCell(r, ["IFSC"])).trim(),
      aadharNo: String(getCell(r, ["Aadhar_Card","Aadhar Card","Aadhaar"])).trim(),
      panCard: String(getCell(r, ["PAN_Card","PAN Card"])).trim(),
      address: String(getCell(r, ["Address"])).trim(),
      mobile: String(getCell(r, ["Mobile"])).trim(),
      email: String(getCell(r, ["Email"])).trim()
    };
    empExtras[empId] = {
      bankName: String(getCell(r, ["Bank_Name","Bank Name"])).trim(),
      aadharNo: String(getCell(r, ["Aadhar_Card","Aadhar Card","Aadhaar"])).trim(),
      panCard: String(getCell(r, ["PAN_Card","PAN Card"])).trim(),
      pfNo: String(getCell(r, ["PF_No"])).trim(),
      bankAc: String(getCell(r, ["Bank_AC"])).trim(),
      ifsc: String(getCell(r, ["IFSC"])).trim(),
      address: String(getCell(r, ["Address"])).trim(),
      leaveAlloc: {
        cl: Number(getCell(r, ["Leave_CL_Allocated","Leave CL Allocated","CL"]) || 0),
        sl: Number(getCell(r, ["Leave_SL_Allocated","Leave SL Allocated","SL"]) || 0),
        el: Number(getCell(r, ["Leave_EL_Allocated","Leave EL Allocated","EL"]) || 0)
      },
      emergencyName1: String(getCell(r, ["Emergency_Contact1_Name","Emergency_Contact_Name","Emergency Name"])).trim(),
      emergencyPhone1: String(getCell(r, ["Emergency_Contact1_Mobile","Emergency_Contact_Mobile","Emergency Mobile"])).trim(),
      emergencyRelation1: String(getCell(r, ["Emergency_Contact1_Relation","Emergency_Contact_Relation","Emergency Relation"])).trim(),
      emergencyName2: String(getCell(r, ["Emergency_Contact2_Name","Emergency2 Name"])).trim(),
      emergencyPhone2: String(getCell(r, ["Emergency_Contact2_Mobile","Emergency2 Mobile"])).trim(),
      emergencyRelation2: String(getCell(r, ["Emergency_Contact2_Relation","Emergency2 Relation"])).trim()
    };
    try {
      if(!existing.has(empId)) salaryChanged = true;
      else if(Number(prevSalary.get(empId) || 0) !== Number(row.baseCtc || 0)) salaryChanged = true;
      if(existing.has(empId)) await updateEmployee(row);
      else { await createEmployee(row); existing.add(empId); }
      ok++;
    } catch(_e){
      fail++;
    }
  }
  saveEmpExtras();
  await fetchEmployees();
  refreshDeptOptions();
  renderTable();
  if(salaryChanged && ok > 0) await invalidateSalaryDependentData("employee-import-salary-update");
  alert(`Import complete. Success: ${ok}, Failed: ${fail}`);
}

["searchInput","filterDept","filterStatus"].forEach(id => { document.getElementById(id).addEventListener("input", renderTable); document.getElementById(id).addEventListener("change", renderTable); });
document.getElementById("clearFilters").addEventListener("click", () => { document.getElementById("searchInput").value = ""; document.getElementById("filterDept").value = ""; document.getElementById("filterStatus").value = ""; renderTable(); });
document.getElementById("btnExportEmployees")?.addEventListener("click", exportXlsx);
document.getElementById("btnImportEmployees")?.addEventListener("click", () => document.getElementById("importEmployeesFile")?.click());
document.getElementById("importEmployeesFile")?.addEventListener("change", async (event) => {
  const file = event.target.files?.[0];
  if(!file) return;
  await importSpreadsheetFile(file);
  event.target.value = "";
});
document.getElementById("btnClearEmployeeHistory")?.addEventListener("click", async () => {
  if(!employees.length){ alert("No employees to clear."); return; }
  if(!confirm("Clear all employees from Employee Master List?")) return;
  const ids = employees.map((e) => String(e.empId || "").toUpperCase()).filter(Boolean);
  for(const id of ids){
    try { await removeEmployee(id); } catch(_e){}
  }
  empExtras = {};
  saveEmpExtras();
  await fetchEmployees();
  refreshDeptOptions();
  renderTable();
  await invalidateSalaryDependentData("employees-cleared");
});
["baseCtc","status"].forEach(id => document.getElementById(id).addEventListener("input", syncPreview));
document.getElementById("empId").addEventListener("input", syncLeaveTakenPreview);
modalEl.addEventListener("hidden.bs.modal", clearForm);
modalEl.addEventListener("shown.bs.modal", () => { syncPreview(); syncLeaveTakenPreview(); });

(async function init(){
  empExtras = safeParse(localStorage.getItem(KEY_EMP_EXTRA)) || {};
  await loadControl();
  await loadLeaves();
  await fetchEmployeeTypes();
  fillSummary();
  syncNotifUI();
  await fetchEmployees();
  refreshDeptOptions();
  renderTable();
  clearForm();
})();
