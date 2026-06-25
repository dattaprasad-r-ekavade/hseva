// Footer year
    document.getElementById("yr").textContent = new Date().getFullYear();

    // Theme (default light)
    const htmlEl = document.documentElement;
    const themeToggle = document.getElementById("themeToggle");
    const themeIcon = document.getElementById("themeIcon");
    const themeText = document.getElementById("themeText");

    function applyTheme(theme){
      htmlEl.setAttribute("data-bs-theme", theme);
      localStorage.setItem("hr_portal_theme", theme);
      const isDark = theme === "dark";
      themeIcon.className = isDark ? "bi bi-sun" : "bi bi-moon";
      if (themeText) themeText.textContent = "";
    }
    applyTheme(localStorage.getItem("hr_portal_theme") || "light");
    themeToggle.addEventListener("click", () => {
      const current = htmlEl.getAttribute("data-bs-theme") || "light";
      applyTheme(current === "dark" ? "light" : "dark");
    });

    function el(id){ return document.getElementById(id); }

    // Fields map
    const F = {
      pfEmpPct: el("pfEmpPct"),
      esiEmpPct: el("esiEmpPct"),
      esiWageLimit: el("esiWageLimit"),
      ptMonthly: el("ptMonthly"),
      ptEnabled: el("ptEnabled"),
      ptMonthlyRow: el("ptMonthlyRow"),
      pfWageCapEnabled: el("pfWageCapEnabled"),
      pfWageCapAmount: el("pfWageCapAmount"),
      pfOnEsiPct: el("pfOnEsiPct"),
      lwfEnabled: el("lwfEnabled"),
      lwfEmpAmt: el("lwfEmpAmt"),
      lwfErAmt: el("lwfErAmt"),
      lwfMonth: el("lwfMonth"),
      bonusEnabled: el("bonusEnabled"),
      bonusMinimumWage: el("bonusMinimumWage"),
      bonusMultiplierMonths: el("bonusMultiplierMonths"),
      bonusPercent: el("bonusPercent"),
      lwfEmpRow: el("lwfEmpRow"),
      lwfErRow: el("lwfErRow"),
      lwfMonthRow: el("lwfMonthRow"),
      ctcRowsBody: el("ctcRowsBody"),
      ctcCompName: el("ctcCompName"),
      ctcCompPct: el("ctcCompPct"),
      btnCtcAdd: el("btnCtcAdd"),
      btnCtcCancelEdit: el("btnCtcCancelEdit"),
      ctcTotalPct: el("ctcTotalPct"),
      deductionRowsBody: el("deductionRowsBody"),
      deductionType: el("deductionType"),
      deductionName: el("deductionName"),
      deductionValue: el("deductionValue"),
      deductionValueSuffix: el("deductionValueSuffix"),
      btnDeductionAdd: el("btnDeductionAdd"),
      btnDeductionCancelEdit: el("btnDeductionCancelEdit"),
      deductionTotalAmt: el("deductionTotalAmt"),
      taxSlabRowsBody: el("taxSlabRowsBody"),
      taxIncomeLabel: el("taxIncomeLabel"),
      taxRatePct: el("taxRatePct"),
      btnTaxSlabAdd: el("btnTaxSlabAdd"),
      btnTaxSlabCancelEdit: el("btnTaxSlabCancelEdit"),
      companyName: el("companyName"),
      companyAddress: el("companyAddress"),
      companyRegNo: el("companyRegNo"),
      companyPAN: el("companyPAN"),
      companyTAN: el("companyTAN"),
      companyGSTIN: el("companyGSTIN"),
      companyContact: el("companyContact"),
      gratuityModeAfter5: el("gratuityModeAfter5"),
      gratuityModeMonthly: el("gratuityModeMonthly"),
      gratuityMinYears: el("gratuityMinYears"),
      gratuityMinYearsWrap: el("gratuityMinYearsWrap"),
    };

    const DEFAULTS = {
      pfEmpPct: 12.0,
      pfErPct: 13.0,
      esiEmpPct: 0.75,
      esiErPct: 3.25,
      esiWageLimit: 21000,
      ptMonthly: 200,
      ptEnabled: "Yes",
      pfWageCapEnabled: "Yes",
      pfWageCapAmount: 15000,
      pfOnEsiPct: 70.0,
      daPctBasic: 0,
      lwfEnabled: "Yes",
      lwfEmpAmt: 20,
      lwfErAmt: 40,
      lwfMonth: 0,
      bonusEnabled: "Yes",
      bonusMinimumWage: 0,
      bonusMultiplierMonths: 12,
      bonusPercent: 8.33,
      gratuityMode: "after_5yr",
      gratuityMinYears: 5,
      ctcBasicPct: 50.0,
      ctcHraPct: 10.0,
      ctcConvPct: 0.0,
      ctcDaPct: 30.0,
      ctcEduPct: 0.0,
      ctcSpecialPct: 0.0,
      incomeTaxSlabs: [
        { income: "Up to Rs 3L", taxPct: 0 },
        { income: "Rs 3L - Rs 6L", taxPct: 5 },
        { income: "Rs 6L - Rs 9L", taxPct: 10 },
        { income: "Rs 9L - Rs 12L", taxPct: 15 },
        { income: "Rs 12L - Rs 15L", taxPct: 20 },
        { income: "Above Rs 15L", taxPct: 30 }
      ],
      ctcAddonRows: [
        { code: "pfEmployerPct", name: "PF Employer %", type: "percent", value: 13.0 },
        { code: "esiEmployerPct", name: "ESI Employer %", type: "percent", value: 3.25 }
      ],
      otherDeductionRows: [],
      companyName: "",
      companyAddress: "",
      companyRegNo: "",
      companyPAN: "",
      companyTAN: "",
      companyGSTIN: "",
      companyContact: ""
    };

    // API + local fallback
    const KEY = "hr_client_control_v1";
    const API_BASES = [
      "api",
      "../api",
      "/api"
    ];
    let currentMode = "SQLite API";

    function safeParse(s){
      try { return JSON.parse(s); } catch(e){ return null; }
    }

    function setStorageMode(text){
      currentMode = text;
      el("storageMode").textContent = text;
    }

    function setLastSaved(v){
      el("lastSaved").textContent = v ? new Date(v).toLocaleString() : "-";
    }

    function num(v){ return isFinite(parseFloat(v)) ? parseFloat(v) : 0; }

    const DEFAULT_CTC_ROWS = [
      { key: "ctcBasicPct", name: "Basic", pct: 50.0 },
      { key: "ctcDaPct", name: "DA", pct: 30.0 },
      { key: "ctcHraPct", name: "HRA", pct: 10.0 }
    ];
    const CTC_KEY_ALIASES = {
      basic: "ctcBasicPct",
      hra: "ctcHraPct",
      conveyance: "ctcConvPct",
      da: "ctcDaPct",
      "educational allowance": "ctcEduPct",
      "education allowance": "ctcEduPct",
      edu: "ctcEduPct",
      "special allowance": "ctcSpecialPct",
      special: "ctcSpecialPct"
    };
    let ctcRows = [];
    let ctcEditIndex = -1;
    let deductionRows = [];
    let deductionEditIndex = -1;
    let taxSlabRows = [];
    let taxSlabEditIndex = -1;

    function normalizeCompName(name){
      return String(name || "").trim().replace(/\s+/g, " ");
    }

    function normalizeCompAlias(name){
      return normalizeCompName(name).toLowerCase();
    }
    function ctcOrderRank(row){
      const alias = normalizeCompAlias(row?.name || row?.key || "");
      if(alias === "basic" || row?.key === "ctcBasicPct") return 1;
      if(alias === "da" || row?.key === "ctcDaPct") return 2;
      if(alias === "hra" || row?.key === "ctcHraPct") return 3;
      if(alias === "conveyance" || alias === "conv" || row?.key === "ctcConvPct") return 4;
      if(alias === "educational allowance" || alias === "education allowance" || alias === "edu" || row?.key === "ctcEduPct") return 5;
      if(alias === "special allowance" || alias === "special" || row?.key === "ctcSpecialPct") return 6;
      return 99;
    }
    function sortCtcRowsForDisplay(rows){
      return [...(rows || [])].sort((a, b) => {
        const ra = ctcOrderRank(a);
        const rb = ctcOrderRank(b);
        if(ra !== rb) return ra - rb;
        return normalizeCompName(a?.name).localeCompare(normalizeCompName(b?.name));
      });
    }

    function normalizeIncomeLabel(text){
      return String(text || "").trim().replace(/\s+/g, " ");
    }

    function normalizeDeductionName(text){
      return String(text || "").trim().replace(/\s+/g, " ");
    }
    function normalizeAddonType(type){
      return String(type || "").trim().toLowerCase() === "amount" ? "amount" : "percent";
    }
    function addonTypeLabel(type){
      return normalizeAddonType(type) === "amount" ? "Rs" : "%";
    }
    function addonValueFormat(row){
      return normalizeAddonType(row?.type) === "amount"
        ? `Rs ${num(row?.value).toFixed(2)}`
        : `${num(row?.value).toFixed(2)}%`;
    }
    function addonCodeFromName(name){
      const alias = normalizeDeductionName(name).toLowerCase();
      if(alias === "pf employer %") return "pfEmployerPct";
      if(alias === "esi employer %") return "esiEmployerPct";
      return "";
    }
    function defaultAddonRows(){
      return (DEFAULTS.ctcAddonRows || []).map((r) => ({
        code: String(r.code || ""),
        name: normalizeDeductionName(r.name),
        type: normalizeAddonType(r.type),
        value: num(r.value)
      }));
    }
    function upsertAddonRow(list, row){
      const next = [...list];
      const key = String(row.code || "").trim();
      if(key){
        const idx = next.findIndex((x) => String(x.code || "").trim() === key);
        if(idx >= 0){
          next[idx] = { ...next[idx], ...row };
          return next;
        }
      }
      next.push(row);
      return next;
    }

    function toCtcRows(data){
      if(Array.isArray(data?.ctcSplitRows) && data.ctcSplitRows.length){
        return data.ctcSplitRows.map(r => ({
          key: r.key || CTC_KEY_ALIASES[normalizeCompAlias(r.name)] || "",
          name: normalizeCompName(r.name || "Component"),
          pct: num(r.pct)
        }));
      }
      return DEFAULT_CTC_ROWS.map(r => ({
        key: r.key,
        name: r.name,
        pct: num(data?.[r.key] ?? r.pct)
      }));
    }

    function toTaxSlabRows(data){
      const list = Array.isArray(data?.incomeTaxSlabs) && data.incomeTaxSlabs.length
        ? data.incomeTaxSlabs
        : DEFAULTS.incomeTaxSlabs;
      return list.map((r) => ({
        income: normalizeIncomeLabel(r?.income),
        taxPct: num(r?.taxPct)
      })).filter((r) => r.income);
    }

    function toDeductionRows(data){
      if(Array.isArray(data?.ctcAddonRows)){
        return data.ctcAddonRows.map((r) => ({
          code: String(r?.code || addonCodeFromName(r?.name)).trim(),
          name: normalizeDeductionName(r?.name),
          type: normalizeAddonType(r?.type),
          value: num(r?.value ?? r?.amount)
        })).filter((r) => r.name);
      }
      let rows = defaultAddonRows();
      rows = upsertAddonRow(rows, {
        code: "pfEmployerPct",
        name: "PF Employer %",
        type: "percent",
        value: num(data?.pfErPct ?? DEFAULTS.pfErPct)
      });
      rows = upsertAddonRow(rows, {
        code: "esiEmployerPct",
        name: "ESI Employer %",
        type: "percent",
        value: num(data?.esiErPct ?? DEFAULTS.esiErPct)
      });
      const legacyRows = Array.isArray(data?.otherDeductionRows) ? data.otherDeductionRows : [];
      legacyRows.forEach((r) => {
        const name = normalizeDeductionName(r?.name);
        if(!name) return;
        rows = upsertAddonRow(rows, {
          code: "",
          name,
          type: "amount",
          value: num(r?.amount)
        });
      });
      return rows.filter((r) => r.name);
    }

    function resetTaxSlabEditor(){
      taxSlabEditIndex = -1;
      if(F.taxIncomeLabel) F.taxIncomeLabel.value = "";
      if(F.taxRatePct) F.taxRatePct.value = "";
      if(F.btnTaxSlabAdd) F.btnTaxSlabAdd.textContent = "Add";
      if(F.btnTaxSlabCancelEdit) F.btnTaxSlabCancelEdit.classList.add("d-none");
    }

    function resetCtcEditor(){
      ctcEditIndex = -1;
      F.ctcCompName.value = "";
      F.ctcCompPct.value = "";
      F.btnCtcAdd.textContent = "Add";
      F.btnCtcCancelEdit.classList.add("d-none");
    }

    function resetDeductionEditor(){
      deductionEditIndex = -1;
      if(F.deductionType) F.deductionType.value = "percent";
      if(F.deductionName) F.deductionName.value = "";
      if(F.deductionValue) F.deductionValue.value = "";
      if(F.btnDeductionAdd) F.btnDeductionAdd.textContent = "Add";
      if(F.btnDeductionCancelEdit) F.btnDeductionCancelEdit.classList.add("d-none");
      syncDeductionTypeUi();
    }

    function renderCtcRows(){
      ctcRows = sortCtcRowsForDisplay(ctcRows);
      F.ctcRowsBody.innerHTML = ctcRows.map((r, idx) => `
        <tr>
          <td class="fw-semibold">${r.name}</td>
          <td class="text-end">${num(r.pct).toFixed(2)}</td>
          <td class="text-end">
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-secondary" type="button" data-ctc-edit="${idx}" title="Edit"><i class="bi bi-pencil"></i></button>
              <button class="btn btn-outline-danger" type="button" data-ctc-delete="${idx}" title="Delete"><i class="bi bi-trash"></i></button>
            </div>
          </td>
        </tr>
      `).join("");
      computeCTC();
    }

    function computeCTC(){
      const total = ctcRows.reduce((sum, row) => sum + num(row.pct), 0);
      F.ctcTotalPct.textContent = total.toFixed(2) + "%";
      F.ctcTotalPct.className = "fw-semibold";
    }

    function renderDeductionRows(){
      if(!F.deductionRowsBody) return;
      deductionRows = [...deductionRows].sort((a, b) => {
        const ac = String(a?.code || "").trim() ? 0 : 1;
        const bc = String(b?.code || "").trim() ? 0 : 1;
        if(ac !== bc) return ac - bc;
        return normalizeDeductionName(a?.name).localeCompare(normalizeDeductionName(b?.name));
      });
      F.deductionRowsBody.innerHTML = deductionRows.map((r, idx) => `
        <tr>
          <td class="fw-semibold">${addonTypeLabel(r.type)}</td>
          <td class="fw-semibold">${r.name}</td>
          <td class="text-end">${addonValueFormat(r)}</td>
          <td class="text-end">
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-secondary" type="button" data-deduction-edit="${idx}" title="Edit"><i class="bi bi-pencil"></i></button>
              <button class="btn btn-outline-danger" type="button" data-deduction-delete="${idx}" title="Delete"><i class="bi bi-trash"></i></button>
            </div>
          </td>
        </tr>
      `).join("");
      computeDeductionTotal();
    }

    function computeDeductionTotal(){
      if(!F.deductionTotalAmt) return;
      const fixedTotal = deductionRows
        .filter((row) => normalizeAddonType(row.type) === "amount")
        .reduce((sum, row) => sum + num(row.value), 0);
      const pctRows = deductionRows.filter((row) => normalizeAddonType(row.type) === "percent").length;
      F.deductionTotalAmt.textContent = `${pctRows} % rows | Rs ${fixedTotal.toFixed(2)} fixed`;
      F.deductionTotalAmt.className = "fw-semibold";
    }
    function syncDeductionTypeUi(){
      if(!F.deductionType || !F.deductionValueSuffix) return;
      F.deductionValueSuffix.textContent = addonTypeLabel(F.deductionType.value);
    }

    function renderTaxSlabRows(){
      if(!F.taxSlabRowsBody) return;
      F.taxSlabRowsBody.innerHTML = taxSlabRows.map((r, idx) => `
        <tr>
          <td>${idx + 1}</td>
          <td class="fw-semibold">${r.income}</td>
          <td class="text-end">${num(r.taxPct).toFixed(2)}%</td>
          <td class="text-end">
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-secondary" type="button" data-tax-edit="${idx}" title="Edit"><i class="bi bi-pencil"></i></button>
              <button class="btn btn-outline-danger" type="button" data-tax-delete="${idx}" title="Delete"><i class="bi bi-trash"></i></button>
            </div>
          </td>
        </tr>
      `).join("");
    }

    function toggleCapFields(){
      const enabled = (F.pfWageCapEnabled.value || "").toLowerCase() === "yes";
      F.pfWageCapAmount.disabled = !enabled;
      F.pfWageCapAmount.closest(".input-group").style.opacity = enabled ? "1" : ".55";
    }

    function togglePtFields(){
      const enabled = (F.ptEnabled.value || "").toLowerCase() === "yes";
      if(F.ptMonthlyRow) F.ptMonthlyRow.style.display = enabled ? "" : "none";
      if(F.ptMonthly) F.ptMonthly.disabled = !enabled;
    }

    function toggleLwfFields(){
      const enabled = (F.lwfEnabled.value || "").toLowerCase() === "yes";
      if(F.lwfEmpRow) F.lwfEmpRow.style.display = enabled ? "" : "none";
      if(F.lwfErRow) F.lwfErRow.style.display = enabled ? "" : "none";
      if(F.lwfMonthRow) F.lwfMonthRow.style.display = enabled ? "" : "none";
      if(F.lwfEmpAmt) F.lwfEmpAmt.disabled = !enabled;
      if(F.lwfErAmt) F.lwfErAmt.disabled = !enabled;
      if(F.lwfMonth) F.lwfMonth.disabled = !enabled;
    }
    function currentGratuityMode(){
      return F.gratuityModeMonthly?.checked ? "monthly" : "after_5yr";
    }
    function applyGratuityMode(mode){
      const use = String(mode || DEFAULTS.gratuityMode).toLowerCase() === "monthly" ? "monthly" : "after_5yr";
      if(F.gratuityModeAfter5) F.gratuityModeAfter5.checked = use === "after_5yr";
      if(F.gratuityModeMonthly) F.gratuityModeMonthly.checked = use === "monthly";
      if(F.gratuityMinYearsWrap) F.gratuityMinYearsWrap.style.display = use === "after_5yr" ? "" : "none";
      if(F.gratuityMinYears) F.gratuityMinYears.disabled = use !== "after_5yr";
    }

    function applyPayload(data){
      const use = data || DEFAULTS;
      Object.keys(DEFAULTS).forEach(k => {
        if(!(k in F) || !F[k]) return;
        F[k].value = use[k] ?? DEFAULTS[k];
      });
      ctcRows = toCtcRows(use);
      renderCtcRows();
      resetCtcEditor();
      deductionRows = toDeductionRows(use);
      renderDeductionRows();
      resetDeductionEditor();
      taxSlabRows = toTaxSlabRows(use);
      renderTaxSlabRows();
      resetTaxSlabEditor();
      applyGratuityMode(use.gratuityMode);
      toggleCapFields();
      togglePtFields();
      toggleLwfFields();
      setLastSaved(use.__lastSaved || null);
    }

    function applyOnboardingPayload(){
      applyPayload(DEFAULTS);
      const numericStatutoryIds = [
        "pfEmpPct","esiEmpPct","esiWageLimit",
        "ptMonthly","pfWageCapAmount","pfOnEsiPct","lwfEmpAmt","lwfErAmt","lwfMonth"
      ];
      numericStatutoryIds.forEach((id) => {
        if(F[id]) F[id].value = "";
      });
      setLastSaved(null);
    }

    function hasConfiguredControl(data){
      if(!data || typeof data !== "object") return false;
      if(data.__configured === true) return true;
      const keys = Object.keys(data).filter((k) => !k.startsWith("__"));
      return keys.length > 0;
    }

    function validate(){
      const issues = [];
      [["PF Employee %","pfEmpPct"],["ESI Employee %","esiEmpPct"]]
        .forEach(([label,key]) => {
          const v = num(F[key].value);
          if(v < 0 || v > 100) issues.push(`${label} must be between 0 and 100`);
        });
      if(num(F.esiWageLimit.value) <= 0) issues.push("ESI Wage Limit must be greater than 0");
      if(num(F.pfOnEsiPct.value) < 0 || num(F.pfOnEsiPct.value) > 100) issues.push("PF Wage % When ESI Applicable must be between 0 and 100");
      const lm = num(F.lwfMonth.value);
      if(lm < 0 || lm > 12) issues.push("LWF Applicable Month must be 0 to 12");
      if(F.bonusEnabled && String(F.bonusEnabled.value || "").toLowerCase() === "yes"){
        if(num(F.bonusMinimumWage.value) < 0) issues.push("Bonus Minimum Wages cannot be negative");
        if(num(F.bonusMultiplierMonths.value) < 0) issues.push("Bonus Months cannot be negative");
        if(num(F.bonusPercent.value) < 0 || num(F.bonusPercent.value) > 100) issues.push("Bonus % must be between 0 and 100");
        [["Bonus Minimum Wages","bonusMinimumWage"],["Bonus Months","bonusMultiplierMonths"],["Bonus %","bonusPercent"]].forEach(([label,key]) => {
          if(F[key] && String(F[key].value || "").trim() === "") issues.push(`${label} is required`);
        });
      }
      [["PF Employee %","pfEmpPct"],["ESI Employee %","esiEmpPct"],["ESI Wage Limit","esiWageLimit"],["PT (Monthly)","ptMonthly"],["PF Wage Cap Amount","pfWageCapAmount"],["PF Wage % When ESI Applicable","pfOnEsiPct"]]
        .forEach(([label,key]) => {
          if(F[key] && String(F[key].value || "").trim() === "") issues.push(`${label} is required`);
        });
      if(F.lwfEnabled && String(F.lwfEnabled.value || "").toLowerCase() === "yes"){
        [["LWF Employee (Amount)","lwfEmpAmt"],["LWF Employer (Amount)","lwfErAmt"],["LWF Applicable Month","lwfMonth"]].forEach(([label,key]) => {
          if(F[key] && String(F[key].value || "").trim() === "") issues.push(`${label} is required`);
        });
      }
      if(F.taxSlabRowsBody){
        if(!taxSlabRows.length) issues.push("Add at least one Income Tax Slab row");
        taxSlabRows.forEach((r, idx) => {
          if(!normalizeIncomeLabel(r.income)) issues.push(`Income Tax Slab row ${idx + 1}: Income range is required`);
          if(num(r.taxPct) < 0 || num(r.taxPct) > 100) issues.push(`Income Tax Slab row ${idx + 1}: Tax % must be between 0 and 100`);
        });
      }
      if(F.deductionRowsBody){
        deductionRows.forEach((r, idx) => {
          if(!normalizeDeductionName(r.name)) issues.push(`CTC Add-on row ${idx + 1}: Name is required`);
          if(num(r.value) < 0) issues.push(`CTC Add-on row ${idx + 1}: Value cannot be negative`);
          if(normalizeAddonType(r.type) === "percent" && num(r.value) > 100) issues.push(`CTC Add-on row ${idx + 1}: % value must be between 0 and 100`);
        });
      }
      if(!F.gratuityModeAfter5?.checked && !F.gratuityModeMonthly?.checked){
        issues.push("Select one gratuity mode");
      }
      if(F.gratuityModeAfter5?.checked){
        if(F.gratuityMinYears && String(F.gratuityMinYears.value || "").trim() === "") issues.push("Minimum Service Years is required");
        if(num(F.gratuityMinYears?.value) < 0) issues.push("Minimum Service Years cannot be negative");
      }
      if(issues.length){
        alert("Please fix:\n\n- " + issues.join("\n- "));
        return false;
      }
      return true;
    }

    function getPayload(){
      const p = {};
      Object.keys(DEFAULTS).forEach(k => {
        if(!(k in F) || !F[k]) return;
        p[k] = F[k].type === "number" ? num(F[k].value) : (F[k].value ?? "").toString();
      });
      p.ctcBasicPct = 0;
      p.ctcHraPct = 0;
      p.ctcConvPct = 0;
      p.ctcDaPct = 0;
      p.ctcEduPct = 0;
      p.ctcSpecialPct = 0;
      p.ctcSplitRows = ctcRows.map(r => ({ key: r.key || "", name: r.name, pct: num(r.pct) }));
      ctcRows.forEach(r => {
        const key = r.key || CTC_KEY_ALIASES[normalizeCompAlias(r.name)];
        if(key && key in p) p[key] = num(r.pct);
      });
      p.incomeTaxSlabs = taxSlabRows.map((r) => ({
        income: normalizeIncomeLabel(r.income),
        taxPct: num(r.taxPct)
      })).filter((r) => r.income);
      p.ctcAddonRows = deductionRows.map((r) => ({
        code: String(r.code || "").trim(),
        name: normalizeDeductionName(r.name),
        type: normalizeAddonType(r.type),
        value: num(r.value)
      })).filter((r) => r.name);
      const pfEmployerRow = deductionRows.find((r) => String(r.code || "") === "pfEmployerPct") || deductionRows.find((r) => addonCodeFromName(r.name) === "pfEmployerPct");
      const esiEmployerRow = deductionRows.find((r) => String(r.code || "") === "esiEmployerPct") || deductionRows.find((r) => addonCodeFromName(r.name) === "esiEmployerPct");
      p.pfErPct = pfEmployerRow && normalizeAddonType(pfEmployerRow.type) === "percent" ? num(pfEmployerRow.value) : 0;
      p.esiErPct = esiEmployerRow && normalizeAddonType(esiEmployerRow.type) === "percent" ? num(esiEmployerRow.value) : 0;
      p.otherDeductionRows = [];
      p.daPctBasic = num(DEFAULTS.daPctBasic);
      p.gratuityMode = currentGratuityMode();
      p.gratuityMinYears = num(F.gratuityMinYears?.value || DEFAULTS.gratuityMinYears);
      return p;
    }

    async function apiFetch(path, options){
      const errors = [];
      for(const base of API_BASES){
        try{
          const res = await fetch(`${base}${path}`, options);
          if(res.status === 404 || res.status === 405){
            errors.push(`${base}${path}:${res.status}`);
            continue;
          }
          return res;
        } catch(err){
          errors.push(String(err?.message || err));
        }
      }
      throw new Error(errors.join(" | ") || "API unavailable");
    }

    async function fetchControlFromApi(){
      const res = await apiFetch("/control", { headers: { "Accept": "application/json" } });
      if(!res.ok) throw new Error("GET /api/control failed");
      return await res.json();
    }

    async function saveControlToApi(payload){
      const res = await apiFetch("/control", {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      if(!res.ok) throw new Error("PUT /api/control failed");
      return await res.json();
    }

    async function resetControlInApi(){
      const res = await apiFetch("/control/reset", { method: "POST" });
      if(!res.ok) throw new Error("POST /api/control/reset failed");
      return await res.json();
    }

    async function load(){
      try {
        const data = await fetchControlFromApi();
        if(hasConfiguredControl(data)){
          applyPayload(data);
          localStorage.setItem(KEY, JSON.stringify(data));
        } else {
          applyOnboardingPayload();
          localStorage.removeItem(KEY);
        }
        setStorageMode("SQLite API");
      } catch (_err) {
        const raw = localStorage.getItem(KEY);
        const data = raw ? safeParse(raw) : null;
        if(hasConfiguredControl(data)) applyPayload(data);
        else applyOnboardingPayload();
        setStorageMode("Browser localStorage (fallback)");
      }
    }

    async function save(){
      computeCTC();
      computeDeductionTotal();
      if(!validate()) return;
      const payload = getPayload();
      try {
        const saved = await saveControlToApi(payload);
        applyPayload(saved);
        localStorage.setItem(KEY, JSON.stringify(saved));
        localStorage.setItem("hr_control_updated_at", new Date().toISOString());
        setStorageMode("SQLite API");
        alert("Success: Control settings saved and applied project-wide.");
      } catch (_err) {
        const localPayload = { ...payload, __lastSaved: new Date().toISOString() };
        localStorage.setItem(KEY, JSON.stringify(localPayload));
        localStorage.setItem("hr_control_updated_at", new Date().toISOString());
        applyPayload(localPayload);
        setStorageMode("Browser localStorage (fallback)");
        alert("Saved locally only. Start PHP API server to apply in all pages.");
      }
    }

    function exportJson(){
      computeCTC();
      const payload = { ...getPayload(), __exportedAt: new Date().toISOString(), __storageMode: currentMode };
      const blob = new Blob([JSON.stringify(payload, null, 2)], {type:"application/json"});
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = "client-control.json";
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
    }

    F.btnCtcAdd.addEventListener("click", () => {
      const name = normalizeCompName(F.ctcCompName.value);
      const pct = num(F.ctcCompPct.value);
      if(!name) return alert("Enter component name.");
      if(pct < 0) return alert("Percentage cannot be negative.");
      const knownKey = CTC_KEY_ALIASES[normalizeCompAlias(name)] || "";
      if(ctcEditIndex >= 0){
        ctcRows[ctcEditIndex] = { ...ctcRows[ctcEditIndex], name, pct, key: ctcRows[ctcEditIndex].key || knownKey };
      } else {
        ctcRows.push({ key: knownKey, name, pct });
      }
      renderCtcRows();
      resetCtcEditor();
    });

    F.btnCtcCancelEdit.addEventListener("click", resetCtcEditor);

    F.ctcRowsBody.addEventListener("click", (event) => {
      const editBtn = event.target.closest("[data-ctc-edit]");
      if(editBtn){
        const idx = Number(editBtn.getAttribute("data-ctc-edit"));
        const row = ctcRows[idx];
        if(!row) return;
        ctcEditIndex = idx;
        F.ctcCompName.value = row.name;
        F.ctcCompPct.value = num(row.pct);
        F.btnCtcAdd.textContent = "Update";
        F.btnCtcCancelEdit.classList.remove("d-none");
        return;
      }
      const deleteBtn = event.target.closest("[data-ctc-delete]");
      if(deleteBtn){
        const idx = Number(deleteBtn.getAttribute("data-ctc-delete"));
        if(!Number.isInteger(idx) || idx < 0 || idx >= ctcRows.length) return;
        ctcRows.splice(idx, 1);
        renderCtcRows();
        if(ctcEditIndex === idx) resetCtcEditor();
      }
    });

    if(F.btnDeductionAdd){
      F.btnDeductionAdd.addEventListener("click", () => {
        const type = normalizeAddonType(F.deductionType?.value);
        const name = normalizeDeductionName(F.deductionName?.value);
        const value = num(F.deductionValue?.value);
        if(!name) return alert("Enter add-on name.");
        if(value < 0) return alert("Add-on value cannot be negative.");
        if(type === "percent" && value > 100) return alert("Percentage add-on must be between 0 and 100.");
        const code = deductionEditIndex >= 0
          ? String(deductionRows[deductionEditIndex]?.code || addonCodeFromName(name)).trim()
          : addonCodeFromName(name);
        if(deductionEditIndex >= 0){
          deductionRows[deductionEditIndex] = { ...deductionRows[deductionEditIndex], code, name, type, value };
        } else {
          deductionRows.push({ code, name, type, value });
        }
        renderDeductionRows();
        resetDeductionEditor();
      });
    }

    if(F.btnDeductionCancelEdit){
      F.btnDeductionCancelEdit.addEventListener("click", resetDeductionEditor);
    }

    if(F.deductionRowsBody){
      F.deductionRowsBody.addEventListener("click", (event) => {
        const editBtn = event.target.closest("[data-deduction-edit]");
        if(editBtn){
          const idx = Number(editBtn.getAttribute("data-deduction-edit"));
          const row = deductionRows[idx];
          if(!row) return;
          deductionEditIndex = idx;
          if(F.deductionType) F.deductionType.value = normalizeAddonType(row.type);
          if(F.deductionName) F.deductionName.value = row.name;
          if(F.deductionValue) F.deductionValue.value = num(row.value);
          if(F.btnDeductionAdd) F.btnDeductionAdd.textContent = "Update";
          if(F.btnDeductionCancelEdit) F.btnDeductionCancelEdit.classList.remove("d-none");
          syncDeductionTypeUi();
          return;
        }
        const deleteBtn = event.target.closest("[data-deduction-delete]");
        if(deleteBtn){
          const idx = Number(deleteBtn.getAttribute("data-deduction-delete"));
          if(!Number.isInteger(idx) || idx < 0 || idx >= deductionRows.length) return;
          deductionRows.splice(idx, 1);
          renderDeductionRows();
          if(deductionEditIndex === idx) resetDeductionEditor();
        }
      });
    }
    if(F.deductionType){
      F.deductionType.addEventListener("change", syncDeductionTypeUi);
    }

    if(F.btnTaxSlabAdd){
      F.btnTaxSlabAdd.addEventListener("click", () => {
        const income = normalizeIncomeLabel(F.taxIncomeLabel?.value);
        const taxPct = num(F.taxRatePct?.value);
        if(!income) return alert("Enter income range.");
        if(taxPct < 0 || taxPct > 100) return alert("Tax % must be between 0 and 100.");
        if(taxSlabEditIndex >= 0){
          taxSlabRows[taxSlabEditIndex] = { income, taxPct };
        } else {
          taxSlabRows.push({ income, taxPct });
        }
        renderTaxSlabRows();
        resetTaxSlabEditor();
      });
    }

    if(F.btnTaxSlabCancelEdit){
      F.btnTaxSlabCancelEdit.addEventListener("click", resetTaxSlabEditor);
    }

    if(F.taxSlabRowsBody){
      F.taxSlabRowsBody.addEventListener("click", (event) => {
        const editBtn = event.target.closest("[data-tax-edit]");
        if(editBtn){
          const idx = Number(editBtn.getAttribute("data-tax-edit"));
          const row = taxSlabRows[idx];
          if(!row) return;
          taxSlabEditIndex = idx;
          if(F.taxIncomeLabel) F.taxIncomeLabel.value = row.income;
          if(F.taxRatePct) F.taxRatePct.value = num(row.taxPct);
          if(F.btnTaxSlabAdd) F.btnTaxSlabAdd.textContent = "Update";
          if(F.btnTaxSlabCancelEdit) F.btnTaxSlabCancelEdit.classList.remove("d-none");
          return;
        }
        const deleteBtn = event.target.closest("[data-tax-delete]");
        if(deleteBtn){
          const idx = Number(deleteBtn.getAttribute("data-tax-delete"));
          if(!Number.isInteger(idx) || idx < 0 || idx >= taxSlabRows.length) return;
          taxSlabRows.splice(idx, 1);
          renderTaxSlabRows();
          if(taxSlabEditIndex === idx) resetTaxSlabEditor();
        }
      });
    }

    F.pfWageCapEnabled.addEventListener("change", toggleCapFields);
    F.ptEnabled.addEventListener("change", togglePtFields);
    F.lwfEnabled.addEventListener("change", toggleLwfFields);
    document.getElementById("btnSave")?.addEventListener("click", save);
    document.getElementById("btnSaveTop")?.addEventListener("click", save);
    document.getElementById("btnValidate").addEventListener("click", validate);
    document.getElementById("btnExportJson").addEventListener("click", exportJson);
    document.getElementById("btnResetDemo").addEventListener("click", async () => {
      try {
        const data = await resetControlInApi();
        applyPayload(data);
        localStorage.setItem(KEY, JSON.stringify(data));
        setStorageMode("SQLite API");
        alert("Reset to defaults from API.");
      } catch (_err) {
        localStorage.removeItem(KEY);
        applyPayload(DEFAULTS);
        setStorageMode("Browser localStorage (fallback)");
        alert("API unavailable. Reset to local defaults.");
      }
    });

    load();

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
      bootstrap.Tooltip.getOrCreateInstance(el);
    });
