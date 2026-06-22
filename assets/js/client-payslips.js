(() => {
  const $ = (id) => document.getElementById(id);
  const on = (id, ev, fn) => { const el = $(id); if (el) el.addEventListener(ev, fn); };
  const API_BASES = [
    "../api",
    "../backend/api.php?path=/api",
    "/api",
    "/backend/api.php?path=/api",
    "api",
    "backend/api.php?path=/api"
  ];
  const API_PAYSLIPS = "/payslips";
  const API_PAYSLIP_GEN = "/payslips/generate";
  const API_CONTROL = "/control";
  const API_EMP = "/employees";
  const KEY_PAYSLIPS = "hr_client_payslips_v1";
  const KEY_CONTROL = "hr_client_control_v1";

  if ($("yr")) $("yr").textContent = new Date().getFullYear();

  function esc(v){
    return String(v ?? "").replace(/[&<>"']/g, (ch) => (
      {"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[ch]
    ));
  }

  const htmlEl = document.documentElement;
  function applyTheme(theme){
    htmlEl.setAttribute("data-bs-theme", theme);
    localStorage.setItem("hr_portal_theme", theme);
    const isDark = theme === "dark";
    if ($("themeIcon")) $("themeIcon").className = isDark ? "bi bi-sun" : "bi bi-moon";
    if ($("themeText")) if ($("themeText")) $("themeText").textContent = "";
  }
  applyTheme(localStorage.getItem("hr_portal_theme") || "light");

  on("themeToggle", "click", () => {
    const cur = htmlEl.getAttribute("data-bs-theme") || "light";
    applyTheme(cur === "dark" ? "light" : "dark");
  });

  function money(n){
    const v = Number(n || 0);
    return v.toLocaleString("en-IN", { minimumFractionDigits: 0, maximumFractionDigits: 0 });
  }
  function round2(n){ return Math.round(Number(n||0)); }
  function num(n){ const v = Number(n); return Number.isFinite(v) ? v : 0; }
  function safeParse(s){ try { return JSON.parse(s); } catch(_e){ return null; } }
  function normalizePayslipLabel(label){
    return String(label || "").replace(/\s*\((employee)\)\s*/i, "").trim();
  }
  function loadControlForEarnings(){
    const c = safeParse(localStorage.getItem(KEY_CONTROL)) || {};
    if(Array.isArray(c.ctcSplitRows) && c.ctcSplitRows.length){
      const rows = c.ctcSplitRows
        .map((r) => ({ label: String(r?.name || "").trim(), pct: num(r?.pct) }))
        .filter((r) => r.label && r.pct > 0);
      if(rows.length) return rows;
    }
    return {
      rows: [
        { label: "Basic", pct: num(c.ctcBasicPct ?? 40) },
        { label: "HRA", pct: num(c.ctcHraPct ?? 40) },
        { label: "Conveyance", pct: num(c.ctcConvPct ?? 5) },
        { label: "DA", pct: num(c.ctcDaPct ?? 0) },
        { label: "Educational Allowance", pct: num(c.ctcEduPct ?? 2) },
        { label: "Special Allowance", pct: num(c.ctcSpecialPct ?? 13) }
      ].filter((r) => r.pct > 0)
    }.rows;
  }
  function controlEarningsForPayslip(p){
    const d = p?.data || {};
    const savedEarnings = Array.isArray(d.earnings) ? d.earnings : [];
    if(savedEarnings.length){
      return savedEarnings;
    }
    const incentiveAmount = Number(d?.incentiveAmount || 0);
    const controlRows = loadControlForEarnings();
    const fromTotals = Number(d?.totals?.earnings || 0);
    const fromExisting = (Array.isArray(d.earnings) ? d.earnings : []).reduce((s, r) => s + Number(r.amount || 0), 0);
    const otAmount = Number(d?.otAmount || 0);
    const base = Math.max(0, (fromTotals > 0 ? fromTotals : fromExisting) - otAmount - incentiveAmount);
    if(base <= 0) return Array.isArray(d.earnings) ? d.earnings : [];
    const totalPct = controlRows.reduce((s, x) => s + num(x.pct), 0);
    if(totalPct <= 0) return Array.isArray(d.earnings) ? d.earnings : [];
    const rows = controlRows.map((x) => ({
      label: x.label,
      amount: round2((base * num(x.pct)) / totalPct)
    }));
    if(otAmount > 0){
      rows.push({
        label: `Overtime (${Number(d?.otHours || 0).toLocaleString("en-IN", { maximumFractionDigits: 2 })} hrs)`,
        amount: round2(otAmount)
      });
    }
    if(incentiveAmount > 0){
      rows.push({
        label: "Incentive",
        amount: round2(incentiveAmount)
      });
    }
    return rows;
  }
  const monthNames = {
    "01":"Jan","02":"Feb","03":"Mar","04":"Apr","05":"May","06":"Jun",
    "07":"Jul","08":"Aug","09":"Sep","10":"Oct","11":"Nov","12":"Dec"
  };
  function badge(status){
    if(status === "success") return `<span class="badge text-bg-success">Success</span>`;
    if(status === "failed") return `<span class="badge text-bg-danger">Failed</span>`;
    return `<span class="badge text-bg-warning">Processing</span>`;
  }

  function loadLocalPayslips(){
    try { return JSON.parse(localStorage.getItem(KEY_PAYSLIPS) || "[]"); }
    catch(_e){ return []; }
  }
  function saveLocalPayslips(rows){
    localStorage.setItem(KEY_PAYSLIPS, JSON.stringify(rows));
  }
  function purgeStaleFailedRows(){
    const rows = loadLocalPayslips();
    if(!rows.length) return;
    const kept = rows.filter((x) => {
      const isFailed = String(x.status || "").toLowerCase() === "failed";
      const isEmp001 = String(x.empId || "").toUpperCase() === "EMP001";
      const isMar2026 = Number(x.month || 0) === 3 && Number(x.year || 0) === 2026;
      return !(isFailed && isEmp001 && isMar2026);
    });
    if(kept.length !== rows.length) saveLocalPayslips(kept);
  }
  function localFailedRows(){
    return loadLocalPayslips().filter((x) => String(x.status || "").toLowerCase() === "failed");
  }
  function mergeWithLocalFailed(apiRows){
    const failed = localFailedRows();
    if(!failed.length) return Array.isArray(apiRows) ? apiRows : [];
    const merged = [...(Array.isArray(apiRows) ? apiRows : []), ...failed];
    merged.sort((a,b) => new Date(b.generatedOn || 0) - new Date(a.generatedOn || 0));
    return merged;
  }
  function empNameById(empId){
    const id = String(empId || "").toUpperCase();
    const found = empLookup.find((e) => String(e.id || "").toUpperCase() === id);
    return found ? found.name : "";
  }
  function makeFailedPayslipRow({ month, year, empId, format, reason }){
    const mm = String(month).padStart(2, "0");
    const yy = String(year);
    const mk = `${yy}-${mm}`;
    const id = `local-failed-${mk}-${empId}-${Date.now()}`;
    return {
      id,
      month: Number(month),
      year: Number(year),
      monthKey: mk,
      empId: String(empId || "").toUpperCase(),
      employeeName: empNameById(empId) || String(empId || "").toUpperCase(),
      generatedOn: new Date().toISOString(),
      status: "failed",
      format: String(format || "pdf").toLowerCase(),
      key: "",
      netPay: 0,
      error: String(reason || "Generation failed")
    };
  }

  async function apiFetch(path, options){
    const errors = [];
    for(const base of API_BASES){
      try{
        const res = await fetch(`${base}${path}`, options);
        const ctype = String(res.headers.get("content-type") || "").toLowerCase();
        const isJson = ctype.indexOf("application/json") >= 0;
        if((res.status === 404 || res.status === 405) && !isJson){
          errors.push(`${base}${path}:${res.status}`);
          continue;
        }
        if(res.ok && ctype && !isJson){
          errors.push(`${base}${path}:non-json-${res.status}`);
          continue;
        }
        return res;
      } catch(err){
        errors.push(String(err?.message || err));
      }
    }
    throw new Error(errors.join(" | ") || "API unavailable");
  }

  async function readErrorDetail(res, fallback){
    try{
      const data = await res.json();
      if(data?.detail) return String(data.detail);
    } catch(_e){}
    return fallback;
  }

  async function apiList(){
    const r = await apiFetch(API_PAYSLIPS, { cache: "no-store" });
    if(!r.ok) throw new Error(await readErrorDetail(r, "Unable to load payslip list."));
    return (await r.json()).rows || [];
  }
  async function apiGet(id){
    const r = await apiFetch(`${API_PAYSLIPS}/${id}`, { cache: "no-store" });
    if(!r.ok) throw new Error(await readErrorDetail(r, "Unable to load payslip details."));
    return (await r.json()).sheet;
  }
  async function apiGenerate(payload){
    const r = await apiFetch(API_PAYSLIP_GEN, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    if(!r.ok){
      throw new Error(await readErrorDetail(r, "Payslip generation failed."));
    }
    return (await r.json()).sheet;
  }
  async function apiEmployees(){
    const r = await apiFetch(`${API_EMP}?activeOnly=1`, { cache: "no-store" });
    if(!r.ok) throw new Error(await readErrorDetail(r, "Unable to load employees."));
    return (await r.json()).rows || [];
  }
  async function apiControl(){
    const r = await apiFetch(API_CONTROL, { cache: "no-store" });
    if(!r.ok) throw new Error(await readErrorDetail(r, "Unable to load control settings."));
    return await r.json();
  }
  async function apiDelete(id){
    const r = await apiFetch(`${API_PAYSLIPS}/${id}`, { method: "DELETE" });
    if(!r.ok) throw new Error(await readErrorDetail(r, "Unable to delete payslip."));
  }
  async function apiClearAll(){
    const r = await apiFetch(`${API_PAYSLIPS}/clear`, { method: "POST" });
    if(!r.ok) throw new Error(await readErrorDetail(r, "Unable to clear payslips."));
  }

  let notifications = [];
  function renderNotifTarget(dot, empty, list){
    if (!list || !empty || !dot) return;
    const unread = notifications.filter(n => n.unread).length;
    dot.style.display = unread > 0 ? "block" : "none";
    if(!notifications.length){
      empty.style.display = "block";
      list.innerHTML = "";
      return;
    }
    empty.style.display = "none";
    list.innerHTML = notifications.slice(0,7).map(n => `
      <button type="button" class="list-group-item list-group-item-action d-flex gap-2 py-3" onclick="openNotif(${n.id})">
        <div style="width:10px;">${n.unread ? '<span class="badge rounded-pill text-bg-primary">&nbsp;</span>' : ""}</div>
        <div class="flex-grow-1">
          <div class="d-flex justify-content-between">
            <div class="fw-semibold">${esc(n.title)}</div>
            <div class="text-muted small">${esc(n.time)}</div>
          </div>
          <div class="text-muted small">${esc(n.desc)}</div>
        </div>
      </button>
    `).join("");
  }
  function renderNotifs(){
    renderNotifTarget($("notifDot"), $("notifEmpty"), $("notifList"));
    renderNotifTarget($("notifDotDesktop"), $("notifEmptyDesktop"), $("notifListDesktop"));
    renderNotifTarget($("notifDotMobile"), $("notifEmptyMobile"), $("notifListMobile"));
  }
  window.openNotif = (id) => {
    const n = notifications.find(x => x.id === id);
    if(!n) return;
    n.unread = false;
    alert(`${n.title}\n\n${n.desc}`);
    renderNotifs();
  };
  on("markAllReadBtn", "click", () => {
    notifications = notifications.map(n => ({...n, unread:false}));
    renderNotifs();
  });
  on("markAllReadBtnDesktop", "click", () => {
    notifications = notifications.map(n => ({...n, unread:false}));
    renderNotifs();
  });
  on("markAllReadBtnMobile", "click", () => {
    notifications = notifications.map(n => ({...n, unread:false}));
    renderNotifs();
  });

  let payslips = [];
  let payslipCache = {};
  let empLookup = [];
  let empDetailsById = {};
  let empLookupLoaded = false;
  let empSelect = null;
  let offlineMode = false;
  let latestPreviewId = null;
  let currentPreviewPayslip = null;
  const tbody = $("filesTbody");
  const previewBodyRow = $("previewBodyRow");

  function setStorageMode(){
    const n = $("storageMode");
    if(!n) return;
    n.textContent = offlineMode ? "Browser localStorage (fallback)" : "SQLite API";
  }

  function toEmpId(val){
    const s = String(val || "").trim().toUpperCase();
    if(!s) return "";
    const m = s.match(/(EMP[0-9A-Z_-]+)/);
    return m ? m[1] : s;
  }
  async function loadEmpLookup(){
    if(empLookupLoaded) return;
    const extra = safeParse(localStorage.getItem("hr_emp_extra_v1")) || {};
    try{
      const rows = await apiEmployees();
      empLookup = (rows || []).map(r => ({
        id: String(r.id || "").toUpperCase(),
        name: String(r.name || r.empName || "").trim(),
        dept: String(r.dept || "").trim(),
        desig: String(r.desig || r.designation || "").trim(),
        company: String(r.company || "").trim()
      })).filter(x => x.id);
      empDetailsById = {};
      (rows || []).forEach((r) => {
        const id = String(r.id || "").toUpperCase();
        if(!id) return;
        const ex = extra[id] || {};
        empDetailsById[id] = {
          pfNo: String(r.pfNo || ex.pfNo || "").trim(),
          bankName: String(r.bankName || ex.bankName || "").trim(),
          bankAc: String(r.bankAc || ex.bankAc || "").trim()
        };
      });
    }catch(_e){
      const local = safeParse(localStorage.getItem("hr_client_employees_v1")) || [];
      empLookup = (local || []).map(r => ({
        id: String(r.empId || r.id || "").toUpperCase(),
        name: String(r.empName || r.name || "").trim(),
        dept: String(r.dept || "").trim(),
        desig: String(r.designation || r.desig || "").trim(),
        company: String(r.company || "").trim()
      })).filter(x => x.id);
      empDetailsById = {};
      (local || []).forEach((r) => {
        const id = String(r.empId || r.id || "").toUpperCase();
        if(!id) return;
        const ex = extra[id] || {};
        empDetailsById[id] = {
          pfNo: String(r.pfNo || ex.pfNo || "").trim(),
          bankName: String(r.bankName || ex.bankName || "").trim(),
          bankAc: String(r.bankAc || ex.bankAc || "").trim()
        };
      });
    }
    if ($("genEmpId")) {
      if (empSelect) empSelect.destroy();
      empSelect = new TomSelect("#genEmpId", {
        options: empLookup.map((e) => ({
          value: e.id,
          text: `${e.id} - ${e.name}`,
          empId: e.id,
          empName: e.name,
          dept: e.dept,
          company: e.company
        })),
        searchField: ["text", "empId", "empName", "dept", "company"],
        maxOptions: 500,
        placeholder: "Search by Emp ID or Name...",
        render: {
          option: (data, escape) => `<div><div class="fw-semibold">${escape(data.empName)}</div><div class="small text-muted-3"><span class="mono">${escape(data.empId)}</span><span> - ${escape(data.dept || "-")}</span></div></div>`,
          item: (data, escape) => `<div>${escape(data.empName)} <span class="small text-muted-3">(${escape(data.empId)})</span></div>`
        }
      });
    }
    empLookupLoaded = true;
  }
  function renderStats(){
    const now = new Date();
    const mk = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`;
    if ($("statTotal")) $("statTotal").textContent = payslips.length;
    if ($("statThisMonth")) $("statThisMonth").textContent = payslips.filter(x => x.monthKey === mk).length;
    if ($("statSuccess")) $("statSuccess").textContent = payslips.filter(x => x.status === "success").length;
    if ($("statFailed")) $("statFailed").textContent = payslips.filter(x => x.status === "failed").length;
  }

  function renderTable(){
    if (!tbody) return;

    const q  = ($("searchInput")?.value || "").toLowerCase().trim();
    const fm = $("filterMonth")?.value || "";
    const fy = $("filterYear")?.value || "";
    const fs = $("filterStatus")?.value || "";

    const filtered = payslips.filter(x => {
      const searchable = `${x.monthKey} ${x.empId} ${x.employeeName} ${x.status} ${x.generatedOn} ${x.format}`.toLowerCase();
      if(q && !searchable.includes(q)) return false;
      if(fm && String(x.month).padStart(2, "0") !== fm) return false;
      if(fy && String(x.year) !== fy) return false;
      if(fs && x.status !== fs) return false;
      return true;
    });

    tbody.innerHTML = filtered.map((x, idx) => `
      <tr>
        <td class="fw-semibold">${idx + 1}</td>
        <td>
          <div class="fw-semibold">Payslip_${esc(x.empId)}_${esc(x.monthKey)}.${esc(x.format || "html")}</div>
          <div class="small text-muted">Name: ${esc(x.employeeName || "")} | Month: <span class="mono">${esc(x.monthKey || "-")}</span></div>
        </td>
        <td>${monthNames[String(x.month).padStart(2, "0")] || String(x.month)} ${x.year}</td>
        <td class="fw-semibold mono">${esc(x.empId || "")}</td>
        <td>${x.generatedOn ? new Date(x.generatedOn).toLocaleString() : "-"}</td>
        <td>${badge(x.status)}</td>
        <td class="text-center">
          <div class="btn-group">
            <button class="btn btn-outline-primary btn-sm" title="View" aria-label="View" onclick="previewPayslip('${esc(x.id)}')"><i class="bi bi-eye"></i></button>
            <button class="btn btn-outline-danger btn-sm" title="Delete" aria-label="Delete" onclick="deletePayslip('${esc(x.id)}')"><i class="bi bi-trash"></i></button>
          </div>
        </td>
      </tr>
    `).join("");

    if ($("emptyState")) $("emptyState").classList.toggle("d-none", filtered.length !== 0);
    if ($("resultCount")) $("resultCount").textContent = `${filtered.length} record${filtered.length === 1 ? "" : "s"}`;
  }

  function setLatestPreview(sheet){
    if(!previewBodyRow) return;
    if(!sheet){
      previewBodyRow.innerHTML = "";
      if($("previewNote")) $("previewNote").textContent = "Generate payslip to preview.";
      if($("badgeMonth")) $("badgeMonth").textContent = "Month: -";
      if($("previewEmp")) $("previewEmp").textContent = "-";
      if($("btnViewPreview")) $("btnViewPreview").disabled = true;
      if($("btnDownloadPreview")) $("btnDownloadPreview").disabled = true;
      latestPreviewId = null;
      return;
    }
    latestPreviewId = String(sheet.id);
    const d = sheet.data || {};
    const t = d.totals || {};
    previewBodyRow.innerHTML = `<tr>
      <td>${esc(sheet.monthKey || "-")}</td>
      <td class="mono fw-semibold">${esc(sheet.empId || "-")}</td>
      <td>${esc(sheet.employeeName || d.employee?.name || "-")}</td>
      <td class="text-end">${money(t.earnings || 0)}</td>
      <td class="text-end">${money(t.deductions || 0)}</td>
      <td class="text-end fw-semibold">${money(t.netPay || 0)}</td>
      <td class="mono">${esc(d.key || sheet.key || "-")}</td>
    </tr>`;
    if($("previewNote")) $("previewNote").textContent = "Latest generated payslip preview.";
    if($("badgeMonth")) $("badgeMonth").textContent = `Month: ${esc(sheet.monthKey || "-")}`;
    if($("previewEmp")) $("previewEmp").textContent = esc(sheet.empId || "-");
    if($("btnViewPreview")) $("btnViewPreview").disabled = false;
    if($("btnDownloadPreview")) $("btnDownloadPreview").disabled = false;
  }

  async function loadPreviewFromLatestList(){
    if(!payslips.length){ setLatestPreview(null); return; }
    const id = String(payslips[0].id);
    try {
      const sheet = await getPayslip(id);
      setLatestPreview(sheet);
    } catch(_e){
      setLatestPreview(null);
    }
  }

  function payslipHtml(p){
    const d = p.data || {}, emp = d.employee || {}, co = d.company || {};
    const grossSalary = Number(d?.grossSalary ?? d?.gross ?? 0);
    const earningsTotal = Number(d?.totals?.earnings ?? 0);
    const otHours = Number(d?.otHours || 0);
    const otAmount = Number(d?.otAmount || 0);
    const lopDeduction = Number(d?.lopDeduction ?? Math.max(0, grossSalary - earningsTotal));
    const adjustedGrossSalary = Number(d?.adjustedGrossSalary ?? Math.max(0, grossSalary - lopDeduction));
    const detail = empDetailsById[String(p.empId || emp.empId || "").toUpperCase()] || {};
    const pfNo = String(emp.pfNo || detail.pfNo || "");
    const bankName = String(emp.bankName || detail.bankName || "");
    const bankAc = String(emp.bankAc || detail.bankAc || "");
    const resolvedEarnings = controlEarningsForPayslip(p);
    const earningsList = (resolvedEarnings || []).map((r) => ({
      label: normalizePayslipLabel(r?.label || ""),
      amount: Number(r?.amount || 0)
    }));
    const deductionsList = (d.deductions || [])
      .filter((r) => {
        const label = String(r?.label || "").trim().toLowerCase();
        const amount = Number(r?.amount || 0);
        if (label === "esi (employee)" || label === "professional tax") return amount > 0;
        return true;
      })
      .map((r) => ({ label: normalizePayslipLabel(r?.label || ""), amount: Number(r?.amount || 0) }));
    const maxRows = Math.max(earningsList.length, deductionsList.length);
    const ledgerRows = Array.from({ length: maxRows }, (_, i) => {
      const e = earningsList[i];
      const drow = deductionsList[i];
      return `<tr>
        <td>${e ? esc(e.label) : ""}</td>
        <td class="text-end">${e ? money(e.amount) : ""}</td>
        <td>${drow ? esc(drow.label) : ""}</td>
        <td class="text-end">${drow ? money(drow.amount) : ""}</td>
      </tr>`;
    }).join("");

    return `
      <div class="payslip-wrap">
        <div class="ps-topbar">PAYSLIP</div>
        <div class="ps-company">
          <div class="name">${esc(co.name)}</div>
          <div class="meta">${esc(co.address)}</div>
          <div class="meta">Contact: ${esc(co.contact)}</div>
          <div class="meta">Reg: ${esc(co.reg)} &nbsp; PAN: ${esc(co.pan)} &nbsp; TAN: ${esc(co.tan)} &nbsp; GSTIN: ${esc(co.gstin)}</div>
        </div>

        <table class="ps-grid ps-meta w-100">
          <tr>
            <th style="width:25%">Pay Period</th><td style="width:25%">${esc(p.monthKey)}</td>
            <th style="width:25%">Employee ID</th><td style="width:25%">${esc(p.empId)}</td>
          </tr>
          <tr>
            <th style="width:25%">LOP Days</th><td style="width:25%">${emp.lopDays || 0}</td>
            <th style="width:25%"></th><td style="width:25%"></td>
          </tr>
        </table>

        <table class="ps-grid ps-emp w-100">
          <tr><th style="width:25%">Employee Name</th><td style="width:25%">${esc(emp.name)}</td><th style="width:25%">UAN</th><td style="width:25%">${esc(emp.uan)}</td></tr>
          <tr><th>Designation</th><td>${esc(emp.designation)}</td><th>PF No</th><td>${esc(pfNo)}</td></tr>
          <tr><th>Department</th><td>${esc(emp.department)}</td><th>ESI No</th><td>${esc(emp.esiNo)}</td></tr>
          <tr><th>DOJ</th><td>${esc(emp.doj)}</td><th>Bank Name</th><td>${esc(bankName)}</td></tr>
          <tr><th>Payable Days</th><td>${emp.payableDays || 0}</td><th>Bank A/C</th><td>${esc(bankAc)}</td></tr>
          <tr><th>Gross Salary</th><td>${money(grossSalary)}</td><th></th><td></td></tr>
          ${otAmount > 0 ? `<tr><th>OT Hours</th><td>${money(otHours)}</td><th>OT Amount</th><td>${money(otAmount)}</td></tr>` : ""}
          ${lopDeduction > 0 ? `<tr><th>LOP Deduction</th><td>- ${money(lopDeduction)}</td><th></th><td></td></tr>` : ""}
          ${lopDeduction > 0 ? `<tr><th>Adjusted Gross Salary</th><td>${money(adjustedGrossSalary)}</td><th></th><td></td></tr>` : ""}
        </table>

        <table class="ps-grid ps-ledger w-100">
          <tr class="ps-sec-head"><td colspan="2">EARNINGS</td><td colspan="2">DEDUCTIONS</td></tr>
          ${ledgerRows}
          <tr class="ps-totals">
            <td>Total Earnings</td><td class="text-end">${money(d.totals?.earnings)}</td>
            <td>Total Deductions</td><td class="text-end">${money(d.totals?.deductions || 0)}</td>
          </tr>
        </table>

        <div class="ps-netbar"><div>Net Pay</div><div>${money(d.totals?.netPay)}</div></div>
        <div class="ps-note">This is a system-generated salary slip</div>
      </div>
    `;
  }

  function payslipPrintCss(){
    return `
      @page { size: A4 portrait; margin: 6mm; }
      html, body { margin: 0; padding: 0; background: #ffffff; color: #45454c; font-family: Arial, Helvetica, sans-serif; }
      .print-shell { width: 100%; max-width: 198mm; margin: 0 auto; box-sizing: border-box; }
      .payslip-wrap { background: #fff; color: #45454c; border: 1px solid rgba(69,69,76,.22); border-radius: 0; overflow: hidden; box-shadow: none; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
      .ps-topbar { background: #45454c; color: #fff; padding: 10px 14px; font-weight: 800; letter-spacing: .5px; text-align: center; border-bottom: 3px solid #d4d7c2; }
      .ps-company { text-align: center; padding: 14px 14px 12px; border-bottom: 1px solid rgba(69,69,76,.18); background: #f2f1fa; }
      .ps-company .name { font-weight: 900; color: #45454c; }
      .ps-company .meta { font-size: 11px; color: rgba(69,69,76,.74); }
      .ps-grid { width: 100%; border-collapse: collapse; border: 1px solid rgba(69,69,76,.26); table-layout: fixed; }
      .ps-grid td, .ps-grid th { padding: 6px 8px; border: 1px solid rgba(69,69,76,.22); vertical-align: top; font-size: 11px; word-break: break-word; overflow-wrap: anywhere; }
      .ps-grid th { background: #efeff8; font-weight: 800; color: #45454c; text-align: left; }
      .ps-meta th { background: #ecebdd; }
      .ps-emp tr:nth-child(even) td { background: #fafafe; }
      .ps-sec-head td { background: #45454c; color: #fff; font-weight: 900; letter-spacing: .2px; }
      .ps-totals td { background: #ecebdd; font-weight: 900; color: #45454c; }
      .ps-netbar { background: #45454c; color: #fff; padding: 10px 14px; font-weight: 900; display: flex; justify-content: space-between; }
      .ps-note { padding: 10px 14px 12px; text-align: center; font-size: 11px; color: rgba(69,69,76,.74); border-top: 1px solid rgba(69,69,76,.16); background: #fafafe; }
      .text-end { text-align: right; }
      .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }
    `;
  }

  function printPayslipPreview(){
    if(!currentPreviewPayslip){
      alert("Open a payslip preview first.");
      return;
    }
    const existingFrame = document.getElementById("payslipPrintFrame");
    if(existingFrame && existingFrame.parentNode){
      existingFrame.parentNode.removeChild(existingFrame);
    }
    const iframe = document.createElement("iframe");
    iframe.id = "payslipPrintFrame";
    iframe.setAttribute("aria-hidden", "true");
    iframe.style.position = "fixed";
    iframe.style.right = "0";
    iframe.style.bottom = "0";
    iframe.style.width = "0";
    iframe.style.height = "0";
    iframe.style.border = "0";
    iframe.style.opacity = "0";
    iframe.style.pointerEvents = "none";
    document.body.appendChild(iframe);
    const printDoc = iframe.contentWindow?.document;
    if(!printDoc){
      iframe.remove();
      alert("Unable to open print view.");
      return;
    }
    printDoc.open();
    printDoc.write(`<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Payslip ${esc(currentPreviewPayslip.empId)} ${esc(currentPreviewPayslip.monthKey)}</title>
  <style>${payslipPrintCss()}</style>
</head>
<body>
  <div class="print-shell">${payslipHtml(currentPreviewPayslip)}</div>
</body>
</html>`);
    printDoc.close();
    iframe.onload = function(){
      const printWin = iframe.contentWindow;
      if(!printWin){
        iframe.remove();
        alert("Unable to open print view.");
        return;
      }
      printWin.focus();
      window.setTimeout(function(){
        printWin.print();
        window.setTimeout(function(){
          iframe.remove();
        }, 400);
      }, 150);
    };
  }

  async function getPayslip(id){
    if(payslipCache[id]) return payslipCache[id];
    if(offlineMode){
      const row = payslips.find((x) => String(x.id) === String(id));
      if(row && row.data){ payslipCache[id] = row; return row; }
    }
    const sheet = await apiGet(id);
    payslipCache[id] = sheet;
    return sheet;
  }

  window.previewPayslip = async (id) => {
    let p = null;
    try { p = await getPayslip(id); } catch(_e) {}
    if(!p) return;
    currentPreviewPayslip = p;
    if ($("previewBody")) $("previewBody").innerHTML = payslipHtml(p);
    if ($("downloadFromPreviewBtn")) $("downloadFromPreviewBtn").onclick = () => window.downloadPayslip(id);
    if ($("printFromPreviewBtn")) $("printFromPreviewBtn").onclick = printPayslipPreview;
    bootstrap.Modal.getOrCreateInstance($("previewModal")).show();
  };

  function downloadTextFile(filename, content, mime="text/html"){
    const blob = new Blob([content], { type: mime + ";charset=utf-8" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  }

  window.downloadPayslip = async (id) => {
    let p = null;
    try { p = await getPayslip(id); } catch(_e) {}
    if(!p) return;
    if(typeof window.html2pdf === "undefined"){
      return alert("PDF library not loaded. Please refresh and try again.");
    }
    const tmp = document.createElement("div");
    tmp.style.background = "#ffffff";
    tmp.style.padding = "16px";
    tmp.style.width = "210mm";
    tmp.innerHTML = payslipHtml(p);
    document.body.appendChild(tmp);
    try {
      await window.html2pdf()
        .set({
          filename: `Payslip_${p.empId}_${p.monthKey}.pdf`,
          margin: [5, 5, 5, 5],
          image: { type: "jpeg", quality: 0.98 },
          html2canvas: { scale: 1.8, useCORS: true },
          pagebreak: { mode: ["css", "legacy"] },
          jsPDF: { unit: "mm", format: "a4", orientation: "portrait" }
        })
        .from(tmp)
        .save();
    } finally {
      tmp.remove();
    }
  };

  async function buildPayslipsExportCsv(){
    const header = ["Payslip File","Month","Employee ID","Employee Name","Generated On","Status","Format","Gross Salary","LOP Deduction","Adjusted Gross","Total Deductions","Net Pay"];
    const rows = [];
    for(const p of payslips){
      let detail = null;
      try { detail = await getPayslip(String(p.id)); } catch(_e) {}
      const d = detail?.data || p?.data || {};
      const grossSalary = Number(d?.grossSalary ?? d?.gross ?? 0);
      const earningsTotal = Number(d?.totals?.earnings ?? 0);
      const lopDeduction = Number(d?.lopDeduction ?? Math.max(0, grossSalary - earningsTotal));
      const adjustedGross = Number(d?.adjustedGrossSalary ?? Math.max(0, grossSalary - lopDeduction));
      const totalDeductions = Number(d?.totals?.deductions ?? 0);
      const netPay = Number(d?.totals?.netPay ?? p?.netPay ?? 0);
      rows.push([
        `Payslip_${p.empId}_${p.monthKey}.${p.format}`,
        p.monthKey,
        p.empId,
        p.employeeName,
        p.generatedOn,
        p.status,
        p.format,
        grossSalary,
        lopDeduction,
        adjustedGross,
        totalDeductions,
        netPay
      ]);
    }
    return [header, ...rows]
      .map(r => r.map(v => `"${String(v??"").replaceAll('"','""')}"`).join(","))
      .join("\n");
  }
  on("exportAllBtn", "click", async () => {
    const csv = await buildPayslipsExportCsv();
    downloadTextFile("payslips_export.csv", csv, "text/csv");
  });
  on("exportAllBtnMobile", "click", async () => {
    const csv = await buildPayslipsExportCsv();
    downloadTextFile("payslips_export.csv", csv, "text/csv");
  });

  ["searchInput","filterMonth","filterYear","filterStatus"].forEach(id => {
    on(id,"input", renderTable);
    on(id,"change", renderTable);
  });

  on("clearFilters","click", () => {
    if ($("searchInput")) $("searchInput").value = "";
    if ($("filterMonth")) $("filterMonth").value = "";
    if ($("filterYear")) $("filterYear").value = "";
    if ($("filterStatus")) $("filterStatus").value = "";
    renderTable();
  });

  async function generateSinglePayslipInline(){
    const month = $("monthSel")?.value || "";
    const year  = $("yearSel")?.value || "";
    const empId = toEmpId(($("genEmpId")?.value || ""));
    const outFormat = $("genFormat")?.value || "pdf";
    if(!month || !year){ alert("Select Month and Year."); return; }
    if(!empId){ alert("Select Name / EMP ID."); return; }
    const doneProcessing = window.HRCommon?.setProcessingState?.([$("btnGenerateTop"), $("btnGenerateTopHeader")], {
      busyText: "Generating...",
      message: "Please wait, we are preparing the payslip."
    });
    const mk = `${year}-${month}`;
    try {
      try {
        const latestControl = await apiControl();
        localStorage.setItem(KEY_CONTROL, JSON.stringify(latestControl || {}));
      } catch(_ignored) {}
      const sheet = await apiGenerate({ month: Number(month), year: Number(year), empId, format: outFormat });
      offlineMode = false;
      payslipCache[sheet.id] = sheet;
      payslips = mergeWithLocalFailed(await apiList());
      saveLocalPayslips(payslips);
    } catch(_e){
      const reason = String(_e?.message || "Generate failed");
      const failedRow = makeFailedPayslipRow({
        month,
        year,
        empId,
        format: outFormat,
        reason
      });
      payslips.unshift(failedRow);
      saveLocalPayslips(payslips);
      notifications.unshift({
        id: Date.now(),
        title: "Payslip failed",
        desc: `Payslip ${empId} (${mk}) failed: ${reason}`,
        time: "Just now",
        unread: true
      });
      renderStats();
      renderTable();
      renderNotifs();
      setStorageMode();
      doneProcessing?.("Payslip failed.", true);
      alert(`Payslip not generated: ${reason}`);
      return;
    }
    notifications.unshift({ id: Date.now(), title: "Payslip generated", desc: `Payslip ${empId} (${mk}) generated.`, time: "Just now", unread: true });
    renderStats();
    renderTable();
    renderNotifs();
    setStorageMode();
    await loadPreviewFromLatestList();
    doneProcessing?.(`Payslip ready for ${empId}.`, false);
  }
  on("btnGenerateTop", "click", () => { generateSinglePayslipInline(); });
  on("btnGenerateTopHeader", "click", () => { generateSinglePayslipInline(); });
  on("btnBulkTop", "click", () => {
    const m = $("monthSel")?.value || "";
    const y = $("yearSel")?.value || "";
    if(!m || !y){ alert("Select Month and Year first."); return; }
    if($("bulkMonth")) $("bulkMonth").value = m;
    if($("bulkYear")) $("bulkYear").value = y;
    bootstrap.Modal.getOrCreateInstance($("bulkGenerateModal")).show();
  });
  on("btnViewPreview", "click", () => { if(latestPreviewId) window.previewPayslip(latestPreviewId); });
  on("btnDownloadPreview", "click", () => { if(latestPreviewId) window.downloadPayslip(latestPreviewId); });

  window.deletePayslip = async (id) => {
    if(!confirm("Delete this payslip?")) return;
    try {
      if(!offlineMode && !String(id).startsWith("local-")) await apiDelete(id);
      payslips = payslips.filter((x) => String(x.id) !== String(id));
      delete payslipCache[id];
      saveLocalPayslips(payslips);
      notifications.unshift({ id: Date.now(), title: "Payslip deleted", desc: `Payslip ${id} removed.`, time: "Just now", unread: true });
      renderNotifs();
      renderStats();
      renderTable();
    } catch(_e){
      alert("Unable to delete payslip.");
    }
  };

  on("btnClearPayslipHistory", "click", async () => {
    if(!payslips.length){
      alert("No payslips to clear.");
      return;
    }
    if(!confirm("Clear all generated payslips?")) return;
    try {
      await apiClearAll();
      offlineMode = false;
    } catch(_e){
      if(!offlineMode){
        alert("Unable to clear payslips on server.");
        return;
      }
    }
    payslips = [];
    payslipCache = {};
    saveLocalPayslips([]);
    notifications.unshift({
      id: Date.now(),
      title: "Payslips cleared",
      desc: "All generated payslips were deleted.",
      time: "Just now",
      unread: true
    });
    renderNotifs();
    renderStats();
    renderTable();
    setLatestPreview(null);
    setStorageMode();
  });

  (() => {
    const form = $("bulkGenerateForm");
    const modalEl = $("bulkGenerateModal");
    const statusEl = $("bulkStatus");
    const submitBtn = $("bulkGenerateBtn");
    if(!form || !modalEl) return;
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    form.addEventListener("submit", async (ev) => {
      ev.preventDefault();
      ev.stopPropagation();
      if(!form.checkValidity()){
        form.classList.add("was-validated");
        return;
      }

      const month = $("bulkMonth").value;
      const year = $("bulkYear").value;
      const outFormat = $("bulkFormat").value || "pdf";
      const mk = `${year}-${month}`;

      let ok = 0, fail = 0;
      submitBtn.disabled = true;
      if(statusEl) statusEl.textContent = "Fetching employees...";

      try {
        const emps = await apiEmployees();
        if(!emps.length) throw new Error("No employees found");
        try {
          const latestControl = await apiControl();
          localStorage.setItem(KEY_CONTROL, JSON.stringify(latestControl || {}));
        } catch(_ignored) {}

        for(let i = 0; i < emps.length; i++){
          const empId = String(emps[i].id || "").toUpperCase();
          if(!empId) continue;
          if(statusEl) statusEl.textContent = `Generating ${i + 1}/${emps.length}: ${empId}`;
          try {
            const sheet = await apiGenerate({ month: Number(month), year: Number(year), empId, format: outFormat });
            offlineMode = false;
            payslipCache[sheet.id] = sheet;
            ok++;
          } catch(_e) {
            fail++;
            payslips.unshift(makeFailedPayslipRow({
              month,
              year,
              empId,
              format: outFormat,
              reason: _e?.message || "Generate failed"
            }));
          }
        }

        try {
          payslips = mergeWithLocalFailed(await apiList()).concat(
            payslips.filter((x) => String(x.status || "").toLowerCase() === "failed" && String(x.id || "").startsWith("local-failed-"))
          );
          // de-duplicate by id
          const seen = new Set();
          payslips = payslips.filter((x) => {
            const k = String(x.id || "");
            if(seen.has(k)) return false;
            seen.add(k);
            return true;
          });
          saveLocalPayslips(payslips);
        } catch(_e) {
          offlineMode = true;
          saveLocalPayslips(payslips);
        }

        notifications.unshift({
          id: Date.now(),
          title: "Bulk payslip generation",
          desc: `${ok} success, ${fail} failed for ${mk}.`,
          time: "Just now",
          unread: true
        });
        renderNotifs();
        renderStats();
        renderTable();
        setStorageMode();
        await loadPreviewFromLatestList();

        if(statusEl) statusEl.textContent = `Completed: ${ok} success, ${fail} failed.`;
        alert(`Bulk generation done.\nSuccess: ${ok}\nFailed: ${fail}`);
        modal.hide();
        form.reset();
        form.classList.remove("was-validated");
      } catch(err){
        if(statusEl) statusEl.textContent = `Error: ${err.message || err}`;
        alert(`Bulk generation failed: ${err.message || err}`);
      } finally {
        submitBtn.disabled = false;
      }
    });
  })();

  (async function init(){
    purgeStaleFailedRows();
    if ($("filterYear")) $("filterYear").value = "";
    await loadEmpLookup();
    on("genEmpId", "focus", () => { loadEmpLookup(); });
    try {
      payslips = mergeWithLocalFailed(await apiList());
      saveLocalPayslips(payslips);
      offlineMode = false;
    } catch(_e){
      payslips = loadLocalPayslips();
      offlineMode = true;
    }
    notifications = [{
      id: 101,
      title: offlineMode ? "Offline mode" : "Backend connected",
      desc: offlineMode ? "Using browser storage fallback." : "Payslips are stored in SQLite.",
      time: "Now",
      unread: true
    }];
    renderNotifs();
    renderStats();
    renderTable();
    setStorageMode();
    await loadPreviewFromLatestList();
  })();
})();

