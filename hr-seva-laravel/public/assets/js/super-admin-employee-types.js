(function () {
  "use strict";

  const API_BASES = ["/api", "/backend/api.php?path=/api", "/backend/api.php?path=/api"];
  let rows = [];
  let editingCode = "";
  let typeModal = null;

  function applyTheme(theme) {
    document.documentElement.setAttribute("data-bs-theme", theme);
    localStorage.setItem("hr_portal_theme", theme);
    const icon = document.getElementById("themeIcon");
    if (icon) icon.className = theme === "dark" ? "bi bi-sun" : "bi bi-moon";
  }

  function setMsg(text, ok) {
    const box = document.getElementById("typeMsg");
    if (!box) return;
    box.className = `alert ${ok ? "alert-success" : "alert-danger"}`;
    box.textContent = text;
    box.classList.remove("d-none");
  }

  function clearMsg() {
    const box = document.getElementById("typeMsg");
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

  function renderCounts() {
    const active = rows.filter((row) => row.isActive);
    document.getElementById("activeTypeCount").textContent = String(active.length);
    document.getElementById("inactiveTypeCount").textContent = String(rows.filter((row) => !row.isActive).length);
    document.getElementById("totalTypeCount").textContent = String(rows.length);
  }

  function renderPreview() {
    const holder = document.getElementById("typePreview");
    if (!holder) return;
    const activeRows = rows.filter((row) => row.isActive).sort((a, b) => a.sortOrder - b.sortOrder || a.label.localeCompare(b.label));
    holder.innerHTML = activeRows.length
      ? activeRows.map((row) => `<span class="badge-soft">${row.label}</span>`).join("")
      : '<div class="small text-muted-3">No active employee types yet.</div>';
  }

  function renderTable() {
    const body = document.getElementById("typeTableBody");
    if (!body) return;
    if (!rows.length) {
      body.innerHTML = '<tr><td colspan="5" class="text-center text-muted-3 py-4">No employee types found.</td></tr>';
      return;
    }
    body.innerHTML = rows
      .slice()
      .sort((a, b) => a.sortOrder - b.sortOrder || a.label.localeCompare(b.label))
      .map((row) => `
        <tr>
          <td><span class="fw-semibold mono">${row.code}</span></td>
          <td>${row.label}</td>
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

  function openCreateModal() {
    clearMsg();
    editingCode = "";
    document.getElementById("employeeTypeModalTitle").textContent = "Add Employee Type";
    document.getElementById("employeeTypeForm").reset();
    document.getElementById("typeCode").readOnly = false;
    document.getElementById("typeCode").value = "";
    document.getElementById("typeLabel").value = "";
    document.getElementById("typeSortOrder").value = "0";
    document.getElementById("typeActive").checked = true;
    typeModal.show();
    document.getElementById("typeCode")?.focus();
  }

  function openEditModal(code) {
    clearMsg();
    const row = rows.find((item) => item.code === code);
    if (!row) return;
    editingCode = code;
    document.getElementById("employeeTypeModalTitle").textContent = "Edit Employee Type";
    document.getElementById("typeCode").readOnly = true;
    document.getElementById("typeCode").value = row.code || "";
    document.getElementById("typeLabel").value = row.label || "";
    document.getElementById("typeSortOrder").value = Number(row.sortOrder || 0);
    document.getElementById("typeActive").checked = !!row.isActive;
    typeModal.show();
  }

  async function loadRows(showToast) {
    clearMsg();
    const res = await apiFetch("/employee-types");
    const data = await res.json();
    if (!res.ok) throw new Error(data?.detail || "Failed to load employee types");
    rows = Array.isArray(data.rows) ? data.rows : [];
    renderAll();
    if (showToast) setMsg("Employee types reloaded.", true);
  }

  async function saveType(ev) {
    ev.preventDefault();
    clearMsg();
    const code = String(document.getElementById("typeCode").value || "").trim().toUpperCase();
    if (!code) {
      setMsg("Please enter an employee type code.", false);
      return;
    }
    document.getElementById("typeCode").value = code;
    const payload = {
      code,
      label: document.getElementById("typeLabel").value.trim(),
      sortOrder: Number(document.getElementById("typeSortOrder").value || 0),
      isActive: document.getElementById("typeActive").checked
    };
    const btn = document.getElementById("saveTypeBtn");
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Saving...';
    try {
      const isEdit = !!editingCode;
      const res = await apiFetch(isEdit ? `/employee-types/${encodeURIComponent(code)}` : "/employee-types", {
        method: isEdit ? "PUT" : "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data?.detail || "Failed to save employee type");
      typeModal.hide();
      await loadRows(false);
      setMsg(isEdit ? "Employee type updated successfully." : "Employee type created successfully.", true);
    } catch (err) {
      setMsg(err.message || "Failed to save employee type", false);
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Save Type';
    }
  }

  async function deleteType(code) {
    clearMsg();
    if (!window.confirm(`Delete employee type ${code}?`)) return;
    try {
      const res = await apiFetch(`/employee-types/${encodeURIComponent(code)}`, { method: "DELETE" });
      const data = await res.json();
      if (!res.ok) throw new Error(data?.detail || "Failed to delete employee type");
      await loadRows(false);
      setMsg("Employee type deleted successfully.", true);
    } catch (err) {
      setMsg(err.message || "Failed to delete employee type", false);
    }
  }

  function bindTableActions() {
    document.getElementById("typeTableBody")?.addEventListener("click", (ev) => {
      const btn = ev.target.closest("button[data-action][data-code]");
      if (!btn) return;
      const action = btn.getAttribute("data-action");
      const code = btn.getAttribute("data-code");
      if (action === "edit") openEditModal(code);
      if (action === "delete") deleteType(code);
    });
  }

  function init() {
    applyTheme(localStorage.getItem("hr_portal_theme") || "light");
    typeModal = bootstrap.Modal.getOrCreateInstance(document.getElementById("employeeTypeModal"));
    document.getElementById("openCreateTypeBtn")?.addEventListener("click", openCreateModal);
    document.getElementById("reloadTypesBtn")?.addEventListener("click", () => loadRows(true).catch((err) => setMsg(err.message || "Reload failed", false)));
    document.getElementById("employeeTypeForm")?.addEventListener("submit", saveType);
    document.getElementById("typeCode")?.addEventListener("input", (ev) => {
      ev.target.value = String(ev.target.value || "").toUpperCase().replace(/[^A-Z0-9_-]/g, "").slice(0, 24);
    });
    bindTableActions();
    loadRows(false).catch((err) => setMsg(err.message || "Failed to load employee types", false));
  }

  init();
})();
