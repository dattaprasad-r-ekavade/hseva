(function () {
  "use strict";

  const API_BASES = ["/api"];
  let rows = [];

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

  function renderAll() {
    const active = rows.filter((row) => row.isActive);
    document.getElementById("activeTypeCount").textContent = String(active.length);
    document.getElementById("inactiveTypeCount").textContent = String(rows.filter((row) => !row.isActive).length);
    document.getElementById("totalTypeCount").textContent = String(rows.length);
    document.getElementById("typePreview").innerHTML = active.length
      ? active.sort((a, b) => a.sortOrder - b.sortOrder || a.label.localeCompare(b.label)).map((row) => `<span class="badge-soft">${row.label}</span>`).join("")
      : '<div class="small text-muted-3">No active employee types yet.</div>';
    const body = document.getElementById("typeTableBody");
    body.innerHTML = rows.length
      ? rows
          .slice()
          .sort((a, b) => a.sortOrder - b.sortOrder || a.label.localeCompare(b.label))
          .map((row) => `
            <tr>
              <td><span class="fw-semibold mono">${row.code}</span></td>
              <td>${row.label}</td>
              <td><span class="badge ${row.isActive ? "text-bg-success" : "text-bg-secondary"}">${row.isActive ? "Active" : "Inactive"}</span></td>
              <td>${row.sortOrder}</td>
            </tr>
          `).join("")
      : '<tr><td colspan="4" class="text-center text-muted-3 py-4">No employee types found.</td></tr>';
  }

  async function loadRows(showToast) {
    const res = await apiFetch("/employee-types");
    const data = await res.json();
    if (!res.ok) throw new Error(data?.detail || "Failed to load employee types");
    rows = Array.isArray(data.rows) ? data.rows : [];
    renderAll();
    if (showToast) setMsg("Employee types reloaded.", true);
  }

  function init() {
    applyTheme(localStorage.getItem("hr_portal_theme") || "light");
    document.getElementById("reloadTypesBtn")?.addEventListener("click", () => loadRows(true).catch((err) => setMsg(err.message || "Reload failed", false)));
    loadRows(false).catch((err) => setMsg(err.message || "Failed to load employee types", false));
  }

  init();
})();
