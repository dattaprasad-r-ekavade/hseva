(function () {
  "use strict";

  const API_BASES = ["/api", "/backend/api.php?path=/api"];
  const tbody = document.getElementById("invoiceTbody");
  const paidAmount = document.getElementById("invPaidAmount");
  const pendingAmount = document.getElementById("invPendingAmount");
  const totalAmount = document.getElementById("invTotalAmount");
  const themeToggle = document.getElementById("themeToggle");
  const themeIcon = document.getElementById("themeIcon");

  function applyTheme(theme) {
    document.documentElement.setAttribute("data-bs-theme", theme);
    localStorage.setItem("hr_portal_theme", theme);
    if (themeIcon) themeIcon.className = theme === "dark" ? "bi bi-sun" : "bi bi-moon";
  }

  function money(n) {
    return new Intl.NumberFormat("en-IN", {
      style: "currency",
      currency: "INR",
      maximumFractionDigits: 0
    }).format(Number(n || 0));
  }

  async function apiGet(path) {
    for (const base of API_BASES) {
      try {
        const res = await fetch(`${base}${path}`, { cache: "no-store" });
        if (res.status === 404 || res.status === 405) continue;
        return res;
      } catch (_e) {}
    }
    throw new Error("API unavailable");
  }

  function render(data) {
    const rows = Array.isArray(data?.rows) ? data.rows : [];
    const summary = data?.summary || {};

    paidAmount.textContent = money(summary.paid || 0);
    pendingAmount.textContent = money(summary.pending || 0);
    totalAmount.textContent = money(summary.total || 0);

    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted-3 py-3">No invoices found.</td></tr>';
      return;
    }

    tbody.innerHTML = rows.map((r, idx) => {
      const status = String(r.status || "Pending");
      const cls = status.toLowerCase() === "paid" ? "paid" : "pending";
      return `
        <tr>
          <td class="fw-semibold">${idx + 1}</td>
          <td class="fw-semibold">${r.invoiceNo || "-"}</td>
          <td>${r.issuedOn || "-"}</td>
          <td>${r.billingMonth || "-"}</td>
          <td class="text-end fw-semibold">${money(r.total || 0)}</td>
          <td><span class="status-pill ${cls}">${status}</span></td>
          <td>${r.paidOn || "-"}</td>
          <td class="text-end"><button class="btn btn-outline-secondary btn-sm" disabled>Download</button></td>
        </tr>
      `;
    }).join("");
  }

  async function init() {
    try {
      const res = await apiGet("/client-invoices");
      const data = await res.json();
      if (!res.ok) throw new Error(data?.detail || "Failed to load invoices");
      render(data);
    } catch (_e) {
      render({ rows: [], summary: {} });
    }
  }

  applyTheme(localStorage.getItem("hr_portal_theme") || "light");
  if (themeToggle) {
    themeToggle.addEventListener("click", function () {
      const current = document.documentElement.getAttribute("data-bs-theme") || "light";
      applyTheme(current === "dark" ? "light" : "dark");
    });
  }
  init();
})();

