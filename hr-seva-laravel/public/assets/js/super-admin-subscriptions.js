const API_BASES = ["/api", "/backend/api.php?path=/api"];
const tbody = document.getElementById("subsTbody");
const form = document.getElementById("subForm");
const modalEl = document.getElementById("subscriptionModal");
const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
const subModalTitle = document.getElementById("subModalTitle");

const clientSubsTbody = document.getElementById("clientSubsTbody");
const clientSubsCount = document.getElementById("clientSubsCount");
const renewModalEl = document.getElementById("renewSubscriptionModal");
const renewModal = renewModalEl ? bootstrap.Modal.getOrCreateInstance(renewModalEl) : null;
const renewForm = document.getElementById("renewSubForm");

let plans = [];
let clients = [];
let subscriptionRows = [];
let editId = null;
let accessTypes = [];

function applyTheme(theme){
  document.documentElement.setAttribute("data-bs-theme", theme);
  localStorage.setItem("hr_portal_theme", theme);
  const icon = document.getElementById("themeIcon");
  if(icon) icon.className = theme === "dark" ? "bi bi-sun" : "bi bi-moon";
}
applyTheme(localStorage.getItem("hr_portal_theme") || "light");
document.getElementById("themeToggle")?.addEventListener("click", () => {
  const current = document.documentElement.getAttribute("data-bs-theme") || "light";
  applyTheme(current === "dark" ? "light" : "dark");
});

function showMsg(text, ok = true){
  const m = document.getElementById("subMsg");
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

function money(n){
  return new Intl.NumberFormat("en-IN", { style: "currency", currency: "INR", maximumFractionDigits: 0 }).format(Number(n || 0));
}

function statusBadge(status){
  const s = String(status || "").toLowerCase();
  if(s.includes("inactive") || s.includes("expired") || s.includes("cancel")) return '<span class="badge text-bg-secondary">'+escapeHtml(status || "Inactive")+'</span>';
  if(s.includes("pending")) return '<span class="badge text-bg-warning text-dark">Pending</span>';
  return '<span class="badge text-bg-success">'+escapeHtml(status || "Active")+'</span>';
}

function fmtDate(v){
  const raw = String(v || "").trim();
  if(!raw) return "-";
  const d = new Date(raw);
  if(Number.isNaN(d.getTime())) return raw;
  return d.toLocaleDateString("en-IN", { day: "2-digit", month: "short", year: "numeric" });
}

function toISODate(d){
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

function addMonths(dateStr, months){
  const base = new Date(dateStr + "T00:00:00");
  if(Number.isNaN(base.getTime())) return "";
  const d = new Date(base.getFullYear(), base.getMonth(), base.getDate());
  d.setMonth(d.getMonth() + Number(months || 0));
  d.setDate(d.getDate() - 1);
  return toISODate(d);
}

function nextDate(dateStr){
  const d = new Date(dateStr + "T00:00:00");
  if(Number.isNaN(d.getTime())) return "";
  d.setDate(d.getDate() + 1);
  return toISODate(d);
}

function todayISO(){
  return toISODate(new Date());
}

function renderAccessTypeOptions(selected){
  const sel = document.getElementById("subAccessTypeCode");
  if(!sel) return;
  const rows = accessTypes.length ? accessTypes : [{ code: "full_access", name: "Full Access" }];
  sel.innerHTML = rows.map((r) => `<option value="${escapeHtml(r.code)}">${escapeHtml(r.name)}</option>`).join("");
  const pick = String(selected || "full_access").toLowerCase();
  const hit = rows.find((r) => String(r.code || "").toLowerCase() === pick);
  sel.value = hit ? hit.code : rows[0].code;
}

function renderRenewPlanOptions(selectedId){
  const sel = document.getElementById("renewPlanId");
  if(!sel) return;
  sel.innerHTML = plans.map((p) => `<option value="${Number(p.id)}">${escapeHtml(p.planName || ("Plan " + p.id))}</option>`).join("");
  if(!plans.length) return;
  const pick = Number(selectedId || plans[0].id || 0);
  const hit = plans.find((p) => Number(p.id) === pick);
  sel.value = String(hit ? hit.id : plans[0].id);
}

function escapeHtml(v){
  return String(v || "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function renderTable(){
  if(!plans.length){
    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted-3 py-3">No plans found.</td></tr>';
    return;
  }
  tbody.innerHTML = plans.map((r, idx) => `
    <tr>
      <td class="fw-semibold">${idx + 1}</td>
      <td class="fw-semibold">${escapeHtml(r.planName || "-")}</td>
      <td>${escapeHtml(r.accessTypeName || r.accessTypeCode || "-")}</td>
      <td>${Number(r.durationMonths || 0)}</td>
      <td class="text-end">${money(r.amount || 0)}</td>
      <td>${statusBadge(r.status)}</td>
      <td>${escapeHtml(r.features || "-")}</td>
      <td class="text-end">
        <div class="btn-group">
          <button class="btn btn-outline-secondary btn-sm" onclick="editPlan(${Number(r.id)})">Edit</button>
          <button class="btn btn-outline-danger btn-sm" onclick="deletePlan(${Number(r.id)})">Delete</button>
        </div>
      </td>
    </tr>
  `).join("");
}

function latestSubsByClient(){
  const map = new Map();
  subscriptionRows.forEach((r) => {
    const id = Number(r.clientId || 0);
    if(id <= 0 || map.has(id)) return;
    map.set(id, r);
  });
  return Array.from(map.values());
}

function renderClientSubsTable(){
  if(!clientSubsTbody || !clientSubsCount) return;
  const latestMap = new Map();
  latestSubsByClient().forEach((r) => {
    latestMap.set(Number(r.clientId || 0), r);
  });
  const rows = clients.map((c) => {
    const cid = Number(c.id || 0);
    const sub = latestMap.get(cid) || null;
    return {
      clientId: cid,
      clientName: c.companyName || sub?.clientName || "-",
      userId: c.userId || sub?.userId || "-",
      planName: sub?.planName || "-",
      startDate: sub?.startDate || "",
      endDate: sub?.endDate || "",
      renewalDate: sub?.renewalDate || "",
      status: sub?.status || "Not Assigned",
      amount: Number(sub?.amount || 0)
    };
  });
  clientSubsCount.textContent = `${rows.length} record${rows.length === 1 ? "" : "s"}`;
  if(!rows.length){
    clientSubsTbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted-3 py-3">No client subscriptions found.</td></tr>';
    return;
  }
  clientSubsTbody.innerHTML = rows.map((r, idx) => `
    <tr>
      <td class="fw-semibold">${idx + 1}</td>
      <td>${escapeHtml(r.clientName || "-")}</td>
      <td>${escapeHtml(r.userId || "-")}</td>
      <td>${escapeHtml(r.planName || "-")}</td>
      <td>${fmtDate(r.startDate)}</td>
      <td>${fmtDate(r.endDate)}</td>
      <td>${fmtDate(r.renewalDate)}</td>
      <td>${statusBadge(r.status || "-")}</td>
      <td class="text-end">${money(r.amount || 0)}</td>
      <td class="text-end"><button class="btn btn-outline-primary btn-sm" onclick="openRenewModal(${Number(r.clientId)})">Renew</button></td>
    </tr>
  `).join("");
}

async function loadAccessTypes(){
  const res = await apiFetch("/access-types");
  const data = await res.json();
  if(!res.ok) throw new Error(data?.detail || "Failed to load access types");
  accessTypes = Array.isArray(data.rows) ? data.rows.map((x) => ({ code: x.code, name: x.name })) : [];
  renderAccessTypeOptions("full_access");
}

async function loadClients(){
  const res = await apiFetch("/clients");
  const data = await res.json();
  if(!res.ok) throw new Error(data?.detail || "Failed to load clients");
  clients = Array.isArray(data.rows) ? data.rows : [];
}

async function loadPlans(){
  const res = await apiFetch("/subscription-plans");
  const data = await res.json();
  if(!res.ok) throw new Error(data?.detail || "Failed to load plans");
  plans = Array.isArray(data.rows) ? data.rows : [];
  renderTable();
}

async function loadClientSubscriptions(){
  const res = await apiFetch("/subscriptions");
  const data = await res.json();
  if(!res.ok) throw new Error(data?.detail || "Failed to load subscriptions");
  subscriptionRows = Array.isArray(data.rows) ? data.rows : [];
  renderClientSubsTable();
}

function clearForm(){
  form.reset();
  form.classList.remove("was-validated");
  editId = null;
  subModalTitle.textContent = "Add Plan";
  document.getElementById("subDurationMonths").value = 12;
  document.getElementById("subStatus").value = "Active";
  renderAccessTypeOptions("full_access");
}

function getPayload(){
  return {
    planName: String(document.getElementById("subPlanName").value || "").trim(),
    accessTypeCode: String(document.getElementById("subAccessTypeCode").value || "full_access"),
    durationMonths: Number(document.getElementById("subDurationMonths").value || 12),
    amount: Number(document.getElementById("subAmount").value || 0),
    status: document.getElementById("subStatus").value,
    features: String(document.getElementById("subNotes").value || "").trim()
  };
}

function currentSubByClientId(clientId){
  return latestSubsByClient().find((r) => Number(r.clientId) === Number(clientId)) || null;
}

function recalcRenewDates(){
  const startEl = document.getElementById("renewStartDate");
  let start = startEl?.value || "";
  if(!start){
    start = todayISO();
    if(startEl) startEl.value = start;
  }
  const planId = Number(document.getElementById("renewPlanId")?.value || 0);
  const plan = plans.find((p) => Number(p.id) === planId);
  if(!plan) return;
  const endDate = addMonths(start, Number(plan.durationMonths || 1));
  document.getElementById("renewEndDate").value = endDate;
  document.getElementById("renewRenewalDate").value = endDate;
  document.getElementById("renewAmount").value = Number(plan.amount || 0);
}

window.openRenewModal = function(clientId){
  const cid = Number(clientId || 0);
  if(cid <= 0 || !renewModal) return;

  const c = clients.find((x) => Number(x.id) === cid) || null;
  const current = currentSubByClientId(cid);
  document.getElementById("renewClientId").value = String(cid);
  document.getElementById("renewClientName").value = (c && c.companyName) || (current && current.clientName) || "";
  document.getElementById("renewUserId").value = (c && c.userId) || (current && current.userId) || "";

  const matchedPlan = plans.find((p) => String(p.planName || "").toLowerCase() === String(current?.planName || "").toLowerCase());
  const planId = Number((matchedPlan && matchedPlan.id) || (c && c.subscriptionPlanId) || (plans[0] && plans[0].id) || 0);
  renderRenewPlanOptions(planId);

  const startDate = current?.endDate ? (nextDate(current.endDate) || todayISO()) : todayISO();
  document.getElementById("renewStartDate").value = startDate;
  document.getElementById("renewStatus").value = "Active";
  document.getElementById("renewNotes").value = "Renewed by Super Admin";
  recalcRenewDates();

  renewForm?.classList.remove("was-validated");
  renewModal.show();
};

window.editPlan = function(id){
  const row = plans.find((x) => Number(x.id) === Number(id));
  if(!row) return;
  editId = Number(row.id);
  subModalTitle.textContent = "Edit Plan";
  document.getElementById("subPlanName").value = row.planName || "";
  renderAccessTypeOptions(row.accessTypeCode || "full_access");
  document.getElementById("subDurationMonths").value = Number(row.durationMonths || 12);
  document.getElementById("subAmount").value = Number(row.amount || 0);
  document.getElementById("subStatus").value = row.status || "Active";
  document.getElementById("subNotes").value = row.features || "";
  modal.show();
};

window.deletePlan = async function(id){
  if(!confirm("Delete this plan?")) return;
  try {
    const res = await apiFetch(`/subscription-plans/${encodeURIComponent(id)}`, { method: "DELETE" });
    const data = await res.json();
    if(!res.ok) throw new Error(data?.detail || "Delete failed");
    await loadPlans();
    showMsg("Plan deleted.", true);
  } catch (e){
    showMsg(e.message || "Delete failed", false);
  }
};

async function assignClientPlan(clientId, planId){
  const c = clients.find((x) => Number(x.id) === Number(clientId));
  if(!c) throw new Error("Client not found");
  const payload = {
    companyName: c.companyName || "",
    companyAddress: c.companyAddress || "",
    companyRegNo: c.companyRegNo || "",
    companyPAN: c.companyPAN || "",
    companyTAN: c.companyTAN || "",
    companyGSTIN: c.companyGSTIN || "",
    companyContactNo: c.companyContactNo || "",
    userId: c.userId || "",
    subscriptionPlanId: Number(planId || 0)
  };
  const res = await apiFetch(`/clients/${Number(clientId)}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload)
  });
  const data = await res.json();
  if(!res.ok) throw new Error(data?.detail || "Unable to assign plan to client");
}

form.addEventListener("submit", async (e) => {
  e.preventDefault();
  e.stopPropagation();
  if(!form.checkValidity()){
    form.classList.add("was-validated");
    return;
  }
  const payload = getPayload();
  try {
    const res = await apiFetch(editId ? `/subscription-plans/${editId}` : "/subscription-plans", {
      method: editId ? "PUT" : "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if(!res.ok) throw new Error(data?.detail || "Save failed");
    await loadPlans();
    modal.hide();
    clearForm();
    showMsg(editId ? "Plan updated." : "Plan created.", true);
  } catch (err){
    showMsg(err.message || "Save failed", false);
  }
});

renewForm?.addEventListener("submit", async (e) => {
  e.preventDefault();
  e.stopPropagation();
  if(!renewForm.checkValidity()){
    renewForm.classList.add("was-validated");
    return;
  }
  const clientId = Number(document.getElementById("renewClientId").value || 0);
  const planId = Number(document.getElementById("renewPlanId").value || 0);
  const plan = plans.find((p) => Number(p.id) === planId);
  if(clientId <= 0 || !plan){
    showMsg("Invalid renewal details", false);
    return;
  }
  const payload = {
    clientId,
    planName: plan.planName || "Plan",
    startDate: document.getElementById("renewStartDate").value || todayISO(),
    endDate: document.getElementById("renewEndDate").value,
    renewalDate: document.getElementById("renewRenewalDate").value,
    status: document.getElementById("renewStatus").value,
    amount: Number(document.getElementById("renewAmount").value || 0),
    notes: String(document.getElementById("renewNotes").value || "").trim()
  };
  if(!payload.endDate || !payload.renewalDate){
    recalcRenewDates();
    payload.endDate = document.getElementById("renewEndDate").value;
    payload.renewalDate = document.getElementById("renewRenewalDate").value;
  }

  try {
    const res = await apiFetch("/subscriptions", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if(!res.ok) throw new Error(data?.detail || "Renewal failed");

    await assignClientPlan(clientId, planId);
    await loadClients();
    await loadClientSubscriptions();
    renewModal.hide();
    showMsg("Client subscription renewed successfully.", true);
  } catch (err){
    showMsg(err.message || "Renewal failed", false);
  }
});

modalEl.addEventListener("hidden.bs.modal", clearForm);
document.getElementById("btnAddSubscription")?.addEventListener("click", () => { clearForm(); });
document.getElementById("renewPlanId")?.addEventListener("change", recalcRenewDates);
document.getElementById("renewStartDate")?.addEventListener("change", recalcRenewDates);

(async function init(){
  try {
    await loadAccessTypes();
    await loadPlans();
    await loadClients();
    await loadClientSubscriptions();
    clearForm();
  } catch (e){
    showMsg(e.message || "Unable to initialize", false);
  }
})();

