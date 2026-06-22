const $ = (id) => document.getElementById(id);
    const KEY_LEAVE = "hr_client_leaves_v1";
    const KEY_EMP_EXTRA = "hr_emp_extra_v1";
    const API_EMP = "/api/employees";
    const API_LEAVE = "/api/leaves";
    const STATUS_OPTIONS = ["Pending", "Not Approved", "Approved"];

    document.getElementById("yr").textContent = new Date().getFullYear();

    const htmlEl = document.documentElement;
    const themeToggle = $("themeToggle");
    const themeIcon = $("themeIcon");
    const themeText = $("themeText");
    const storageMode = $("storageMode");
    const importModal = new bootstrap.Modal(document.getElementById("importModal"));

    let employees = [];
    let employeeSelect = null;
    let entries = [];
    let editingId = null;
    let currentFiltered = [];

    function safeParse(value) {
      try { return JSON.parse(value); } catch (_e) { return null; }
    }

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

    function setStorageMode(text) {
      storageMode.textContent = text;
    }

    function normalizeStatus(value) {
      const raw = String(value || "").trim().toLowerCase();
      if (raw === "approved") return "Approved";
      if (raw === "pending") return "Pending";
      if (raw === "not approved" || raw === "rejected" || raw === "not_approved" || raw === "not-approved") return "Not Approved";
      return "Pending";
    }

    function getEmployeeLeaveAlloc(empId) {
      const raw = safeParse(localStorage.getItem(KEY_EMP_EXTRA)) || {};
      const x = raw[String(empId || "").toUpperCase()] || {};
      const alloc = x.leaveAlloc || {};
      return {
        cl: Math.max(0, Number(alloc.cl || 0)),
        sl: Math.max(0, Number(alloc.sl || 0)),
        el: Math.max(0, Number(alloc.el || 0))
      };
    }

    function buildBalanceMap(allEntries) {
      const map = {};
      const list = Array.isArray(allEntries) ? allEntries : [];
      list.forEach((e) => {
        const emp = String(e.empId || "").toUpperCase();
        if (!emp) return;
        if (!map[emp]) {
          const a = getEmployeeLeaveAlloc(emp);
          map[emp] = { cl: a.cl, sl: a.sl, el: a.el };
        }
      });
      list.forEach((e) => {
        const emp = String(e.empId || "").toUpperCase();
        if (!emp || !map[emp]) return;
        if (normalizeStatus(e.status) === "Not Approved") return;
        const t = String(e.leaveType || "").toUpperCase();
        const days = Math.max(0, Number(e.days || 0));
        if (t === "CL") map[emp].cl -= days;
        if (t === "SL") map[emp].sl -= days;
        if (t === "EL") map[emp].el -= days;
      });
      Object.keys(map).forEach((k) => {
        map[k].cl = Math.max(0, map[k].cl);
        map[k].sl = Math.max(0, map[k].sl);
        map[k].el = Math.max(0, map[k].el);
        map[k].total = map[k].cl + map[k].sl + map[k].el;
      });
      return map;
    }

    function initStatusControls() {
      const statusSel = $("status");
      if (statusSel) {
        statusSel.innerHTML = STATUS_OPTIONS.map((s) => `<option value="${s}">${s}</option>`).join("");
        statusSel.value = "Pending";
      }
      const filterStatus = $("filterStatus");
      if (filterStatus) {
        filterStatus.innerHTML = `<option value="">All</option>${STATUS_OPTIONS.map((s) => `<option value="${s}">${s}</option>`).join("")}`;
      }
    }

    function defaultEmployees() {
      const raw = safeParse(localStorage.getItem("hr_client_employees_v1")) || [];
      return (Array.isArray(raw) ? raw : []).map((e) => ({
        id: String(e.empId || e.id || "").toUpperCase(),
        name: String(e.empName || e.name || ""),
        dept: String(e.dept || ""),
        desig: String(e.designation || e.desig || ""),
        company: String(e.company || "")
      })).filter((e) => e.id);
    }

    function rowToLeaveShape(r) {
      return {
        id: r.id ?? null,
        empId: String(r.empId || "").toUpperCase(),
        empName: String(r.empName || ""),
        dept: String(r.dept || ""),
        desig: String(r.desig || ""),
        company: String(r.company || ""),
        fromDate: String(r.fromDate || ""),
        toDate: String(r.toDate || ""),
        days: Number(r.days || 0),
        leaveType: String(r.leaveType || "").toUpperCase(),
        reason: String(r.reason || ""),
        status: normalizeStatus(r.status || "Pending"),
        halfDay: String(r.halfDay || "No"),
        markedBy: String(r.markedBy || "Client HR")
      };
    }

    async function fetchEmployeesApi() {
      const res = await fetch(API_EMP, { cache: "no-store" });
      if (!res.ok) throw new Error("GET /api/employees failed");
      const data = await res.json();
      return (data.rows || []).map((e) => ({
        id: e.id,
        name: e.name,
        dept: e.dept || "",
        desig: e.desig || "",
        company: e.company || ""
      }));
    }

    async function fetchLeavesApi() {
      const res = await fetch(API_LEAVE, { cache: "no-store" });
      if (!res.ok) throw new Error("GET /api/leaves failed");
      const data = await res.json();
      return (data.rows || []).map(rowToLeaveShape);
    }

    async function saveLeaveApi(payload, id = null) {
      const url = id ? `${API_LEAVE}/${id}` : API_LEAVE;
      const method = id ? "PUT" : "POST";
      const res = await fetch(url, {
        method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      if (!res.ok) throw new Error(`${method} ${url} failed`);
      const data = await res.json();
      return rowToLeaveShape(data.row);
    }

    async function deleteLeaveApi(id) {
      const res = await fetch(`${API_LEAVE}/${id}`, { method: "DELETE" });
      if (!res.ok) throw new Error(`DELETE /api/leaves/${id} failed`);
    }

    async function bulkUpsertLeavesApi(rows) {
      const res = await fetch(`${API_LEAVE}/bulk-upsert`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ rows })
      });
      if (!res.ok) throw new Error("POST /api/leaves/bulk-upsert failed");
      const data = await res.json();
      return (data.rows || []).map(rowToLeaveShape);
    }

    function saveLeavesLocal() {
      localStorage.setItem(KEY_LEAVE, JSON.stringify(entries));
    }

    function loadLeavesLocal() {
      try {
        const raw = JSON.parse(localStorage.getItem(KEY_LEAVE) || "[]");
        return Array.isArray(raw) ? raw.map(rowToLeaveShape) : [];
      } catch (_e) {
        return [];
      }
    }

    function initEmployeeSelect() {
      if (employeeSelect) employeeSelect.destroy();
      employeeSelect = new TomSelect("#employeeSelect", {
        options: employees.map((e) => ({
          value: e.id,
          text: `${e.name} (${e.id})`,
          empId: e.id,
          empName: e.name,
          dept: e.dept,
          desig: e.desig,
          company: e.company || ""
        })),
        searchField: ["text", "empId", "empName", "dept", "company"],
        maxOptions: 500,
        placeholder: "Search by Emp ID or Name...",
        render: {
          option: function(data, escape) {
            return `<div><div class="fw-semibold">${escape(data.empName)}</div><div class="small text-muted-3"><span class="mono">${escape(data.empId)}</span><span> - ${escape(data.dept || "-")}</span></div></div>`;
          },
          item: function(data, escape) {
            return `<div>${escape(data.empName)} <span class="small text-muted-3">(${escape(data.empId)})</span></div>`;
          }
        },
        onChange: function(value) {
          if (!value) {
            clearEmployeeFields();
            return;
          }
          const emp = employees.find((x) => x.id === String(value).toUpperCase());
          if (emp) fillEmployeeFields(emp);
        }
      });
    }

    function fillEmployeeFields(emp) {
      $("empId").value = emp.id || "";
      $("empName").value = emp.name || "";
      $("dept").value = emp.dept || "";
      $("desig").value = emp.desig || "";
    }

    function clearEmployeeFields() {
      $("empId").value = "";
      $("empName").value = "";
      $("dept").value = "";
      $("desig").value = "";
    }

    function calcRangeDays() {
      const from = $("fromDate").value;
      const to = $("toDate").value;
      if (!from || !to) {
        $("daysText").textContent = "0";
        return 0;
      }
      const d1 = new Date(`${from}T00:00:00`);
      const d2 = new Date(`${to}T00:00:00`);
      const diff = Math.floor((d2 - d1) / 86400000) + 1;
      const days = diff > 0 ? diff : 0;
      $("daysText").textContent = String(days);
      return days;
    }

    function fmtDate(iso) {
      if (!iso) return "-";
      const d = new Date(`${iso}T00:00:00`);
      return d.toLocaleDateString(undefined, { year: "numeric", month: "short", day: "2-digit" });
    }

    function badgeType(t) {
      const map = { CL: "secondary", SL: "info", EL: "primary", LOP: "warning" };
      return `<span class="badge text-bg-${map[t] || "secondary"}">${t}</span>`;
    }

    function badgeStatus(s) {
      const status = normalizeStatus(s);
      const map = { Approved: "success", Pending: "warning", "Not Approved": "danger" };
      return `<span class="badge text-bg-${map[status] || "secondary"}">${status}</span>`;
    }

    function statusSelectHtml(currentStatus, entryIndex) {
      const current = normalizeStatus(currentStatus);
      return `<select class="form-select form-select-sm js-status-select" data-entry-index="${entryIndex}" aria-label="Change leave status">
        ${STATUS_OPTIONS.map((s) => `<option value="${s}" ${s === current ? "selected" : ""}>${s}</option>`).join("")}
      </select>`;
    }

    function getFilters() {
      return {
        month: $("filterMonth").value,
        year: $("filterYear").value,
        type: $("filterType").value,
        status: $("filterStatus") ? $("filterStatus").value : ""
      };
    }

    function passesFilters(e, f) {
      if (f.type && e.leaveType !== f.type) return false;
      if (f.status && e.status !== f.status) return false;
      const d = new Date(`${e.fromDate}T00:00:00`);
      const m = String(d.getMonth() + 1);
      const y = String(d.getFullYear());
      if (f.month && m !== f.month) return false;
      if (f.year && y !== f.year) return false;
      return true;
    }

    function render() {
      const filtered = entries.filter((e) => passesFilters(e, getFilters()));
      const balances = buildBalanceMap(entries);
      currentFiltered = filtered;
      $("tbody").innerHTML = filtered.map((e, i) => {
        const entryIndex = entries.indexOf(e);
        const bal = balances[String(e.empId || "").toUpperCase()] || { cl: 0, sl: 0, el: 0, total: 0 };
        const dateText = e.fromDate === e.toDate ? fmtDate(e.fromDate) : `${fmtDate(e.fromDate)} -> ${fmtDate(e.toDate)}`;
        return `
          <tr>
            <td class="fw-semibold">${i + 1}</td>
            <td><div class="fw-semibold">${e.empName}</div><div class="small text-muted-3 mono">${e.empId}</div></td>
            <td><div class="fw-semibold">${e.dept || "-"}</div><div class="small text-muted-3">${e.desig || "-"}</div></td>
            <td>${dateText}</td>
            <td>${badgeType(e.leaveType)}</td>
            <td class="fw-semibold">${e.days}</td>
            <td class="fw-semibold">${bal.cl}</td>
            <td class="fw-semibold">${bal.sl}</td>
            <td class="fw-semibold">${bal.el}</td>
            <td class="fw-semibold">${bal.total}</td>
            <td class="text-truncate" style="max-width:220px;" title="${e.reason}">${e.reason}</td>
            <td>${statusSelectHtml(e.status, entryIndex)}</td>
            <td>${e.markedBy || "-"}</td>
            <td class="text-center">
              <div class="btn-group">
                <button class="btn btn-outline-secondary btn-sm" title="Edit" aria-label="Edit" onclick="editEntry(${e.id ?? -1})"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-outline-danger btn-sm" title="Delete" aria-label="Delete" onclick="removeEntry(${e.id ?? -1})"><i class="bi bi-trash"></i></button>
              </div>
            </td>
          </tr>`;
      }).join("");
      if (!filtered.length) {
        $("tbody").innerHTML = '<tr><td colspan="14" class="text-center py-4 text-muted-3">No entries found.</td></tr>';
      }
      $("countText").textContent = `${filtered.length} entries`;
    }

    async function updateEntryStatus(entryIndex, nextStatus) {
      const idx = Number(entryIndex);
      if (!Number.isInteger(idx) || idx < 0 || idx >= entries.length) return;
      const existing = entries[idx];
      const status = normalizeStatus(nextStatus);
      if (normalizeStatus(existing.status) === status) return;

      const payload = {
        empId: existing.empId,
        empName: existing.empName,
        dept: existing.dept || "",
        desig: existing.desig || "",
        company: existing.company || "",
        fromDate: existing.fromDate,
        toDate: existing.toDate,
        days: Number(existing.days || 0),
        leaveType: String(existing.leaveType || "").toUpperCase(),
        reason: existing.reason || "",
        status,
        halfDay: existing.halfDay || "No",
        markedBy: existing.markedBy || "Client HR"
      };

      try {
        if (existing.id != null) {
          const saved = await saveLeaveApi(payload, existing.id);
          entries[idx] = saved;
          setStorageMode("API (SQLite)");
        } else {
          entries[idx] = { ...existing, status };
          saveLeavesLocal();
          setStorageMode("Browser localStorage (offline mode)");
        }
      } catch (_e) {
        entries[idx] = { ...existing, status };
        saveLeavesLocal();
        setStorageMode("Browser localStorage (offline mode)");
      }
      render();
    }

    function resetForm() {
      const form = $("leaveForm");
      form.reset();
      form.classList.remove("was-validated");
      editingId = null;
      $("singleDateWrap").classList.remove("d-none");
      $("rangeDateWrap").classList.add("d-none");
      $("daysText").textContent = "0";
      $("btnReset").textContent = "Reset";
      clearEmployeeFields();
      if (employeeSelect) employeeSelect.clear();
      showEmpSelectError(false);
    }

    function showEmpSelectError(show) {
      $("empSelectError").style.display = show ? "block" : "none";
    }

    window.editEntry = function(id) {
      const row = entries.find((x) => Number(x.id) === Number(id));
      if (!row) return;
      editingId = row.id;
      if (employeeSelect) employeeSelect.setValue(row.empId, true);
      fillEmployeeFields({ id: row.empId, name: row.empName, dept: row.dept, desig: row.desig, company: row.company });
      $("leaveType").value = row.leaveType;
      $("reason").value = row.reason;
      $("status").value = normalizeStatus(row.status);
      $("markedBy").value = row.markedBy || "Client HR";
      if (row.fromDate === row.toDate) {
        $("dateMode").value = "single";
        $("singleDateWrap").classList.remove("d-none");
        $("rangeDateWrap").classList.add("d-none");
        $("leaveDate").value = row.fromDate;
        $("halfDay").value = Number(row.days) === 0.5 ? "Yes" : (row.halfDay || "No");
      } else {
        $("dateMode").value = "range";
        $("singleDateWrap").classList.add("d-none");
        $("rangeDateWrap").classList.remove("d-none");
        $("fromDate").value = row.fromDate;
        $("toDate").value = row.toDate;
        calcRangeDays();
      }
      $("btnReset").textContent = "Cancel Edit";
    };

    window.removeEntry = async function(id) {
      const row = entries.find((x) => Number(x.id) === Number(id));
      if (!row) return;
      if (!confirm("Delete this leave entry?")) return;
      try {
        await deleteLeaveApi(id);
        entries = entries.filter((x) => Number(x.id) !== Number(id));
        setStorageMode("API (SQLite)");
      } catch (_e) {
        entries = entries.filter((x) => Number(x.id) !== Number(id));
        saveLeavesLocal();
        setStorageMode("Browser localStorage (offline mode)");
      }
      render();
    };

    $("dateMode").addEventListener("change", () => {
      const mode = $("dateMode").value;
      $("singleDateWrap").classList.toggle("d-none", mode === "range");
      $("rangeDateWrap").classList.toggle("d-none", mode === "single");
      if (mode === "range") calcRangeDays();
    });
    $("fromDate").addEventListener("change", calcRangeDays);
    $("toDate").addEventListener("change", calcRangeDays);

    $("btnToday")?.addEventListener("click", () => {
      const d = new Date();
      const yyyy = d.getFullYear();
      const mm = String(d.getMonth() + 1).padStart(2, "0");
      const dd = String(d.getDate()).padStart(2, "0");
      $("leaveDate").value = `${yyyy}-${mm}-${dd}`;
    });

    $("btnClearFilters").addEventListener("click", () => {
      $("filterMonth").value = "";
      $("filterYear").value = "";
      $("filterType").value = "";
      if ($("filterStatus")) $("filterStatus").value = "";
      render();
    });
    ["filterMonth", "filterYear", "filterType", "filterStatus"].forEach((id) => {
      if ($(id)) $(id).addEventListener("change", render);
    });

    function exportRows() {
      return currentFiltered.length ? currentFiltered : entries;
    }

    function rowsForFile(rows) {
      return rows.map((e) => ({
        Emp_ID: e.empId,
        Emp_Name: e.empName,
        Department: e.dept,
        Designation: e.desig,
        Company: e.company,
        From_Date: e.fromDate,
        To_Date: e.toDate,
        Days: e.days,
        Leave_Type: e.leaveType,
        Reason: e.reason,
        Status: e.status,
        Marked_By: e.markedBy,
        Half_Day: e.halfDay
      }));
    }

    $("btnExportCsv")?.addEventListener("click", () => {
      const ws = XLSX.utils.json_to_sheet(rowsForFile(exportRows()));
      const csv = XLSX.utils.sheet_to_csv(ws);
      const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = "leave_entries.csv";
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
    });

    $("importFile").addEventListener("change", () => {
      const f = $("importFile").files && $("importFile").files[0];
      $("importInfo").textContent = f ? `${f.name} (${Math.round(f.size / 1024)} KB)` : "No file selected.";
    });

    function normalizeImportRow(r) {
      return {
        empId: String(r.Emp_ID || r.emp_id || r.empId || "").trim().toUpperCase(),
        empName: String(r.Emp_Name || r.emp_name || r.empName || "").trim(),
        dept: String(r.Department || r.dept || "").trim(),
        desig: String(r.Designation || r.desig || "").trim(),
        company: String(r.Company || r.company || "").trim(),
        fromDate: String(r.From_Date || r.from_date || r.fromDate || "").trim(),
        toDate: String(r.To_Date || r.to_date || r.toDate || "").trim(),
        days: Number(r.Days || r.days || 0),
        leaveType: String(r.Leave_Type || r.leave_type || r.leaveType || "").trim().toUpperCase(),
        reason: String(r.Reason || r.reason || "").trim(),
        status: normalizeStatus(r.Status || r.status || "Pending"),
        markedBy: String(r.Marked_By || r.marked_by || r.markedBy || "Client HR").trim(),
        halfDay: String(r.Half_Day || r.half_day || r.halfDay || "No").trim()
      };
    }

    async function readImportFile(file) {
      const name = (file.name || "").toLowerCase();
      if (name.endsWith(".csv")) {
        const text = await file.text();
        const wb = XLSX.read(text, { type: "string" });
        const ws = wb.Sheets[wb.SheetNames[0]];
        return XLSX.utils.sheet_to_json(ws, { defval: "" });
      }
      const data = await file.arrayBuffer();
      const wb = XLSX.read(data, { type: "array" });
      const ws = wb.Sheets[wb.SheetNames[0]];
      return XLSX.utils.sheet_to_json(ws, { defval: "" });
    }

    $("btnImportUpload").addEventListener("click", async () => {
      const f = $("importFile").files && $("importFile").files[0];
      if (!f) return alert("Please select a file.");

      try {
        const rawRows = await readImportFile(f);
        const rows = rawRows.map(normalizeImportRow).filter((r) => r.empId && r.empName && r.fromDate && r.toDate && r.leaveType && r.reason && r.days > 0);
        if (!rows.length) return alert("No valid leave rows found.");

        try {
          await bulkUpsertLeavesApi(rows);
          entries = await fetchLeavesApi();
          setStorageMode("API (SQLite)");
          alert(`Imported ${rows.length} leave entries to SQLite.`);
        } catch (_apiErr) {
          const merged = [...loadLeavesLocal(), ...rows.map(rowToLeaveShape)];
          entries = merged.map((x, i) => ({ ...x, id: x.id || Date.now() + i }));
          saveLeavesLocal();
          setStorageMode("Browser localStorage (offline mode)");
          alert(`Imported ${rows.length} leave entries locally.`);
        }

        render();
        $("importFile").value = "";
        $("importInfo").textContent = "No file selected.";
        importModal.hide();
      } catch (_e) {
        alert("Import failed. Please check the file format.");
      }
    });

    $("leaveForm").addEventListener("submit", async (ev) => {
      ev.preventDefault();
      const form = ev.currentTarget;
      const selectedEmpId = employeeSelect ? employeeSelect.getValue() : "";
      if (!selectedEmpId) return showEmpSelectError(true);
      showEmpSelectError(false);

      if (!form.checkValidity()) {
        form.classList.add("was-validated");
        return;
      }

      const emp = employees.find((x) => x.id === String(selectedEmpId).toUpperCase());
      if (!emp) return alert("Employee not found.");

      const mode = $("dateMode").value;
      let fromDate = "";
      let toDate = "";
      let days = 0;
      let halfDay = "No";

      if (mode === "single") {
        fromDate = $("leaveDate").value;
        toDate = fromDate;
        if (!fromDate) return alert("Select leave date.");
        halfDay = $("halfDay").value;
        days = halfDay === "Yes" ? 0.5 : 1;
      } else {
        fromDate = $("fromDate").value;
        toDate = $("toDate").value;
        if (!fromDate || !toDate) return alert("Select from and to dates.");
        days = calcRangeDays();
        if (days <= 0) return alert("Invalid date range.");
      }

      const payload = {
        empId: emp.id,
        empName: emp.name,
        dept: emp.dept || "",
        desig: emp.desig || "",
        company: emp.company || "",
        fromDate,
        toDate,
        days,
        leaveType: $("leaveType").value,
        reason: $("reason").value.trim(),
        status: normalizeStatus($("status").value),
        halfDay,
        markedBy: $("markedBy").value.trim() || "Client HR"
      };

      try {
        const saved = await saveLeaveApi(payload, editingId);
        if (editingId) {
          const idx = entries.findIndex((x) => Number(x.id) === Number(editingId));
          if (idx >= 0) entries[idx] = saved;
        } else {
          entries.unshift(saved);
        }
        setStorageMode("API (SQLite)");
      } catch (_e) {
        if (editingId) {
          const idx = entries.findIndex((x) => Number(x.id) === Number(editingId));
          if (idx >= 0) entries[idx] = { ...payload, id: editingId };
        } else {
          entries.unshift({ ...payload, id: Date.now() });
        }
        saveLeavesLocal();
        setStorageMode("Browser localStorage (offline mode)");
      }

      render();
      resetForm();
    });

    $("btnReset").addEventListener("click", resetForm);
    $("tbody").addEventListener("change", async (ev) => {
      const sel = ev.target.closest(".js-status-select");
      if (!sel) return;
      await updateEntryStatus(sel.getAttribute("data-entry-index"), sel.value);
    });

    (async function init() {
      initStatusControls();
      try {
        employees = await fetchEmployeesApi();
      } catch (_e) {
        employees = defaultEmployees();
      }
      initEmployeeSelect();

      try {
        entries = await fetchLeavesApi();
        setStorageMode("API (SQLite)");
        saveLeavesLocal();
      } catch (_e) {
        entries = loadLeavesLocal();
        setStorageMode("Browser localStorage (offline mode)");
      }
      render();
    })();
