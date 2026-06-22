const $ = (id) => document.getElementById(id);
    const API_EMP = "/api/employees";
    const API_LEAVES = "/api/leaves";
    const API_ATT_DAILY = "/api/attendance/daily";
    const API_ATT_UPSERT = "/api/attendance/daily/upsert";
    const API_ATT_GENERATE = "/api/attendance/generate";
    const API_ATT_SHEETS = "/api/attendance/sheets";
    const API_ATT_SHEETS_CLEAR = "/api/attendance/sheets/clear";
    const API_ATT_STATUSES = "/api/attendance-statuses";

    const KEY_ATT = "hr_client_attendance_daily_v1";
    const KEY_SHEETS = "hr_client_attendance_sheets_v1";
    const KEY_LEAVES = "hr_client_leaves_v1";
    const KEY_ATT_NOTES = "hr_client_attendance_notes_v1";
    const KEY_ATT_HALF = "hr_client_attendance_halfday_v1";

    document.getElementById("yr").textContent = new Date().getFullYear();

    const htmlEl = document.documentElement;
    const themeToggle = $("themeToggle");
    const themeIcon = $("themeIcon");
    const themeText = $("themeText");
    const storageMode = $("storageMode");

    function applyTheme(theme) {
      htmlEl.setAttribute("data-bs-theme", theme);
      localStorage.setItem("hr_portal_theme", theme);
      const isDark = theme === "dark";
      themeIcon.className = isDark ? "bi bi-sun" : "bi bi-moon";
      if (themeText) themeText.textContent = "";
    }
    applyTheme(localStorage.getItem("hr_portal_theme") || "light");
    themeToggle.addEventListener("click", () => {
      applyTheme((htmlEl.getAttribute("data-bs-theme") || "light") === "dark" ? "light" : "dark");
    });

    const statusModal = new bootstrap.Modal(document.getElementById("statusModal"));
    const sheetViewModal = new bootstrap.Modal(document.getElementById("sheetViewModal"));

    let employees = [];
    let empQuick = null;
    let activeCell = null;
    let pendingStatus = "";
    let monthMap = {};
    let noteMap = {};
    let halfDayMap = {};
    let sheetList = [];
    let activeSheetRows = [];
    let attendanceStatuses = [];

    const now = new Date();
    $("monthSel").value = String(now.getMonth() + 1);
    $("yearSel").value = String(now.getFullYear());
    if ($("quickDate")) $("quickDate").value = fmtISO(now);

    function safeParse(s) { try { return JSON.parse(s); } catch (_e) { return null; } }
    function fmtISO(d) { return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`; }
    function monthKey(y, m) { return `${y}-${String(m).padStart(2, "0")}`; }
    function daysInMonth(y, m) { return new Date(y, m, 0).getDate(); }
    function dayLabel(y, m, d) { return new Date(y, m - 1, d).toLocaleDateString(undefined, { weekday: "short" }); }
    const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

    function updateGridPeriodBadges() {
      const y = Number($("yearSel").value);
      const m = Number($("monthSel").value);
      if ($("gridMonthBadge")) $("gridMonthBadge").textContent = `Month: ${monthNames[m - 1] || "-"}`;
      if ($("gridYearBadge")) $("gridYearBadge").textContent = `Year: ${Number.isFinite(y) ? y : "-"}`;
    }

    function setStorageMode(text) { storageMode.textContent = text; }

    function defaultAttendanceStatuses() {
      return [
        { code: "P", shortLabel: "P", fullLabel: "Present", buttonClass: "btn-outline-success", sortOrder: 10, isActive: true, noteRequired: false, isPaid: true },
        { code: "A", shortLabel: "A", fullLabel: "Absent", buttonClass: "btn-outline-danger", sortOrder: 15, isActive: true, noteRequired: false, isPaid: false },
        { code: "WO", shortLabel: "WO", fullLabel: "Weekly Off", buttonClass: "btn-outline-secondary", sortOrder: 20, isActive: true, noteRequired: false, isPaid: true },
        { code: "CL", shortLabel: "CL", fullLabel: "Casual", buttonClass: "btn-outline-primary", sortOrder: 30, isActive: true, noteRequired: true, isPaid: true },
        { code: "SL", shortLabel: "SL", fullLabel: "Sick", buttonClass: "btn-outline-info", sortOrder: 40, isActive: true, noteRequired: true, isPaid: true },
        { code: "EL", shortLabel: "EL", fullLabel: "Earned", buttonClass: "btn-outline-dark", sortOrder: 50, isActive: true, noteRequired: true, isPaid: true },
        { code: "LOP", shortLabel: "LOP", fullLabel: "Loss of Pay", buttonClass: "btn-outline-warning", sortOrder: 60, isActive: true, noteRequired: true, isPaid: false }
      ];
    }

    function sortAttendanceStatuses(list) {
      return (Array.isArray(list) ? list : []).slice().sort((a, b) => Number(a.sortOrder || 0) - Number(b.sortOrder || 0) || String(a.code || "").localeCompare(String(b.code || "")));
    }

    function getActiveAttendanceStatuses() {
      return sortAttendanceStatuses(attendanceStatuses).filter((row) => row && row.isActive !== false);
    }

    function getStatusConfig(code) {
      const target = String(code || "").toUpperCase();
      return attendanceStatuses.find((row) => String(row.code || "").toUpperCase() === target) || null;
    }

    async function fetchAttendanceStatusesApi() {
      const res = await fetch(`${API_ATT_STATUSES}?activeOnly=1`, { cache: "no-store" });
      if (!res.ok) throw new Error("GET /api/attendance-statuses failed");
      const data = await res.json();
      return Array.isArray(data.rows) ? data.rows : [];
    }

    function renderAttendanceStatuses() {
      const legend = $("attendanceLegend");
      const hint = $("attendanceStatusHint");
      const buttonsWrap = $("attendanceStatusButtons");
      const rows = getActiveAttendanceStatuses();
      if (hint) {
        const codes = rows.map((row) => String(row.code || "").toUpperCase()).join("/");
        hint.textContent = rows.length ? `Click any cell to change status (${codes}).` : "Click any cell to change status.";
      }
      if (legend) {
        legend.innerHTML = rows.length
          ? rows.map((row) => `<span class="badge-soft">${row.shortLabel || row.code}: ${row.fullLabel || row.code}</span>`).join("")
          : '<span class="badge-soft">No active attendance statuses</span>';
      }
      if (buttonsWrap) {
        buttonsWrap.innerHTML = rows.length
          ? rows.map((row) => `<div class="col-6 d-grid"><button class="btn ${row.buttonClass || "btn-outline-secondary"}" data-st="${row.code}">${row.shortLabel || row.code} - ${row.fullLabel || row.code}</button></div>`).join("")
          : '<div class="col-12"><div class="small text-muted-3">No active attendance statuses available.</div></div>';
        buttonsWrap.querySelectorAll("[data-st]").forEach((btn) => {
          btn.addEventListener("click", () => {
            pendingStatus = String(btn.getAttribute("data-st") || "").toUpperCase();
            buttonsWrap.querySelectorAll("[data-st]").forEach((b) => b.classList.remove("active"));
            btn.classList.add("active");
            if ($("leaveNoteError")) $("leaveNoteError").classList.add("d-none");
          });
        });
      }
    }

    async function loadAttendanceStatuses() {
      try {
        const rows = await fetchAttendanceStatusesApi();
        attendanceStatuses = rows.length ? rows : defaultAttendanceStatuses();
      } catch (_e) {
        attendanceStatuses = defaultAttendanceStatuses();
      }
      renderAttendanceStatuses();
    }

    function defaultEmployees() {
      const raw = safeParse(localStorage.getItem("hr_client_employees_v1")) || [];
      return (Array.isArray(raw) ? raw : []).map((e) => ({
        id: String(e.empId || e.id || "").toUpperCase(),
        name: String(e.empName || e.name || ""),
        dept: String(e.dept || ""),
        desig: String(e.designation || e.desig || "")
      })).filter((e) => e.id);
    }

    async function fetchEmployeesApi() {
      const res = await fetch(`${API_EMP}?activeOnly=1`, { cache: "no-store" });
      if (!res.ok) throw new Error("GET /api/employees failed");
      const data = await res.json();
      return (data.rows || []).map((e) => ({ id: e.id, name: e.name, dept: e.dept || "", desig: e.desig || "" }));
    }

    function initQuickSelect() {
      if (!$("empQuick")) return;
      if (empQuick) empQuick.destroy();
      empQuick = new TomSelect("#empQuick", {
        options: employees.map((e) => ({ value: e.id, text: `${e.name} (${e.id})`, empId: e.id, empName: e.name, dept: e.dept, desig: e.desig })),
        searchField: ["text", "empId", "empName", "dept", "desig"],
        placeholder: "Search employee...",
        render: {
          option: (d, esc) => `<div><div class="fw-semibold">${esc(d.empName)}</div><div class="small text-muted-3"><span class="mono">${esc(d.empId)}</span> - ${esc(d.dept || "-")} - ${esc(d.desig || "-")}</div></div>`,
          item: (d, esc) => `<div>${esc(d.empName)} <span class="small text-muted-3">(${esc(d.empId)})</span></div>`
        }
      });
    }

    async function fetchMonthDailyApi(y, m) {
      const res = await fetch(`${API_ATT_DAILY}?month=${m}&year=${y}`, { cache: "no-store" });
      if (!res.ok) throw new Error("GET /api/attendance/daily failed");
      const data = await res.json();
      const out = {};
      (data.rows || []).forEach((r) => {
        out[`${String(r.empId || "").toUpperCase()}|${r.date}`] = String(r.status || "").toUpperCase();
      });
      return out;
    }

    async function upsertMonthDailyApi(y, m, mapObj) {
      const records = Object.keys(mapObj).map((k) => {
        const parts = k.split("|");
        return { empId: parts[0], date: parts[1], status: mapObj[k] };
      });
      const res = await fetch(API_ATT_UPSERT, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ month: m, year: y, records })
      });
      if (!res.ok) throw new Error("POST /api/attendance/daily/upsert failed");
      return res.json();
    }

    async function generateAttendanceApi(y, m) {
      const res = await fetch(API_ATT_GENERATE, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ month: m, year: y, fillDefault: false, sundayWeeklyOff: false })
      });
      if (!res.ok) throw new Error("POST /api/attendance/generate failed");
      const data = await res.json();
      return data.sheet;
    }

    async function fetchSheetsApi() {
      const res = await fetch(API_ATT_SHEETS, { cache: "no-store" });
      if (!res.ok) throw new Error("GET /api/attendance/sheets failed");
      const data = await res.json();
      return data.rows || [];
    }

    async function fetchSheetByIdApi(id) {
      const res = await fetch(`${API_ATT_SHEETS}/${id}`, { cache: "no-store" });
      if (!res.ok) throw new Error("GET /api/attendance/sheets/{id} failed");
      const data = await res.json();
      return data.sheet;
    }
    async function deleteSheetByIdApi(id) {
      const res = await fetch(`${API_ATT_SHEETS}/${id}`, { method: "DELETE" });
      if (!res.ok) throw new Error("DELETE /api/attendance/sheets/{id} failed");
      return res.json();
    }
    async function clearSheetsApi() {
      const res = await fetch(API_ATT_SHEETS_CLEAR, { method: "POST" });
      if (!res.ok) throw new Error("POST /api/attendance/sheets/clear failed");
      return res.json();
    }

    function localDailyData() { return safeParse(localStorage.getItem(KEY_ATT)) || {}; }
    function saveLocalDailyData(all) { localStorage.setItem(KEY_ATT, JSON.stringify(all)); }
    function loadLocalMonth(y, m) { const all = localDailyData(); return all[monthKey(y, m)] || {}; }
    function saveLocalMonth(y, m, mapObj) { const all = localDailyData(); all[monthKey(y, m)] = mapObj; saveLocalDailyData(all); }
    function loadLocalNotes() { return safeParse(localStorage.getItem(KEY_ATT_NOTES)) || {}; }
    function saveLocalNotes(all) { localStorage.setItem(KEY_ATT_NOTES, JSON.stringify(all)); }
    function loadLocalHalfDays() { return safeParse(localStorage.getItem(KEY_ATT_HALF)) || {}; }
    function saveLocalHalfDays(all) { localStorage.setItem(KEY_ATT_HALF, JSON.stringify(all)); }

    function loadLocalSheets() { return safeParse(localStorage.getItem(KEY_SHEETS)) || []; }
    function saveLocalSheets(rows) { localStorage.setItem(KEY_SHEETS, JSON.stringify(rows)); }
    function normText(v) { return String(v || "").trim().toLowerCase(); }
    function sheetPeriodLabel(s) { return s.period || `${s.year}-${String(s.month).padStart(2, "0")}`; }
    function sheetStatusLabel(s) { return String(s.status || "Generated"); }
    function sundayDatesOfMonth(y, m) {
      const out = [];
      const dim = daysInMonth(y, m);
      for (let d = 1; d <= dim; d++) {
        const dt = new Date(y, m - 1, d);
        if (dt.getDay() === 0) out.push(fmtISO(dt));
      }
      return out;
    }
    function stripAutoSundayWoFromMonthMap(mapObj, y, m) {
      const mapRef = mapObj || {};
      const emps = Array.isArray(employees) ? employees : [];
      if (!emps.length) return false;
      const sundays = sundayDatesOfMonth(y, m);
      if (!sundays.length) return false;

      let sundayWo = 0;
      let nonSundayWo = 0;
      const sundaySet = new Set(sundays);
      Object.keys(mapRef).forEach((k) => {
        const parts = String(k).split("|");
        if (parts.length !== 2) return;
        const dt = parts[1];
        const st = String(mapRef[k] || "").toUpperCase();
        if (st !== "WO") return;
        if (sundaySet.has(dt)) sundayWo++;
        else nonSundayWo++;
      });

      const likelyAuto = sundayWo >= Math.floor(emps.length * sundays.length * 0.6) && nonSundayWo === 0;
      if (!likelyAuto) return false;

      let changed = false;
      emps.forEach((emp) => {
        sundays.forEach((dateIso) => {
          const key = `${emp.id}|${dateIso}`;
          if (String(mapRef[key] || "").toUpperCase() === "WO") {
            delete mapRef[key];
            changed = true;
          }
        });
      });
      return changed;
    }

    function getFilteredSheets() {
      const q = normText($("sheetSearch")?.value);
      const monthVal = String($("sheetMonth")?.value || "All");
      const yearVal = String($("sheetYear")?.value || "All");
      const statusVal = String($("sheetStatus")?.value || "All");
      return (sheetList || []).filter((s) => {
        const monthNum = Number(s.month);
        const monthName = monthNames[monthNum - 1] || "";
        const period = sheetPeriodLabel(s);
        const status = sheetStatusLabel(s);
        const hay = normText([
          s.id,
          period,
          monthName,
          s.year,
          s.rowCount,
          s.generatedAt,
          status
        ].join(" "));
        if (q && !hay.includes(q)) return false;
        if (monthVal !== "All" && String(monthNum) !== String(Number(monthVal))) return false;
        if (yearVal !== "All" && String(s.year) !== yearVal) return false;
        if (statusVal !== "All" && normText(status) !== normText(statusVal)) return false;
        return true;
      });
    }

    function populateSheetFilterOptions() {
      const monthSel = $("sheetMonth");
      const yearSel = $("sheetYear");
      const statusSel = $("sheetStatus");
      if (!monthSel || !yearSel || !statusSel) return;

      const prevMonth = monthSel.value || "All";
      const prevYear = yearSel.value || "All";
      const prevStatus = statusSel.value || "All";

      const months = Array.from(new Set((sheetList || []).map((s) => Number(s.month)).filter((n) => Number.isFinite(n) && n >= 1 && n <= 12))).sort((a, b) => a - b);
      const years = Array.from(new Set((sheetList || []).map((s) => Number(s.year)).filter((n) => Number.isFinite(n)))).sort((a, b) => b - a);
      const statuses = Array.from(new Set((sheetList || []).map((s) => sheetStatusLabel(s))));

      monthSel.innerHTML = ['<option value="All">All</option>', ...months.map((m) => `<option value="${m}">${monthNames[m - 1]}</option>`)].join("");
      yearSel.innerHTML = ['<option value="All">All</option>', ...years.map((y) => `<option value="${y}">${y}</option>`)].join("");
      statusSel.innerHTML = ['<option value="All">All</option>', ...statuses.map((st) => `<option value="${st}">${st}</option>`)].join("");

      monthSel.value = Array.from(monthSel.options).some((o) => o.value === prevMonth) ? prevMonth : "All";
      yearSel.value = Array.from(yearSel.options).some((o) => o.value === prevYear) ? prevYear : "All";
      statusSel.value = Array.from(statusSel.options).some((o) => o.value === prevStatus) ? prevStatus : "All";
    }

    async function loadMonthData() {
      const y = Number($("yearSel").value);
      const m = Number($("monthSel").value);
      let normalized = false;
      try {
        monthMap = await fetchMonthDailyApi(y, m);
        normalized = stripAutoSundayWoFromMonthMap(monthMap, y, m);
        setStorageMode("API (SQLite)");
        if (normalized) {
          try { await upsertMonthDailyApi(y, m, monthMap); } catch (_e2) {}
        }
        saveLocalMonth(y, m, monthMap);
      } catch (_e) {
        monthMap = loadLocalMonth(y, m);
        normalized = stripAutoSundayWoFromMonthMap(monthMap, y, m);
        if (normalized) saveLocalMonth(y, m, monthMap);
        setStorageMode("Browser localStorage (offline mode)");
      }
      noteMap = loadLocalNotes();
      halfDayMap = loadLocalHalfDays();
    }

    async function persistMonthData() {
      const y = Number($("yearSel").value);
      const m = Number($("monthSel").value);
      try {
        await upsertMonthDailyApi(y, m, monthMap);
        setStorageMode("API (SQLite)");
      } catch (_e) {
        saveLocalMonth(y, m, monthMap);
        setStorageMode("Browser localStorage (offline mode)");
      }
    }


    function buildCalendar() {
      const y = Number($("yearSel").value);
      const m = Number($("monthSel").value);
      const dim = daysInMonth(y, m);

      const head = [`<tr><th>Employee</th>`];
      for (let d = 1; d <= dim; d++) {
        head.push(`<th class="text-center">${d}<div class="small text-muted-3">${dayLabel(y, m, d)}</div></th>`);
      }
      head.push("</tr>");
      $("calHead").innerHTML = head.join("");

      const rows = [];
      employees.forEach((emp) => {
        rows.push(`<tr><th><div class="fw-semibold">${emp.name}</div><div class="small text-muted-3 mono">${emp.id}</div></th>`);
        for (let d = 1; d <= dim; d++) {
          const dateIso = `${y}-${String(m).padStart(2, "0")}-${String(d).padStart(2, "0")}`;
          const key = `${emp.id}|${dateIso}`;
          const st = monthMap[key] || "";
          rows.push(`<td class="text-center"><div class="cell ${st ? `st-${st}` : ""}" data-emp="${emp.id}" data-date="${dateIso}" data-st="${st}">${st || ""}</div></td>`);
        }
        rows.push("</tr>");
      });
      $("calBody").innerHTML = rows.join("");

      document.querySelectorAll(".cell").forEach((cell) => {
        cell.addEventListener("click", () => {
          activeCell = cell;
          statusModal.show();
        });
      });

      renderSummary();
    }

    function isLeaveStatus(st) {
      return ["CL", "SL", "EL", "LOP"].includes(String(st || "").toUpperCase());
    }

    function requiresStatusNote(st) {
      const cfg = getStatusConfig(st);
      return !!(cfg && cfg.noteRequired);
    }

    async function upsertLeaveFromAttendance(empId, dateIso, leaveType, reason, halfDay) {
      const emp = employees.find((e) => String(e.id).toUpperCase() === String(empId).toUpperCase());
      const payload = {
        empId: String(empId).toUpperCase(),
        empName: emp?.name || String(empId).toUpperCase(),
        dept: emp?.dept || "",
        desig: emp?.desig || "",
        company: emp?.company || "",
        fromDate: dateIso,
        toDate: dateIso,
        days: String(halfDay || "No") === "Yes" ? 0.5 : 1,
        leaveType,
        reason: String(reason || "").trim(),
        status: "Approved",
        halfDay: String(halfDay || "No"),
        markedBy: "Attendance"
      };
      try {
        const dt = new Date(`${dateIso}T00:00:00`);
        const m = dt.getMonth() + 1;
        const y = dt.getFullYear();
        const res = await fetch(`${API_LEAVES}?month=${m}&year=${y}`, { cache: "no-store" });
        if (!res.ok) throw new Error("GET /api/leaves failed");
        const data = await res.json();
        const rows = data.rows || [];
        const existing = rows.find((r) =>
          String(r.empId || "").toUpperCase() === payload.empId &&
          String(r.fromDate || "") === dateIso &&
          String(r.toDate || r.fromDate || "") === dateIso
        );
        if (existing && existing.id) {
          await fetch(`${API_LEAVES}/${existing.id}`, {
            method: "PUT",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
          });
          return;
        }
        await fetch(API_LEAVES, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload)
        });
      } catch (_e) {
        const list = safeParse(localStorage.getItem(KEY_LEAVES)) || [];
        const idx = list.findIndex((r) =>
          String(r.empId || "").toUpperCase() === payload.empId &&
          String(r.fromDate || r.from || "") === dateIso &&
          String(r.toDate || r.to || r.fromDate || "") === dateIso
        );
        if (idx >= 0) list[idx] = { ...list[idx], ...payload, id: list[idx].id || Date.now() };
        else list.unshift({ ...payload, id: Date.now() });
        localStorage.setItem(KEY_LEAVES, JSON.stringify(list));
      }
    }

    async function deleteLeaveFromAttendance(empId, dateIso) {
      try {
        const dt = new Date(`${dateIso}T00:00:00`);
        const m = dt.getMonth() + 1;
        const y = dt.getFullYear();
        const res = await fetch(`${API_LEAVES}?month=${m}&year=${y}`, { cache: "no-store" });
        if (!res.ok) throw new Error("GET /api/leaves failed");
        const data = await res.json();
        const rows = data.rows || [];
        const targets = rows.filter((r) =>
          String(r.empId || "").toUpperCase() === String(empId).toUpperCase() &&
          String(r.fromDate || "") === dateIso &&
          String(r.toDate || r.fromDate || "") === dateIso &&
          String(r.markedBy || "").toLowerCase() === "attendance"
        );
        for (const t of targets) {
          if (t.id) await fetch(`${API_LEAVES}/${t.id}`, { method: "DELETE" });
        }
      } catch (_e) {
        const list = safeParse(localStorage.getItem(KEY_LEAVES)) || [];
        const next = list.filter((r) => !(
          String(r.empId || "").toUpperCase() === String(empId).toUpperCase() &&
          String(r.fromDate || r.from || "") === dateIso &&
          String(r.toDate || r.to || r.fromDate || "") === dateIso &&
          String(r.markedBy || "").toLowerCase() === "attendance"
        ));
        localStorage.setItem(KEY_LEAVES, JSON.stringify(next));
      }
    }

    async function setCellStatus(cell, status, noteText, halfDay) {
      const empId = cell.dataset.emp;
      const dateIso = cell.dataset.date;
      const key = `${empId}|${dateIso}`;
      if (status) monthMap[key] = status;
      else delete monthMap[key];
      if (status && isLeaveStatus(status)) {
        noteMap[key] = String(noteText || "").trim();
        halfDayMap[key] = String(halfDay || "No");
      } else {
        delete noteMap[key];
        delete halfDayMap[key];
      }
      saveLocalNotes(noteMap);
      saveLocalHalfDays(halfDayMap);
      cell.dataset.st = status || "";
      cell.className = `cell ${status ? `st-${status}` : ""}`;
      cell.textContent = status || "";
      await persistMonthData();
      if (status && isLeaveStatus(status)) {
        await upsertLeaveFromAttendance(empId, dateIso, status, noteMap[key] || "", halfDayMap[key] || "No");
      } else {
        await deleteLeaveFromAttendance(empId, dateIso);
      }
      renderSummary(empId);
    }

    $("btnSaveStatus")?.addEventListener("click", async () => {
      if (!activeCell || !pendingStatus) {
        alert("Please select status first.");
        return;
      }
      const noteEl = $("leaveNote");
      const noteErr = $("leaveNoteError");
      const note = (noteEl?.value || "").trim();
      const halfDay = $("leaveHalfDay")?.value || "No";
      if (requiresStatusNote(pendingStatus) && !note) {
        if (noteErr) noteErr.classList.remove("d-none");
        noteEl?.focus();
        return;
      }
      if (noteErr) noteErr.classList.add("d-none");
      await setCellStatus(activeCell, pendingStatus, note, halfDay);
      statusModal.hide();
    });

    $("btnClearCell").addEventListener("click", async () => {
      if (activeCell) await setCellStatus(activeCell, "");
      pendingStatus = "";
      document.querySelectorAll('#attendanceStatusButtons [data-st]').forEach((b) => b.classList.remove("active"));
      if ($("leaveNote")) $("leaveNote").value = "";
      if ($("leaveHalfDay")) $("leaveHalfDay").value = "No";
      if ($("leaveNoteError")) $("leaveNoteError").classList.add("d-none");
      statusModal.hide();
    });

    document.getElementById("statusModal")?.addEventListener("show.bs.modal", () => {
      if (!activeCell) return;
      const key = `${activeCell.dataset.emp}|${activeCell.dataset.date}`;
      pendingStatus = String(activeCell.dataset.st || "").toUpperCase();
      document.querySelectorAll('#attendanceStatusButtons [data-st]').forEach((b) => {
        b.classList.toggle("active", String(b.getAttribute("data-st") || "").toUpperCase() === pendingStatus);
      });
      if ($("leaveNote")) $("leaveNote").value = noteMap[key] || "";
      if ($("leaveHalfDay")) $("leaveHalfDay").value = halfDayMap[key] || "No";
      if ($("leaveNoteError")) $("leaveNoteError").classList.add("d-none");
    });

    function renderSummary(empId) {
      if (!employees.length) return;
      const target = empId || employees[0].id;
      const y = Number($("yearSel").value);
      const m = Number($("monthSel").value);
      const dim = daysInMonth(y, m);
      const c = { P: 0, A: 0, WO: 0, CL: 0, SL: 0, EL: 0, LOP: 0 };
      for (let d = 1; d <= dim; d++) {
        const dateIso = `${y}-${String(m).padStart(2, "0")}-${String(d).padStart(2, "0")}`;
        const st = monthMap[`${target}|${dateIso}`] || "";
        if (c[st] !== undefined) c[st]++;
      }
      const cards = [
        ["Present", c.P, "bi-check2-circle"],
        ["Weekly Off", c.WO, "bi-calendar2-week"],
        ["CL", c.CL, "bi-emoji-smile"],
        ["SL", c.SL, "bi-heart-pulse"],
        ["LOP", c.LOP, "bi-exclamation-triangle"],
        ["Absent", c.A, "bi-x-circle"]
      ];
      $("summaryCards").innerHTML = cards.map((x) => `<div class="col-2"><div class="glass-soft p-3 h-100"><div class="d-flex align-items-center justify-content-between"><div><div class="small text-muted-3">${x[0]}</div><div class="fw-semibold fs-4">${x[1]}</div></div><i class="bi ${x[2]} fs-4"></i></div></div></div>`).join("");
    }

    async function syncLeaves() {
      const y = Number($("yearSel").value);
      const m = Number($("monthSel").value);
      const validLeave = new Set(["CL", "SL", "EL", "LOP"]);
      const leaveByDate = {};
      let applied = 0;
      try {
        const res = await fetch(`${API_LEAVES}?month=${m}&year=${y}&status=Approved`, { cache: "no-store" });
        if (!res.ok) throw new Error("GET /api/leaves failed");
        const data = await res.json();
        (data.rows || []).forEach((l) => {
          const empId = String(l.empId || "").toUpperCase();
          const type = String(l.leaveType || "").toUpperCase();
          const from = l.fromDate;
          const to = l.toDate || l.fromDate;
          if (!empId || !from || !type || !validLeave.has(type)) return;
          if (!employees.some((e) => e.id === empId)) return;
          const start = new Date(`${from}T00:00:00`);
          const end = new Date(`${to}T00:00:00`);
          for (let dt = new Date(start); dt <= end; dt.setDate(dt.getDate() + 1)) {
            if (dt.getFullYear() !== y || dt.getMonth() + 1 !== m) continue;
            const dateIso = fmtISO(dt);
            if (!leaveByDate[dateIso]) leaveByDate[dateIso] = {};
            leaveByDate[dateIso][empId] = type;
          }
        });
      } catch (_e) {
        const leaves = safeParse(localStorage.getItem(KEY_LEAVES)) || [];
        leaves.forEach((l) => {
          const empId = String(l.empId || "").toUpperCase();
          const type = String(l.leaveType || l.type || "").toUpperCase();
          const from = l.fromDate || l.from;
          const to = l.toDate || l.to || from;
          if (!empId || !from || !type || !validLeave.has(type)) return;
          if (!employees.some((e) => e.id === empId)) return;
          const start = new Date(`${from}T00:00:00`);
          const end = new Date(`${to}T00:00:00`);
          for (let dt = new Date(start); dt <= end; dt.setDate(dt.getDate() + 1)) {
            if (dt.getFullYear() !== y || dt.getMonth() + 1 !== m) continue;
            const dateIso = fmtISO(dt);
            if (!leaveByDate[dateIso]) leaveByDate[dateIso] = {};
            leaveByDate[dateIso][empId] = type;
          }
        });
      }

      // Update only leave dates:
      // do NOT set P for all; only fill P for non-leave employees when cell is blank.
      Object.keys(leaveByDate).forEach((dateIso) => {
        const leaveForDate = leaveByDate[dateIso] || {};
        employees.forEach((emp) => {
          const key = `${emp.id}|${dateIso}`;
          if (!leaveForDate[emp.id] && !monthMap[key]) {
            monthMap[key] = "P";
          }
        });
        Object.keys(leaveForDate).forEach((empId) => {
          monthMap[`${empId}|${dateIso}`] = leaveForDate[empId];
          applied++;
        });
      });

      await persistMonthData();
      buildCalendar();
      alert(`Synced leave dates only. Applied ${applied} leave entries.`);
    }

    async function generateMonth() {
      const y = Number($("yearSel").value);
      const m = Number($("monthSel").value);
      const doneProcessing = window.HRCommon?.setProcessingState?.([$("btnGenerate"), $("btnGeneratePeriod")], {
        busyText: "Generating...",
        message: "Please wait, we are preparing the attendance sheet."
      });
      try {
        await generateAttendanceApi(y, m);
        monthMap = await fetchMonthDailyApi(y, m);
        setStorageMode("API (SQLite)");
      } catch (_e) {
        const dim = daysInMonth(y, m);
        employees.forEach((emp) => {
          for (let d = 1; d <= dim; d++) {
            const dt = new Date(y, m - 1, d);
            const dateIso = fmtISO(dt);
            const key = `${emp.id}|${dateIso}`;
            if (!monthMap[key]) monthMap[key] = "P";
          }
        });
        await persistMonthData();
      }
      saveLocalMonth(y, m, monthMap);
      buildCalendar();
      await loadSheetList();
      const monthLabel = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"][m - 1] || String(m);
      const rowCount = employees.length;
      doneProcessing?.(`Attendance sheet ready for ${monthLabel} ${y}.`, false);
      alert(`Success: Attendance sheet generated for ${monthLabel} ${y} (${rowCount} employees).`);
    }

    function parseCsvLine(line) {
      const out = [];
      let cur = "";
      let inQuotes = false;
      for (let i = 0; i < line.length; i++) {
        const ch = line[i];
        const next = line[i + 1];
        if (ch === '"') {
          if (inQuotes && next === '"') {
            cur += '"';
            i++;
          } else {
            inQuotes = !inQuotes;
          }
        } else if (ch === "," && !inQuotes) {
          out.push(cur.trim());
          cur = "";
        } else {
          cur += ch;
        }
      }
      out.push(cur.trim());
      return out.map((x) => x.replace(/^"|"$/g, "").trim());
    }

    function parseCsvRows(text) {
      return String(text || "")
        .split(/\r?\n/)
        .map((line) => parseCsvLine(line))
        .filter((row) => row.some((cell) => String(cell || "").trim() !== ""));
    }

    function employeeIdFromImportLabel(label) {
      const raw = String(label || "").trim();
      if (!raw) return "";
      const upper = raw.toUpperCase();
      const byId = employees.find((e) => String(e.id || "").toUpperCase() === upper);
      if (byId) return byId.id;
      const nameNorm = raw.toLowerCase();
      const exactName = employees.find((e) => String(e.name || "").trim().toLowerCase() === nameNorm);
      if (exactName) return exactName.id;
      const contains = employees.find((e) => {
        const empName = String(e.name || "").trim().toLowerCase();
        return empName && (nameNorm.includes(empName) || empName.includes(nameNorm));
      });
      return contains ? contains.id : "";
    }

    function parseAttendanceImport(text, y, m) {
      const rows = parseCsvRows(text);
      if (rows.length < 2) return [];

      const firstRow = rows[0].map((x) => String(x || "").trim().toLowerCase());
      const rowFormat = firstRow.includes("emp_id") && firstRow.includes("date") && firstRow.includes("status");
      if (rowFormat) {
        return rows.slice(1).map((r) => ({
          empId: String(r[0] || "").toUpperCase(),
          date: String(r[1] || ""),
          status: String(r[2] || "").toUpperCase()
        }));
      }

      const headerRowIndex = rows.findIndex((r) => String(r[0] || "").trim().toLowerCase() === "employee");
      if (headerRowIndex < 0) return [];
      const header = rows[headerRowIndex];
      const dayNumbers = header.slice(1).map((v) => Number(String(v || "").trim()));
      const hasDays = dayNumbers.some((n) => Number.isInteger(n) && n >= 1 && n <= 31);
      if (!hasDays) return [];

      let dataStart = headerRowIndex + 1;
      const maybeWeekdayRow = rows[dataStart] || [];
      if (maybeWeekdayRow.length > 1) {
        const weekdayHits = maybeWeekdayRow.slice(1).filter((v) => /^(sun|mon|tue|wed|thu|fri|sat)$/i.test(String(v || "").trim())).length;
        if (weekdayHits >= Math.min(3, dayNumbers.length)) dataStart++;
      }

      const out = [];
      for (let i = dataStart; i < rows.length; i++) {
        const row = rows[i];
        const empId = employeeIdFromImportLabel(row[0] || "");
        if (!empId) continue;
        for (let c = 1; c < header.length; c++) {
          const day = Number(header[c]);
          if (!Number.isInteger(day) || day < 1 || day > 31) continue;
          const status = String(row[c] || "").trim().toUpperCase();
          if (!status) continue;
          const dateIso = `${y}-${String(m).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
          out.push({ empId, date: dateIso, status });
        }
      }
      return out;
    }

    async function importCSV(file) {
      const text = await file.text();
      const y = Number($("yearSel").value);
      const m = Number($("monthSel").value);
      const valid = new Set(getActiveAttendanceStatuses().map((row) => String(row.code || "").toUpperCase()));
      let ok = 0;
      const records = parseAttendanceImport(text, y, m);
      if (!records.length) {
        alert("Import file format not recognized. Use either Employee ID, Date, Status format or the exported attendance sheet CSV.");
        return;
      }
      records.forEach((r) => {
        const empId = String(r.empId || "").toUpperCase();
        const dateIso = r.date;
        const st = String(r.status || "").toUpperCase();
        if (!empId || !dateIso || !valid.has(st)) return;
        const dt = new Date(`${dateIso}T00:00:00`);
        if (Number.isNaN(dt.getTime())) return;
        if (dt.getFullYear() !== y || dt.getMonth() + 1 !== m) return;
        if (!employees.some((e) => e.id === empId)) return;
        monthMap[`${empId}|${dateIso}`] = st;
        ok++;
      });
      await persistMonthData();
      buildCalendar();
      alert(`Import done. Imported ${ok} records.`);
    }

    function buildDaywiseAoa(y, m, mapObj) {
      const dim = daysInMonth(y, m);
      const headerTop = ["Employee"];
      const headerBottom = [""];
      for (let d = 1; d <= dim; d++) {
        headerTop.push(String(d));
        headerBottom.push(dayLabel(y, m, d));
      }
      const body = employees.map((emp) => {
        const row = [emp.name || emp.id];
        for (let d = 1; d <= dim; d++) {
          const dateIso = fmtISO(new Date(y, m - 1, d));
          row.push(mapObj[`${emp.id}|${dateIso}`] || "");
        }
        return row;
      });
      return [headerTop, headerBottom, ...body];
    }

    function downloadAoaCsv(aoa, filename) {
      const csv = aoa.map((r) => r.map((x) => `"${String(x ?? "")}"`).join(",")).join("\n");
      const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
    }
    function buildExportInfoRows(y, m) {
      const profile = safeParse(localStorage.getItem("hr_client_profile_v1")) || {};
      return [
        ["Company Name *", profile.companyName || ""],
        ["CIN / LLPIN / Reg. No", profile.regNo || profile.companyRegNo || ""],
        ["Company Address *", profile.companyAddress || ""],
        ["PAN", profile.pan || profile.companyPAN || ""],
        ["TAN", profile.tan || profile.companyTAN || ""],
        ["GSTIN", profile.gstin || profile.companyGSTIN || ""],
        ["PF Establishment ID", profile.pfEstId || ""],
        ["ESIC Employer Code", profile.esicCode || ""],
        ["Month", monthNames[Number(m) - 1] || ""],
        ["Year", Number(y) || ""],
        []
      ];
    }

    function downloadAoaXlsx(aoa, filename, y, m) {
      const info = buildExportInfoRows(y, m);
      const out = [...info, ...aoa];
      const ws = XLSX.utils.aoa_to_sheet(out);
      const rowStart = info.length + 1;
      const rowEnd = out.length;
      const colCount = Math.max(...aoa.map(r => (r || []).length), 0);
      const mkCol = (n) => { let s = ""; while(n > 0){ const m = (n - 1) % 26; s = String.fromCharCode(65 + m) + s; n = Math.floor((n - 1) / 26); } return s; };
      if(colCount > 0 && rowEnd >= rowStart){
        ws["!autofilter"] = { ref: `A${rowStart}:${mkCol(colCount)}${rowEnd}` };
        ws["!cols"] = Array.from({ length: colCount }, () => ({ wch: 12 }));
        for(let r = rowStart; r <= rowEnd; r++){
          for(let c = 1; c <= colCount; c++){
            const ref = `${mkCol(c)}${r}`;
            if(!ws[ref]) ws[ref] = { t: "s", v: "" };
            ws[ref].s = {
              border: { top:{style:"thin", color:{rgb:"FF000000"}}, bottom:{style:"thin", color:{rgb:"FF000000"}}, left:{style:"thin", color:{rgb:"FF000000"}}, right:{style:"thin", color:{rgb:"FF000000"}} },
              fill: r <= rowStart + 1 ? { patternType: "solid", fgColor: { rgb: "FF0B1F3A" } } : undefined,
              font: r <= rowStart + 1 ? { bold: true, color: { rgb: "FFFFFFFF" } } : undefined
            };
          }
        }
      }
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, "Attendance");
      XLSX.writeFileXLSX(wb, filename, { cellStyles: true, bookType: "xlsx" });
    }

    function exportCSV() {
      const y = Number($("yearSel").value);
      const m = Number($("monthSel").value);
      const aoa = buildDaywiseAoa(y, m, monthMap);
      const out = [...buildExportInfoRows(y, m), ...aoa];
      downloadAoaCsv(out, `attendance_${monthKey(y, m)}.csv`);
    }

    async function quickApply() {
      const empId = String(empQuick ? empQuick.getValue() : "").toUpperCase();
      const dateIso = $("quickDate").value;
      const st = $("quickStatus").value;
      if (!empId) return alert("Select employee.");
      if (!dateIso) return alert("Select date.");
      const y = Number($("yearSel").value);
      const m = Number($("monthSel").value);
      const dt = new Date(`${dateIso}T00:00:00`);
      if (dt.getFullYear() !== y || dt.getMonth() + 1 !== m) return alert("Quick date must be inside selected month/year.");
      const cell = document.querySelector(`.cell[data-emp="${empId}"][data-date="${dateIso}"]`);
      if (!cell) return alert("Generate or load month first.");
      await setCellStatus(cell, st);
    }

    async function loadSheetList() {
      try {
        sheetList = await fetchSheetsApi();
        saveLocalSheets(sheetList);
      } catch (_e) {
        sheetList = loadLocalSheets();
      }
      populateSheetFilterOptions();
      const filtered = getFilteredSheets();
      const body = $("sheetListBody");
      if (!filtered.length) {
        body.innerHTML = '<tr><td colspan="5" class="text-center text-muted-3 py-4">No generated sheets yet.</td></tr>';
        return;
      }
      body.innerHTML = filtered.map((s, i) => `
        <tr>
          <td>${i + 1}</td>
          <td>${s.period || `${s.year}-${String(s.month).padStart(2, "0")}`}</td>
          <td>${s.rowCount || 0}</td>
          <td>${new Date(s.generatedAt).toLocaleString()}</td>
          <td class="text-center">
            <div class="btn-group">
              <button class="btn btn-outline-primary btn-sm" title="View" aria-label="View" onclick="viewSheet('${s.id}')"><i class="bi bi-eye"></i></button>
              <button class="btn btn-outline-secondary btn-sm" title="Download XLSX" aria-label="Download XLSX" onclick="downloadSheetXlsx('${s.id}')"><i class="bi bi-file-earmark-excel"></i></button>
              <button class="btn btn-outline-danger btn-sm" title="Delete" aria-label="Delete" onclick="deleteAttendanceSheet('${s.id}')"><i class="bi bi-trash"></i></button>
            </div>
          </td>
        </tr>`).join("");
    }

    function countSundaysInMonth(y, m) {
      if (!Number.isFinite(y) || !Number.isFinite(m) || m < 1 || m > 12) return 0;
      const dim = daysInMonth(y, m);
      let c = 0;
      for (let d = 1; d <= dim; d++) {
        if (new Date(y, m - 1, d).getDay() === 0) c++;
      }
      return c;
    }

    function shouldStripAutoSundayWo(rows, sundayCount) {
      if (!Array.isArray(rows) || !rows.length || sundayCount <= 0) return false;
      let autoLike = 0;
      rows.forEach((r) => {
        const wo = Number(r.WO ?? r.wo ?? 0);
        const other = Number(r.A ?? r.a ?? 0) + Number(r.CL ?? r.cl ?? 0) + Number(r.SL ?? r.sl ?? 0) + Number(r.EL ?? r.el ?? 0) + Number(r.LOP ?? r.lop ?? r.lopDays ?? 0);
        if (wo >= sundayCount && other === 0) autoLike++;
      });
      return autoLike >= Math.max(3, Math.floor(rows.length * 0.5));
    }

    async function getSheetRows(sheetId) {
      try {
        const sheet = await fetchSheetByIdApi(sheetId);
        return sheet || {};
      } catch (_e) {
        return { rows: [] };
      }
    }

    window.viewSheet = async function(sheetId) {
      const sheet = await getSheetRows(sheetId);
      const rows = Array.isArray(sheet.rows) ? sheet.rows : [];
      activeSheetRows = rows;
      $("sheetViewTitle").textContent = `Attendance Sheet: ${sheetId}`;
      const sundayCount = countSundaysInMonth(Number(sheet.year), Number(sheet.month));
      const stripAutoSundayWo = shouldStripAutoSundayWo(rows, sundayCount);
      $("sheetViewBody").innerHTML = rows.map((r) => {
        const empId = r.empId ?? r.emp_id ?? "-";
        const empName = r.empName ?? r.employeeName ?? r.name ?? "-";
        const p = Number(r.P ?? r.p ?? 0);
        const a = Number(r.A ?? r.a ?? 0);
        const woRaw = Number(r.woTaken ?? r.WOTaken ?? r.WO ?? r.wo ?? 0);
        const wo = stripAutoSundayWo ? Math.max(0, woRaw - sundayCount) : woRaw;
        const cl = Number(r.CL ?? r.cl ?? 0);
        const sl = Number(r.SL ?? r.sl ?? 0);
        const el = Number(r.EL ?? r.el ?? 0);
        const lop = Number(r.LOP ?? r.lop ?? r.lopDays ?? 0);
        const payable = Number(r.payableDays ?? r.payable ?? r.paidDays ?? (p + wo + cl + sl + el));
        return `<tr><td>${empId}</td><td>${empName}</td><td>${p}</td><td>${a}</td><td>${wo}</td><td>${cl}</td><td>${sl}</td><td>${el}</td><td>${lop}</td><td>${payable}</td></tr>`;
      }).join("") || '<tr><td colspan="10" class="text-center text-muted-3 py-3">No rows found.</td></tr>';
      sheetViewModal.show();
    };

    async function getDaywiseAoaForSheet(sheetId) {
      const s = (sheetList || []).find((x) => String(x.id) === String(sheetId));
      if (!s) return null;
      const y = Number(s.year);
      const m = Number(s.month);
      let mapObj = {};
      try {
        mapObj = await fetchMonthDailyApi(y, m);
      } catch (_e) {
        mapObj = loadLocalMonth(y, m);
      }
      return { aoa: buildDaywiseAoa(y, m, mapObj), key: monthKey(y, m) };
    }

    window.downloadSheetCsv = async function(sheetId) {
      const out = await getDaywiseAoaForSheet(sheetId);
      if (!out) return alert("Sheet not found.");
      downloadAoaCsv(out.aoa, `attendance_sheet_${out.key}.csv`);
    };

    window.downloadSheetXlsx = async function(sheetId) {
      const out = await getDaywiseAoaForSheet(sheetId);
      if (!out) return alert("Sheet not found.");
      const [yy, mm] = String(out.key || "").split("-");
      downloadAoaXlsx(out.aoa, `attendance_sheet_${out.key}.xlsx`, Number(yy), Number(mm));
    };
    window.deleteAttendanceSheet = async function(sheetId) {
      if (!sheetId) return;
      if (!confirm("Delete this generated attendance sheet?")) return;
      try {
        await deleteSheetByIdApi(sheetId);
      } catch (_e) {
        // ignore API failure and still remove from local list if present
      }
      sheetList = (sheetList || []).filter((s) => String(s.id) !== String(sheetId));
      saveLocalSheets(sheetList);
      await loadSheetList();
    };
    async function clearAttendanceSheetHistory() {
      if (!confirm("Clear all generated attendance sheet history?")) return;
      try {
        await clearSheetsApi();
      } catch (_e) {
        // fallback to local clear if API is unavailable
      }
      sheetList = [];
      saveLocalSheets(sheetList);
      await loadSheetList();
    }

    if ($("btnGenerate")) $("btnGenerate").addEventListener("click", generateMonth);
    if ($("btnGeneratePeriod")) $("btnGeneratePeriod").addEventListener("click", generateMonth);
    $("btnSyncLeaves").addEventListener("click", syncLeaves);
    $("btnExport").addEventListener("click", exportCSV);
    $("btnQuickApply")?.addEventListener("click", quickApply);
    $("btnImport").addEventListener("click", () => $("fileInput").click());
    $("fileInput").addEventListener("change", async (e) => {
      const f = e.target.files && e.target.files[0];
      if (f) await importCSV(f);
      e.target.value = "";
    });
    ["sheetSearch", "sheetMonth", "sheetYear", "sheetStatus"].forEach((id) => {
      const el = $(id);
      if (!el) return;
      const ev = id === "sheetSearch" ? "input" : "change";
      el.addEventListener(ev, () => { loadSheetList(); });
    });
    $("clearSheetFilters")?.addEventListener("click", () => {
      if ($("sheetSearch")) $("sheetSearch").value = "";
      if ($("sheetMonth")) $("sheetMonth").value = "All";
      if ($("sheetYear")) $("sheetYear").value = "All";
      if ($("sheetStatus")) $("sheetStatus").value = "All";
      loadSheetList();
    });
    $("btnClearSheetHistory")?.addEventListener("click", clearAttendanceSheetHistory);

    async function onPeriodChange() {
      updateGridPeriodBadges();
      await loadMonthData();
      buildCalendar();
    }
    $("monthSel").addEventListener("change", onPeriodChange);
    $("yearSel").addEventListener("change", onPeriodChange);

    (async function init() {
      try {
        employees = await fetchEmployeesApi();
      } catch (_e) {
        employees = defaultEmployees();
      }
      await loadAttendanceStatuses();
      initQuickSelect();
      updateGridPeriodBadges();
      await loadMonthData();
      buildCalendar();
      await loadSheetList();
    })();
