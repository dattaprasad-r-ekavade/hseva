(function () {
  "use strict";

  const API_BASES = ["/api"];
  const tbody = document.getElementById("billingTbody");
  const paidAmount = document.getElementById("paidAmount");
  const pendingAmount = document.getElementById("pendingAmount");
  const totalAmount = document.getElementById("totalAmount");
  const planName = document.getElementById("billPlanName");
  const renewBtn = document.getElementById("btnRenewSubscription");
  const renewModalEl = document.getElementById("clientRenewModal");
  const renewModal = renewModalEl ? bootstrap.Modal.getOrCreateInstance(renewModalEl) : null;
  const renewForm = document.getElementById("clientRenewForm");
  const themeToggle = document.getElementById("themeToggle");
  const themeIcon = document.getElementById("themeIcon");
  let latestBillingData = null;
  let latestRows = [];
  let currentRenewPayload = null;

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
  function dateText(v) {
    const raw = String(v || "").trim();
    if (!raw) return "-";
    const d = new Date(raw);
    if (Number.isNaN(d.getTime())) return raw;
    return d.toLocaleDateString("en-IN", { day: "2-digit", month: "short", year: "numeric" });
  }
  function authSession() {
    try { return JSON.parse(sessionStorage.getItem("hr_auth_session_v1") || "null"); } catch (_e) { return null; }
  }
  function isoOrEmpty(v) {
    const raw = String(v || "").trim();
    if (!raw) return "";
    const d = new Date(raw);
    if (Number.isNaN(d.getTime())) return "";
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${y}-${m}-${day}`;
  }
  function todayISO() {
    return isoOrEmpty(new Date());
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
    latestBillingData = data || null;
    const rows = Array.isArray(data?.rows) ? data.rows : [];
    latestRows = rows.slice();
    const summary = data?.summary || {};
    const curPlan = data?.currentPlan || {};

    paidAmount.textContent = money(summary.paid || 0);
    pendingAmount.textContent = money(summary.pending || 0);
    totalAmount.textContent = money(summary.total || 0);
    planName.textContent = curPlan.planName ? `${curPlan.planName} (${curPlan.status || "-"})` : "-";

    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted-3 py-3">No billing records found.</td></tr>';
      return;
    }

    tbody.innerHTML = rows.map((r, idx) => {
      const status = String(r.status || "Pending");
      const cls = status.toLowerCase() === "paid" ? "paid" : "pending";
      return `
        <tr>
          <td class="fw-semibold">${idx + 1}</td>
          <td class="fw-semibold">${r.invoiceNo || "-"}</td>
          <td>${r.billingMonth || "-"}</td>
          <td class="text-end">${money(r.amount || 0)}</td>
          <td class="text-end">${money(r.gst || 0)}</td>
          <td class="text-end fw-semibold">${money(r.total || 0)}</td>
          <td><span class="status-pill ${cls}">${status}</span></td>
          <td>${dateText(r.dueDate)}</td>
          <td class="text-end">
            <button class="btn btn-outline-primary btn-sm btn-renew-row" data-row="${idx}" type="button">Renew</button>
          </td>
        </tr>
      `;
    }).join("");
  }

  function buildRenewPayload(rowOverride) {
    const data = latestBillingData || {};
    const rows = Array.isArray(data.rows) ? data.rows : [];
    const summary = data.summary || {};
    const currentPlan = data.currentPlan || {};
    const pendingRow = rowOverride || rows.find((r) => String(r.status || "").toLowerCase() === "pending") || rows[0] || null;
    const amount = Number(summary.pending || 0) > 0
      ? Number(summary.pending || 0)
      : Number(pendingRow?.total || 0);
    const startDate = isoOrEmpty(pendingRow?.issuedOn) || todayISO();
    const endDate = isoOrEmpty(pendingRow?.endDate) || isoOrEmpty(pendingRow?.dueDate);
    const renewalDate = isoOrEmpty(pendingRow?.dueDate) || endDate;
    return {
      clientId: Number(data.clientId || 0),
      clientName: String(data.clientName || ""),
      userId: String(authSession()?.user?.username || ""),
      planName: String(currentPlan.planName || ""),
      planStatus: String(pendingRow?.status || currentPlan.status || ""),
      invoiceNo: String(pendingRow?.invoiceNo || ""),
      billingMonth: String(pendingRow?.billingMonth || ""),
      dueDate: String(pendingRow?.dueDate || ""),
      startDate,
      endDate,
      renewalDate,
      amount: amount
    };
  }

  function fillRenewModal(payload) {
    document.getElementById("renewClientName").value = payload.clientName || "";
    document.getElementById("renewUserId").value = payload.userId || "";
    document.getElementById("renewPlanName").value = payload.planName || "-";
    document.getElementById("renewStatus").value = payload.planStatus || "-";
    document.getElementById("renewStartDate").value = dateText(payload.startDate);
    document.getElementById("renewEndDate").value = dateText(payload.endDate);
    document.getElementById("renewRenewalDate").value = dateText(payload.renewalDate);
    document.getElementById("renewAmount").value = money(payload.amount || 0);
    document.getElementById("renewNotes").value = "Renewed by Client";
  }

  function openRenewModal(rowOverride) {
    const payload = buildRenewPayload(rowOverride);
    currentRenewPayload = payload;
    if (!renewModal) {
      window.dispatchEvent(new CustomEvent("hr:renew-subscription", { detail: payload }));
      return;
    }
    fillRenewModal(payload);
    renewModal.show();
  }

  async function init() {
    try {
      const res = await apiGet("/client-billing");
      const data = await res.json();
      if (!res.ok) throw new Error(data?.detail || "Failed to load billing");
      render(data);
    } catch (_e) {
      render({ rows: [], summary: {}, currentPlan: null });
    }
  }

  applyTheme(localStorage.getItem("hr_portal_theme") || "light");
  if (themeToggle) {
    themeToggle.addEventListener("click", function () {
      const current = document.documentElement.getAttribute("data-bs-theme") || "light";
      applyTheme(current === "dark" ? "light" : "dark");
    });
  }
  renewForm?.addEventListener("submit", (ev) => {
    ev.preventDefault();
    const payload = { ...(currentRenewPayload || {}) };
    payload.notes = String(document.getElementById("renewNotes")?.value || "").trim();
    window.dispatchEvent(new CustomEvent("hr:renew-subscription", { detail: payload }));
    renewModal?.hide();
  });
  renewBtn?.addEventListener("click", () => openRenewModal(null));
  tbody?.addEventListener("click", (ev) => {
    const btn = ev.target.closest(".btn-renew-row");
    if (!btn) return;
    const idx = Number(btn.getAttribute("data-row") || -1);
    if (idx < 0 || idx >= latestRows.length) return;
    openRenewModal(latestRows[idx]);
  });
  init();
})();

