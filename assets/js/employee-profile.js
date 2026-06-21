const KEY_CONTROL = "hr_client_control_v1";
    const KEY_EMP_EXTRA = "hr_emp_extra_v1";
    const KEY_EMP_LOCAL = "hr_client_employees_v1";
    const KEY_LEAVES = "hr_client_leaves_v1";
    const API_BASES = ["/api", "/backend/api.php?path=/api"];
    const $ = (id) => document.getElementById(id);
    const qp = new URLSearchParams(location.search);
    const empId = String(qp.get("empId") || "").toUpperCase();

    function safeParse(s){ try { return JSON.parse(s); } catch(_e){ return null; } }
    function inr(n){ return "Rs " + Number(n || 0).toLocaleString("en-IN"); }
    function num(v){ const n = Number(v); return Number.isNaN(n) ? 0 : n; }
    function normalizeStatus(value){
      const raw = String(value || "").trim().toLowerCase();
      if(raw === "approved") return "Approved";
      if(raw === "pending") return "Pending";
      if(raw === "not approved" || raw === "rejected" || raw === "not_approved" || raw === "not-approved") return "Not Approved";
      return "Pending";
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
    function toUi(r){ return { empId: String(r.id || "").toUpperCase(), empName: String(r.name || ""), doj: String(r.doj || ""), dept: String(r.dept || ""), designation: String(r.desig || ""), employmentType: String(r.type || "Full-time"), status: String(r.status || "Active"), baseCtc: Number(r.baseCtc || 0), pf: String(r.pf || ""), esi: String(r.esi || ""), uan: String(r.uan || ""), esiNo: String(r.esiNo || ""), mobile: String(r.mobile || ""), email: String(r.email || "") }; }

    async function apiFetch(path, opts = {}){
      for(const base of API_BASES){
        try{
          const res = await fetch(`${base}${path}`, { cache: "no-store", ...opts });
          if(res.status === 404 || res.status === 405) continue;
          return res;
        }catch(_e){}
      }
      throw new Error("API unavailable");
    }

    async function fetchControlLive(){
      try{
        const res = await apiFetch("/control");
        if(res.ok){
          const row = await res.json();
          localStorage.setItem(KEY_CONTROL, JSON.stringify(row || {}));
          return row || {};
        }
      }catch(_e){}
      return safeParse(localStorage.getItem(KEY_CONTROL)) || {};
    }

    async function fetchLeavesLive(){
      try{
        const res = await apiFetch("/leaves");
        if(res.ok){
          const data = await res.json();
          const rows = Array.isArray(data?.rows) ? data.rows : [];
          localStorage.setItem(KEY_LEAVES, JSON.stringify(rows));
          return rows;
        }
      }catch(_e){}
      const local = safeParse(localStorage.getItem(KEY_LEAVES)) || [];
      return Array.isArray(local) ? local : [];
    }
    async function fetchAdvanceData(){
      try{
        const [advRes, histRes] = await Promise.all([
          apiFetch("/advances"),
          apiFetch("/advances/history")
        ]);
        const advData = advRes.ok ? await advRes.json() : { rows: [] };
        const histData = histRes.ok ? await histRes.json() : { rows: [] };
        return {
          advances: Array.isArray(advData?.rows) ? advData.rows.filter((r) => String(r.empId || "").toUpperCase() === empId) : [],
          history: Array.isArray(histData?.rows) ? histData.rows.filter((r) => String(r.empId || "").toUpperCase() === empId) : []
        };
      }catch(_e){
        return { advances: [], history: [] };
      }
    }

    async function saveLeaveLive(payload){
      const res = await apiFetch("/leaves", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      if(!res.ok) throw new Error("POST /api/leaves failed");
      const data = await res.json();
      return data?.row || payload;
    }

    function applyTheme(theme){
      document.documentElement.setAttribute("data-bs-theme", theme);
      localStorage.setItem("hr_portal_theme", theme);
      $("themeIcon").className = theme === "dark" ? "bi bi-sun" : "bi bi-moon";
    }

    function calcLeaveStats(leaveEntries, extra){
      const alloc = {
        cl: num(extra?.leaveAlloc?.cl),
        sl: num(extra?.leaveAlloc?.sl),
        el: num(extra?.leaveAlloc?.el)
      };
      const used = { CL:0, SL:0, EL:0, LOP:0 };
      leaveEntries.forEach((x) => {
        if(String(x.empId || x.emp_id || "").toUpperCase() !== empId) return;
        if(String(x.status || "").toLowerCase() === "rejected") return;
        const t = String(x.leaveType || x.leave_type || x.type || "").toUpperCase();
        if(used[t] === undefined) return;
        used[t] += num(x.days);
      });
      return { alloc, used, clBal: Math.max(0, alloc.cl - used.CL), slBal: Math.max(0, alloc.sl - used.SL), elBal: Math.max(0, alloc.el - used.EL) };
    }

    function calcRangeDays(fromDate, toDate){
      if(!fromDate || !toDate) return 0;
      const d1 = new Date(`${fromDate}T00:00:00`);
      const d2 = new Date(`${toDate}T00:00:00`);
      if(Number.isNaN(d1.getTime()) || Number.isNaN(d2.getTime())) return 0;
      const diff = Math.floor((d2 - d1) / 86400000) + 1;
      return diff > 0 ? diff : 0;
    }

    function renderLeaveBlocks(leaveEntries, extra){
      const leave = calcLeaveStats(leaveEntries, extra);
      $("leaveSummary").innerHTML = [
        {label:"CL Balance", value:leave.clBal},
        {label:"SL Balance", value:leave.slBal},
        {label:"EL Balance", value:leave.elBal},
        {label:"Leaves Taken", value:num(leave.used.CL) + num(leave.used.SL) + num(leave.used.EL)},
        {label:"LOP Taken", value:leave.used.LOP}
      ].map(x => `<div class="col-12 col-sm-6 col-xl-3"><div class="metric-card h-100"><div class="metric-label">${x.label}</div><div class="metric-value">${x.value}</div></div></div>`).join("");

      const empLeaves = leaveEntries
        .filter((x) => String(x.empId || x.emp_id || "").toUpperCase() === empId)
        .sort((a, b) => String(b.date || b.fromDate || b.from || "").localeCompare(String(a.date || a.fromDate || a.from || "")));
      $("leaveDetailsBody").innerHTML = empLeaves.length ? empLeaves.map((x) => {
        const dateVal = x.date || x.fromDate || x.from || "-";
        const leaveType = String(x.leaveType || x.leave_type || x.type || "-").toUpperCase();
        const daysVal = num(x.days || 0);
        const statusVal = x.status || "-";
        const reasonVal = x.reason || x.note || "-";
        return `<tr>
          <td>${dateVal}</td>
          <td>${leaveType}</td>
          <td class="text-end">${daysVal}</td>
          <td>${statusVal}</td>
          <td>${reasonVal}</td>
        </tr>`;
      }).join("") : `<tr><td colspan="5" class="text-center text-muted-3 py-3">No leave records found.</td></tr>`;
    }
    function renderAdvanceBlocks(advanceData){
      const advances = Array.isArray(advanceData?.advances) ? advanceData.advances : [];
      const history = Array.isArray(advanceData?.history) ? advanceData.history : [];
      const totalAdvance = advances.reduce((sum, x) => sum + num(x.amount), 0);
      const remaining = advances.reduce((sum, x) => sum + num(x.remainingBalance), 0);
      const recovered = history.reduce((sum, x) => sum + num(x.deductedAmount), 0);
      const active = advances.filter((x) => String(x.status || "").toLowerCase() !== "closed" && num(x.remainingBalance) > 0).length;
      $("advanceSummary").innerHTML = [
        {label:"Active Advances", value:active},
        {label:"Total Advance", value:inr(totalAdvance)},
        {label:"Recovered", value:inr(recovered)},
        {label:"Outstanding", value:inr(remaining)}
      ].map(x => `<div class="col-12 col-sm-6 col-xl-3"><div class="metric-card h-100"><div class="metric-label">${x.label}</div><div class="metric-value">${x.value}</div></div></div>`).join("");
      $("advanceHistoryBody").innerHTML = history.length ? history.map((x) => `<tr>
          <td>${x.period || "-"}</td>
          <td class="text-end">${inr(x.scheduledAmount)}</td>
          <td class="text-end">${inr(x.deductedAmount)}</td>
          <td class="text-end">${inr(x.balanceAfter)}</td>
          <td>${x.status || "-"}</td>
        </tr>`).join("") : `<tr><td colspan="5" class="text-center text-muted-3 py-3">No advance deductions found.</td></tr>`;
    }

    function kv(label, value){ return `<div class="kv"><div class="text-muted-3">${label}</div><div class="fw-semibold">${value || "-"}</div></div>`; }
    function kvTitle(title){ return `<div class="section-title mt-2 mb-1">${title}</div>`; }
    function kvDeduction(label, amount){ return kv(label, `- ${inr(Math.max(0, Math.round(num(amount))))}`); }
    function kvEarning(label, amount){ return kv(label, `+ ${inr(Math.max(0, Math.round(num(amount))))}`); }
    function prettyComponentName(raw){
      const key = String(raw || "").trim();
      const map = {
        ctcBasicPct: "Basic",
        ctcHraPct: "HRA",
        ctcConvPct: "Conveyance",
        ctcDaPct: "DA",
        ctcEduPct: "Educational Allowance",
        ctcSpecialPct: "Special Allowance"
      };
      return map[key] || key;
    }
    function ctcRowsFromControl(control){
      const dynamic = Array.isArray(control?.ctcSplitRows) ? control.ctcSplitRows : [];
      const rows = dynamic
        .map((r) => ({
          name: prettyComponentName(r?.name || r?.key || ""),
          pct: num(r?.pct)
        }))
        .filter((r) => r.name && r.pct > 0);
      if (rows.length) return rows;
      return [
        { name: "Basic", pct: num(control?.ctcBasicPct ?? control?.basicPctOfCtc ?? 40) },
        { name: "HRA", pct: num(control?.ctcHraPct ?? control?.hraPctOfCtc ?? 40) },
        { name: "Conveyance", pct: num(control?.ctcConvPct ?? control?.conveyPctOfCtc ?? 5) },
        { name: "DA", pct: num(control?.ctcDaPct ?? 0) },
        { name: "Educational Allowance", pct: num(control?.ctcEduPct ?? control?.eduPctOfCtc ?? 2) },
        { name: "Special Allowance", pct: num(control?.ctcSpecialPct ?? control?.specialPctOfCtc ?? 13) }
      ].filter((r) => r.pct > 0);
    }
    function salaryHtml(employee, control, leaveEntries){
      const gross = num(employee.baseCtc);
      const ctcRows = ctcRowsFromControl(control);
      const componentRows = ctcRows.map((r) => kvEarning(r.name, gross * r.pct / 100));
      const byAlias = {};
      ctcRows.forEach((r) => { byAlias[String(r.name || "").trim().toLowerCase()] = num(r.pct); });
      const basicPct = num(byAlias["basic"]);
      const now = new Date();
      const currY = now.getFullYear();
      const currM = now.getMonth();
      const dim = new Date(currY, currM + 1, 0).getDate();
      const lopDays = (Array.isArray(leaveEntries) ? leaveEntries : []).reduce((sum, x) => {
        const xEmp = String(x.empId || x.emp_id || "").toUpperCase();
        if (xEmp !== String(employee.empId || "").toUpperCase()) return sum;
        if (String(x.status || "").toLowerCase() === "rejected") return sum;
        const t = String(x.leaveType || x.leave_type || x.type || "").toUpperCase();
        if (t !== "LOP") return sum;
        const dt = String(x.date || x.fromDate || x.from || "");
        if (!dt) return sum;
        const d = new Date(`${dt}T00:00:00`);
        if (Number.isNaN(d.getTime())) return sum;
        if (d.getFullYear() !== currY || d.getMonth() !== currM) return sum;
        return sum + num(x.days || 1);
      }, 0);
      const lopDeduction = dim > 0 ? (gross / dim) * lopDays : 0;
      const adjustedGross = Math.max(0, gross - lopDeduction);

      const daPctOfBasic = num(control?.daPctBasic ?? control?.daPctOfBasic ?? 0);
      const pfEmpPct = num(control?.pfEmployeePct ?? control?.pfEmpPct ?? 12);
      const pfCapEnabled = ["yes","true","1"].includes(String(control?.pfWageCapEnabled ?? "Yes").toLowerCase());
      const pfCapAmount = num(control?.pfWageCapAmount ?? 15000);
      const esiLimit = num(control?.esiWageLimit ?? 21000);
      const pfOnEsiPct = num(control?.pfOnEsiPct ?? 0);
      const adjustedBasicAmt = adjustedGross * basicPct / 100;
      const pfWagesBase = adjustedBasicAmt + (adjustedBasicAmt * daPctOfBasic / 100);
      const pfApplicable = String(employee.pf || (employee.status === "Active" ? "Yes" : "No")).toLowerCase() === "yes";
      const esiApplicableByEmp = String(employee.esi || "Yes").toLowerCase() === "yes";
      const esiApplicable = esiApplicableByEmp && adjustedGross > 0 && adjustedGross <= esiLimit;
      const statutoryBase = esiApplicable
        ? (adjustedGross * pfOnEsiPct / 100)
        : adjustedGross;
      const pfWages = esiApplicable
        ? statutoryBase
        : ((pfCapEnabled && adjustedGross > esiLimit) ? pfCapAmount : pfWagesBase);
      const pfEe = pfApplicable ? (pfWages * pfEmpPct / 100) : 0;

      const esiEmpPct = num(control?.esiEmployeePct ?? control?.esiEmpPct ?? 0.75);
      const esiEe = esiApplicable ? (statutoryBase * esiEmpPct / 100) : 0;

      const ptEnabled = String(control?.ptEnabled ?? "").toLowerCase() === "yes" || String(control?.ptEnabled ?? "").toLowerCase() === "true";
      const lwfEnabled = String(control?.lwfEnabled ?? "").toLowerCase() === "yes" || String(control?.lwfEnabled ?? "").toLowerCase() === "true";
      const ptAmt = ptEnabled ? num(control?.ptMonthly ?? 0) : 0;
      const lwfAmt = lwfEnabled ? num(control?.lwfEmpAmt ?? control?.lwfEmployeeAmt ?? 0) : 0;

      const deductionRows = [
        ...(pfApplicable ? [kvDeduction("PF", pfEe)] : []),
        ...(esiApplicable ? [kvDeduction("ESI", esiEe)] : []),
        ...(ptEnabled ? [kvDeduction("PT", ptAmt)] : []),
        ...(lwfEnabled ? [kvDeduction("LWF", lwfAmt)] : [])
      ];
      const netSalary = adjustedGross - (pfEe + esiEe + ptAmt + lwfAmt);
      return [
        kvEarning("Gross Monthly", gross),
        ...(lopDays > 0 ? [kvDeduction("LOP", lopDeduction)] : []),
        kv("Adjusted Gross Salary", inr(Math.max(0, Math.round(adjustedGross)))),
        kvTitle("EARNINGS"),
        ...componentRows,
        ...(deductionRows.length ? [kvTitle("DEDUCTIONS"), ...deductionRows] : []),
        kv("Net Salary", inr(Math.max(0, Math.round(netSalary))))
      ].join("");
    }

    async function init(){
      applyTheme(localStorage.getItem("hr_portal_theme") || "light");
      $("themeToggle").addEventListener("click", () => {
        const current = document.documentElement.getAttribute("data-bs-theme") || "light";
        applyTheme(current === "dark" ? "light" : "dark");
      });

      if(!empId){
        $("profileSub").textContent = "Employee ID missing.";
        return;
      }

      let control = await fetchControlLive();
      const empExtras = safeParse(localStorage.getItem(KEY_EMP_EXTRA)) || {};
      let leaveEntries = await fetchLeavesLive();
      const advanceData = await fetchAdvanceData();

      let employee = null;
      try{
        const res = await apiFetch("/employees");
        if(res.ok){
          const data = await res.json();
          const rows = Array.isArray(data?.rows) ? data.rows.map(toUi) : [];
          localStorage.setItem(KEY_EMP_LOCAL, JSON.stringify(rows));
          employee = rows.find(x => x.empId === empId) || null;
        }
      }catch(_e){}
      if(!employee){
        const localRows = safeParse(localStorage.getItem(KEY_EMP_LOCAL)) || [];
        employee = (Array.isArray(localRows) ? localRows : []).find(x => String(x.empId || "").toUpperCase() === empId) || null;
      }

      if(!employee){
        $("profileSub").textContent = `Employee ${empId} not found`;
        $("empName").textContent = "Employee not found";
        return;
      }

      const extra = empExtras[empId] || {};
      $("avatarText").textContent = (employee.empName || employee.empId).slice(0,2).toUpperCase();
      $("empName").textContent = employee.empName || "-";
      $("empMeta").textContent = `${employee.empId} | ${employee.dept || "-"} | ${employee.designation || "-"}`;
      $("profileSub").textContent = `Viewing ${employee.empId}`;

      $("employeeDetails").innerHTML = [
        kv("Date of Joining", employee.doj || "-"),
        kv("Emp type", employee.employmentType || "-"),
        kv("Status", employee.status || "-"),
        kv("Mobile", employee.mobile || "-"),
        kv("Email", employee.email || "-"),
        kv("Address", extra.address || "-"),
        kv("Emergency Contact 1", [extra.emergencyName1 || extra.emergencyName, extra.emergencyPhone1 || extra.emergencyPhone, extra.emergencyRelation1 || extra.emergencyRelation].filter(Boolean).join(" | ") || "-"),
        kv("Emergency Contact 2", [extra.emergencyName2, extra.emergencyPhone2, extra.emergencyRelation2].filter(Boolean).join(" | ") || "-"),
        kv("Aadhar Card", extra.aadharNo || "-"),
        kv("PAN Card", extra.panCard || "-"),
        kv("Bank Name", extra.bankName || "-"),
        kv("Bank A/C", extra.bankAc || "-"),
        kv("IFSC", extra.ifsc || "-"),
        kv("UAN", employee.uan || "-"),
        kv("ESI No", employee.esiNo || "-")
      ].join("");

      function renderSalaryBlock(){
        $("salaryDetails").innerHTML = salaryHtml(employee, control, leaveEntries);
      }
      renderSalaryBlock();
      renderLeaveBlocks(leaveEntries, extra);
      renderAdvanceBlocks(advanceData);

      function todayIso(){
        return new Date().toISOString().slice(0, 10);
      }
      function updateLeaveModeUi(){
        const mode = $("leaveMode")?.value || "single";
        $("leaveSingleWrap")?.classList.toggle("d-none", mode === "range");
        $("leaveRangeWrap")?.classList.toggle("d-none", mode !== "range");
        $("leaveHalfDayWrap")?.classList.toggle("d-none", mode === "range");
      }
      function updateLeaveDays(){
        const mode = $("leaveMode")?.value || "single";
        if(mode === "single"){
          const hasDate = !!($("leaveDateSingle")?.value);
          const isHalf = ($("leaveHalfDay")?.value || "No") === "Yes";
          $("leaveApplyDays").value = hasDate ? (isHalf ? "0.5" : "1") : "0";
          return;
        }
        const days = calcRangeDays($("leaveFromDate")?.value, $("leaveToDate")?.value);
        $("leaveApplyDays").value = String(days || 0);
      }
      function setLeaveApplyMsg(msg, ok){
        const el = $("leaveApplyMsg");
        if(!el) return;
        el.className = `small mb-2 ${ok ? "text-success" : "text-danger"}`;
        el.textContent = msg;
      }
      function resetLeaveApplyForm(){
        const form = $("leaveApplyForm");
        if(form) form.reset();
        if($("leaveMode")) $("leaveMode").value = "single";
        if($("leaveDateSingle")) $("leaveDateSingle").value = todayIso();
        if($("leaveHalfDay")) $("leaveHalfDay").value = "No";
        if($("leaveApplyStatus")) $("leaveApplyStatus").value = "Pending";
        updateLeaveModeUi();
        updateLeaveDays();
        setLeaveApplyMsg("", true);
      }

      $("leaveMode")?.addEventListener("change", () => { updateLeaveModeUi(); updateLeaveDays(); });
      $("leaveDateSingle")?.addEventListener("change", updateLeaveDays);
      $("leaveHalfDay")?.addEventListener("change", updateLeaveDays);
      $("leaveFromDate")?.addEventListener("change", updateLeaveDays);
      $("leaveToDate")?.addEventListener("change", updateLeaveDays);
      $("btnLeaveApplyReset")?.addEventListener("click", resetLeaveApplyForm);
      const applyLeaveModalEl = $("applyLeaveModal");
      const applyLeaveModal = applyLeaveModalEl ? bootstrap.Modal.getOrCreateInstance(applyLeaveModalEl) : null;
      applyLeaveModalEl?.addEventListener("shown.bs.modal", () => $("leaveApplyType")?.focus());
      resetLeaveApplyForm();

      $("leaveApplyForm")?.addEventListener("submit", async (ev) => {
        ev.preventDefault();
        const mode = $("leaveMode")?.value || "single";
        let fromDate = "";
        let toDate = "";
        let days = 0;
        let halfDay = "No";
        if(mode === "single"){
          fromDate = $("leaveDateSingle")?.value || "";
          toDate = fromDate;
          if(!fromDate){ setLeaveApplyMsg("Select leave date.", false); return; }
          halfDay = $("leaveHalfDay")?.value || "No";
          days = halfDay === "Yes" ? 0.5 : 1;
        } else {
          fromDate = $("leaveFromDate")?.value || "";
          toDate = $("leaveToDate")?.value || "";
          if(!fromDate || !toDate){ setLeaveApplyMsg("Select from and to dates.", false); return; }
          days = calcRangeDays(fromDate, toDate);
          if(days <= 0){ setLeaveApplyMsg("Invalid date range.", false); return; }
        }
        const leaveType = String($("leaveApplyType")?.value || "").toUpperCase();
        const reason = String($("leaveApplyReason")?.value || "").trim();
        if(!leaveType){ setLeaveApplyMsg("Select leave type.", false); return; }
        if(!reason){ setLeaveApplyMsg("Enter leave reason.", false); return; }

        const payload = {
          empId: employee.empId,
          empName: employee.empName,
          dept: employee.dept || "",
          desig: employee.designation || "",
          company: "",
          fromDate,
          toDate,
          days,
          leaveType,
          reason,
          status: normalizeStatus($("leaveApplyStatus")?.value || "Pending"),
          halfDay,
          markedBy: "Employee Self"
        };

        try{
          const saved = await saveLeaveLive(payload);
          const row = {
            ...payload,
            ...saved,
            empId: String(saved.empId || saved.emp_id || payload.empId).toUpperCase(),
            leaveType: String(saved.leaveType || saved.leave_type || saved.type || payload.leaveType).toUpperCase(),
            fromDate: String(saved.fromDate || saved.from || saved.date || payload.fromDate),
            toDate: String(saved.toDate || saved.to || saved.fromDate || saved.from || saved.date || payload.toDate),
            days: num(saved.days || payload.days),
            status: normalizeStatus(saved.status || payload.status),
            reason: String(saved.reason || saved.note || payload.reason)
          };
          leaveEntries.unshift(row);
          localStorage.setItem(KEY_LEAVES, JSON.stringify(leaveEntries));
          setLeaveApplyMsg("Leave applied successfully.", true);
        } catch(_e){
          leaveEntries.unshift({ ...payload, id: Date.now() });
          localStorage.setItem(KEY_LEAVES, JSON.stringify(leaveEntries));
          setLeaveApplyMsg("API unavailable. Leave saved locally.", true);
        }

        renderLeaveBlocks(leaveEntries, extra);
        renderSalaryBlock();
        resetLeaveApplyForm();
        applyLeaveModal?.hide();
      });

      if(extra.attachmentDataUrl){
        $("docBtn").disabled = false;
        $("docBtn").onclick = () => openPdfDataUrl(extra.attachmentDataUrl);
      }
      let lastSalaryHtml = $("salaryDetails").innerHTML;
      async function refreshSalaryFromControl(){
        control = await fetchControlLive();
        const nextHtml = salaryHtml(employee, control, leaveEntries);
        if(nextHtml !== lastSalaryHtml){
          $("salaryDetails").innerHTML = nextHtml;
          lastSalaryHtml = nextHtml;
        }
      }
      window.addEventListener("focus", refreshSalaryFromControl);
      window.addEventListener("storage", (e) => {
        if (!e || e.key === KEY_CONTROL || e.key === null) refreshSalaryFromControl();
        if(e && (e.key === KEY_LEAVES || e.key === null)){
          const localLeaves = safeParse(localStorage.getItem(KEY_LEAVES)) || [];
          leaveEntries = Array.isArray(localLeaves) ? localLeaves : [];
          renderLeaveBlocks(leaveEntries, extra);
          renderSalaryBlock();
        }
      });
      document.addEventListener("visibilitychange", () => {
        if (document.visibilityState === "visible") refreshSalaryFromControl();
      });
      setInterval(refreshSalaryFromControl, 10000);
    }

    init();
