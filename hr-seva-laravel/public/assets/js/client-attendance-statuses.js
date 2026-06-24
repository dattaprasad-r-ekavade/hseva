(function () {
  "use strict";

  const API_BASES = ["/api", "/backend/api.php?path=/api", "/backend/api.php?path=/api"];

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
    return `<span class="badge-soft">${row.shortLabel || row.code} - ${row.fullLabel || row.code}</span>`;
  }

  function renderCounts(rows) {
    const active = rows.filter((row) => row.isActive);
    document.getElementById("activeCount").textContent = String(active.length);
    document.getElementById("paidCount").textContent = String(rows.filter((row) => row.isPaid).length);
    document.getElementById("unpaidCount").textContent = String(rows.filter((row) => !row.isPaid).length);
    document.getElementById("noteCount").textContent = String(rows.filter((row) => row.noteRequired).length);
  }

  function renderPreview(rows) {
    const holder = document.getElementById("statusPreview");
    if (!holder) return;
    const activeRows = rows.filter((row) => row.isActive);
    holder.innerHTML = activeRows.length
      ? activeRows.map(statusChip).join("")
      : '<div class="small text-muted-3">No active attendance statuses available.</div>';
  }

  function renderTable(rows) {
    const body = document.getElementById("statusTableBody");
    if (!body) return;
    if (!rows.length) {
      body.innerHTML = '<tr><td colspan="6" class="text-center text-muted-3 py-4">No attendance statuses found.</td></tr>';
      return;
    }
    body.innerHTML = rows.map((row) => `
      <tr>
        <td><span class="fw-semibold mono">${row.code}</span></td>
        <td>
          <div class="fw-semibold">${row.fullLabel}</div>
          <div class="small text-muted-3">${row.shortLabel}</div>
        </td>
        <td><span class="badge ${row.isPaid ? "text-bg-success" : "text-bg-warning"}">${row.isPaid ? "Paid" : "Unpaid"}</span></td>
        <td><span class="badge ${row.noteRequired ? "text-bg-primary" : "text-bg-secondary"}">${row.noteRequired ? "Required" : "Optional"}</span></td>
        <td><span class="badge ${row.isActive ? "text-bg-success" : "text-bg-secondary"}">${row.isActive ? "Active" : "Inactive"}</span></td>
        <td>${row.sortOrder}</td>
      </tr>
    `).join("");
  }

  async function loadRows(showToast) {
    clearMsg();
    const res = await apiFetch("/attendance-statuses");
    const data = await res.json();
    if (!res.ok) throw new Error(data?.detail || "Failed to load attendance statuses");
    const rows = (Array.isArray(data.rows) ? data.rows : []).slice().sort((a, b) => Number(a.sortOrder || 0) - Number(b.sortOrder || 0) || String(a.code || "").localeCompare(String(b.code || "")));
    renderCounts(rows);
    renderPreview(rows);
    renderTable(rows);
    if (showToast) setMsg("Attendance statuses reloaded.", true);
  }

  function init() {
    applyTheme(localStorage.getItem("hr_portal_theme") || "light");
    document.getElementById("themeToggle")?.addEventListener("click", () => {
      const current = document.documentElement.getAttribute("data-bs-theme") || "light";
      applyTheme(current === "dark" ? "light" : "dark");
    });
    document.getElementById("reloadStatusesBtn")?.addEventListener("click", () => {
      loadRows(true).catch((err) => setMsg(err.message || "Failed to load attendance statuses", false));
    });
    loadRows(false).catch((err) => setMsg(err.message || "Failed to load attendance statuses", false));
  }

  document.addEventListener("DOMContentLoaded", init);
})();
