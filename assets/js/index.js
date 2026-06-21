const $ = (id) => document.getElementById(id);
  $("yr").textContent = new Date().getFullYear();

  const htmlEl = document.documentElement;
  const themeToggle = $("themeToggle");
  const themeIcon = $("themeIcon");
  const themeText = $("themeText");

  function applyTheme(theme){
    htmlEl.setAttribute("data-bs-theme", theme);
    localStorage.setItem("hr_portal_theme", theme);
    const isDark = theme === "dark";
    themeIcon.className = isDark ? "bi bi-sun" : "bi bi-moon";
    if (themeText) themeText.textContent = "";
  }
  applyTheme(localStorage.getItem("hr_portal_theme") || "light");
  themeToggle.addEventListener("click", () => {
    const current = htmlEl.getAttribute("data-bs-theme") || "light";
    applyTheme(current === "dark" ? "light" : "dark");
  });

  const API_DASH = "/api/dashboard/summary";
  const API_SUBS = "/api/subscription-info";
  const KEY_DASH = "hr_client_dash_cache_v1";

  const monthSelect = $("monthSelect");
  const monthBadge = $("monthBadge");
  const monthBadge2 = $("monthBadge2");
  const monthBadgeWorkflowDash = $("monthBadgeWorkflowDash");
  const alertsBody = $("alertsBody");
  const curPlanName = $("curPlanName");
  const curPlanMeta = $("curPlanMeta");
  const curPlanFeatures = $("curPlanFeatures");
  const plansTbody = $("plansTbody");
  const dashboardBody = $("dashboardBody") || document.querySelector(".content .container-fluid");

  function dashCacheKey(){
    try {
      const auth = JSON.parse(sessionStorage.getItem("hr_auth_session_v1") || "null");
      const role = String(auth?.user?.role || "").toLowerCase();
      if (role === "super-admin" || role === "admin") {
        const selectedClient = Number(localStorage.getItem("hr_superadmin_selected_client_id_v1") || 0);
        return `${KEY_DASH}:sa:${selectedClient || 0}`;
      }
      const clientId = Number(auth?.user?.clientId || 0);
      return `${KEY_DASH}:client:${clientId || 0}`;
    } catch (_e) {
      return `${KEY_DASH}:anon`;
    }
  }

  function fmtINR(n){
    try { return new Intl.NumberFormat("en-IN",{style:"currency",currency:"INR",maximumFractionDigits:0}).format(Number(n || 0)); }
    catch(_e){ return "Rs " + Number(n || 0); }
  }

  function monthLabel(year, month){
    return new Date(year, month - 1, 1).toLocaleDateString(undefined, { month: "short", year: "numeric" });
  }

  function buildMonthOptions(){
    const now = new Date();
    const opts = [];
    for(let i = 0; i < 12; i++){
      const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
      const y = d.getFullYear();
      const m = d.getMonth() + 1;
      const value = `${y}-${String(m).padStart(2, "0")}`;
      opts.push(`<option value="${value}" ${i===0?"selected":""}>${monthLabel(y,m)}</option>`);
    }
    monthSelect.innerHTML = opts.join("");
  }

  function renderSummary(s){
    let sessionName = "";
    try {
      const auth = JSON.parse(sessionStorage.getItem("hr_auth_session_v1") || "null");
      sessionName = String(auth?.user?.name || "").trim();
    } catch (_e) {}
    $("companyName").textContent = sessionName || s.companyName || "Company";
    $("kpiEmployees").textContent = s.employees ?? 0;
    $("kpiPaidDays").textContent = Number(s.avgPaidDays || 0).toFixed(1);
    $("kpiGross").textContent = fmtINR(s.gross || 0);
    $("kpiDed").textContent = fmtINR(s.deductions || 0);

    $("pfCount").textContent = s.pfCount ?? 0;
    $("esiCount").textContent = s.esiCount ?? 0;
    $("netTotal").textContent = fmtINR(s.netTotal || 0);
    $("payslipCount").textContent = s.payslipCount ?? 0;

    const label = s.period ? monthLabel(Number(s.period.slice(0,4)), Number(s.period.slice(5,7))) : "-";
    monthBadge.textContent = label;
    monthBadge2.textContent = label;
    if (monthBadgeWorkflowDash) monthBadgeWorkflowDash.textContent = label;

    alertsBody.innerHTML = "";
    (s.alerts || []).forEach((a) => {
      const statusClass = a.status === "Completed"
        ? "hr-status-completed"
        : (a.status === "In Progress" ? "hr-status-progress" : "hr-status-pending");
      const dueDate = a.dueDate || a.due || "-";
      const dateTime = a.dateTime || "-";
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${dueDate}</td>
        <td>${dateTime}</td>
        <td>${a.task || "-"}</td>
        <td><span class="${statusClass} fw-semibold">${a.status || "Pending"}</span></td>
        <td class="text-end">
          <a class="btn btn-outline-secondary btn-sm" href="client-compliance-calendar.html">
            ${a.action || "View"} <i class="bi bi-arrow-right-short"></i>
          </a>
        </td>`;
      alertsBody.appendChild(tr);
    });

    if(!(s.alerts || []).length){
      alertsBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted-3 py-3">No alerts for this period.</td></tr>';
    }

  }
  function renderSubscriptionInfo(info){
    const current = info?.currentPlan || null;
    const plans = Array.isArray(info?.plans) ? info.plans : [];

    if(current){
      curPlanName.textContent = current.planName || "-";
      curPlanMeta.textContent = `${Number(current.durationMonths || 0)} months | ${fmtINR(current.amount || 0)} | ${current.status || "-"}`;
      curPlanFeatures.textContent = current.features ? `Features: ${current.features}` : "";
    } else {
      curPlanName.textContent = "-";
      curPlanMeta.textContent = "No subscription plan assigned.";
      curPlanFeatures.textContent = "";
    }

    const currentId = Number(current?.id || 0);
    const others = plans.filter((p) => Number(p.id || 0) !== currentId);
    plansTbody.innerHTML = others.length ? others.map((p, idx) => `
      <tr>
        <td class="fw-semibold">${idx + 1}</td>
        <td>${p.planName || "-"}</td>
        <td>${Number(p.durationMonths || 0)} months</td>
        <td class="text-end">${fmtINR(p.amount || 0)}</td>
        <td>${p.status || "-"}</td>
      </tr>
    `).join("") : '<tr><td colspan="5" class="text-center text-muted-3 py-3">No other plans available.</td></tr>';
  }
  async function fetchSubscriptionInfo(){
    try {
      const res = await fetch(API_SUBS, { cache: "no-store" });
      if(!res.ok) throw new Error("subscription info failed");
      const data = await res.json();
      renderSubscriptionInfo(data);
    } catch (_e){
      renderSubscriptionInfo({ currentPlan: null, plans: [] });
    }
  }

  async function fetchSummary(){
    const [year, month] = (monthSelect.value || "").split("-");
    const y = Number(year);
    const m = Number(month);
    const cacheKey = dashCacheKey();
    try {
      const res = await fetch(`${API_DASH}?month=${m}&year=${y}`, { cache: "no-store" });
      if(!res.ok) throw new Error("dashboard api failed");
      const data = await res.json();
      localStorage.setItem(cacheKey, JSON.stringify({ period: monthSelect.value, data }));
      renderSummary(data);
    } catch(_e){
      const cached = JSON.parse(localStorage.getItem(cacheKey) || "null");
      if(cached && cached.data && cached.period === monthSelect.value){
        renderSummary(cached.data);
      } else {
        renderSummary({ period: `${y}-${String(m).padStart(2,"0")}`, employees:0, avgPaidDays:0, gross:0, deductions:0, pfCount:0, esiCount:0, netTotal:0, payslipCount:0, alerts:[], activity:[] });
      }
    }
  }

  function revealDashboard(){
    if (dashboardBody) dashboardBody.classList.remove("dash-loading");
  }

  monthSelect.addEventListener("change", fetchSummary);
  (async function initDashboard(){
    buildMonthOptions();
    await Promise.allSettled([fetchSummary(), fetchSubscriptionInfo()]);
    revealDashboard();
  })();
