const $ = (id) => document.getElementById(id);
const htmlEl = document.documentElement;

function applyTheme(theme){
  htmlEl.setAttribute("data-bs-theme", theme);
  localStorage.setItem("hr_portal_theme", theme);
  const isDark = theme === "dark";
  if ($("themeIcon")) $("themeIcon").className = isDark ? "bi bi-sun" : "bi bi-moon";
}
applyTheme(localStorage.getItem("hr_portal_theme") || "light");
$("themeToggle")?.addEventListener("click", () => {
  const current = htmlEl.getAttribute("data-bs-theme") || "light";
  applyTheme(current === "dark" ? "light" : "dark");
});

const API_EMP = "/api/employees";
const API_FNF_GEN = "/api/fnf/generate";
const API_FNF_LIST = "/api/fnf/sheets";
const API_FNF_CLEAR = "/api/fnf/clear";
const API_GRATUITY_LIST = "/api/gratuity/sheets";
const API_CONTROL = "/api/control";
const API_ADVANCES = "/api/advances";
const API_LOANS = "/api/loans";
const API_INCENTIVES = "/api/incentives";
const KEY = "fnf_data";
const KEY_ATT = "hr_client_attendance_daily_v1";
const KEY_EMP_EXTRA = "hr_emp_extra_v1";
const KEY_LEAVES = "hr_client_leaves_v1";
const KEY_CONTROL = "hr_client_control_v1";
const FNF_TEMPLATE_XLSX = "assets/templates/new-fnfs.xlsx";

let fnfRows = [];
let fnfViewModal = null;
let empSelectTs = null;
let employeeById = new Map();
let currentViewRow = null;
let gratuitySheetCache = new Map();

function round2(n){ return Math.round(Number(n||0)); }
function money(n){ return round2(n).toLocaleString("en-IN", { maximumFractionDigits:0 }); }
function esc(v){
  return String(v ?? "").replace(/[&<>"']/g, (ch) => (
    {"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[ch]
  ));
}
function controlOtherDeductionTotal(control){
  return (Array.isArray(control.otherDeductionRows) ? control.otherDeductionRows : [])
    .reduce((sum, r) => sum + Number(r.amount || 0), 0);
}
function computeStatutoryFromControl(control, gross, earned, emp, monthNum){
  const splitRows = Array.isArray(control.splitRows) ? control.splitRows : [];
  const basicPct = Number((splitRows.find((r) => String(r.label || "").toLowerCase().includes("basic")) || {}).pct || 0);
  const basicEarned = earned * (basicPct / 100);
  const pfEnabled = String(emp.pf || "Yes").toLowerCase() !== "no";
  const esiEnabled = String(emp.esi || "Yes").toLowerCase() !== "no";
  const esiLimit = Number(control.esiWageLimit || 0);
  const esiApplicable = esiEnabled && earned > 0 && esiLimit > 0 && earned <= esiLimit;
  const basicForPf = basicEarned * (1 + Number(control.daPctBasic || 0) / 100);
  const statutoryBase = esiApplicable
    ? (earned * Number(control.pfOnEsiPct || 0) / 100)
    : earned;
  const pfWages = esiApplicable
    ? statutoryBase
    : ((control.pfWageCapEnabled && esiLimit > 0 && earned > esiLimit) ? Number(control.pfWageCapAmount || 0) : basicForPf);
  const pf = pfEnabled ? (pfWages * Number(control.pfPct || 0) / 100) : 0;
  const esi = esiApplicable ? (statutoryBase * Number(control.esiPct || 0) / 100) : 0;
  const pt = control.ptEnabled ? Number(control.ptMonthly || 0) : 0;
  const lwf = (control.lwfEnabled && (Number(control.lwfMonth || 0) === 0 || Number(control.lwfMonth || 0) === Number(monthNum || 0)))
    ? Number(control.lwfEmpAmt || 0)
    : 0;
  return { pf, esi, pt, lwf, esiApplicable, statutoryBase, pfWages };
}
function calcFinalLocal(data){
  const gross = Number(data.gross || 0);
  const control = getControlSettings();
  const exitIso = String(data.exitDate || data.exit || "");
  const exitDt = exitIso ? new Date(exitIso) : null;
  const monthDays = (exitDt && !Number.isNaN(exitDt.getTime()))
    ? daysInMonth(exitDt.getFullYear(), exitDt.getMonth() + 1)
    : 30;
  const perDay = gross > 0 && monthDays > 0 ? gross / monthDays : 0;
  const paidDays = Number(data.paidDays || 0);
  const lopDays = Number(data.lopDays || 0);
  const earned = perDay * paidDays;
  const lopDed = perDay * lopDays;
  const leaveEncash = perDay * Number(data.elDays || 0);
  const total = earned + leaveEncash + Number(data.bonus || 0) + Number(data.gratuity || 0);
  const controlOtherDed = controlOtherDeductionTotal(control);
  const emp = employeeById.get(String(data.empId || "").toUpperCase()) || {};
  const exitMonthNum = (exitDt && !Number.isNaN(exitDt.getTime())) ? (exitDt.getMonth() + 1) : 0;
  const stat = computeStatutoryFromControl(control, gross, earned, emp, exitMonthNum);
  const noDeductionRuleApplied = paidDays < 15;
  const statutory = noDeductionRuleApplied ? 0 : (stat.pf + stat.esi + stat.pt + stat.lwf);
  const deductions = noDeductionRuleApplied
    ? 0
    : (lopDed + Number(data.advance || 0) + Number(data.notice || 0) + controlOtherDed + statutory);
  return round2(total - deductions);
}
function safeDate(value){
  const d = new Date(String(value || ""));
  return Number.isNaN(d.getTime()) ? null : d;
}
function numOrZero(v){
  const n = Number(v);
  return Number.isFinite(n) ? n : 0;
}
function calcPayableFromForm(){
  const payload = {
    exitDate: $("exitDate")?.value || "",
    gross: Number($("gross")?.value || 0),
    paidDays: Number($("paidDays")?.value || 0),
    lopDays: Number($("lopDays")?.value || 0),
    elDays: Number($("elDays")?.value || 0),
    bonus: Number($("bonus")?.value || 0),
    gratuity: Number($("gratuity")?.value || 0),
    advance: Number($("advance")?.value || 0),
    notice: Number($("notice")?.value || 0)
  };
  return calcFinalLocal(payload);
}
function refreshPayablePreview(){
  const el = $("payableSalaryPreview");
  if(!el) return;
  el.value = `Rs ${money(calcPayableFromForm())}`;
}

function setStorageMode(text){
  if($("storageMode")) $("storageMode").textContent = text;
}
function safeParse(s){
  try { return JSON.parse(s); } catch(_e){ return null; }
}
function asNumberOrZero(v){
  const n = Number(v);
  return Number.isFinite(n) ? n : 0;
}
function asBool(v){
  if(typeof v === "boolean") return v;
  return String(v ?? "").trim().toLowerCase() === "yes";
}
function daysInMonth(y,m){ return new Date(y, m, 0).getDate(); }
function monthKey(y,m){ return `${y}-${String(m).padStart(2,"0")}`; }
function formatDateDDMMYYYY(v){
  if(!v) return "-";
  const d = new Date(v);
  if(Number.isNaN(d.getTime())) return String(v);
  return `${String(d.getDate()).padStart(2, "0")}-${String(d.getMonth()+1).padStart(2, "0")}-${d.getFullYear()}`;
}
function loadLocal(){
  try { return JSON.parse(localStorage.getItem(KEY) || "[]"); }
  catch(_e){ return []; }
}
function saveLocal(rows){
  localStorage.setItem(KEY, JSON.stringify(rows));
}

async function fetchEmployees(){
  const r = await fetch(API_EMP, { cache: "no-store" });
  if(!r.ok) throw new Error("employee list failed");
  const payload = await r.json();
  return payload.rows || [];
}
async function generateApi(payload){
  const r = await fetch(API_FNF_GEN, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload)
  });
  if(!r.ok){
    const msg = await r.text();
    throw new Error(msg || "generate failed");
  }
  return (await r.json()).sheet;
}
async function listApi(){
  const r = await fetch(API_FNF_LIST, { cache: "no-store" });
  if(!r.ok) throw new Error("list failed");
  return (await r.json()).rows || [];
}
async function getApi(id){
  const r = await fetch(`${API_FNF_LIST}/${id}`, { cache: "no-store" });
  if(!r.ok) throw new Error("get failed");
  return (await r.json()).sheet;
}
async function deleteApi(id){
  const r = await fetch(`${API_FNF_LIST}/${id}`, { method: "DELETE" });
  if(!r.ok) throw new Error("delete failed");
}
async function clearApi(){
  const r = await fetch(API_FNF_CLEAR, { method: "POST" });
  if(!r.ok) throw new Error("clear failed");
}
async function listGratuityApi(){
  const r = await fetch(API_GRATUITY_LIST, { cache: "no-store" });
  if(!r.ok) throw new Error("gratuity list failed");
  return (await r.json()).rows || [];
}
async function syncControlSettings(force){
  const existing = safeParse(localStorage.getItem(KEY_CONTROL));
  if(!force && existing && typeof existing === "object") return existing;
  const r = await fetch(API_CONTROL, { cache: "no-store" });
  if(!r.ok) throw new Error("control settings failed");
  const data = await r.json();
  localStorage.setItem(KEY_CONTROL, JSON.stringify(data || {}));
  return data || {};
}
async function fetchOutstandingAdvanceAmount(empId, exitDate){
  const eid = String(empId || "").toUpperCase().trim();
  if(!eid) return 0;
  const r = await fetch(`${API_ADVANCES}?outstanding=1`, { cache: "no-store" });
  if(!r.ok) throw new Error("advance list failed");
  const data = await r.json();
  const cutoff = String(exitDate || "").trim();
  return round2((Array.isArray(data?.rows) ? data.rows : []).reduce((sum, row) => {
    if(String(row?.empId || "").toUpperCase() !== eid) return sum;
    const disbursedOn = String(row?.disbursedOn || "").trim();
    if(cutoff && disbursedOn && disbursedOn > cutoff) return sum;
    return sum + Number(row?.remainingBalance || 0);
  }, 0));
}
async function fetchOutstandingLoanAmount(empId, exitDate){
  const eid = String(empId || "").toUpperCase().trim();
  if(!eid) return 0;
  const r = await fetch(API_LOANS, { cache: "no-store" });
  if(!r.ok) throw new Error("loan list failed");
  const data = await r.json();
  const cutoff = String(exitDate || "").trim();
  return round2((Array.isArray(data?.rows) ? data.rows : []).reduce((sum, row) => {
    if(String(row?.empId || "").toUpperCase() !== eid) return sum;
    const requestDate = String(row?.requestDate || "").trim();
    if(cutoff && requestDate && requestDate > cutoff) return sum;
    return sum + Number(row?.balanceAmount || 0);
  }, 0));
}
async function fetchFnfIncentiveAmount(empId, exitDate){
  const eid = String(empId || "").toUpperCase().trim();
  const exit = String(exitDate || "").trim();
  if(!eid || !exit) return 0;
  const dt = new Date(exit);
  if(Number.isNaN(dt.getTime())) return 0;
  const month = dt.getMonth() + 1;
  const year = dt.getFullYear();
  const url = `${API_INCENTIVES}?empId=${encodeURIComponent(eid)}&month=${month}&year=${year}&to=${encodeURIComponent(exit)}`;
  const r = await fetch(url, { cache: "no-store" });
  if(!r.ok) throw new Error("incentive list failed");
  const data = await r.json();
  return round2((Array.isArray(data?.rows) ? data.rows : []).reduce((sum, row) => sum + Number(row?.amount || 0), 0));
}
async function getGratuityApi(id){
  if(gratuitySheetCache.has(id)) return gratuitySheetCache.get(id);
  const r = await fetch(`${API_GRATUITY_LIST}/${id}`, { cache: "no-store" });
  if(!r.ok) throw new Error("gratuity get failed");
  const sheet = (await r.json()).sheet;
  gratuitySheetCache.set(id, sheet);
  return sheet;
}

function xlsxData(row){
  return [{
    Emp_ID: row.empId || row.emp || "",
    Employee_Name: row.employeeName || "",
    Exit_Date: row.exitDate || row.exit || "",
    Gross_Salary: round2(row.gross || 0),
    Paid_Days: round2(row.paidDays || 0),
    LOP_Days: round2(row.lopDays || 0),
    EL_Days: round2(row.elDays || row.el || 0),
    Bonus: round2(row.bonus || 0),
    Gratuity: round2(row.gratuity || 0),
    Advance_Salary: round2(row.advance || 0),
    Notice_Recovery: round2(row.notice || 0),
    Payable_Salary: round2(row.finalPay ?? row.final ?? 0),
    Generated_At: row.generatedAt ? new Date(row.generatedAt).toLocaleString() : ""
  }];
}

function getControlSettings(){
  const c = safeParse(localStorage.getItem(KEY_CONTROL)) || {};
  const splitRows = Array.isArray(c.ctcSplitRows) && c.ctcSplitRows.length
    ? c.ctcSplitRows.map((r) => ({ label: String(r.name || r.key || "").trim(), pct: asNumberOrZero(r.pct) })).filter((r) => r.label)
    : [
        ["Basic", c.ctcBasicPct ?? c.basicPctOfCtc],
        ["HRA", c.ctcHraPct ?? c.hraPctOfCtc],
        ["Conveyance Allowance", c.ctcConvPct ?? c.conveyPctOfCtc],
        ["Educational Allowance", c.ctcEduPct ?? c.eduPctOfCtc],
        ["Special Allowance", c.ctcSpecialPct ?? c.specialPctOfCtc]
      ].map(([label, pct]) => ({ label, pct: asNumberOrZero(pct) })).filter((r) => r.pct > 0);
  return {
    splitRows,
    pfPct: asNumberOrZero(c.pfEmpPct ?? c.pfEmployeePct),
    pfOnEsiPct: asNumberOrZero(c.pfOnEsiPct),
    daPctBasic: asNumberOrZero(c.daPctBasic),
    ptEnabled: asBool(c.ptEnabled),
    ptMonthly: asNumberOrZero(c.ptMonthly),
    pfWageCapEnabled: asBool(c.pfWageCapEnabled),
    pfWageCapAmount: asNumberOrZero(c.pfWageCapAmount),
    esiPct: asNumberOrZero(c.esiEmpPct ?? c.esiEmployeePct),
    esiWageLimit: asNumberOrZero(c.esiWageLimit),
    lwfEnabled: asBool(c.lwfEnabled),
    lwfEmpAmt: asNumberOrZero(c.lwfEmpAmt ?? c.lwfEmployeeAmt),
    lwfMonth: asNumberOrZero(c.lwfMonth),
    otherDeductionRows: Array.isArray(c.otherDeductionRows)
      ? c.otherDeductionRows
          .map((r) => ({ name: String(r?.name || "").trim(), amount: round2(Number(r?.amount || 0)) }))
          .filter((r) => r.name && r.amount > 0)
      : []
  };
}

function populateFilterYears(){
  const el = $("filterYear");
  if(!el) return;
  const years = new Set();
  years.add(String(new Date().getFullYear()));
  fnfRows.forEach((row) => {
    const src = String(row.exitDate || row.exit || row.generatedAt || "");
    const dt = safeDate(src);
    if(dt) years.add(String(dt.getFullYear()));
  });
  const currentValue = String(el.value || "").trim();
  const sorted = [...years].map((y) => Number(y)).filter(Number.isFinite).sort((a, b) => b - a);
  el.innerHTML = `<option value="">All</option>${sorted.map((y) => `<option value="${y}">${y}</option>`).join("")}`;
  el.value = sorted.some((y) => String(y) === currentValue) ? currentValue : "";
}

function getCompanyProfile(){
  const p = safeParse(localStorage.getItem("hr_client_profile_v1")) || {};
  const c = safeParse(localStorage.getItem(KEY_CONTROL)) || {};
  const auth = safeParse(sessionStorage.getItem("hr_auth_session_v1")) || {};
  const user = auth && auth.user ? auth.user : {};
  const pick = (...vals) => {
    for (const v of vals) {
      const s = String(v ?? "").trim();
      if (s) return s;
    }
    return "";
  };
  return {
    name: pick(p.companyName, c.companyName, user.companyName, user.name),
    address: pick(p.companyAddress, c.companyAddress, user.companyAddress),
    contact: pick(p.contactNo, p.mobile, p.phone, c.companyContact, c.companyContactNo, user.companyContactNo),
    reg: pick(p.regNo, p.companyRegNo, c.companyRegNo, user.companyRegNo),
    pan: pick(p.pan, p.companyPAN, c.companyPAN, user.companyPAN),
    tan: pick(p.tan, p.companyTAN, c.companyTAN, user.companyTAN),
    gstin: pick(p.gstin, p.companyGSTIN, c.companyGSTIN, user.companyGSTIN)
  };
}
function setSheetCell(ws, ref, value, type = "s"){
  if(value === null || value === undefined || value === ""){
    if(!ws[ref]) ws[ref] = { t: "s", v: "" };
    else ws[ref].v = "";
    return;
  }
  if(!ws[ref]) ws[ref] = { t: type, v: value };
  else {
    ws[ref].t = type;
    ws[ref].v = value;
    if(type === "n") delete ws[ref].w;
  }
}

function getFNFStatement(row){
  const empId = String(row.empId || row.emp || "").toUpperCase();
  const emp = employeeById.get(empId) || {};
  const grossMonthly = Number(row.gross || 0);
  const exitRaw = row.exitDate || row.exit || "";
  const resignationRaw = row.resignationDate || row.resignation_date || row.resignation || exitRaw;
  const exitDate = exitRaw ? new Date(exitRaw) : null;
  const monthDays = exitDate && !Number.isNaN(exitDate.getTime()) ? daysInMonth(exitDate.getFullYear(), exitDate.getMonth() + 1) : 30;
  const paidDays = round2(numOrZero(row.paidDays));
  const noDeductionRuleApplied = Boolean(row.noDeductionsRuleApplied) || paidDays < 15;
  const lopDays = round2(numOrZero(row.lopDays));
  const perDay = round2(numOrZero(row.perDay || (grossMonthly > 0 ? grossMonthly / monthDays : 0)));
  const earned = round2(numOrZero(row.earned || (perDay * paidDays)));
  const lopDeduction = noDeductionRuleApplied ? 0 : round2(numOrZero(row.lopDeduction || (perDay * lopDays)));
  const notice = noDeductionRuleApplied ? 0 : round2(numOrZero(row.notice));
  const advance = noDeductionRuleApplied ? 0 : round2(numOrZero(row.advance));
  const bonus = round2(numOrZero(row.bonus));
  const gratuity = round2(numOrZero(row.gratuity));
  const el = round2(numOrZero(row.elDays || row.el));
  const leaveEncash = round2(numOrZero(row.leaveEncashment || (perDay * el)));
  const totalDeductionsRaw = row.totalDeductions;
  const totalEarnings = round2(numOrZero(row.totalEarnings || (earned + leaveEncash + bonus + gratuity)));
  const control = getControlSettings();
  const monthlyRows = control.splitRows.map((x) => ({ label: x.label, amount: round2((grossMonthly * Number(x.pct || 0)) / 100) }));
  const earningsRows = control.splitRows.map((x) => ({ label: x.label, amount: round2((earned * Number(x.pct || 0)) / 100) }));
  const rowOtherItems = Array.isArray(row.otherDeductionItems)
    ? row.otherDeductionItems
        .map((r) => ({ name: String(r?.name || "").trim(), amount: round2(numOrZero(r?.amount)) }))
        .filter((r) => r.name && r.amount > 0)
    : [];
  const controlOtherDedRows = rowOtherItems.length ? rowOtherItems : (control.otherDeductionRows || []);
  const controlOtherDedTotal = noDeductionRuleApplied ? 0 : round2(
    row.otherDeductions !== undefined
      ? numOrZero(row.otherDeductions)
      : controlOtherDedRows.reduce((sum, r) => sum + numOrZero(r.amount), 0)
  );
  const getAmt = (list, names) => {
    const set = names.map((x) => String(x).toLowerCase());
    const found = list.find((x) => set.some((n) => String(x.label || "").toLowerCase().includes(n)));
    return round2(found?.amount || 0);
  };
  const basicMonthly = getAmt(monthlyRows, ["basic"]);
  const hraMonthly = getAmt(monthlyRows, ["hra"]);
  const convMonthly = getAmt(monthlyRows, ["conveyance", "conv"]);
  const daMonthly = getAmt(monthlyRows, ["da", "dearness"]);
  const eduMonthly = getAmt(monthlyRows, ["educational", "education", "edu"]);
  const specialMonthly = getAmt(monthlyRows, ["special"]);
  const basicEarned = getAmt(earningsRows, ["basic"]);
  const hraEarned = getAmt(earningsRows, ["hra"]);
  const convEarned = getAmt(earningsRows, ["conveyance", "conv"]);
  const daEarned = getAmt(earningsRows, ["da", "dearness"]);
  const eduEarned = getAmt(earningsRows, ["educational", "education", "edu"]);
  const specialEarned = getAmt(earningsRows, ["special"]);
  const earnedForStat = round2(numOrZero(row.earned || (perDay * paidDays)));
  const exitMonthNum = exitDate && !Number.isNaN(exitDate.getTime()) ? (exitDate.getMonth() + 1) : 0;
  const stat = computeStatutoryFromControl(control, grossMonthly, earnedForStat, emp, exitMonthNum);
  const pf = noDeductionRuleApplied ? 0 : round2(numOrZero(row.pfEE ?? stat.pf));
  const esi = noDeductionRuleApplied ? 0 : round2(numOrZero(row.esiEE ?? stat.esi));
  const pt = noDeductionRuleApplied ? 0 : round2(numOrZero(row.pt ?? stat.pt));
  const lwf = noDeductionRuleApplied ? 0 : round2(numOrZero(row.lwf ?? stat.lwf));
  const statutoryDeductions = round2(numOrZero(row.statutoryDeductions ?? (pf + esi + pt + lwf)));
  const displayOtherDedRows = noDeductionRuleApplied ? [] : controlOtherDedRows;
  const deductionRows = [
    { label: "Provident Fund", amount: pf },
    { label: "ESIC", amount: esi },
    { label: "Professional Tax", amount: pt },
    { label: "Labour Welfare Fund", amount: lwf },
    { label: "LOP Deduction", amount: lopDeduction },
    { label: "Advance / Loan", amount: advance },
    { label: "Notice Pay", amount: notice },
    ...displayOtherDedRows.map((r) => ({ label: r.name, amount: round2(numOrZero(r.amount)) }))
  ].filter((x) => round2(numOrZero(x.amount)) > 0);
  const totalDeductions = round2(numOrZero(
    totalDeductionsRaw !== undefined
      ? totalDeductionsRaw
      : (noDeductionRuleApplied ? 0 : (lopDeduction + advance + notice + controlOtherDedTotal + statutoryDeductions))
  ));
  const computedDeductionTotal = round2(deductionRows.reduce((sum, x) => sum + round2(numOrZero(x.amount)), 0));
  const deductionAdjustment = round2(totalDeductions - computedDeductionTotal);
  const otherDeductions = round2(totalDeductions - (pf + esi + pt + lwf));
  const otherEarnings = round2(leaveEncash + bonus + gratuity);
  const grossSalary = earned;
  const netPayable = round2(numOrZero(row.finalPay ?? row.final ?? (totalEarnings - totalDeductions)));
  const monthLabel = exitDate && !Number.isNaN(exitDate.getTime())
    ? `${exitDate.toLocaleString("en-IN", { month: "long" })} ${exitDate.getFullYear()}`
    : "-";
  return {
    empId,
    empName: emp.name || row.employeeName || row.empName || "-",
    designation: emp.desig || emp.designation || row.desig || row.designation || "-",
    dateOfJoining: emp.joiningDate || emp.doj || emp.dateOfJoining || row.joiningDate || row.doj || "-",
    dateOfResignation: formatDateDDMMYYYY(resignationRaw),
    dateOfLeaving: formatDateDDMMYYYY(exitRaw),
    monthLabel,
    grossMonthly: round2(grossMonthly),
    monthlyRows,
    basicMonthly,
    hraMonthly,
    convMonthly,
    daMonthly,
    eduMonthly,
    specialMonthly,
    paidDays: round2(paidDays),
    lopDays: round2(lopDays),
    basicEarned,
    hraEarned,
    convEarned,
    daEarned,
    eduEarned,
    specialEarned,
    earningsRows,
    grossSalary,
    totalEarnings,
    lopDeduction,
    pf,
    esi,
    pt,
    lwf,
    notice,
    advance,
    bonus,
    gratuity,
    leaveEncash,
    statutoryDeductions,
    controlOtherDedRows,
    controlOtherDedTotal,
    noDeductionRuleApplied,
    deductionRows,
    deductionAdjustment,
    otherDeductions,
    otherEarnings,
    totalDeductions,
    netPayable
  };
}

function fnfStatementHtml(row){
  const s = getFNFStatement(row);
  const cp = getCompanyProfile();
  const brandInk = "#45454c";
  const brandBorder = "rgba(69,69,76,.28)";
  const brandSoft = "#efeff8";
  const brandSoftAlt = "#ecebdd";
  const employeeInfoRows = [
    { label: "Name :", value: s.empName },
    { label: "Designation:", value: s.designation },
    { label: "Employee Code :", value: s.empId },
    { label: "Date of Joining :", value: formatDateDDMMYYYY(s.dateOfJoining) },
    { label: "Date of Resignation:", value: s.dateOfResignation },
    { label: "Date of Leaving :", value: s.dateOfLeaving }
  ];
  const grossDistributionRows = [
    ...((Array.isArray(s.monthlyRows) ? s.monthlyRows : []).map((r) => ({
      label: `${String(r?.label || "").trim()}:`,
      value: money(r?.amount)
    }))),
    { label: "Gross Salary :", value: `Rs ${money(s.grossMonthly)}`, strong: true }
  ].filter((r) => r.label !== ":" && r.label !== "");
  const topSectionRowsHtml = Array.from({ length: Math.max(employeeInfoRows.length, grossDistributionRows.length, 1) }).map((_, i) => {
    const left = employeeInfoRows[i];
    const right = grossDistributionRows[i];
    return `<tr>
        <td style="border:1px solid ${brandBorder};padding:2px 4px;">${left ? `<b>${esc(left.label)}</b>` : ""}</td>
        <td style="border:1px solid ${brandBorder};padding:2px 4px;">${left ? esc(left.value) : ""}</td>
        <td style="border:1px solid ${brandBorder};padding:2px 4px;">${right ? `<b>${esc(right.label)}</b>` : ""}</td>
        <td style="border:1px solid ${brandBorder};padding:2px 4px;text-align:right;">${right ? (right.strong ? `<b>${esc(right.value)}</b>` : esc(right.value)) : ""}</td>
      </tr>`;
  }).join("");
  const dynamicEarningsRows = (Array.isArray(s.earningsRows) ? s.earningsRows : [])
    .map((r) => ({
      label: `${String(r?.label || "").trim()}:`,
      amount: round2(numOrZero(r?.amount))
    }))
    .filter((r) => r.label !== ":" && r.label !== "" && r.amount >= 0);
  const earningsRows = dynamicEarningsRows.length ? dynamicEarningsRows : [
    { label: "Basic :", amount: s.basicEarned },
    { label: "HRA:", amount: s.hraEarned },
    { label: "Conveyance Allowance:", amount: s.convEarned },
    { label: "DA:", amount: s.daEarned },
    { label: "Educational Allowance:", amount: s.eduEarned },
    { label: "Special Allowance:", amount: s.specialEarned }
  ];
  const deductionRows = Array.isArray(s.deductionRows) ? s.deductionRows : [];
  const pairRows = Math.max(earningsRows.length, deductionRows.length, 1);
  const earningsDeductionRowsHtml = Array.from({ length: pairRows }).map((_, i) => {
    const e = earningsRows[i];
    const d = deductionRows[i];
    return `
        <tr>
          <td style="border:1px solid ${brandBorder};padding:2px 4px;">${e ? `<b>${esc(e.label)}</b>` : ""}</td>
          <td style="border:1px solid ${brandBorder};padding:2px 4px;text-align:right;">${e ? money(e.amount) : ""}</td>
          <td style="border:1px solid ${brandBorder};padding:2px 4px;">${d ? esc(d.label) : ""}</td>
          <td style="border:1px solid ${brandBorder};padding:2px 4px;text-align:right;">${d ? money(d.amount) : ""}</td>
        </tr>`;
  }).join("");
  const adjustmentRowHtml = Math.abs(round2(s.deductionAdjustment || 0)) > 0
    ? `<tr><td style="border:1px solid ${brandBorder};padding:2px 4px;"></td><td style="border:1px solid ${brandBorder};padding:2px 4px;"></td><td style="border:1px solid ${brandBorder};padding:2px 4px;">Adjustment</td><td style="border:1px solid ${brandBorder};padding:2px 4px;text-align:right;">${money(s.deductionAdjustment)}</td></tr>`
    : "";
  return `
    <div id="fnfStatementSheet" style="font-family:'Times New Roman',serif;color:${brandInk};background:#fff;padding:6px;print-color-adjust:exact;-webkit-print-color-adjust:exact;">
      <table style="width:100%;border-collapse:collapse;font-size:13px;table-layout:fixed;">
        <tr><th colspan="4" style="border:1px solid ${brandBorder};text-align:center;padding:2px 4px;font-size:24px;line-height:1.15;background:${brandInk};color:#fff;">${esc(cp.name)}</th></tr>
        <tr><td colspan="4" style="border:1px solid ${brandBorder};text-align:center;padding:2px 4px;background:${brandSoft};">${esc(cp.address)}</td></tr>
        <tr><td colspan="4" style="border:1px solid ${brandBorder};text-align:center;padding:2px 4px;font-size:16px;background:${brandSoft};"><b>Contact:</b> ${esc(cp.contact)}</td></tr>
        <tr><td colspan="4" style="border:1px solid ${brandBorder};text-align:center;padding:2px 4px;"><b>Reg:</b> ${esc(cp.reg)} &nbsp;&nbsp;<b>PAN:</b> ${esc(cp.pan)} &nbsp;&nbsp;<b>TAN:</b> ${esc(cp.tan)} &nbsp;&nbsp;<b>GSTIN:</b> ${esc(cp.gstin)}</td></tr>
        <tr><th colspan="4" style="border:1px solid ${brandBorder};text-align:center;padding:2px 4px;font-size:20px;line-height:1.1;background:${brandInk};color:#fff;">FULL &amp; FINAL SETTLEMENT</th></tr>
        ${topSectionRowsHtml}
        <tr><td style="border:1px solid ${brandBorder};padding:2px 4px;"></td><td style="border:1px solid ${brandBorder};padding:2px 4px;"></td><td style="border:1px solid ${brandBorder};padding:2px 4px;"></td><td style="border:1px solid ${brandBorder};padding:2px 4px;"></td></tr>
        <tr><td style="border:1px solid ${brandBorder};padding:2px 4px;"><b>Salary per month :</b></td><td style="border:1px solid ${brandBorder};padding:2px 4px;"></td><td style="border:1px solid ${brandBorder};padding:2px 4px;"></td><td style="border:1px solid ${brandBorder};padding:2px 4px;"></td></tr>
        <tr><td style="border:1px solid ${brandBorder};padding:10px 4px;"></td><td style="border:1px solid ${brandBorder};padding:10px 4px;"></td><td style="border:1px solid ${brandBorder};padding:10px 4px;"></td><td style="border:1px solid ${brandBorder};padding:10px 4px;"></td></tr>
        <tr><th colspan="4" style="border:1px solid ${brandBorder};text-align:center;padding:2px 4px;font-size:16px;line-height:1.1;background:${brandSoft};color:${brandInk};">Salary for the month of ${esc(s.monthLabel)}</th></tr>
        <tr><td style="border:1px solid ${brandBorder};padding:2px 4px;"><b>Days Paid</b></td><td style="border:1px solid ${brandBorder};padding:2px 4px;text-align:right;"><b>${money(s.paidDays)}</b></td><td style="border:1px solid ${brandBorder};padding:2px 4px;"></td><td style="border:1px solid ${brandBorder};padding:2px 4px;"></td></tr>
        <tr><th colspan="2" style="border:1px solid ${brandBorder};text-align:center;padding:2px 4px;font-size:15px;background:${brandSoft};color:${brandInk};">Earnings</th><th colspan="2" style="border:1px solid ${brandBorder};text-align:center;padding:2px 4px;font-size:15px;background:${brandSoft};color:${brandInk};">Deductions</th></tr>
        ${earningsDeductionRowsHtml}
        <tr><td style="border:1px solid ${brandBorder};padding:2px 4px;background:${brandSoftAlt};"><b>Gross Salary :</b></td><td style="border:1px solid ${brandBorder};padding:2px 4px;text-align:right;background:${brandSoftAlt};"><b>${money(s.grossSalary)}</b></td><td style="border:1px solid ${brandBorder};padding:2px 4px;background:${brandSoftAlt};"><b>Total</b></td><td style="border:1px solid ${brandBorder};padding:2px 4px;text-align:right;background:${brandSoftAlt};"><b>${money(s.statutoryDeductions)}</b></td></tr>
        <tr><th colspan="2" style="border:1px solid ${brandBorder};text-align:center;padding:2px 4px;font-size:15px;background:${brandSoft};color:${brandInk};">Other Earnings</th><th colspan="2" style="border:1px solid ${brandBorder};text-align:center;padding:2px 4px;font-size:15px;background:${brandSoft};color:${brandInk};">Other Deductions</th></tr>
        <tr><td style="border:1px solid ${brandBorder};padding:2px 4px;">Gratuity</td><td style="border:1px solid ${brandBorder};padding:2px 4px;text-align:right;">${money(s.gratuity)}</td><td style="border:1px solid ${brandBorder};padding:2px 4px;">Notice pay</td><td style="border:1px solid ${brandBorder};padding:2px 4px;text-align:right;">${money(s.notice)}</td></tr>
        <tr><td style="border:1px solid ${brandBorder};padding:2px 4px;">Earned Leave Encashment</td><td style="border:1px solid ${brandBorder};padding:2px 4px;text-align:right;">${money(s.leaveEncash)}</td><td style="border:1px solid ${brandBorder};padding:2px 4px;">Advance / Loan Recovery</td><td style="border:1px solid ${brandBorder};padding:2px 4px;text-align:right;">${money(s.advance + s.lopDeduction)}</td></tr>
        ${adjustmentRowHtml}
        <tr><td style="border:1px solid ${brandBorder};padding:2px 4px;background:${brandSoftAlt};"><b>Total</b></td><td style="border:1px solid ${brandBorder};padding:2px 4px;text-align:right;background:${brandSoftAlt};"><b>${money(s.otherEarnings)}</b></td><td style="border:1px solid ${brandBorder};padding:2px 4px;background:${brandSoftAlt};"><b>Total</b></td><td style="border:1px solid ${brandBorder};padding:2px 4px;text-align:right;background:${brandSoftAlt};"><b>${money(s.otherDeductions)}</b></td></tr>
        <tr><td style="border:1px solid ${brandBorder};padding:2px 4px;background:${brandSoftAlt};"><b>Gross Total</b></td><td style="border:1px solid ${brandBorder};padding:2px 4px;text-align:right;background:${brandSoftAlt};"><b>${money(s.totalEarnings)}</b></td><td style="border:1px solid ${brandBorder};padding:2px 4px;background:${brandSoftAlt};"><b>Total Deductions</b></td><td style="border:1px solid ${brandBorder};padding:2px 4px;text-align:right;background:${brandSoftAlt};"><b>${money(s.totalDeductions)}</b></td></tr>
        <tr><td colspan="4" style="border:1px solid ${brandBorder};padding:8px 4px;"></td></tr>
        <tr><td style="border:1px solid ${brandBorder};padding:2px 4px;background:${brandInk};color:#fff;"><b>Net Amount Payable :</b></td><td style="border:1px solid ${brandBorder};padding:2px 4px;text-align:right;background:${brandInk};color:#fff;"><b>${money(s.netPayable)}</b></td><td style="border:1px solid ${brandBorder};padding:2px 4px;background:${brandInk};"></td><td style="border:1px solid ${brandBorder};padding:2px 4px;background:${brandInk};"></td></tr>
        <tr><td colspan="4" style="border:1px solid ${brandBorder};padding:2px 4px;background:${brandSoft};"><b>DECLARATION :</b></td></tr>
        <tr><td colspan="4" style="border:1px solid ${brandBorder};padding:2px 4px;">In view of this settlement / payment, I have no further claim of whatsoever nature against the</td></tr>
        <tr><td colspan="4" style="border:1px solid ${brandBorder};padding:2px 4px;">company including that of reinstatement or re - employment.</td></tr>
        <tr><td colspan="4" style="border:1px solid ${brandBorder};padding:14px 4px;"></td></tr>
        <tr><td style="border:1px solid ${brandBorder};padding:14px 4px;">Name of workman :</td><td style="border:1px solid ${brandBorder};padding:14px 4px;"></td><td style="border:1px solid ${brandBorder};padding:14px 4px;text-align:right;">Sign:</td><td style="border:1px solid ${brandBorder};padding:14px 4px;"></td></tr>
        <tr><td style="border:1px solid ${brandBorder};padding:28px 4px;"></td><td style="border:1px solid ${brandBorder};padding:28px 4px;"></td><td style="border:1px solid ${brandBorder};padding:28px 4px;"></td><td style="border:1px solid ${brandBorder};padding:28px 4px;"></td></tr>
        <tr><th style="border:1px solid ${brandBorder};padding:2px 4px;text-align:center;background:${brandSoft};color:${brandInk};">HR Sign</th><th style="border:1px solid ${brandBorder};padding:2px 4px;text-align:center;background:${brandSoft};color:${brandInk};">Accounts Sign</th><th style="border:1px solid ${brandBorder};padding:2px 4px;text-align:center;background:${brandSoft};color:${brandInk};">GM Sign</th><th style="border:1px solid ${brandBorder};padding:2px 4px;"></th></tr>
        <tr><td style="border:1px solid ${brandBorder};padding:10px 4px;text-align:center;"><b>Name:</b></td><td style="border:1px solid ${brandBorder};padding:10px 4px;text-align:center;"><b>Name:</b></td><td style="border:1px solid ${brandBorder};padding:10px 4px;text-align:center;"><b>Name:</b></td><td style="border:1px solid ${brandBorder};padding:10px 4px;"></td></tr>
        <tr><td style="border:1px solid ${brandBorder};padding:16px 4px;"></td><td style="border:1px solid ${brandBorder};padding:16px 4px;"></td><td style="border:1px solid ${brandBorder};padding:16px 4px;"></td><td style="border:1px solid ${brandBorder};padding:16px 4px;"></td></tr>
      </table>
    </div>
  `;
}

async function downloadXlsx(row){
  const s = getFNFStatement(row);
  const cp = getCompanyProfile();
  let wb;
  try{
    const res = await fetch(FNF_TEMPLATE_XLSX, { cache: "no-store" });
    if(!res.ok) throw new Error("template fetch failed");
    const buf = await res.arrayBuffer();
    wb = XLSX.read(buf, { type: "array", cellStyles: true });
  } catch(_e){
    wb = XLSX.utils.book_new();
    const wsFallback = XLSX.utils.aoa_to_sheet([["FULL & FINAL SETTLEMENT"],["Template missing"]]);
    XLSX.utils.book_append_sheet(wb, wsFallback, "Sheet1");
  }
  const ws = wb.Sheets[wb.SheetNames[0]];
  const companyBlock = `${cp.name}\n${cp.address}\nContact: ${cp.contact}\nReg: ${cp.reg}   PAN: ${cp.pan}   TAN: ${cp.tan}   GSTIN: ${cp.gstin}`;
  const clearRange = (startRow, endRow, cols) => {
    for(let rowNo = startRow; rowNo <= endRow; rowNo++){
      cols.forEach((col) => setSheetCell(ws, `${col}${rowNo}`, "", "s"));
    }
  };
  const employeeInfoRows = [
    ["Name : ", s.empName],
    ["Designation: ", s.designation],
    ["Employee Code : ", s.empId],
    ["Date of Joining : ", formatDateDDMMYYYY(s.dateOfJoining)],
    ["Date of Resignation: ", s.dateOfResignation],
    ["Date of Leaving  : ", s.dateOfLeaving]
  ];
  const grossDistributionRows = [
    ...((Array.isArray(s.monthlyRows) ? s.monthlyRows : []).map((r) => [`${String(r?.label || "").trim()}:`, round2(r?.amount)])),
    ["Gross Salary :", `Rs ${money(s.grossMonthly)}`]
  ];
  const topSectionRows = Math.max(employeeInfoRows.length, grossDistributionRows.length, 1);
  const salaryPerMonthRow = 5 + topSectionRows + 1;
  const salaryMonthHeaderRow = salaryPerMonthRow + 2;
  const daysPaidRow = salaryMonthHeaderRow + 1;
  const sectionHeaderRow = salaryMonthHeaderRow + 2;
  const earningsStartRow = sectionHeaderRow + 1;
  const pairRows = Math.max((Array.isArray(s.earningsRows) ? s.earningsRows.length : 0), (Array.isArray(s.deductionRows) ? s.deductionRows.length : 0), 1);
  const grossSummaryRow = earningsStartRow + pairRows;
  const otherHeaderRow = grossSummaryRow + 1;
  const otherRowOne = otherHeaderRow + 2;
  const otherRowTwo = otherRowOne + 1;
  const adjustmentRow = otherRowTwo + (Math.abs(round2(s.deductionAdjustment || 0)) > 0 ? 1 : 0);
  const otherTotalRow = adjustmentRow + 1;
  const grandTotalRow = otherTotalRow + 1;
  const netPayableRow = grandTotalRow + 2;
  const declarationRow = netPayableRow + 1;
  clearRange(5, 42, ["A","B","C","D"]);
  setSheetCell(ws, "A1", companyBlock, "s");
  setSheetCell(ws, "A4", "FULL & FINAL SETTLEMENT", "s");
  for(let i = 0; i < topSectionRows; i++){
    const left = employeeInfoRows[i] || ["", ""];
    const right = grossDistributionRows[i] || ["", ""];
    const rowNo = 5 + i;
    setSheetCell(ws, `A${rowNo}`, left[0], "s");
    setSheetCell(ws, `B${rowNo}`, left[1], "s");
    setSheetCell(ws, `C${rowNo}`, right[0], "s");
    setSheetCell(ws, `D${rowNo}`, right[1], typeof right[1] === "number" ? "n" : "s");
  }
  setSheetCell(ws, `A${salaryPerMonthRow}`, "Salary per month : ", "s");
  setSheetCell(ws, `A${salaryMonthHeaderRow}`, `Salary for the month of  ${s.monthLabel}`, "s");
  setSheetCell(ws, `A${daysPaidRow}`, "Days Paid  ", "s"); setSheetCell(ws, `B${daysPaidRow}`, round2(s.paidDays), "n");
  setSheetCell(ws, `A${sectionHeaderRow}`, "Earnings", "s"); setSheetCell(ws, `C${sectionHeaderRow}`, "Deductions", "s");
  for(let i = 0; i < pairRows; i++){
    const e = (Array.isArray(s.earningsRows) ? s.earningsRows[i] : null) || null;
    const d = (Array.isArray(s.deductionRows) ? s.deductionRows[i] : null) || null;
    const rowNo = earningsStartRow + i;
    setSheetCell(ws, `A${rowNo}`, e ? `${String(e.label || "").trim()}:` : "", "s");
    setSheetCell(ws, `B${rowNo}`, e ? round2(e.amount) : "", e ? "n" : "s");
    setSheetCell(ws, `C${rowNo}`, d ? String(d.label || "") : "", "s");
    setSheetCell(ws, `D${rowNo}`, d ? round2(d.amount) : "", d ? "n" : "s");
  }
  setSheetCell(ws, `A${grossSummaryRow}`, "Gross Salary :", "s"); setSheetCell(ws, `B${grossSummaryRow}`, round2(s.grossSalary), "n"); setSheetCell(ws, `C${grossSummaryRow}`, "Total", "s"); setSheetCell(ws, `D${grossSummaryRow}`, round2(s.statutoryDeductions), "n");
  setSheetCell(ws, `A${otherHeaderRow}`, "Other Earnings", "s"); setSheetCell(ws, `C${otherHeaderRow}`, "Other Deductions", "s");
  setSheetCell(ws, `A${otherRowOne}`, "Gratuity ", "s"); setSheetCell(ws, `B${otherRowOne}`, round2(s.gratuity), "n"); setSheetCell(ws, `C${otherRowOne}`, "Notice pay ", "s"); setSheetCell(ws, `D${otherRowOne}`, round2(s.notice), "n");
  setSheetCell(ws, `A${otherRowTwo}`, "Earned Leave Encashment", "s"); setSheetCell(ws, `B${otherRowTwo}`, round2(s.leaveEncash), "n"); setSheetCell(ws, `C${otherRowTwo}`, "Advance / Loan Recovery", "s"); setSheetCell(ws, `D${otherRowTwo}`, round2(s.advance + s.lopDeduction + s.controlOtherDedTotal), "n");
  if(Math.abs(round2(s.deductionAdjustment || 0)) > 0){
    setSheetCell(ws, `C${otherRowTwo + 1}`, "Adjustment", "s");
    setSheetCell(ws, `D${otherRowTwo + 1}`, round2(s.deductionAdjustment), "n");
  }
  setSheetCell(ws, `A${otherTotalRow}`, "Total ", "s"); setSheetCell(ws, `B${otherTotalRow}`, round2(s.otherEarnings), "n"); setSheetCell(ws, `C${otherTotalRow}`, "Total ", "s"); setSheetCell(ws, `D${otherTotalRow}`, round2(s.otherDeductions), "n");
  setSheetCell(ws, `A${grandTotalRow}`, "Gross Total ", "s"); setSheetCell(ws, `B${grandTotalRow}`, round2(s.totalEarnings), "n"); setSheetCell(ws, `C${grandTotalRow}`, "Total Deductions ", "s"); setSheetCell(ws, `D${grandTotalRow}`, round2(s.totalDeductions), "n");
  setSheetCell(ws, `A${netPayableRow}`, "Net Amount Payable : ", "s"); setSheetCell(ws, `B${netPayableRow}`, round2(s.netPayable), "n");
  setSheetCell(ws, `A${declarationRow}`, "DECLARATION :", "s");
  setSheetCell(ws, `A${declarationRow + 1}`, "In view of this settlement / payment, I have no further claim of whatsoever nature against the", "s");
  setSheetCell(ws, `A${declarationRow + 2}`, "company including that of reinstatement or re - employment.", "s");
  setSheetCell(ws, `A${declarationRow + 4}`, "Name of workman : ", "s"); setSheetCell(ws, `C${declarationRow + 4}`, "Sign:", "s");
  setSheetCell(ws, `A${declarationRow + 9}`, "HR Sign", "s"); setSheetCell(ws, `B${declarationRow + 9}`, "Accounts Sign", "s"); setSheetCell(ws, `C${declarationRow + 9}`, "GM Sign", "s");
  setSheetCell(ws, `A${declarationRow + 10}`, "Name:", "s"); setSheetCell(ws, `B${declarationRow + 10}`, "Name:", "s"); setSheetCell(ws, `C${declarationRow + 10}`, "Name:", "s");
  setSheetCell(ws, "A43", "", "s"); setSheetCell(ws, "B43", "", "s"); setSheetCell(ws, "C43", "", "s"); setSheetCell(ws, "D43", "", "s");
  wb.Sheets[wb.SheetNames[0]] = ws;
  const id = row.empId || row.emp || "EMP";
  XLSX.writeFileXLSX(wb, `fnf_${id}_${row.exitDate || row.exit || "sheet"}.xlsx`, { cellStyles: true, bookType: "xlsx" });
}
async function downloadPdf(row){
  if(typeof window.html2pdf === "undefined"){
    alert("PDF library not loaded.");
    return;
  }
  const tmp = document.createElement("div");
  tmp.innerHTML = fnfStatementHtml(row);
  document.body.appendChild(tmp);
  try{
    await window.html2pdf()
      .set({
        margin: [8, 6, 8, 6],
        filename: `fnf_${row.empId || row.emp || "EMP"}_${row.exitDate || row.exit || "sheet"}.pdf`,
        image: { type: "jpeg", quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: "mm", format: "a4", orientation: "portrait" }
      })
      .from(tmp.firstElementChild)
      .save();
  } finally {
    tmp.remove();
  }
}
function downloadCsv(row){
  const ws = XLSX.utils.json_to_sheet(xlsxData(row));
  const csv = XLSX.utils.sheet_to_csv(ws);
  const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
  const a = document.createElement("a");
  a.href = URL.createObjectURL(blob);
  const id = row.empId || row.emp || "EMP";
  a.download = `fnf_${id}_${row.exitDate || row.exit || "sheet"}.csv`;
  document.body.appendChild(a);
  a.click();
  a.remove();
}

function detailsHtml(row){
  return fnfStatementHtml(row);
}

function rowMonthYear(row){
  const v = String(row.exitDate || row.exit || "");
  const d = v ? new Date(v) : null;
  if(!d || Number.isNaN(d.getTime())) return { month: "", year: "", label: "-" };
  const month = d.getMonth() + 1;
  const year = d.getFullYear();
  return { month: String(month).padStart(2, "0"), year: String(year), label: `${d.toLocaleString("en-IN",{month:"short"})} ${year}` };
}

function rowStatus(row){
  const s = String(row.status || "").toLowerCase().trim();
  if(s === "failed" || s === "processing" || s === "success") return s;
  return "success";
}

function filteredFnfRows(){
  const q = String($("searchInput")?.value || "").toLowerCase().trim();
  const fm = String($("filterMonth")?.value || "").trim();
  const fy = String($("filterYear")?.value || "").trim();
  const fs = String($("filterStatus")?.value || "").trim();
  return fnfRows.filter((x) => {
    const my = rowMonthYear(x);
    const status = rowStatus(x);
    const searchStr = `${x.empId || x.emp || ""} ${x.exitDate || x.exit || ""} ${my.label} ${x.generatedAt || ""}`.toLowerCase();
    if(q && !searchStr.includes(q)) return false;
    if(fm && my.month !== fm) return false;
    if(fy && my.year !== fy) return false;
    if(fs && status !== fs) return false;
    return true;
  });
}

function renderStats(){
  const now = new Date();
  const thisMonth = now.getMonth() + 1;
  const thisYear = now.getFullYear();
  if($("statTotal")) $("statTotal").textContent = String(fnfRows.length);
  if($("statThisMonth")) $("statThisMonth").textContent = String(fnfRows.filter((x) => {
    const my = rowMonthYear(x);
    return Number(my.month) === thisMonth && Number(my.year) === thisYear;
  }).length);
  if($("statSuccess")) $("statSuccess").textContent = String(fnfRows.filter((x) => rowStatus(x) === "success").length);
  if($("statFailed")) $("statFailed").textContent = String(fnfRows.filter((x) => rowStatus(x) === "failed").length);
}

function render(){
  populateFilterYears();
  renderStats();
  const rows = filteredFnfRows();
  if($("resultCount")) $("resultCount").textContent = String(rows.length);
  if(!rows.length){
    $("fnfTable").innerHTML = `<tr><td colspan="7" class="text-center text-muted-3 py-4">No FNF sheets generated yet.</td></tr>`;
    return;
  }
  $("fnfTable").innerHTML = rows.map((x, idx) => {
    const my = rowMonthYear(x);
    const generatedOn = x.generatedAt ? new Date(x.generatedAt).toLocaleString() : "-";
    const fileName = `fnf_${x.empId || x.emp || "EMP"}_${x.exitDate || x.exit || "sheet"}.${(x.format || "pdf").toLowerCase()}`;
    const status = rowStatus(x);
    const badge = status === "failed"
      ? '<span class="badge text-bg-danger">Failed</span>'
      : (status === "processing" ? '<span class="badge text-bg-warning">Processing</span>' : '<span class="badge text-bg-success">Success</span>');
    return `
    <tr>
      <td class="fw-semibold">${idx + 1}</td>
      <td><div class="fw-semibold">${esc(fileName)}</div><div class="small text-muted-3">Exit: ${esc(x.exitDate || x.exit || "-")}</div></td>
      <td>${esc(my.label)}</td>
      <td class="fw-semibold">${esc(x.empId || x.emp || "-")}</td>
      <td>${generatedOn}</td>
      <td>${badge}</td>
      <td class="text-center">
        <div class="btn-group">
          <button class="btn btn-sm btn-outline-primary" title="View" aria-label="View" onclick="viewRow('${esc(x.id || "")}')"><i class="bi bi-eye"></i></button>
          <button class="btn btn-sm btn-outline-secondary" title="Download PDF" aria-label="Download PDF" onclick="downloadRowPdf('${esc(x.id || "")}')"><i class="bi bi-file-earmark-pdf"></i></button>
          <button class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete" onclick="deleteRow('${esc(x.id || "")}')"><i class="bi bi-trash"></i></button>
        </div>
      </td>
    </tr>
  `;
  }).join("");
}

function findById(id){
  return fnfRows.find((x) => String(x.id || "") === String(id));
}

window.viewRow = async (id) => {
  let row = findById(id);
  if(!row) return;
  if(id && !String(id).startsWith("local-")){
    try { row = await getApi(id); }
    catch(_e){ }
  }
  currentViewRow = row;
  $("fnfViewBody").innerHTML = detailsHtml(row);
  const printBtn = $("fnfPrintBtn");
  const pdfBtn = $("fnfDownloadPdfBtn");
  const xlsBtn = $("fnfDownloadXlsBtn");
  if(printBtn){
    printBtn.onclick = () => {
      const printWin = window.open("", "_blank");
      if(!printWin) return;
      printWin.document.write(`<html><head><title>FNF</title></head><body>${detailsHtml(currentViewRow || row)}</body></html>`);
      printWin.document.close();
      printWin.focus();
      printWin.print();
    };
  }
  if(pdfBtn) pdfBtn.onclick = () => downloadPdf(currentViewRow || row);
  if(xlsBtn) xlsBtn.onclick = () => downloadXlsx(currentViewRow || row);
  fnfViewModal.show();
};
window.downloadRowXlsx = async (id) => {
  let row = findById(id);
  if(!row) return;
  if(id && !String(id).startsWith("local-")){
    try { row = await getApi(id); }
    catch(_e){ }
  }
  downloadXlsx(row);
};
window.downloadRowCsv = async (id) => {
  let row = findById(id);
  if(!row) return;
  if(id && !String(id).startsWith("local-")){
    try { row = await getApi(id); }
    catch(_e){ }
  }
  downloadCsv(row);
};
window.downloadRowPdf = async (id) => {
  let row = findById(id);
  if(!row) return;
  if(id && !String(id).startsWith("local-")){
    try { row = await getApi(id); }
    catch(_e){ }
  }
  downloadPdf(row);
};
window.deleteRow = async (id) => {
  if(!confirm("Delete this FNF sheet?")) return;
  try {
    if(id && !String(id).startsWith("local-")){
      await deleteApi(id);
      fnfRows = await listApi();
      saveLocal(fnfRows);
      setStorageMode("API (SQLite)");
    } else {
      throw new Error("local row");
    }
  } catch(_e){
    fnfRows = loadLocal().filter((x) => String(x.id || "") !== String(id));
    saveLocal(fnfRows);
    setStorageMode("Browser localStorage (offline mode)");
  }
  render();
};

async function loadEmployees(){
  try {
    const rows = (await fetchEmployees()).map(normalizeEmployee).filter((e) => e.id);
    employeeById = new Map(rows.map((e) => [String(e.id || "").toUpperCase(), e]));
    $("empSelect").innerHTML = `<option value="">Search employee...</option>` + rows
      .map((e) => `<option value="${esc(e.id)}">${esc(e.id)} - ${esc(e.name || "")}</option>`)
      .join("");
  } catch(_e){
    const raw = safeParse(localStorage.getItem("hr_client_employees_v1")) || [];
    const local = (Array.isArray(raw) ? raw : []).map((e) => ({
      id: String(e.empId || e.id || "").toUpperCase(),
      name: String(e.empName || e.name || ""),
      desig: String(e.desig || e.designation || ""),
      joiningDate: String(e.joiningDate || e.doj || e.dateOfJoining || ""),
      baseCtc: Number(
        e.baseCtc ??
        e.baseCtcMonthly ??
        e.base_ctc_monthly ??
        e.ctc ??
        0
      ) || 0
    })).filter((e) => e.id);
    employeeById = new Map(local.map((e) => [String(e.id || "").toUpperCase(), e]));
    $("empSelect").innerHTML = `<option value="">Search employee...</option>` + local
      .map((e) => `<option value="${e.id}">${e.id} - ${e.name}</option>`)
      .join("");
  }
  if(empSelectTs){ empSelectTs.destroy(); empSelectTs = null; }
  empSelectTs = new TomSelect("#empSelect", {
    create: false,
    sortField: { field: "text", direction: "asc" },
    maxOptions: 300,
    placeholder: "Search by Emp ID or Name...",
    onChange: autoFillFnfData
  });
}

function normalizeEmployee(raw){
  const e = raw || {};
  const id = String(e.empId || e.id || e.employeeId || e.employee_id || "").toUpperCase().trim();
  return {
    id,
    name: String(e.empName || e.name || e.employeeName || e.employee_name || "").trim(),
    desig: String(e.desig || e.designation || e.role || "").trim(),
    joiningDate: String(e.joiningDate || e.doj || e.dateOfJoining || "").trim(),
    baseCtc: Number(
      e.baseCtc ??
      e.baseCtcMonthly ??
      e.base_ctc_monthly ??
      e.ctc ??
      e.gross ??
      e.grossSalary ??
      e.gross_salary ??
      0
    ) || 0
  };
}

function leaveBalanceEL(empId){
  const extraAll = safeParse(localStorage.getItem(KEY_EMP_EXTRA)) || {};
  const lv = safeParse(localStorage.getItem(KEY_LEAVES)) || [];
  const alloc = Number(extraAll?.[empId]?.leaveAlloc?.el || 0);
  const used = (Array.isArray(lv) ? lv : []).reduce((sum, x) => {
    const id = String(x.empId || x.emp_id || "").toUpperCase();
    const type = String(x.leaveType || x.leave_type || x.type || "").toUpperCase();
    const st = String(x.status || "").toLowerCase();
    if(id !== empId || type !== "EL" || st === "rejected") return sum;
    return sum + Number(x.days || 0);
  }, 0);
  return Math.max(0, alloc - used);
}

function attendancePaidLop(empId, y, m, startDay = 1, endDay = null){
  const all = safeParse(localStorage.getItem(KEY_ATT)) || {};
  const monthData = all[monthKey(y,m)] || {};
  const dim = daysInMonth(y,m);
  const from = Math.max(1, Number(startDay || 1));
  const to = Math.min(dim, Number(endDay || dim));
  let lop = 0;
  for(let d=from; d<=to; d++){
    const iso = `${y}-${String(m).padStart(2,"0")}-${String(d).padStart(2,"0")}`;
    const st = String(monthData[`${empId}|${iso}`] || "").toUpperCase();
    if(st === "LOP" || st === "A") lop++;
  }
  const spanDays = Math.max(0, (to - from) + 1);
  return { paidDays: Math.max(0, spanDays - lop), lopDays: lop };
}
async function fetchFnfGratuityFromRecords(empId, exitDate){
  const eid = String(empId || "").toUpperCase();
  const exit = safeDate(exitDate);
  if(!eid || !exit) return 0;
  try {
    const rows = await listGratuityApi();
    const month = exit.getMonth() + 1;
    const year = exit.getFullYear();
    const monthly = rows.find((r) => String(r.mode || "") === "monthly" && Number(r.month || 0) === month && Number(r.year || 0) === year);
    if(monthly?.id){
      const sheet = await getGratuityApi(monthly.id);
      const found = (sheet?.rows || []).find((r) => String(r.empId || "").toUpperCase() === eid);
      if(found) return Number(found.gratuityAmount || 0) || 0;
    }
    const single = rows.find((r) => String(r.mode || "") === "after_5yr" && String(r.empId || "").toUpperCase() === eid);
    if(single) return Number(single.gratuityAmount || 0) || 0;
  } catch(_e){
    return 0;
  }
  return 0;
}

function pickMonthYearFromExit(){
  const ex = $("exitDate")?.value;
  if(ex){
    const dt = new Date(ex);
    if(!Number.isNaN(dt.getTime())) return { y: dt.getFullYear(), m: dt.getMonth() + 1 };
  }
  const now = new Date();
  return { y: now.getFullYear(), m: now.getMonth() + 1 };
}

async function autoFillFnfData(){
  const empId = String($("empSelect")?.value || "").toUpperCase();
  if(!empId) return;
  try { await syncControlSettings(false); } catch(_e){}
  const emp = employeeById.get(empId) || {};
  const ex = $("exitDate")?.value;
  let att = { paidDays: 0, lopDays: 0 };
  if(ex){
    const dt = new Date(ex);
    if(!Number.isNaN(dt.getTime())){
      const y = dt.getFullYear();
      const m = dt.getMonth() + 1;
      const exitDay = dt.getDate();
      att = attendancePaidLop(empId, y, m, 1, exitDay);
    }
  } else {
    const { y, m } = pickMonthYearFromExit();
    att = attendancePaidLop(empId, y, m);
  }
  const elBal = leaveBalanceEL(empId);
  const grossVal = Number(emp.baseCtc || emp.gross || 0);
  if($("gross")) $("gross").value = grossVal > 0 ? grossVal : ($("gross").value || "");
  if($("paidDays")) $("paidDays").value = att.paidDays;
  if($("lopDays")) $("lopDays").value = att.lopDays;
  if($("elDays")) $("elDays").value = elBal;
  const gratuity = await fetchFnfGratuityFromRecords(empId, $("exitDate")?.value || "");
  if($("gratuity")) $("gratuity").value = gratuity > 0 ? gratuity.toFixed(2) : "";
  try {
    const incentiveAmount = await fetchFnfIncentiveAmount(empId, $("exitDate")?.value || "");
    if($("bonus")) $("bonus").value = incentiveAmount > 0 ? incentiveAmount.toFixed(2) : "";
  } catch(_e){}
  try {
    const [advanceAmount, loanAmount] = await Promise.all([
      fetchOutstandingAdvanceAmount(empId, $("exitDate")?.value || ""),
      fetchOutstandingLoanAmount(empId, $("exitDate")?.value || "")
    ]);
    const combinedRecovery = round2(advanceAmount + loanAmount);
    if($("advance")) $("advance").value = combinedRecovery > 0 ? combinedRecovery.toFixed(2) : "";
  } catch(_e){}
  refreshPayablePreview();
}

$("fnfForm").addEventListener("submit", async (e) => {
  e.preventDefault();

  const payload = {
    empId: $("empSelect").value,
    format: $("fnfFormat")?.value || "pdf",
    resignationDate: $("resignationDate").value,
    exitDate: $("exitDate").value,
    gross: Number($("gross").value || 0),
    paidDays: Number($("paidDays").value || 0),
    lopDays: Number($("lopDays").value || 0),
    elDays: Number($("elDays").value || 0),
    bonus: Number($("bonus").value || 0),
    gratuity: Number($("gratuity").value || 0),
    advance: Number($("advance").value || 0),
    notice: Number($("notice").value || 0)
  };
  if(!payload.empId) return alert("Select employee.");
  if(!payload.resignationDate) return alert("Resignation date is required.");
  if(!payload.exitDate) return alert("Exit date is required.");
  const resignationDt = safeDate(payload.resignationDate);
  const exitDt = safeDate(payload.exitDate);
  if(!resignationDt) return alert("Resignation date is invalid.");
  if(!exitDt) return alert("Exit date is invalid.");
  if(exitDt.getTime() < resignationDt.getTime()) return alert("Exit date cannot be before resignation date.");
  const invalidNum = [
    ["Gross Salary", payload.gross],
    ["Paid Days", payload.paidDays],
    ["LOP Days", payload.lopDays],
    ["EL Balance", payload.elDays],
    ["Bonus / Incentives", payload.bonus],
    ["Gratuity", payload.gratuity],
    ["Advance / Loan", payload.advance],
    ["Notice Recovery", payload.notice]
  ].find((x) => !Number.isFinite(Number(x[1])) || Number(x[1]) < 0);
  if(invalidNum) return alert(`${invalidNum[0]} must be a valid non-negative number.`);
  if(Number(payload.gross) <= 0) return alert("Gross Salary must be greater than 0 before FNF calculation.");
  const doneProcessing = window.HRCommon?.setProcessingState?.([$("fnfForm")?.querySelector('button[type="submit"]'), $("topGenerateBtn")], {
    busyText: "Generating...",
    message: "Please wait, we are preparing the FNF statement."
  });

  try {
    await syncControlSettings(true);
    await generateApi(payload);
    fnfRows = await listApi();
    saveLocal(fnfRows);
    setStorageMode("API (SQLite)");
    doneProcessing?.("FNF statement generated successfully.", false);
  } catch(_e){
    const emp = employeeById.get(String(payload.empId || "").toUpperCase()) || {};
    const control = getControlSettings();
    const controlOtherDed = controlOtherDeductionTotal(control);
    const exitForLocal = safeDate(payload.exitDate);
    const monthDaysLocal = (exitForLocal && !Number.isNaN(exitForLocal.getTime()))
      ? daysInMonth(exitForLocal.getFullYear(), exitForLocal.getMonth() + 1)
      : 30;
    const perDayLocal = round2(payload.gross > 0 ? payload.gross / monthDaysLocal : 0);
    const earnedLocal = round2(perDayLocal * payload.paidDays);
    const lopDedLocal = round2(perDayLocal * payload.lopDays);
    const leaveEncashLocal = round2(perDayLocal * payload.elDays);
    const totalEarningsLocal = round2(earnedLocal + leaveEncashLocal + payload.bonus + payload.gratuity);
    const exitMonthLocal = (exitForLocal && !Number.isNaN(exitForLocal.getTime())) ? (exitForLocal.getMonth() + 1) : 0;
    const statLocal = computeStatutoryFromControl(control, payload.gross, earnedLocal, emp, exitMonthLocal);
    const noDeductionRuleLocal = Number(payload.paidDays || 0) < 15;
    const statutoryLocal = round2(statLocal.pf + statLocal.esi + statLocal.pt + statLocal.lwf);
    const totalDeductionsLocal = noDeductionRuleLocal
      ? 0
      : round2(lopDedLocal + payload.advance + payload.notice + controlOtherDed + statutoryLocal);
    const localRow = {
      id: `local-${Date.now()}`,
      empId: payload.empId,
      employeeName: emp.name || payload.empId,
      designation: emp.desig || emp.designation || "",
      joiningDate: emp.joiningDate || emp.doj || emp.dateOfJoining || "",
      exitDate: payload.exitDate,
      gross: payload.gross,
      resignationDate: payload.resignationDate,
      paidDays: payload.paidDays,
      lopDays: payload.lopDays,
      elDays: payload.elDays,
      bonus: payload.bonus,
      gratuity: payload.gratuity,
      advance: noDeductionRuleLocal ? 0 : payload.advance,
      notice: noDeductionRuleLocal ? 0 : payload.notice,
      format: payload.format,
      perDay: perDayLocal,
      earned: earnedLocal,
      lopDeduction: noDeductionRuleLocal ? 0 : lopDedLocal,
      leaveEncashment: leaveEncashLocal,
      totalEarnings: totalEarningsLocal,
      totalDeductions: totalDeductionsLocal,
      finalPay: round2(totalEarningsLocal - totalDeductionsLocal),
      pfEE: noDeductionRuleLocal ? 0 : round2(statLocal.pf),
      esiEE: noDeductionRuleLocal ? 0 : round2(statLocal.esi),
      pt: noDeductionRuleLocal ? 0 : round2(statLocal.pt),
      lwf: noDeductionRuleLocal ? 0 : round2(statLocal.lwf),
      statutoryDeductions: noDeductionRuleLocal ? 0 : statutoryLocal,
      noDeductionsRuleApplied: noDeductionRuleLocal,
      generatedAt: new Date().toISOString()
    };
    fnfRows = [localRow, ...loadLocal()];
    saveLocal(fnfRows);
    setStorageMode("Browser localStorage (offline mode)");
    doneProcessing?.("FNF saved locally in offline mode.", false);
  }

  render();
  e.target.reset();
});

["gross","paidDays","lopDays","elDays","bonus","gratuity","advance","notice"].forEach((id) => {
  $(id)?.addEventListener("input", refreshPayablePreview);
});

$("btnClearAll").addEventListener("click", async () => {
  if(!confirm("Clear all FNF history?")) return;
  try {
    await clearApi();
    fnfRows = [];
    saveLocal(fnfRows);
    setStorageMode("API (SQLite)");
  } catch(_e){
    localStorage.removeItem(KEY);
    fnfRows = [];
    setStorageMode("Browser localStorage (offline mode)");
  }
  render();
});

const topGenerateBtn = $("topGenerateBtn");
const topClearBtn = $("topClearBtn");
if(topGenerateBtn){
  topGenerateBtn.addEventListener("click", () => {
    $("fnfForm")?.requestSubmit();
  });
}
if(topClearBtn){
  topClearBtn.addEventListener("click", () => $("btnClearAll")?.click());
}
$("empSelect")?.addEventListener("change", autoFillFnfData);
$("exitDate")?.addEventListener("change", autoFillFnfData);
["searchInput","filterMonth","filterYear","filterStatus"].forEach((id) => {
  const el = $(id);
  if(!el) return;
  el.addEventListener("input", render);
  el.addEventListener("change", render);
});
$("clearFilters")?.addEventListener("click", () => {
  if($("searchInput")) $("searchInput").value = "";
  if($("filterMonth")) $("filterMonth").value = "";
  if($("filterYear")) $("filterYear").value = "";
  if($("filterStatus")) $("filterStatus").value = "";
  render();
});
(async function init(){
  fnfViewModal = new bootstrap.Modal($("fnfViewModal"));
  try { await syncControlSettings(true); } catch(_e){}
  await loadEmployees();
  try {
    fnfRows = await listApi();
    saveLocal(fnfRows);
    setStorageMode("API (SQLite)");
  } catch(_e){
    fnfRows = loadLocal();
    setStorageMode("Browser localStorage (offline mode)");
  }
  render();
  refreshPayablePreview();
})();

