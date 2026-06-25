(function () {
  "use strict";

  const API_BASES = ["/api"];

  const curPlanName = document.getElementById("curPlanName");
  const curPlanMeta = document.getElementById("curPlanMeta");
  const curPlanFeatures = document.getElementById("curPlanFeatures");
  const plansTbody = document.getElementById("plansTbody");
  const plansCount = document.getElementById("plansCount");
  const themeToggle = document.getElementById("themeToggle");
  const themeIcon = document.getElementById("themeIcon");

  function applyTheme(theme) {
    document.documentElement.setAttribute("data-bs-theme", theme);
    localStorage.setItem("hr_portal_theme", theme);
    if (themeIcon) themeIcon.className = theme === "dark" ? "bi bi-sun" : "bi bi-moon";
  }

  function fmtINR(n) {
    try {
      return new Intl.NumberFormat("en-IN", {
        style: "currency",
        currency: "INR",
        maximumFractionDigits: 0
      }).format(Number(n || 0));
    } catch (_e) {
      return "Rs " + Number(n || 0);
    }
  }
  function fmtDate(v) {
    const raw = String(v || "").trim();
    if (!raw) return "-";
    const d = new Date(raw);
    if (Number.isNaN(d.getTime())) return raw;
    return d.toLocaleDateString("en-IN", { day: "2-digit", month: "short", year: "numeric" });
  }
  function remainingDaysText(endDate) {
    const raw = String(endDate || "").trim();
    if (!raw) return "";
    const end = new Date(raw + "T23:59:59");
    if (Number.isNaN(end.getTime())) return "";
    const now = new Date();
    const ms = end.getTime() - now.getTime();
    const days = Math.ceil(ms / 86400000);
    if (days < 0) return "Expired";
    if (days === 0) return "Ends today";
    return `${days} day${days === 1 ? "" : "s"} left`;
  }

  function render(data) {
    const current = data?.currentPlan || null;
    const plans = Array.isArray(data?.plans) ? data.plans : [];

    if (current) {
      curPlanName.textContent = current.planName || "-";
      const parts = [
        `${Number(current.durationMonths || 0)} months`,
        `${fmtINR(current.amount || 0)}`,
        `${current.status || "-"}`
      ];
      if (current.endDate) {
        parts.push(`Ends ${fmtDate(current.endDate)}`);
        const rem = remainingDaysText(current.endDate);
        if (rem) parts.push(rem);
      }
      curPlanMeta.textContent = parts.join(" | ");
      curPlanFeatures.textContent = current.features ? `Features: ${current.features}` : "";
    } else {
      curPlanName.textContent = "-";
      curPlanMeta.textContent = "No subscription plan assigned.";
      curPlanFeatures.textContent = "";
    }

    const currentId = Number(current?.id || 0);
    const others = plans.filter((p) => Number(p.id || 0) !== currentId);
    plansCount.textContent = `${others.length} plan${others.length === 1 ? "" : "s"}`;

    if (!others.length) {
      plansTbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted-3 py-3">No other plans available.</td></tr>';
      return;
    }
    plansTbody.innerHTML = others.map((p, idx) => {
      const status = String(p.status || "-");
      const statusClass = status.toLowerCase() === "active" ? "active" : "inactive";
      return `
      <tr>
        <td class="fw-semibold">${idx + 1}</td>
        <td>${p.planName || "-"}</td>
        <td>${Number(p.durationMonths || 0)} months</td>
        <td class="text-end">${fmtINR(p.amount || 0)}</td>
        <td><span class="plan-status ${statusClass}">${status}</span></td>
      </tr>
    `;
    }).join("");
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

  async function loadSubscriptionInfo() {
    try {
      const res = await apiGet("/subscription-info");
      if (!res.ok) throw new Error("Failed to load subscription info");
      const data = await res.json();
      render(data);
    } catch (_e) {
      render({ currentPlan: null, plans: [] });
    }
  }

  applyTheme(localStorage.getItem("hr_portal_theme") || "light");
  if (themeToggle) {
    themeToggle.addEventListener("click", function () {
      const current = document.documentElement.getAttribute("data-bs-theme") || "light";
      applyTheme(current === "dark" ? "light" : "dark");
    });
  }
  loadSubscriptionInfo();
})();
