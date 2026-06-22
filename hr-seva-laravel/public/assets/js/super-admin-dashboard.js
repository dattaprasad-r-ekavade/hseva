const API_BASES = ["/api", "/backend/api.php?path=/api"];
const KEY_SUPERADMIN_CLIENT_ID = "hr_superadmin_selected_client_id_v1";

const el = (id) => document.getElementById(id);

function applyTheme(theme){
  document.documentElement.setAttribute("data-bs-theme", theme);
  localStorage.setItem("hr_portal_theme", theme);
  const icon = el("themeIcon");
  if(icon) icon.className = theme === "dark" ? "bi bi-sun" : "bi bi-moon";
}
applyTheme(localStorage.getItem("hr_portal_theme") || "light");
el("themeToggle")?.addEventListener("click", () => {
  const current = document.documentElement.getAttribute("data-bs-theme") || "light";
  applyTheme(current === "dark" ? "light" : "dark");
});

function showMsg(text, ok = false){
  const m = el("dashMsg");
  if(!m) return;
  m.className = `alert ${ok ? "alert-success" : "alert-danger"}`;
  m.textContent = text;
  m.classList.remove("d-none");
}

async function apiFetch(path, options = {}){
  const errors = [];
  for(const base of API_BASES){
    const url = `${base}${path}`;
    try {
      const res = await fetch(url, { cache: "no-store", ...options });
      if(res.status === 404 || res.status === 405){ errors.push(`${url}:${res.status}`); continue; }
      return res;
    } catch (e){ errors.push(`${url}:${e}`); }
  }
  throw new Error("API unavailable " + errors.join(" | "));
}

function fmtDate(iso){
  if(!iso) return "-";
  const d = new Date(iso);
  if(Number.isNaN(d.getTime())) return "-";
  return d.toLocaleString();
}

function escapeHtml(v){
  return String(v || "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function selectedClientId(){
  const id = Number(localStorage.getItem(KEY_SUPERADMIN_CLIENT_ID) || 0);
  return Number.isFinite(id) && id > 0 ? id : 0;
}

function setScopeText(client){
  const headerSub = document.querySelector(".topbar .me-auto .small.text-muted-3");
  const overviewSub = document.querySelector(".glass .small.text-muted-3");
  const isScoped = Number(client?.id || 0) > 0;
  if(headerSub){
    headerSub.textContent = isScoped
      ? `Selected client: ${String(client.companyName || "Client")} (${String(client.userId || "-")})`
      : "Control clients, access types, and platform usage";
  }
  if(overviewSub){
    overviewSub.textContent = isScoped ? "Selected client in Client Module" : "Configured in Client Module";
  }
}

async function loadDashboard(){
  try {
    const scopeClientId = selectedClientId();
    const shiftPath = scopeClientId > 0 ? `/shift/dashboard?companyId=${scopeClientId}` : "/shift/dashboard?all=1";
    const [clientsRes, typesRes, shiftRes] = await Promise.all([
      apiFetch("/clients"),
      apiFetch("/access-types"),
      apiFetch(shiftPath)
    ]);
    const clientsData = await clientsRes.json();
    const typesData = await typesRes.json();
    const shiftData = await shiftRes.json();

    if(!clientsRes.ok) throw new Error(clientsData?.detail || "Failed to load clients");
    if(!typesRes.ok) throw new Error(typesData?.detail || "Failed to load access types");
    if(!shiftRes.ok) throw new Error(shiftData?.detail || "Failed to load shift dashboard");

    const clients = Array.isArray(clientsData.rows) ? clientsData.rows : [];
    const types = Array.isArray(typesData.rows) ? typesData.rows : [];
    const scopedClients = scopeClientId > 0
      ? clients.filter((c) => Number(c.id || 0) === scopeClientId)
      : clients;
    const scopedClient = scopedClients[0] || null;

    setScopeText(scopedClient);

    el("kpiClients").textContent = String(scopedClients.length);
    el("kpiUsers").textContent = String(new Set(scopedClients.map((c) => (c.userId || "").toLowerCase()).filter(Boolean)).size);
    el("kpiAccessTypes").textContent = String(types.length);
    el("kpiCustomTypes").textContent = String(types.filter((t) => !t.isSystem).length);

    const typeMap = new Map(types.map((t) => [String(t.code || "").toLowerCase(), t.name || t.code]));

    const tbody = el("clientsTbody");
    const rows = scopedClients.slice().sort((a, b) => String(b.__updatedAt || "").localeCompare(String(a.__updatedAt || "")));
    tbody.innerHTML = rows.length ? rows.map((c, idx) => {
      const typeCode = String(c.accessType || "custom").toLowerCase();
      const typeName = typeMap.get(typeCode) || c.accessType || "custom";
      return `
        <tr>
          <td class="fw-semibold">${idx + 1}</td>
          <td class="fw-semibold">${escapeHtml(c.companyName || "-")}</td>
          <td>${escapeHtml(c.userId || "-")}</td>
          <td><span class="badge text-bg-light border">${escapeHtml(typeName)}</span></td>
          <td>${escapeHtml(c.companyContactNo || "-")}</td>
          <td>${escapeHtml(fmtDate(c.__updatedAt))}</td>
        </tr>
      `;
    }).join("") : '<tr><td colspan="6" class="text-center text-muted-3 py-3">No clients found.</td></tr>';

    const typeList = el("typeList");
    typeList.innerHTML = types.length ? types.map((t) => {
      const enabled = Object.values(t.permissions || {}).filter(Boolean).length;
      return `
        <li class="list-group-item d-flex align-items-center justify-content-between px-0">
          <div>
            <div class="fw-semibold">${escapeHtml(t.name || t.code)}</div>
            <div class="small text-muted-3">${escapeHtml(t.code || "")}</div>
          </div>
          <span class="badge text-bg-light border">${enabled} modules</span>
        </li>
      `;
    }).join("") : '<li class="list-group-item px-0 text-muted-3">No access types found.</li>';

    const shiftWrap = el("shiftWidgetCards");
    if(shiftWrap){
      const t = (shiftData && shiftData.totals) || {};
      const cards = [
        ["Companies Using Shift", t.companiesUsingModule || 0],
        ["Active Shifts", t.activeShifts || 0],
        ["Employees Assigned", t.employeesAssignedInRoster || 0],
        ["Today Shifts", t.todayShifts || 0],
        ["Weekly Off Today", t.weeklyOffToday || 0],
        ["Leave Today", t.leaveToday || 0],
        ["Night Shift Today", t.nightShiftToday || 0],
        ["Missing Rosters", t.upcomingConflictsOrMissingRosters || 0],
        ["Without Default Shift", t.employeesWithoutDefaultShift || 0]
      ];
      shiftWrap.innerHTML = cards.map((c) => `
        <div class="col-12 col-md-6 col-xl-4">
          <div class="glass p-3">
            <div class="small text-muted-3">${escapeHtml(c[0])}</div>
            <div class="fs-3 fw-semibold">${escapeHtml(String(c[1]))}</div>
          </div>
        </div>
      `).join("");
    }
  } catch (e){
    showMsg(e.message || "Unable to load dashboard");
  }
}

loadDashboard();
window.addEventListener("hr:selected-client-changed", () => {
  loadDashboard();
});
