const $ = (id) => document.getElementById(id);
    $("yr").textContent = new Date().getFullYear();
    function safeParse(s){ try { return JSON.parse(s); } catch(e){ return null; } }
    function monthKey(y,m){ return `${y}-${String(m).padStart(2,"0")}`; }
    function daysInMonth(y,m){ return new Date(y, m, 0).getDate(); }
    function money(n){ return (Number(n||0)).toLocaleString("en-IN",{maximumFractionDigits:0}); }
    function round2(n){ return Math.round(Number(n||0)); }

    const htmlEl = document.documentElement;
    function applyTheme(theme){ htmlEl.setAttribute("data-bs-theme", theme); localStorage.setItem("hr_portal_theme", theme); const isDark = theme === "dark"; $("themeIcon").className = isDark ? "bi bi-sun" : "bi bi-moon"; if ($("themeText")) $("themeText").textContent = ""; }
    applyTheme(localStorage.getItem("hr_portal_theme") || "light");
    $("themeToggle").addEventListener("click", () => { const current = htmlEl.getAttribute("data-bs-theme") || "light"; applyTheme(current === "dark" ? "light" : "dark"); });

    const KEY_ATT = "hr_client_attendance_daily_v1";
    const KEY_CONTROL = "hr_client_control_v1";
    const KEY_EMP_LOCAL = "hr_client_employees_v1";
    const KEY_OVR = "hr_client_payroll_overrides_v2";
    const KEY_SALARY_FILES = "hr_salary_sheet_files_v2";
    const API_BASES = ["/api", "/backend/api.php?path=/api", "/backend/api.php?path=/api"];
    const API_PAYROLL_SHEETS = "/payroll/sheets";
    const API_PAYROLL_CLEAR = "/payroll/clear";
    const monthNames = {1:"Jan",2:"Feb",3:"Mar",4:"Apr",5:"May",6:"Jun",7:"Jul",8:"Aug",9:"Sep",10:"Oct",11:"Nov",12:"Dec"};
    let files = safeParse(localStorage.getItem(KEY_SALARY_FILES)) || [];
    let sheetById = {};
    let previewLoadingSheetId = "";
    let activePreviewSheetId = null;
    let notifications = [];
    function loadEmployees(){
      const raw = safeParse(localStorage.getItem(KEY_EMP_LOCAL)) || [];
      return (Array.isArray(raw) ? raw : []).map((e) => ({
        empId: String(e.empId || e.id || "").toUpperCase(),
        empName: String(e.empName || e.name || ""),
        dept: String(e.dept || ""),
        desig: String(e.designation || e.desig || ""),
        baseCtc: num(e.baseCtc ?? e.baseCtcMonthly ?? e.base_ctc_monthly ?? 0)
      })).filter((e) => e.empId);
    }
    let employees = [];
    let empById = new Map();
    function refreshEmployees(){
      employees = loadEmployees();
      empById = new Map(employees.map((e) => [e.empId.toUpperCase(), e]));
    }
    refreshEmployees();

    const DEFAULT_CONTROL = {
      pfEmployeePct: 12, pfEmployerPct: 13, esiEmployeePct: 0.75, esiEmployerPct: 3.25,
      esiWageLimit: 21000, ptMonthly: 200, ptEnabled: true, pfWageCapEnabled: true, pfWageCapAmount: 15000,
      pfOnEsiPct: 70, daPctOfBasic: 0, lwfEnabled: true, lwfEmployeeAmt: 20, lwfEmployerAmt: 40, lwfApplicableMonth: 0,
      basicPctOfCtc: 40, hraPctOfCtc: 40, conveyPctOfCtc: 5, daPctOfCtc: 0, eduPctOfCtc: 2, specialPctOfCtc: 13,
      ctcAddonRows: [],
      otherDeductionRows: []
    };

    function num(v){ const n = Number(v); return isNaN(n) ? 0 : n; }
    function bool(v){ if(typeof v === "boolean") return v; if(typeof v === "string") return ["yes","true","1","y"].includes(v.toLowerCase()); return !!v; }
    function normalizeAlias(name){ return String(name || "").trim().toLowerCase().replace(/\s+/g, " "); }
    function pickNum(primary, fallback, def){
      if(primary !== undefined && primary !== null && primary !== "") return num(primary);
      if(fallback !== undefined && fallback !== null && fallback !== "") return num(fallback);
      return num(def);
    }
    function ctcByNameMap(rows){
      const map = {};
      (Array.isArray(rows) ? rows : []).forEach((r) => {
        const k = normalizeAlias(r?.key || r?.name || "");
        if(k) map[k] = num(r?.pct);
      });
      return map;
    }
    function getCtcRowsNormalized(ctrl){
      const dynamicRows = Array.isArray(ctrl?.ctcSplitRows) ? ctrl.ctcSplitRows : [];
      if(dynamicRows.length){
        const rows = dynamicRows
          .map((r) => ({ name: String(r?.name || r?.key || "").trim(), pct: num(r?.pct) }))
          .filter((r) => r.name && r.pct > 0);
        if(rows.length) return rows;
      }
      return [
        { name: "Basic", pct: num(ctrl.basicPctOfCtc) },
        { name: "HRA", pct: num(ctrl.hraPctOfCtc) },
        { name: "Conveyance", pct: num(ctrl.conveyPctOfCtc) },
        { name: "DA", pct: num(ctrl.daPctOfCtc) },
        { name: "Educational Allowance", pct: num(ctrl.eduPctOfCtc) },
        { name: "Special Allowance", pct: num(ctrl.specialPctOfCtc) }
      ].filter((r) => r.pct > 0);
    }
    function getOtherDeductionRowsNormalized(ctrl){
      const rows = Array.isArray(ctrl?.otherDeductionRows) ? ctrl.otherDeductionRows : [];
      const out = [];
      rows.forEach((r) => {
        const name = String(r?.name || r?.label || "").trim();
        const amount = round2(Math.max(0, num(r?.amount ?? r?.monthly ?? r?.value ?? 0)));
        if(!name || amount <= 0) return;
        const hit = out.find((x) => x.name === name);
        if(hit) hit.amount = round2(hit.amount + amount);
        else out.push({ name, amount });
      });
      return out;
    }
    function getAddonPercent(ctrl, code, fallback){
      const rows = Array.isArray(ctrl?.ctcAddonRows) ? ctrl.ctcAddonRows : [];
      const hit = rows.find((r) => String(r?.code || "").trim() === code);
      const v = hit?.value;
      return v !== undefined && v !== null && v !== "" ? num(v) : num(fallback);
    }
    function getComponentColsForRules(ctrl){
      return getCtcRowsNormalized(ctrl).map((r) => String(r.name || "").trim()).filter(Boolean);
    }
    function getOtherDeductionColsForRules(ctrl){
      return getOtherDeductionRowsNormalized(ctrl).map((r) => r.name);
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

    function loadControl(){
      const c = safeParse(localStorage.getItem(KEY_CONTROL));
      if(c){
        const byName = ctcByNameMap(c.ctcSplitRows || []);
        return {
          ...DEFAULT_CONTROL,
          pfEmployeePct: num(c.pfEmployeePct ?? c.pfEmpPct ?? DEFAULT_CONTROL.pfEmployeePct),
          pfEmployerPct: getAddonPercent(c, "pfEmployerPct", c.pfEmployerPct ?? c.pfErPct ?? DEFAULT_CONTROL.pfEmployerPct),
          esiEmployeePct: num(c.esiEmployeePct ?? c.esiEmpPct ?? DEFAULT_CONTROL.esiEmployeePct),
          esiEmployerPct: getAddonPercent(c, "esiEmployerPct", c.esiEmployerPct ?? c.esiErPct ?? DEFAULT_CONTROL.esiEmployerPct),
          esiWageLimit: num(c.esiWageLimit ?? DEFAULT_CONTROL.esiWageLimit),
          ptMonthly: num(c.ptMonthly ?? DEFAULT_CONTROL.ptMonthly),
          ptEnabled: bool(c.ptEnabled ?? DEFAULT_CONTROL.ptEnabled),
          pfWageCapEnabled: bool(c.pfWageCapEnabled ?? DEFAULT_CONTROL.pfWageCapEnabled),
          pfWageCapAmount: num(c.pfWageCapAmount ?? DEFAULT_CONTROL.pfWageCapAmount),
          pfOnEsiPct: num(c.pfOnEsiPct ?? DEFAULT_CONTROL.pfOnEsiPct),
          daPctOfBasic: num(c.daPctOfBasic ?? c.daPctBasic ?? DEFAULT_CONTROL.daPctOfBasic),
          lwfEnabled: bool(c.lwfEnabled ?? DEFAULT_CONTROL.lwfEnabled),
          lwfEmployeeAmt: num(c.lwfEmployeeAmt ?? c.lwfEmpAmt ?? DEFAULT_CONTROL.lwfEmployeeAmt),
          lwfEmployerAmt: num(c.lwfEmployerAmt ?? c.lwfErAmt ?? DEFAULT_CONTROL.lwfEmployerAmt),
          lwfApplicableMonth: num(c.lwfApplicableMonth ?? c.lwfMonth ?? DEFAULT_CONTROL.lwfApplicableMonth),
          basicPctOfCtc: pickNum(c.basicPctOfCtc ?? c.ctcBasicPct, byName.basic ?? byName.ctcbasicpct, DEFAULT_CONTROL.basicPctOfCtc),
          hraPctOfCtc: pickNum(c.hraPctOfCtc ?? c.ctcHraPct, byName.hra ?? byName.ctchrapct, DEFAULT_CONTROL.hraPctOfCtc),
          conveyPctOfCtc: pickNum(c.conveyPctOfCtc ?? c.ctcConvPct, byName.conveyance ?? byName.conv ?? byName.ctcconvpct, DEFAULT_CONTROL.conveyPctOfCtc),
          daPctOfCtc: pickNum(c.daPctOfCtc ?? c.ctcDaPct, byName.da ?? byName.ctcdapct, DEFAULT_CONTROL.daPctOfCtc),
          eduPctOfCtc: pickNum(c.eduPctOfCtc ?? c.ctcEduPct, byName["educational allowance"] ?? byName["education allowance"] ?? byName.edu ?? byName.ctcedupct, DEFAULT_CONTROL.eduPctOfCtc),
          specialPctOfCtc: pickNum(c.specialPctOfCtc ?? c.ctcSpecialPct, byName["special allowance"] ?? byName.special ?? byName.ctcspecialpct, DEFAULT_CONTROL.specialPctOfCtc),
          ctcSplitRows: Array.isArray(c.ctcSplitRows) ? c.ctcSplitRows : [],
          ctcAddonRows: Array.isArray(c.ctcAddonRows) ? c.ctcAddonRows : [],
          otherDeductionRows: Array.isArray(c.otherDeductionRows) ? c.otherDeductionRows : []
        };
      }
      return { ...DEFAULT_CONTROL };
    }
    function otherDeductionItems(ctrl){
      const rows = getOtherDeductionRowsNormalized(ctrl);
      const map = {};
      rows.forEach((r) => { map[r.name] = round2(r.amount); });
      return map;
    }
    function ctcAddonRows(ctrl){
      return (Array.isArray(ctrl?.ctcAddonRows) ? ctrl.ctcAddonRows : []).map((r) => ({
        code: String(r?.code || "").trim(),
        name: String(r?.name || "").trim(),
        type: String(r?.type || "").trim().toLowerCase() === "amount" ? "amount" : "percent",
        value: num(r?.value ?? r?.amount ?? 0)
      })).filter((r) => r.name && r.value > 0);
    }
    function ctcAddonTotalForGross(gross, ctrl){
      const addonMap = ctcAddonMapForGross(gross, ctrl);
      return round2(Object.values(addonMap).reduce((sum, value) => sum + num(value), 0));
    }
    function getCtcAddonColsForRules(ctrl){
      return ctcAddonRows(ctrl).map((r) => r.name);
    }
    function ctcAddonMapForGross(gross, ctrl){
      const out = {};
      ctcAddonRows(ctrl).forEach((row) => {
        let value = 0;
        if(row.type === "amount"){
          value = num(row.value);
        } else {
          const isPfEmployer = row.code === "pfEmployerPct" || normalizeAlias(row.name) === "pf employer %";
          const isEsiEmployer = row.code === "esiEmployerPct" || normalizeAlias(row.name) === "esi employer %";
          const esiLimit = num(ctrl?.esiWageLimit || 0);
          const esiEligible = esiLimit > 0 ? num(gross) <= esiLimit : true;
          const pfCapAmount = num(ctrl?.pfWageCapAmount || 0);
          const pfOnEsiPct = num(ctrl?.pfOnEsiPct || 0);
          const statutoryBase = esiEligible ? (num(gross) * pfOnEsiPct / 100) : num(gross);
          const contributionBase = (isPfEmployer || isEsiEmployer)
            ? (esiEligible
                ? statutoryBase
                : (isPfEmployer ? (pfCapAmount > 0 ? Math.min(num(gross), pfCapAmount) : num(gross)) : num(gross)))
            : num(gross);
          const pfBase = isPfEmployer
            ? (esiEligible
                ? (num(gross) * pfOnEsiPct / 100)
                : (pfCapAmount > 0 ? Math.min(num(gross), pfCapAmount) : num(gross)))
            : num(gross);
          value = (isEsiEmployer && !esiEligible) ? 0 : ((pfBase * num(row.value)) / 100);
          if(isEsiEmployer && esiEligible){
            value = (contributionBase * num(row.value)) / 100;
          }
        }
        out[row.name] = round2(value);
      });
      return out;
    }

    async function refreshControlLive(){
      try {
        const res = await apiFetch("/control", { cache: "no-store" });
        if(res.ok){
          const data = await res.json();
          localStorage.setItem(KEY_CONTROL, JSON.stringify(data || {}));
        }
      } catch(_e){}
      const nextRules = loadControl();
      const prevRules = JSON.stringify(rules || {});
      const nextSerialized = JSON.stringify(nextRules || {});
      rules = nextRules;
      if(prevRules !== nextSerialized) render();
    }

    function loadAttendanceMonth(y,m){ const all = safeParse(localStorage.getItem(KEY_ATT)) || {}; return all[monthKey(y,m)] || {}; }
    function loadOverrides(){ return safeParse(localStorage.getItem(KEY_OVR)) || {}; }
    function saveOverrides(o){
      localStorage.setItem(KEY_OVR, JSON.stringify(o));
      if ($("lastAction")) $("lastAction").textContent = new Date().toLocaleString();
    }
    function saveFiles(){ localStorage.setItem(KEY_SALARY_FILES, JSON.stringify(files)); }
    function normalizeSheetIndexRow(x){
      const month = Number(x?.month || 0);
      const year = Number(x?.year || 0);
      const period = String(x?.period || monthKey(year, month));
      const id = String(x?.id || "");
      const generatedAt = String(x?.generatedAt || x?.createdOn || "");
      const createdOn = generatedAt ? new Date(generatedAt).toLocaleString() : "";
      return {
        id,
        month,
        year,
        period,
        generatedAt,
        createdOn,
        status: "success",
        format: "xlsx",
        fileName: `salary_sheet_${period}.xlsx`,
        rowCount: Number(x?.rowCount || 0),
        totalPfWage: Number(x?.totalPfWage || 0),
        totalPfEe: Number(x?.totalPfEe || 0),
        totalPfEr: Number(x?.totalPfEr || 0)
      };
    }
    async function listPayrollSheetsApi(){
      const r = await apiFetch(API_PAYROLL_SHEETS, { cache: "no-store" });
      if(!r.ok) throw new Error("list failed");
      const data = await r.json();
      return (Array.isArray(data?.rows) ? data.rows : []).map(normalizeSheetIndexRow);
    }
    async function getPayrollSheetApi(id){
      const r = await apiFetch(`${API_PAYROLL_SHEETS}/${encodeURIComponent(id)}`, { cache: "no-store" });
      if(!r.ok) throw new Error("get failed");
      const data = await r.json();
      return normalizeStoredSheet(data?.sheet || null);
    }
    async function deletePayrollSheetApi(id){
      const r = await apiFetch(`${API_PAYROLL_SHEETS}/${encodeURIComponent(id)}`, { method: "DELETE" });
      if(!r.ok) throw new Error("delete failed");
      return true;
    }
    async function clearPayrollSheetsApi(){
      const r = await apiFetch(API_PAYROLL_CLEAR, { method: "POST" });
      if(!r.ok) throw new Error("clear failed");
      return true;
    }
    function componentMapFromRow(r, activeRules){
      const cols = getComponentColsForRules(activeRules);
      const out = {};
      const fromRow = (r && typeof r.components === "object") ? r.components : null;
      if(fromRow){
        cols.forEach((name) => { out[name] = round2(num(fromRow[name])); });
        return out;
      }
      const split = distributeFromCTC(num(r?.gross || 0), activeRules);
      cols.forEach((name) => { out[name] = round2(num(split.components?.[name] || 0)); });
      return out;
    }

    function toSheetRows(rows, y, m){
      const includePt = !!rules.ptEnabled;
      const includeLwf = !!rules.lwfEnabled;
      const componentCols = getComponentColsForRules(rules);
      const addonCols = getCtcAddonColsForRules(rules);
      const deductionCols = getOtherDeductionColsForRules(rules);
      return (rows || []).map((r, idx) => ({
        "Sr No": idx + 1,
        Name: r.empName,
        Month: monthKey(y, m),
        Year: y,
        "No. of Days Payable": round2(r.paidDays),
        "Weekly Off": round2(r.wo),
        "Work Days": round2(r.workDays),
        "CTC Monthly": round2(num(r.ctcMonthly ?? (num(r.gross) + ctcAddonTotalForGross(num(r.gross), rules)))),
        ...addonCols.reduce((acc, name) => {
          acc[name] = ctcAddonMapForGross(num(r.gross), rules)[name];
          return acc;
        }, {}),
        "Wages for the month": round2(r.gross),
        "LOP Dedu": round2(r.lopDeduction),
        "Earned Wages": round2(r.earnedGross),
        "OT Hours": round2(r.otHours ?? 0),
        "OT Amount": round2(r.otAmount ?? 0),
        Incentive: round2(r.incentiveAmount ?? 0),
        "Total Earnings": round2(r.totalEarnings ?? (num(r.earnedGross) + num(r.otAmount) + num(r.incentiveAmount))),
        ...componentCols.reduce((acc, name) => { acc[name] = componentMapFromRow(r, rules)[name]; return acc; }, {}),
        PF: round2(r.pfEE),
        PF_ER: round2(r.pfER),
        ESI_EE: round2(r.esiEE),
        ESI_ER: round2(r.esiER),
        ...(includePt ? { PT: round2(r.pt) } : {}),
        ...(includeLwf ? { LWF_EE: round2(r.lwf), LWF_ER: round2(r.lwfER) } : {}),
        "Advance Salary": round2(r.advanceSalaryDeduction ?? 0),
        "Loan EMI": round2(r.loanDeduction ?? 0),
        ...deductionCols.reduce((acc, name) => {
          const v = num((r.otherDeductionItems || {})[name]);
          acc[name] = round2(v);
          return acc;
        }, {}),
        "Total Deductions": round2(r.totalDed),
        "Net Wages Paid": round2(r.net),
        "Signature of the employee": ""
      }));
    }
    function withSalaryTotals(rows){
      const list = Array.isArray(rows) ? [...rows] : [];
      if(!list.length) return list;
      const allKeys = Object.keys(list[0] || {});
      const textKeys = new Set(["Sr No", "Month", "Year", "Name", "Signature of the employee"]);
      const totalRow = {};
      allKeys.forEach((key) => {
        if (key === "Name") { totalRow[key] = "Total"; return; }
        if (textKeys.has(key)) { totalRow[key] = ""; return; }
        totalRow[key] = round2(list.reduce((s, r) => s + num(r[key]), 0));
      });
      list.push(totalRow);
      return list;
    }
    function adjustRowsByStatutoryFlags(rows){
      rules = loadControl();
      const includePt = !!rules.ptEnabled;
      const includeLwf = !!rules.lwfEnabled;
      return (rows || []).map((r) => {
        const next = { ...r };
        const earnedGross = round2(num(next["Earned Wages"] ?? next.Earned_Gross ?? next.earnedGross ?? next.Net_Pay ?? 0));
        const basic = round2(num(next.Basic ?? next.basic ?? 0));
        const esiEligible = num(rules.esiWageLimit || 0) > 0 && earnedGross > 0 && earnedGross <= num(rules.esiWageLimit || 0);
        const statutoryBase = esiEligible ? round2(earnedGross * (num(rules.pfOnEsiPct || 0) / 100)) : earnedGross;
        const pfBase = round2(basic + ((basic * num(rules.daPctOfBasic || 0)) / 100));
        const pfWages = esiEligible
          ? statutoryBase
          : ((rules.pfWageCapEnabled && num(rules.esiWageLimit || 0) > 0 && earnedGross > num(rules.esiWageLimit || 0)) ? round2(num(rules.pfWageCapAmount || 0)) : pfBase);
        next.PF = round2(num(next.PF ?? next.PF_EE ?? 0));
        next.PF_ER = round2((pfWages * num(rules.pfEmployerPct || 0)) / 100);
        next.ESI_EE = esiEligible ? round2(num(next.ESI_EE ?? ((statutoryBase * num(rules.esiEmployeePct || 0)) / 100))) : 0;
        next.ESI_ER = esiEligible ? round2((statutoryBase * num(rules.esiEmployerPct || 0)) / 100) : 0;
        if(includeLwf){
          const hasLwfEe = num(next.LWF_EE ?? next.LWF ?? 0) > 0;
          next.LWF_EE = hasLwfEe ? round2(num(next.LWF_EE ?? next.LWF ?? 0)) : 0;
          next.LWF_ER = hasLwfEe ? round2(num(next.LWF_ER ?? rules.lwfEmployerAmt ?? 0)) : 0;
        }
        if(!includePt) delete next.PT;
        if(!includeLwf){
          delete next.LWF_EE;
          delete next.LWF_ER;
          delete next.LWF;
        }
        return next;
      });
    }
    function parseMonthYearLabel(rows, meta = {}){
      const monthNum = Number(meta.month || 0);
      const yearNum = Number(meta.year || 0);
      if(monthNum >= 1 && monthNum <= 12 && yearNum > 0){
        return `${monthNames[monthNum] || String(monthNum).padStart(2, "0")} ${yearNum}`;
      }
      const mk = String((rows && rows[0] && rows[0].Month) || "");
      const m = mk.match(/^(\d{4})-(\d{2})$/);
      if(m){
        const y = Number(m[1]);
        const mm = Number(m[2]);
        return `${monthNames[mm] || m[2]} ${y}`;
      }
      return "-";
    }
    async function loadProfileForExport(){
      const localProfile = safeParse(localStorage.getItem("hr_client_profile_v1")) || {};
      try {
        const res = await apiFetch("/profile", { cache: "no-store" });
        if(res.ok){
          const apiProfile = await res.json();
          localStorage.setItem("hr_client_profile_v1", JSON.stringify(apiProfile));
          return { ...localProfile, ...apiProfile };
        }
      } catch(_e){}
      const ctrl = safeParse(localStorage.getItem(KEY_CONTROL)) || {};
      const fromControl = {
        companyName: ctrl.companyName || "",
        companyAddress: ctrl.companyAddress || "",
        regNo: ctrl.companyRegNo || "",
        pan: ctrl.companyPAN || "",
        tan: ctrl.companyTAN || "",
        gstin: ctrl.companyGSTIN || "",
        contactNo: ctrl.companyContact || ""
      };
      return { ...fromControl, ...localProfile };
    }
    function downloadAsXlsx(rows, fileName, meta = {}){
      const wb = XLSX.utils.book_new();
      const profile = meta.profile || safeParse(localStorage.getItem("hr_client_profile_v1")) || {};
      const monthYearLabel = parseMonthYearLabel(rows, meta);
      const info = [
        ["Company Name *", profile.companyName || ""],
        ["Month & Year", monthYearLabel],
        ["CIN / LLPIN / Reg. No", profile.regNo || profile.companyRegNo || ""],
        ["Company Address *", profile.companyAddress || ""],
        ["PAN", profile.pan || profile.companyPAN || ""],
        ["TAN", profile.tan || profile.companyTAN || ""],
        ["GSTIN", profile.gstin || profile.companyGSTIN || ""],
        ["PF Establishment ID", profile.pfEstId || ""],
        ["ESIC Employer Code", profile.esicCode || ""],
        []
      ];
      const outRows = formatRowsForXlsx(withSalaryTotals(rows || []));
      const ws = XLSX.utils.aoa_to_sheet(info);
      const rowStart = info.length + 1;
      XLSX.utils.sheet_add_json(ws, outRows, { origin: `A${rowStart}`, skipHeader: false });
      const colCount = Object.keys(outRows[0] || {}).length;
      const rowEnd = rowStart + outRows.length;
      const mkCol = (n) => { let s = ""; while(n > 0){ const m = (n - 1) % 26; s = String.fromCharCode(65 + m) + s; n = Math.floor((n - 1) / 26); } return s; };
      if(colCount > 0 && outRows.length){
        ws["!autofilter"] = { ref: `A${rowStart}:${mkCol(colCount)}${rowEnd}` };
        ws["!cols"] = Array.from({ length: colCount }, () => ({ wch: 16 }));
        for(let r = rowStart; r <= rowEnd; r++){
          for(let c = 1; c <= colCount; c++){
            const ref = `${mkCol(c)}${r}`;
            if(!ws[ref]) ws[ref] = { t: "s", v: "" };
            ws[ref].s = {
              border: {
                top: { style: "thin", color: { rgb: "FF000000" } },
                bottom: { style: "thin", color: { rgb: "FF000000" } },
                left: { style: "thin", color: { rgb: "FF000000" } },
                right: { style: "thin", color: { rgb: "FF000000" } }
              },
              fill: r === rowStart ? { patternType: "solid", fgColor: { rgb: "FF0B1F3A" } } : undefined,
              font: r === rowStart ? { bold: true, color: { rgb: "FFFFFFFF" } } : undefined
            };
          }
        }
      }
      XLSX.utils.book_append_sheet(wb, ws, "Register_of_Wages");
      XLSX.writeFileXLSX(wb, fileName.endsWith(".xlsx") ? fileName : `${fileName}.xlsx`, { cellStyles: true, bookType: "xlsx" });
    }
    function formatRowsForXlsx(rows){
      const includePt = !!rules.ptEnabled;
      const includeLwf = !!rules.lwfEnabled;
      const componentCols = getComponentColsForRules(rules);
      const addonCols = getCtcAddonColsForRules(rules);
      const deductionCols = getOtherDeductionColsForRules(rules);
      return (rows || []).map((row) => {
        const out = {
          "Sr No": row["Sr No"] ?? "",
          Name: row.Name ?? row.Employee_Name ?? row.empName ?? "",
          Month: row.Month ?? "",
          Year: row.Year ?? "",
          "No. of Day": row["No. of Days Payable"] ?? "",
          "Weekly Off": row["Weekly Off"] ?? "",
          "Work Days": row["Work Days"] ?? "",
          "CTC Month": row["CTC Monthly"] ?? ""
        };
        addonCols.forEach((name) => {
          out[name] = `- ${money(num(row[name]))}`;
        });
        out["Wages for"] = row["Wages for the month"] ?? "";
        out["LOP Dedu"] = `- ${money(num(row["LOP Dedu"]))}`;
        out["Earned Wa"] = row["Earned Wages"] ?? "";
        out["OT Hours"] = row["OT Hours"] ?? "";
        out["OT Amount"] = row["OT Amount"] ?? "";
        out["Incentive"] = row["Incentive"] ?? "";
        out["Total Earnings"] = row["Total Earnings"] ?? "";
        componentCols.forEach((name) => {
          out[name] = row[name] ?? "";
        });
        out.PF = `- ${money(num(row.PF ?? row.PF_EE))}`;
        out["ESI EE"] = `- ${money(num(row.ESI_EE ?? row.ESI))}`;
        if(includePt) out.PT = `- ${money(num(row.PT))}`;
        if(includeLwf) out["LWF EE"] = `- ${money(num(row.LWF_EE ?? row.LWF))}`;
        out["Advance Salary"] = `- ${money(num(row["Advance Salary"] ?? row.Advance_Salary ?? row.advanceSalaryDeduction))}`;
        out["Loan EMI"] = `- ${money(num(row["Loan EMI"] ?? row.Loan_EMI ?? row.loanDeduction))}`;
        deductionCols.forEach((name) => {
          out[name] = `- ${money(num(row[name]))}`;
        });
        out["Total Dedu"] = `- ${money(num(row["Total Deductions"] ?? row.Total_Deductions))}`;
        out["Net Wages"] = row["Net Wages Paid"] ?? row.Net_Pay ?? "";
        return out;
      });
    }
    function downloadAsCsv(rows, fileName){
      const cols = Object.keys((rows && rows[0]) || {});
      const csv = [cols, ...(rows || []).map(r => cols.map(c => r[c]))]
        .map(row => row.map(v => `"${String(v ?? "").replaceAll('"','""')}"`).join(","))
        .join("\n");
      const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = fileName.endsWith(".csv") ? fileName : `${fileName}.csv`;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
    }

    function countStatuses(attMonth, empId, y, m){
      const dim = daysInMonth(y,m); const counts = { P:0, WO:0, CL:0, SL:0, EL:0, LOP:0, A:0, BLANK:0 };
      for(let d=1; d<=dim; d++){ const dateIso = `${y}-${String(m).padStart(2,"0")}-${String(d).padStart(2,"0")}`; const key = `${empId}|${dateIso}`; const st = (attMonth[key] || "").toUpperCase(); if(!st){ counts.BLANK++; continue; } if(counts[st] !== undefined) counts[st]++; else counts.BLANK++; }
      const absentModeEl = $("absentMode");
      const mode = absentModeEl ? absentModeEl.value : "A";
      const lopFromAbsent = mode === "LOP" ? counts.A : 0;
      const paidDays = dim - (counts.LOP + lopFromAbsent); const lopDays = counts.LOP + lopFromAbsent;
      return { dim, counts, paidDays: Math.max(0, paidDays), lopDays: Math.max(0, lopDays) };
    }

    function distributeFromCTC(ctc, rules){
      const rows = getCtcRowsNormalized(rules);
      const totalPct = rows.reduce((s, r) => s + num(r.pct), 0);
      const amounts = rows.map((r) => ({ name: r.name, amount: (ctc * (totalPct > 0 ? num(r.pct) / totalPct : 0)) }));
      const componentMap = {};
      amounts.forEach((r) => { componentMap[String(r.name || "").trim()] = round2(num(r.amount)); });
      const byAlias = new Map(amounts.map((r) => [normalizeAlias(r.name), num(r.amount)]));
      const basic = num(byAlias.get("basic"));
      const hra = num(byAlias.get("hra"));
      const convey = num(byAlias.get("conveyance") || byAlias.get("conv"));
      const da = num(byAlias.get("da"));
      const edu = num(byAlias.get("educational allowance") || byAlias.get("education allowance") || byAlias.get("edu"));
      const special = num(byAlias.get("special allowance") || byAlias.get("special"));
      const gross = amounts.reduce((s, r) => s + num(r.amount), 0);
      return { basic, hra, convey, da, edu, special, gross, components: componentMap };
    }
    function pfWagesFromBasic(basic, adjustedGross, rules, useEsiPfRule){
      const da = (basic * rules.daPctOfBasic) / 100;
      const basePfWages = basic + da;
      if(useEsiPfRule){
        return adjustedGross * (num(rules.pfOnEsiPct || 0) / 100);
      }
      const pfThreshold = num(rules.esiWageLimit || 0);
      if(rules.pfWageCapEnabled && pfThreshold > 0 && adjustedGross > pfThreshold){
        return num(rules.pfWageCapAmount);
      }
      return basePfWages;
    }
    function statutoryBaseFromRules(adjustedGross, rules, esiEligible){
      if(esiEligible){
        return adjustedGross * (num(rules.pfOnEsiPct || 0) / 100);
      }
      return adjustedGross;
    }

    function calcRow(empId, y, m, attMonth, rules, overrides){
      const emp = empById.get(empId.toUpperCase());
      const { dim, counts, paidDays, lopDays } = countStatuses(attMonth, empId, y, m);
      const o = overrides[empId] || {};
      const pfAppl = (o.pfAppl ?? true) === true, esiAppl = (o.esiAppl ?? true) === true, ptAppl = (o.ptAppl ?? true) === true, lwfAppl = (o.lwfAppl ?? true) === true;
      let gross = num(o.gross); let basic = 0, hra=0, convey=0, da=0, edu=0, special=0;
      if(gross > 0){
        const split = distributeFromCTC(gross, rules);
        basic = split.basic; hra = split.hra; convey = split.convey; da = split.da; edu = split.edu; special = split.special; gross = split.gross;
      } else {
        // Priority for salary source:
        // 1) Employee override CTC (if set in payroll page)
        // 2) Employee Master Base_CTC_Monthly (latest saved)
        // 3) fallback default
        const ctc = num(o.ctc || 0);
        const masterCtc = num(emp?.baseCtc || 0);
        const effectiveCtc = ctc > 0 ? ctc : (masterCtc > 0 ? masterCtc : 25000);
        const split = distributeFromCTC(effectiveCtc, rules);
        basic = split.basic; hra = split.hra; convey = split.convey; da = split.da; edu = split.edu; special = split.special; gross = split.gross;
      }
      // Weekly Off is paid: LOP deduction is on full month days.
      const monthDays = Math.max(1, dim);
      const lopDeduction = (gross / monthDays) * lopDays;
      const earnedGross = Math.max(0, gross - lopDeduction);
      const adjustedParts = distributeFromCTC(earnedGross, rules);
      const esiEligible = (earnedGross <= rules.esiWageLimit);
      const pfWages = pfWagesFromBasic(adjustedParts.basic, earnedGross, rules, esiAppl && esiEligible);
      const statutoryBase = statutoryBaseFromRules(earnedGross, rules, esiAppl && esiEligible);
      const pfEE = pfAppl ? (pfWages * rules.pfEmployeePct) / 100 : 0;
      // PF (Employer): employer-side CTC cost, not included in employee deductions.
      const pfER = pfAppl ? (pfWages * rules.pfEmployerPct) / 100 : 0;
      const esiEE = (esiAppl && esiEligible) ? (statutoryBase * rules.esiEmployeePct) / 100 : 0;
      const esiER = (esiAppl && esiEligible) ? (statutoryBase * rules.esiEmployerPct) / 100 : 0;
      const pt = (ptAppl && rules.ptEnabled) ? rules.ptMonthly : 0;
      const isLwfMonth = (rules.lwfApplicableMonth === 0) || (rules.lwfApplicableMonth === m);
      const lwf = (rules.lwfEnabled && lwfAppl && isLwfMonth) ? rules.lwfEmployeeAmt : 0;
      const lwfER = (rules.lwfEnabled && lwfAppl && isLwfMonth) ? rules.lwfEmployerAmt : 0;
      const otherDeductionMap = otherDeductionItems(rules);
      const otherDeductions = round2(Object.values(otherDeductionMap).reduce((s, v) => s + num(v), 0));
      const totalDed = pfEE + esiEE + pt + lwf + otherDeductions;
      const net = earnedGross - totalDed;
      const parts = distributeFromCTC(gross, rules);
      const weeklyOff = num(counts.WO || 0);
      const workDays = Math.max(0, paidDays - weeklyOff);
      const ctcMonthly = round2(gross + ctcAddonTotalForGross(gross, rules));
      return { empId, empName: emp?.empName || empId, dept: emp?.dept || "", desig: emp?.desig || "", dim, paidDays, wo: round2(weeklyOff), workDays: round2(workDays), lopDays, cl: counts.CL, sl: counts.SL, el: counts.EL, gross: round2(gross), ctcMonthly, da: round2(da), earnedGross: round2(earnedGross), lopDeduction: round2(Math.max(0, lopDeduction)), pfEE: round2(pfEE), pfER: round2(pfER), esiEE: round2(esiEE), esiER: round2(esiER), pt: round2(pt), lwf: round2(lwf), lwfER: round2(lwfER), otherDeductions: round2(otherDeductions), otherDeductionItems: otherDeductionMap, totalDed: round2(totalDed), net: round2(net), components: parts.components || {}, flags: { pfAppl, esiAppl, ptAppl, lwfAppl, esiEligible } };
    }

    let rules = loadControl();
    let overrides = loadOverrides();

    function buildRowsForPeriod(y, m){
      refreshEmployees();
      rules = loadControl();
      overrides = loadOverrides();
      const attMonth = loadAttendanceMonth(y, m);
      return employees.map((e) => calcRow(e.empId, y, m, attMonth, rules, overrides));
    }

    function render(){
      const y = parseInt($("yearSel").value,10), m = parseInt($("monthSel").value,10);
      if(!Number.isInteger(y) || !Number.isInteger(m)){
        window.__lastRows = [];
        renderSheetStats();
        renderSheetTable();
        return;
      }
      const rows = buildRowsForPeriod(y, m);
      if ($("tbody")) {
        $("tbody").innerHTML = rows.map(r => `<tr><td class="fw-semibold mono">${r.empId}</td><td><div class="fw-semibold">${r.empName}</div><div class="small text-muted-3">${r.desig}</div></td><td><div class="fw-semibold">${r.dept || "-"}</div><div class="small text-muted-3">-</div></td><td class="fw-semibold">${r.dim}</td><td class="fw-semibold">${r.paidDays}</td><td class="fw-semibold">${r.lopDays}</td><td>${r.cl}</td><td>${r.sl}</td><td>${r.el}</td><td class="text-end fw-semibold">Rs ${money(r.gross)}</td><td class="text-end fw-semibold">Rs ${money(r.earnedGross)}</td><td class="text-end">Rs ${money(r.pfEE)}</td><td class="text-end">Rs ${money(r.esiEE)}${r.flags.esiEligible ? "" : `<div class="small text-muted-3">Not eligible</div>`}</td><td class="text-end">Rs ${money(r.pt)}</td><td class="text-end">Rs ${money(r.lwf)}</td><td class="text-end fw-semibold">Rs ${money(r.totalDed)}</td><td class="text-end fw-semibold">Rs ${money(r.net)}</td><td class="text-end"><button class="btn btn-outline-secondary btn-sm" onclick="openEmpModal('${r.empId}')"><i class="bi bi-pencil"></i></button></td></tr>`).join("");
      }
      if ($("countText")) $("countText").textContent = `${rows.length} employees`;
      if ($("lastAction")) $("lastAction").textContent = new Date().toLocaleString();
      window.__lastRows = rows;
      renderSheetStats();
      renderSheetTable();
    }

    function renderSheetStats(){
      const now = new Date();
      const nowMonth = now.getMonth() + 1, nowYear = now.getFullYear();
      if($("statTotal")) $("statTotal").textContent = files.length;
      if($("statThisMonth")) $("statThisMonth").textContent = files.filter(x => x.month === nowMonth && x.year === nowYear).length;
      if($("statSuccess")) $("statSuccess").textContent = files.filter(x => x.status === "success").length;
      if($("statFailed")) $("statFailed").textContent = files.filter(x => x.status === "failed").length;
    }
    function getSheetRows(item){
      const src = (item?.rows && item.rows.length) ? item.rows : toSheetRows(window.__lastRows || [], item?.year, item?.month);
      const month = Number(item?.month || 0);
      const year = Number(item?.year || 0);
      const componentCols = getComponentColsForRules(rules);
      const addonCols = getCtcAddonColsForRules(rules);
      const deductionCols = getOtherDeductionColsForRules(rules);
      return (src || []).map((r, idx) => {
        if(Object.prototype.hasOwnProperty.call(r || {}, "Name") || Object.prototype.hasOwnProperty.call(r || {}, "Employee_Name")){
          return r;
        }
        const gross = round2(num(r?.gross ?? r?.Gross ?? 0));
        const earned = round2(num(r?.earnedGross ?? r?.earned ?? r?.["Gross Salary Payable"] ?? 0));
        const pfEE = round2(num(r?.pfEE ?? r?.PF ?? 0));
        const pfER = round2(num(r?.pfER ?? r?.PF_ER ?? 0));
        const esiEE = round2(num(r?.esiEE ?? r?.ESI_EE ?? 0));
        const esiER = round2(num(r?.esiER ?? r?.ESI_ER ?? 0));
        const pt = round2(num(r?.pt ?? r?.PT ?? 0));
        const lwf = round2(num(r?.lwf ?? r?.LWF_EE ?? r?.LWF ?? 0));
        const lwfER = round2(num(r?.lwfER ?? r?.LWF_ER ?? 0));
          const advanceSalary = round2(num(r?.advanceSalaryDeduction ?? r?.Advance_Salary ?? r?.["Advance Salary"] ?? 0));
          const loanDeduction = round2(num(r?.loanDeduction ?? r?.Loan_EMI ?? r?.["Loan EMI"] ?? 0));
        const otHours = round2(num(r?.otHours ?? r?.OT_Hours ?? r?.["OT Hours"] ?? 0));
        const otAmount = round2(num(r?.otAmount ?? r?.OT_Amount ?? r?.["OT Amount"] ?? 0));
        const incentive = round2(num(r?.incentiveAmount ?? r?.Incentive ?? r?.["Incentive"] ?? 0));
        const totalEarnings = round2(num(r?.totalEarnings ?? r?.Total_Earnings ?? r?.["Total Earnings"] ?? (earned + otAmount + incentive)));
        const apiOtherItems = Array.isArray(r?.otherDeductionItems)
          ? r.otherDeductionItems.reduce((acc, it) => {
              const nm = String(it?.name || "").trim();
              if(!nm) return acc;
              acc[nm] = round2(Math.max(0, num(it?.amount ?? 0)));
              return acc;
            }, {})
          : ((r?.otherDeductionItems && typeof r.otherDeductionItems === "object") ? r.otherDeductionItems : {});
        const totalDed = round2(num(r?.totalDeductions ?? r?.totalDed ?? r?.["Total Deductions"] ?? 0));
        const net = round2(num(r?.netPayable ?? r?.net ?? r?.["Net Wages Paid"] ?? 0));
        const rowComp = componentMapFromRow({ gross: gross }, rules);
        const rowAddons = ctcAddonMapForGross(gross, rules);
        const out = {
          "Sr No": idx + 1,
          Name: String(r?.empName || r?.Employee_Name || r?.Name || "-"),
          Month: monthKey(year, month),
          Year: year,
          "No. of Days Payable": round2(num(r?.paidDays ?? 0)),
          "Weekly Off": round2(num(r?.WO ?? r?.wo ?? 0)),
          "Work Days": round2(num(r?.workDays ?? 0)),
          "CTC Monthly": round2(num(r?.ctcMonthly ?? r?.["CTC Monthly"] ?? (gross + ctcAddonTotalForGross(gross, rules)))),
          "Wages for the month": gross,
          "LOP Dedu": round2(num(r?.lopDeduction ?? r?.["LOP Dedu"] ?? 0)),
          "Earned Wages": earned,
          "OT Hours": otHours,
          "OT Amount": otAmount,
          "Incentive": incentive,
          "Total Earnings": totalEarnings,
          PF: pfEE,
          PF_ER: pfER,
          ESI_EE: esiEE,
          ESI_ER: esiER,
          PT: pt,
          LWF_EE: lwf,
          LWF_ER: lwfER,
          "Advance Salary": advanceSalary,
          "Loan EMI": loanDeduction,
          "Total Deductions": totalDed,
          "Net Wages Paid": net
        };
        deductionCols.forEach((name) => {
          const fromApi = num(apiOtherItems?.[name]);
          const fromFlat = num(r?.[name]);
          out[name] = round2(fromApi || fromFlat || 0);
        });
        const componentValues = {};
        componentCols.forEach((c) => { componentValues[c] = round2(num(rowComp[c] || 0)); });
        const addonValues = {};
        addonCols.forEach((c) => { addonValues[c] = round2(num(r?.[c] ?? rowAddons[c] ?? 0)); });
        const ordered = {};
        Object.keys(out).forEach((key) => {
          ordered[key] = out[key];
          if(key === "CTC Monthly"){
            addonCols.forEach((c) => { ordered[c] = addonValues[c]; });
          }
          if(key === "Total Earnings"){
            componentCols.forEach((c) => { ordered[c] = componentValues[c]; });
          }
        });
        return ordered;
      });
    }
    function normalizeStoredSheet(sheet){
      if(!sheet || typeof sheet !== "object") return sheet;
      const normalizedRows = adjustRowsByStatutoryFlags(getSheetRows(sheet));
      return {
        ...sheet,
        rows: normalizedRows,
        rowCount: Number(sheet?.rowCount || normalizedRows.length || 0)
      };
    }
    function calcSheetTotals(item){
      if(item && item.rowCount > 0 && (item.totalPfWage > 0 || item.totalPfEe > 0 || item.totalPfEr > 0)){
        return {
          rowCount: Number(item.rowCount || 0),
          totalPfWage: round2(Number(item.totalPfWage || 0)),
          totalPfEe: round2(Number(item.totalPfEe || 0)),
          totalPfEr: round2(Number(item.totalPfEr || 0)),
          rows: getSheetRows(item)
        };
      }
      const rows = getSheetRows(item);
      const totalPfWage = rows.reduce((s, r) => s + num(r["Earned Wages"] ?? r["Wages for the month"] ?? r.Gross ?? 0), 0);
      const totalPfEe = rows.reduce((s, r) => s + num(r.PF ?? r.PF_EE ?? 0), 0);
      const totalPfEr = rows.reduce((s, r) => s + num(r.PF_ER ?? r.pfER ?? 0), 0);
      return { rowCount: rows.length, totalPfWage: round2(totalPfWage), totalPfEe: round2(totalPfEe), totalPfEr: round2(totalPfEr), rows };
    }
    function renderSalaryPreview(item){
      const body = $("salaryPreviewBody");
      if(!body) return;
      const includePt = !!rules.ptEnabled;
      const includeLwf = !!rules.lwfEnabled;
      const componentCols = getComponentColsForRules(rules);
      const addonCols = getCtcAddonColsForRules(rules);
      const deductionCols = getOtherDeductionColsForRules(rules);
      const headRow = $("salaryPreviewHeadRow") || document.querySelector(".salary-preview-table thead tr");
      if(headRow){
        const safeCol = (name) => String(name || "").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        headRow.innerHTML = `
          <th>Sr No</th>
          <th>Name</th>
          <th>Month</th>
          <th>Year</th>
          <th class="text-end">No. of Day</th>
          <th class="text-end">Weekly Off</th>
          <th class="text-end">Work Days</th>
          <th class="text-end">CTC Month</th>
          ${addonCols.map((n) => `<th class="text-end">${safeCol(n)}</th>`).join("")}
          <th class="text-end">Wages for</th>
          <th class="text-end">LOP Dedu</th>
          <th class="text-end">Earned Wa</th>
          <th class="text-end">OT Hours</th>
          <th class="text-end">OT Amount</th>
          <th class="text-end">Incentive</th>
          <th class="text-end">Total Earnings</th>
          ${componentCols.map((n) => `<th class="text-end">${safeCol(n)}</th>`).join("")}
          <th class="text-end">PF EE</th>
          <th class="text-end">PF ER</th>
          <th class="text-end">ESI EE</th>
          <th class="text-end">ESI ER</th>
          ${includePt ? '<th class="text-end">PT</th>' : ''}
          ${includeLwf ? '<th class="text-end">LWF EE</th>' : ''}
          ${includeLwf ? '<th class="text-end">LWF ER</th>' : ''}
          <th class="text-end">Advance Salary</th>
          <th class="text-end">Loan EMI</th>
          ${deductionCols.map((n) => `<th class="text-end">${safeCol(n)}</th>`).join("")}
          <th class="text-end">Total Dedu</th>
          <th class="text-end">Net Wages</th>
        `;
      }
      if(!item){
        body.innerHTML = "";
        if($("salaryPreviewNote")) $("salaryPreviewNote").textContent = "Generate salary sheet to preview.";
        if($("previewMonthBadge")) $("previewMonthBadge").textContent = "Month: -";
        if($("previewRowsBadge")) $("previewRowsBadge").textContent = "0";
        if($("btnDownloadPreview")) $("btnDownloadPreview").disabled = true;
        return;
      }
      const itemId = String(item.id || "");
      if((!Array.isArray(item.rows) || !item.rows.length) && itemId){
        const cached = sheetById[itemId];
        if(cached && Array.isArray(cached.rows) && cached.rows.length){
          item.rows = cached.rows;
          item.month = Number(cached.month || item.month || 0);
          item.year = Number(cached.year || item.year || 0);
        } else {
          if($("salaryPreviewNote")) $("salaryPreviewNote").textContent = "Loading latest preview...";
          body.innerHTML = `<tr><td colspan="24" class="text-center text-muted-3 py-3">Loading preview...</td></tr>`;
          if($("btnDownloadPreview")) $("btnDownloadPreview").disabled = true;
          if(previewLoadingSheetId !== itemId){
            previewLoadingSheetId = itemId;
            getPayrollSheetApi(itemId).then((full) => {
              if(!full) return;
              sheetById[itemId] = full;
              const hit = files.find((x) => String(x.id) === itemId);
              if(hit){
                hit.rows = Array.isArray(full.rows) ? full.rows : [];
                hit.month = Number(full.month || hit.month || 0);
                hit.year = Number(full.year || hit.year || 0);
              }
              saveFiles();
              const selected = activePreviewSheetId
                ? files.find((x) => String(x.id) === String(activePreviewSheetId))
                : files[0];
              renderSalaryPreview(selected || hit || item);
            }).catch(() => {
              if($("salaryPreviewNote")) $("salaryPreviewNote").textContent = "Unable to load preview data.";
              body.innerHTML = `<tr><td colspan="24" class="text-center text-muted-3 py-3">Unable to load preview.</td></tr>`;
            }).finally(() => {
              if(previewLoadingSheetId === itemId) previewLoadingSheetId = "";
            });
          }
          return;
        }
      }
      const meta = calcSheetTotals(item);
      const rows = meta.rows;
      body.innerHTML = rows.map((r, idx) => {
        const rowComp = componentCols.map((name) => {
          const val = (r[name] !== undefined) ? num(r[name]) : num(componentMapFromRow({ gross: r["Wages for the month"] ?? r.Gross ?? 0 }, rules)[name]);
          return `<td class="text-end">${money(val)}</td>`;
        }).join("");
        const addonMap = ctcAddonMapForGross(num(r["Wages for the month"] ?? r.Gross ?? 0), rules);
        const rowAddons = addonCols.map((name) => `<td class="text-end">- ${money(r[name] ?? addonMap[name] ?? 0)}</td>`).join("");
        const wagesFor = num(r["Wages for the month"] ?? r.Gross ?? 0);
        const earnedWages = num(r["Earned Wages"] ?? r.Net_Pay ?? 0);
        const otHours = num(r["OT Hours"] ?? r.OT_Hours ?? r.otHours ?? 0);
        const otAmount = num(r["OT Amount"] ?? r.OT_Amount ?? r.otAmount ?? 0);
        const incentive = num(r["Incentive"] ?? r.incentiveAmount ?? 0);
        const totalEarnings = num(r["Total Earnings"] ?? r.Total_Earnings ?? r.totalEarnings ?? (earnedWages + otAmount + incentive));
        const lopDeduction = Math.max(0, wagesFor - earnedWages);
        const payableDays = num(r["No. of Days Payable"]);
        const weeklyOff = num(r["Weekly Off"] ?? r.WO ?? 0);
        const workDays = num(r["Work Days"] ?? r.Work_Days ?? Math.max(0, payableDays - weeklyOff));
        const ctcMonthly = num(r["CTC Monthly"] ?? r.ctcMonthly ?? (wagesFor + ctcAddonTotalForGross(wagesFor, rules)));
        return `<tr>
          <td>${r["Sr No"] ?? (idx + 1)}</td>
          <td>${r.Name || r.Employee_Name || r.empName || "-"}</td>
          <td>${r.Month || monthKey(item.year, item.month)}</td>
          <td>${r.Year ?? item.year ?? "-"}</td>
          <td class="text-end">${money(payableDays)}</td>
          <td class="text-end">${money(weeklyOff)}</td>
          <td class="text-end">${money(workDays)}</td>
          <td class="text-end">${money(ctcMonthly)}</td>
          ${rowAddons}
          <td class="text-end">${money(wagesFor)}</td>
          <td class="text-end">- ${money(lopDeduction)}</td>
          <td class="text-end">${money(earnedWages)}</td>
          <td class="text-end">${money(otHours)}</td>
          <td class="text-end">${money(otAmount)}</td>
          <td class="text-end">${money(incentive)}</td>
          <td class="text-end">${money(totalEarnings)}</td>
          ${rowComp}
          <td class="text-end">- ${money(r.PF ?? r.PF_EE ?? r.pfEE)}</td>
          <td class="text-end">- ${money(r.PF_ER ?? r.pfER)}</td>
          <td class="text-end">- ${money(r.ESI_EE ?? r.ESI ?? r.esiEE)}</td>
          <td class="text-end">- ${money(r.ESI_ER ?? r.esiER)}</td>
          ${includePt ? `<td class="text-end">- ${money(r.PT ?? r.pt)}</td>` : ""}
          ${includeLwf ? `<td class="text-end">- ${money(r.LWF_EE ?? r.LWF ?? r.lwf)}</td>` : ""}
          ${includeLwf ? `<td class="text-end">- ${money(r.LWF_ER ?? r.lwfER)}</td>` : ""}
          <td class="text-end">- ${money(r["Advance Salary"] ?? r.Advance_Salary ?? r.advanceSalaryDeduction)}</td>
          <td class="text-end">- ${money(r["Loan EMI"] ?? r.Loan_EMI ?? r.loanDeduction)}</td>
          ${deductionCols.map((n) => `<td class="text-end">- ${money(r[n] ?? 0)}</td>`).join("")}
          <td class="text-end">- ${money(r["Total Deductions"] ?? r.Total_Deductions)}</td>
          <td class="text-end">${money(r["Net Wages Paid"] ?? r.Net_Pay)}</td>
        </tr>`;
      }).join("");
      if($("salaryPreviewNote")) $("salaryPreviewNote").textContent = "Preview ready. It is also saved in list below.";
      if($("previewMonthBadge")) $("previewMonthBadge").textContent = `Month: ${monthKey(item.year, item.month)}`;
      if($("previewRowsBadge")) $("previewRowsBadge").textContent = String(meta.rowCount);
      if($("btnDownloadPreview")){
        $("btnDownloadPreview").disabled = meta.rowCount === 0;
        $("btnDownloadPreview").onclick = () => downloadSheet(item.id);
      }
    }
    function renderSheetTable(){
      if(!$("filesTbody")) return;
      const q = ($("searchInput")?.value || "").toLowerCase().trim();
      const fm = $("filterMonth")?.value || "";
      const fs = $("filterStatus")?.value || "";
      $("sheetListCard")?.classList.toggle("filter-highlight", Boolean(q || fm || fs));
      const filtered = files.filter(x => {
        const searchable = `${x.fileName} ${x.month} ${monthNames[x.month] || ""} ${x.year} ${x.status} ${x.createdOn}`.toLowerCase();
        if(q && !searchable.includes(q)) return false;
        if(fm && String(x.month) !== String(fm)) return false;
        if(fs && String(x.status) !== String(fs)) return false;
        return true;
      });
      $("filesTbody").innerHTML = filtered.map((x, idx) => {
        const meta = calcSheetTotals(x);
        return `<tr>
          <td>${idx + 1}</td>
          <td>${monthKey(x.year, x.month)}</td>
          <td>${x.createdOn}</td>
          <td>${x.year}</td>
          <td class="text-end">${meta.rowCount}</td>
          <td class="text-end">Rs ${money(meta.totalPfWage)}</td>
          <td class="text-end">Rs ${money(meta.totalPfEe)}</td>
          <td class="text-end">Rs ${money(meta.totalPfEr)}</td>
          <td class="text-center">
            <div class="btn-group">
              <button class="btn btn-outline-primary btn-sm" title="View" aria-label="View" onclick="viewSheet('${x.id}')"><i class="bi bi-eye"></i></button>
              <button class="btn btn-outline-secondary btn-sm" title="Download" aria-label="Download" onclick="downloadSheet('${x.id}')"><i class="bi bi-file-earmark-excel"></i></button>
              <button class="btn btn-outline-danger btn-sm" title="Delete" aria-label="Delete" onclick="deleteSheet('${x.id}')"><i class="bi bi-trash"></i></button>
            </div>
          </td>
        </tr>`;
      }).join("");
      $("emptyState")?.classList.toggle("d-none", filtered.length !== 0);
      if($("resultCount")) $("resultCount").textContent = String(filtered.length);
      const selected = activePreviewSheetId != null
        ? files.find((x) => String(x.id) === String(activePreviewSheetId))
        : null;
      renderSalaryPreview(selected || files[0] || null);
    }
    function scrollToSalaryPreview(){
      const body = $("salaryPreviewBody");
      if(!body || !body.closest) return;
      const wrap = body.closest(".glass");
      if(wrap && wrap.scrollIntoView){
        wrap.scrollIntoView({ behavior: "smooth", block: "start" });
      }
    }
    window.downloadSheet = async function(id){
      const item = files.find((x) => String(x.id) === String(id));
      if(!item) return;
      if((!item.rows || !item.rows.length) && !sheetById[String(item.id)]){
        try {
          const full = await getPayrollSheetApi(item.id);
          if(full){
            sheetById[String(item.id)] = full;
            item.rows = Array.isArray(full.rows) ? full.rows : [];
            item.month = Number(full.month || item.month || 0);
            item.year = Number(full.year || item.year || 0);
          }
        } catch(_e){}
      }
      const sourceRows = (item.rows && item.rows.length)
        ? item.rows
        : (sheetById[String(item.id)]?.rows || toSheetRows(window.__lastRows || [], item.year, item.month));
      const rows = adjustRowsByStatutoryFlags(sourceRows);
      if((item.format || "").toLowerCase() === "csv"){
        downloadAsCsv(rows, item.fileName);
        return;
      }
      const profile = await loadProfileForExport();
      downloadAsXlsx(rows, item.fileName.replace(/\.pdf$/i, ".xlsx"), { month: item.month, year: item.year, profile });
    };
    window.viewSheet = async function(id){
      const item = files.find((x) => String(x.id) === String(id));
      if(!item) return;
      if((!item.rows || !item.rows.length) && !sheetById[String(item.id)]){
        try {
          const full = await getPayrollSheetApi(item.id);
          if(full){
            sheetById[String(item.id)] = full;
            item.rows = Array.isArray(full.rows) ? full.rows : [];
            item.month = Number(full.month || item.month || 0);
            item.year = Number(full.year || item.year || 0);
          }
        } catch(_e){}
      }
      activePreviewSheetId = id;
      renderSalaryPreview(item);
      scrollToSalaryPreview();
    };
    window.deleteSheet = async function(id){
      const idx = files.findIndex((x) => String(x.id) === String(id));
      if(idx < 0) return;
      if(!confirm("Delete this generated salary sheet?")) return;
      try {
        await deletePayrollSheetApi(id);
      } catch(_e){
        alert("Unable to delete from server.");
        return;
      }
      files = files.filter((x) => String(x.id) !== String(id));
      delete sheetById[String(id)];
      saveFiles();
      renderSheetStats();
      renderSheetTable();
    };
    function renderNotifs(listEl, emptyEl, dotEl){
      const unreadCount = notifications.filter(n => n.unread).length;
      if(dotEl) dotEl.style.display = unreadCount > 0 ? "block" : "none";
      if(!listEl) return;
      if(!notifications.length){ if(emptyEl) emptyEl.style.display = "block"; listEl.innerHTML = ""; return; }
      if(emptyEl) emptyEl.style.display = "none";
      listEl.innerHTML = notifications.slice(0, 7).map(n => `<button type="button" class="list-group-item list-group-item-action d-flex gap-2 py-3"><div style="width:10px;">${n.unread ? '<span class="badge rounded-pill text-bg-primary">&nbsp;</span>' : ''}</div><div class="flex-grow-1"><div class="d-flex justify-content-between"><div class="fw-semibold">${n.title}</div><div class="text-muted small">${n.time}</div></div><div class="text-muted small">${n.desc}</div></div></button>`).join("");
    }
    function syncNotifUI(){ renderNotifs($("notifListDesktop"), $("notifEmptyDesktop"), $("notifDotDesktop")); }

    function exportCSV(rows){
      const y = parseInt($("yearSel").value,10), m = parseInt($("monthSel").value,10);
      const includePt = !!rules.ptEnabled;
      const includeLwf = !!rules.lwfEnabled;
      const componentCols = getComponentColsForRules(rules);
      const header = [
        "Emp_ID","Employee_Name","Department","Designation","Year","Month","Days_in_Month","Paid_Days","Weekly_Off","Work_Days","LOP_Days","CL","SL","EL",
        "Gross_Month","Earned_Gross","OT_Hours","OT_Amount","Incentive","Total_Earnings", ...componentCols, "PF_EE","PF_ER","ESI_EE","ESI_ER",
        ...(includePt ? ["PT"] : []),
        ...(includeLwf ? ["LWF_EE","LWF_ER"] : []),
          "Advance_Salary","Loan_EMI",
          "Total_Deductions","Net_Payable"
      ];
      const data = rows.map(r => {
        const parts = componentMapFromRow(r, rules);
        const weeklyOff = num(r.wo ?? r["Weekly Off"] ?? r.WO ?? 0);
        const workDays = num(r.workDays ?? r["Work Days"] ?? r.Work_Days ?? Math.max(0, num(r.paidDays ?? 0) - weeklyOff));
        return [
          r.empId, r.empName, r.dept, r.desig, y, m, r.dim, r.paidDays, weeklyOff, workDays, r.lopDays, r.cl, r.sl, r.el,
          r.gross, r.earnedGross, num(r.otHours ?? 0), num(r.otAmount ?? 0), num(r.incentiveAmount ?? 0), num(r.totalEarnings ?? (num(r.earnedGross) + num(r.otAmount) + num(r.incentiveAmount))), ...componentCols.map((c) => parts[c] ?? 0), r.pfEE, r.pfER, r.esiEE, r.esiER,
          ...(includePt ? [r.pt] : []),
          ...(includeLwf ? [r.lwf, r.lwfER] : []),
          num(r.advanceSalaryDeduction ?? r["Advance Salary"] ?? 0),
          num(r.loanDeduction ?? r["Loan EMI"] ?? 0),
          r.totalDed, r.net
        ];
      });
      const csv = [header, ...data].map(row => row.map(v => `"${String(v??"").replaceAll('"','""')}"`).join(",")).join("\n");
      const blob = new Blob([csv], {type:"text/csv;charset=utf-8;"}); const url = URL.createObjectURL(blob); const a = document.createElement("a"); a.href = url; a.download = `payroll_${monthKey(y,m)}.csv`; document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
    }

    const empModal = new bootstrap.Modal(document.getElementById("empModal"));
    let modalEmpId = null;
    window.openEmpModal = (empId) => { modalEmpId = empId; const emp = empById.get(empId.toUpperCase()); const o = loadOverrides()[empId] || {}; $("mEmpName").textContent = emp?.empName || empId; $("mEmpId").textContent = empId; $("mGross").value = o.gross ?? ""; $("mCTC").value = o.ctc ?? ""; $("mPfAppl").checked = (o.pfAppl ?? true) === true; $("mEsiAppl").checked = (o.esiAppl ?? true) === true; $("mPtAppl").checked = (o.ptAppl ?? true) === true; $("mLwfAppl").checked = (o.lwfAppl ?? true) === true; empModal.show(); };
    $("btnModalSave").addEventListener("click", () => { if(!modalEmpId) return; const all = loadOverrides(); all[modalEmpId] = { gross: $("mGross").value ? Number($("mGross").value) : null, ctc: $("mCTC").value ? Number($("mCTC").value) : null, pfAppl: $("mPfAppl").checked, esiAppl: $("mEsiAppl").checked, ptAppl: $("mPtAppl").checked, lwfAppl: $("mLwfAppl").checked }; saveOverrides(all); empModal.hide(); render(); });
    $("btnModalReset").addEventListener("click", () => { if(!modalEmpId) return; const all = loadOverrides(); delete all[modalEmpId]; saveOverrides(all); empModal.hide(); render(); });


    function recalc(){ render(); if ($("lastAction")) $("lastAction").textContent = new Date().toLocaleString(); }
    $("btnExport")?.addEventListener("click", () => exportCSV(window.__lastRows || []));
    $("monthSel").addEventListener("change", recalc);
    $("yearSel").addEventListener("change", recalc);
    ["searchInput","filterMonth","filterStatus"].forEach(id => {
      if($(id)){ $(id).addEventListener("input", renderSheetTable); $(id).addEventListener("change", renderSheetTable); }
    });
    $("clearFilters")?.addEventListener("click", () => { if($("searchInput")) $("searchInput").value = ""; if($("filterMonth")) $("filterMonth").value = ""; if($("filterStatus")) $("filterStatus").value = ""; renderSheetTable(); });
    $("btnRefreshSheets")?.addEventListener("click", async () => {
      try {
        files = await listPayrollSheetsApi();
        saveFiles();
      } catch(_e){
        files = safeParse(localStorage.getItem(KEY_SALARY_FILES)) || [];
      }
      renderSheetStats();
      renderSheetTable();
    });
    $("btnClearSheetHistory")?.addEventListener("click", async () => {
      if(!confirm("Clear all generated salary sheet history?")) return;
      try {
        await clearPayrollSheetsApi();
      } catch(_e){
        alert("Unable to clear history on server.");
        return;
      }
      files = [];
      sheetById = {};
      saveFiles();
      renderSheetStats();
      renderSheetTable();
    });
    $("markAllReadBtnDesktop")?.addEventListener("click", () => { notifications = notifications.map(n => ({...n, unread:false})); syncNotifUI(); });
    async function generateSheet(month, year, format){
      const doneProcessing = window.HRCommon?.setProcessingState?.($("btnGenerateSheet"), {
        busyText: "Generating...",
        message: "Please wait, we are calculating and saving the salary sheet."
      });
      const fileName = `salary_sheet_${(monthNames[month] || "month").toLowerCase()}_${year}.${format}`;
      let calcRows = [];
      let generatedSheetId = null;
      try{
        const res = await apiFetch("/payroll/generate", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ month: Number(month), year: Number(year), absentMode: "LOP" })
        });
        if(!res.ok){
          let msg = "Unable to generate salary sheet.";
          try{
            const data = await res.json();
            if(data?.detail) msg = String(data.detail);
          } catch(_e){}
          throw new Error(msg);
        }
        const data = await res.json();
        generatedSheetId = String(data?.sheet?.id || "");
        const apiRows = data?.sheet?.rows || [];
        const currentRules = loadControl();
        calcRows = apiRows.map((r) => ({
          empId: String(r.empId || "").toUpperCase(),
          empName: String(r.empName || ""),
          dept: String(r.dept || ""),
          desig: String(r.desig || ""),
          dim: num(r.daysInMonth || 0),
          paidDays: num(r.paidDays || 0),
          wo: num(r.WO ?? 0),
          workDays: num(r.workDays ?? (num(r.paidDays ?? 0) - num(r.WO ?? 0))),
          lopDays: num(r.lopDays || 0),
          cl: num(r.CL || 0),
          sl: num(r.SL || 0),
          el: num(r.EL || 0),
          gross: round2(num(r.gross || 0)),
          earnedGross: round2(num(r.earnedGross || 0)),
          otHours: round2(num(r.otHours || 0)),
          otAmount: round2(num(r.otAmount || 0)),
          incentiveAmount: round2(num(r.incentiveAmount || 0)),
          totalEarnings: round2(num(r.totalEarnings ?? (num(r.earnedGross || 0) + num(r.otAmount || 0) + num(r.incentiveAmount || 0)))),
          pfEE: round2(num(r.pfEE || 0)),
          pfER: round2(num(r.pfER || 0)),
          esiEE: round2(num(r.esiEE || 0)),
          esiER: round2(num(r.esiER || 0)),
          pt: round2(num(r.pt || 0)),
          lwf: round2(num(r.lwf || 0)),
          otherDeductions: round2(num(r.otherDeductions || 0)),
          otherDeductionItems: Array.isArray(r.otherDeductionItems) ? r.otherDeductionItems.reduce((acc, it) => {
            const nm = String(it?.name || "").trim();
            if(!nm) return acc;
            acc[nm] = round2(Math.max(0, num(it?.amount || 0)));
            return acc;
          }, {}) : {},
          lwfER: round2(num(r.lwf || 0) > 0 ? num(currentRules.lwfEmployerAmt || 0) : 0),
          advanceSalaryDeduction: round2(num(r.advanceSalaryDeduction || 0)),
          loanDeduction: round2(num(r.loanDeduction || 0)),
          totalDed: round2(num(r.totalDeductions || 0)),
          net: round2(num(r.netPayable || 0)),
          flags: { esiEligible: !!r.esiEligible }
        }));
      } catch(e){
        doneProcessing?.(e?.message || "Unable to generate salary sheet.", true);
        alert(e?.message || "Unable to generate salary sheet.");
        return;
      }
      try {
        files = await listPayrollSheetsApi();
        const generatedRows = normalizeStoredSheet({ id: generatedSheetId, month, year, rows: toSheetRows(calcRows, year, month) })?.rows || [];
        if(generatedSheetId){
          sheetById[String(generatedSheetId)] = { id: generatedSheetId, month, year, rows: generatedRows };
        }
        const hit = generatedSheetId ? files.find((x) => String(x.id) === String(generatedSheetId)) : null;
        if(hit){
          hit.rows = generatedRows;
          hit.rowCount = generatedRows.length;
        }
        saveFiles();
      } catch(_e) {
        const rows = toSheetRows(calcRows, year, month);
        const normalized = normalizeStoredSheet({ id: Date.now(), fileName, month, year, createdOn: new Date().toLocaleString(), status: "success", format, rows });
        files.unshift(normalized);
        saveFiles();
      }
      notifications.unshift({ id: Date.now(), title: "Salary sheet generated", desc: `${fileName} generated successfully.`, time: "Just now", unread: true });
      window.__lastRows = calcRows;
      if(generatedSheetId) activePreviewSheetId = generatedSheetId;
      renderSheetStats();
      renderSheetTable();
      syncNotifUI();
      doneProcessing?.(`Salary sheet ready for ${String(month).padStart(2, "0")}/${year}.`, false);
      alert(`Salary sheet generated successfully.\n\nFile: ${fileName}`);
    }

    $("btnGenerateSheet")?.addEventListener("click", () => {
      const month = Number($("monthSel")?.value || 0);
      const year = Number($("yearSel")?.value || 0);
      if(!month || !year){
        alert("Please select Month and Year.");
        return;
      }
      generateSheet(month, year, "xlsx");
    });

    (async function init(){
      // Clear old saved payroll-calc data once and use new storage keys.
      localStorage.removeItem("hr_client_payroll_overrides_v1");
      localStorage.removeItem("hr_salary_sheet_files_v1");
      if ($("lastAction")) $("lastAction").textContent = new Date().toLocaleString();
      render();
      try {
        files = await listPayrollSheetsApi();
        saveFiles();
      } catch(_e){
        files = safeParse(localStorage.getItem(KEY_SALARY_FILES)) || [];
      }
      renderSheetStats();
      renderSheetTable();
      syncNotifUI();
      refreshControlLive();
      window.addEventListener("focus", refreshControlLive);
      window.addEventListener("storage", (e) => {
        if (!e || e.key === KEY_CONTROL || e.key === null) refreshControlLive();
      });
      document.addEventListener("visibilitychange", () => {
        if (document.visibilityState === "visible") refreshControlLive();
      });
      setInterval(refreshControlLive, 10000);
    })();

