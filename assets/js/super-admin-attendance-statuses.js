(function () {
  "use strict";

  const API_BASES = ["/api", "/backend/api.php?path=/api", "/backend/api.php?path=/api"];
  const BUILTINS = [
    { code: "P", shortLabel: "P", fullLabel: "Present", buttonClass: "btn-outline-success", sortOrder: 10, isActive: true, noteRequired: false, isPaid: true },
    { code: "A", shortLabel: "A", fullLabel: "Absent", buttonClass: "btn-outline-danger", sortOrder: 15, isActive: true, noteRequired: false, isPaid: false },
    { code: "WO", shortLabel: "WO", fullLabel: "Weekly Off", buttonClass: "btn-outline-secondary", sortOrder: 20, isActive: true, noteRequired: false, isPaid: true },
    { code: "CL", shortLabel: "CL", fullLabel: "Casual", buttonClass: "btn-outline-primary", sortOrder: 30, isActive: true, noteRequired: true, isPaid: true },
    { code: "SL", shortLabel: "SL", fullLabel: "Sick", buttonClass: "btn-outline-info", sortOrder: 40, isActive: true, noteRequired: true, isPaid: true },
    { code: "EL", shortLabel: "EL", fullLabel: "Earned", buttonClass: "btn-outline-dark", sortOrder: 50, isActive: true, noteRequired: true, isPaid: true },
    { code: "LOP", shortLabel: "LOP", fullLabel: "Loss of Pay", buttonClass: "btn-outline-warning", sortOrder: 60, isActive: true, noteRequired: true, isPaid: false }
  ];

  let rows = [];
  let editingCode = "";
  let statusModal = null;

  function applyTheme(theme) {
    document.documentElement.setAttribute("data-bs-theme", theme);
    localStorage.setItem("hr_portal_theme", theme);
    const icon = document.getElementById("themeIcon");
    if (icon) icon.className = theme === "dark" ? "bi bi-sun" : "bi bi-moon";
  }

  function setMsg(text, ok) {
    const box = document.getElementById("statusMsg");
    if (!box) return;
    box.className = `alert ${ok ? "alert-success" : "alert-danger"}`;
    box.textContent = text;
    box.classList.remove("d-none");
  }

  function clearMsg() {
    const box = document.getElementById("statusMsg");
    if (!box) return;
    box.className = "alert d-none";
    box.textContent = "";
  }

  async function apiFetch(path, options = {}) {
    const errors = [];
    for (const base of API_BASES) {
      try {
        const res = await fetch(`${base}${path}`, { cache: "no-store", ...options });
        if (res.status === 404 || res.status === 405) {
          errors.push(`${base}${path}:${res.status}`);
          continue;
        }
        return res;
      } catch (err) {
        errors.push(String(err));
      }
    }
    throw new Error(errors.join(" | ") || "API unavailable");
  }

  function statusChip(row) {
    const btn = row.buttonClass || "btn-outline-secondary";
    return `<span class="badge-soft border ${btn.replace("btn-outline-", "border-")}">${row.shortLabel} - ${row.fullLabel}</span>`;
  }

  function renderCounts() {
    const active = rows.filter((row) => row.isActive);
    document.getElementById("activeCount").textContent = String(active.length);
    document.getElementById("paidCount").textContent = String(rows.filter((row) => row.isPaid).length);
    document.getElementById("unpaidCount").textContent = String(rows.filter((row) => !row.isPaid).length);
    document.getElementById("noteCount").textContent = String(rows.filter((row) => row.noteRequired).length);
  }

  function renderPreview() {
    const holder = document.getElementById("statusPreview");
    if (!holder) return;
    const activeRows = rows.filter((row) => row.isActive).sort((a, b) => a.sortOrder - b.sortOrder || a.code.localeCompare(b.code));
    holder.innerHTML = activeRows.length
      ? activeRows.map(statusChip).join("")
      : '<div class="small text-muted-3">No active attendance statuses yet.</div>';
  }

  function renderTable() {
    const body = document.getElementById("statusTableBody");
    if (!body) return;
    if (!rows.length) {
      body.innerHTML = '<tr><td colspan="7" class="text-center text-muted-3 py-4">No attendance statuses found.</td></tr>';
      return;
    }
    body.innerHTML = rows
      .slice()
      .sort((a, b) => a.sortOrder - b.sortOrder || a.code.localeCompare(b.code))
      .map((row) => `
        <tr>
          <td><span class="fw-semibold mono">${row.code}</span></td>
          <td>
            <div class="fw-semibold">${row.fullLabel}</div>
            <div class="small text-muted-3">${row.shortLabel} | ${row.buttonClass}</div>
          </td>
          <td><span class="badge ${row.isPaid ? "text-bg-success" : "text-bg-warning"}">${row.isPaid ? "Paid" : "Unpaid"}</span></td>
          <td><span class="badge ${row.noteRequired ? "text-bg-primary" : "text-bg-secondary"}">${row.noteRequired ? "Required" : "Optional"}</span></td>
          <td><span class="badge ${row.isActive ? "text-bg-success" : "text-bg-secondary"}">${row.isActive ? "Active" : "Inactive"}</span></td>
          <td>${row.sortOrder}</td>
          <td class="text-end">
            <div class="d-inline-flex gap-2">
              <button class="btn btn-outline-secondary btn-sm" type="button" data-action="edit" data-code="${row.code}">
                <i class="bi bi-pencil"></i>
              </button>
              <button class="btn btn-outline-danger btn-sm" type="button" data-action="delete" data-code="${row.code}">
                <i class="bi bi-trash3"></i>
              </button>
            </div>
          </td>
        </tr>
      `).join("");
  }

  function renderAll() {
    renderCounts();
    renderPreview();
    renderTable();
  }

  function fillCodeOptions(selectedCode, locked) {
    const codeEl = document.getElementById("statusCode");
    if (!codeEl) return;
    codeEl.value = selectedCode || "";
    codeEl.readOnly = !!locked;
  }

  function openCreateModal() {
    clearMsg();
    editingCode = "";
    document.getElementById("attendanceStatusModalTitle").textContent = "Add Attendance Status";
    document.getElementById("attendanceStatusForm").reset();
    fillCodeOptions("", false);
    document.getElementById("statusCode").value = "";
    document.getElementById("statusShortLabel").value = "";
    document.getElementById("statusFullLabel").value = "";
    document.getElementById("statusButtonClass").value = "btn-outline-secondary";
    document.getElementById("statusSortOrder").value = "0";
    document.getElementById("statusPaid").value = "paid";
    document.getElementById("statusActive").checked = true;
    document.getElementById("statusNoteRequired").checked = false;
    statusModal.show();
    document.getElementById("statusCode")?.focus();
  }

  function openEditModal(code) {
    clearMsg();
    const row = rows.find((item) => item.code === code);
    if (!row) return;
    editingCode = code;
    document.getElementById("attendanceStatusModalTitle").textContent = "Edit Attendance Status";
    fillCodeOptions(code, true);
    document.getElementById("statusShortLabel").value = row.shortLabel || "";
    document.getElementById("statusFullLabel").value = row.fullLabel || "";
    document.getElementById("statusButtonClass").value = row.buttonClass || "btn-outline-secondary";
    document.getElementById("statusSortOrder").value = Number(row.sortOrder || 0);
    document.getElementById("statusPaid").value = row.isPaid ? "paid" : "unpaid";
    document.getElementById("statusActive").checked = !!row.isActive;
    document.getElementById("statusNoteRequired").checked = !!row.noteRequired;
    statusModal.show();
  }

  async function loadRows(showToast) {
    clearMsg();
    const res = await apiFetch("/attendance-statuses");
    const data = await res.json();
    if (!res.ok) throw new Error(data?.detail || "Failed to load attendance statuses");
    rows = Array.isArray(data.rows) ? data.rows : [];
    renderAll();
    if (showToast) setMsg("Attendance statuses reloaded.", true);
  }

  async function saveStatus(ev) {
    ev.preventDefault();
    clearMsg();
    const code = String(document.getElementById("statusCode").value || "").trim().toUpperCase();
    if (!code) {
      setMsg("Please enter a status code.", false);
      return;
    }
    document.getElementById("statusCode").value = code;
    const payload = {
      code,
      shortLabel: document.getElementById("statusShortLabel").value.trim(),
      fullLabel: document.getElementById("statusFullLabel").value.trim(),
      buttonClass: document.getElementById("statusButtonClass").value,
      sortOrder: Number(document.getElementById("statusSortOrder").value || 0),
      isPaid: document.getElementById("statusPaid").value === "paid",
      isActive: document.getElementById("statusActive").checked,
      noteRequired: document.getElementById("statusNoteRequired").checked
    };
    const btn = document.getElementById("saveStatusBtn");
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Saving...';
    try {
      const isEdit = !!editingCode;
      const res = await apiFetch(isEdit ? `/attendance-statuses/${encodeURIComponent(code)}` : "/attendance-statuses", {
        method: isEdit ? "PUT" : "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data?.detail || "Failed to save attendance status");
      statusModal.hide();
      await loadRows(false);
      setMsg(isEdit ? "Attendance status updated successfully." : "Attendance status created successfully.", true);
    } catch (err) {
      setMsg(err.message || "Failed to save attendance status", false);
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Save Status';
    }
  }

  async function deleteStatus(code) {
    clearMsg();
    if (!window.confirm(`Delete attendance status ${code}?`)) return;
    try {
      const res = await apiFetch(`/attendance-statuses/${encodeURIComponent(code)}`, { method: "DELETE" });
      const data = await res.json();
      if (!res.ok) throw new Error(data?.detail || "Failed to delete attendance status");
      await loadRows(false);
      setMsg("Attendance status deleted successfully.", true);
    } catch (err) {
      setMsg(err.message || "Failed to delete attendance status", false);
    }
  }

  function bindTableActions() {
    document.getElementById("statusTableBody")?.addEventListener("click", (ev) => {
      const btn = ev.target.closest("button[data-action][data-code]");
      if (!btn) return;
      const action = btn.getAttribute("data-action");
      const code = btn.getAttribute("data-code");
      if (action === "edit") openEditModal(code);
      if (action === "delete") deleteStatus(code);
    });
  }

  function bindFormHelpers() {
    document.getElementById("statusCode")?.addEventListener("input", (ev) => {
      ev.target.value = String(ev.target.value || "").toUpperCase().replace(/[^A-Z0-9_-]/g, "").slice(0, 12);
    });
    document.getElementById("statusCode")?.addEventListener("change", (ev) => {
      if (editingCode) return;
      const code = String(ev.target.value || "");
      const preset = BUILTINS.find((row) => row.code === code);
      if (!preset) return;
      document.getElementById("statusShortLabel").value = preset.shortLabel;
      document.getElementById("statusFullLabel").value = preset.fullLabel;
      document.getElementById("statusButtonClass").value = preset.buttonClass;
      document.getElementById("statusSortOrder").value = preset.sortOrder;
      document.getElementById("statusPaid").value = preset.isPaid ? "paid" : "unpaid";
      document.getElementById("statusActive").checked = !!preset.isActive;
      document.getElementById("statusNoteRequired").checked = !!preset.noteRequired;
    });
  }

  function init() {
    applyTheme(localStorage.getItem("hr_portal_theme") || "light");
    document.getElementById("themeToggle")?.addEventListener("click", () => {
      const current = document.documentElement.getAttribute("data-bs-theme") || "light";
      applyTheme(current === "dark" ? "light" : "dark");
    });
    statusModal = new bootstrap.Modal(document.getElementById("attendanceStatusModal"));
    document.getElementById("openCreateStatusBtn")?.addEventListener("click", openCreateModal);
    document.getElementById("reloadStatusesBtn")?.addEventListener("click", () => {
      loadRows(true).catch((err) => setMsg(err.message || "Failed to load attendance statuses", false));
    });
    document.getElementById("attendanceStatusForm")?.addEventListener("submit", saveStatus);
    bindTableActions();
    bindFormHelpers();
    loadRows(false).catch((err) => setMsg(err.message || "Failed to load attendance statuses", false));
  }

  document.addEventListener("DOMContentLoaded", init);
})();
