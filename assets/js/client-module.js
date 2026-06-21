const themeRoot = document.documentElement;
function applyTheme(theme){
  if(!themeRoot) return;
  themeRoot.setAttribute("data-bs-theme", theme);
  localStorage.setItem("hr_portal_theme", theme);
  const icon = document.getElementById("themeIcon");
  if(icon) icon.className = theme === "dark" ? "bi bi-sun" : "bi bi-moon";
}
applyTheme(localStorage.getItem("hr_portal_theme") || "light");
document.getElementById("themeToggle")?.addEventListener("click", () => {
  const current = themeRoot.getAttribute("data-bs-theme") || "light";
  applyTheme(current === "dark" ? "light" : "dark");
});

const API_BASES = ["/api", "/backend/api.php?path=/api"];
const KEY_CLIENTS_LOCAL = "hr_client_module_cache_v1";
let clients = [];
let editId = null;
let subscriptionPlans = [];

const clientTbody = document.getElementById("clientTbody");
const emptyState = document.getElementById("emptyState");
const resultCount = document.getElementById("resultCount");
const form = document.getElementById("clientForm");
const modalEl = document.getElementById("clientModal");
const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
const modalTitle = document.getElementById("clientModalTitle");
const userPasswordEl = document.getElementById("userPassword");

function safeParse(raw){
  try { return JSON.parse(raw); } catch (_e) { return null; }
}

function saveLocal(rows){
  localStorage.setItem(KEY_CLIENTS_LOCAL, JSON.stringify(Array.isArray(rows) ? rows : []));
}

function loadLocal(){
  const rows = safeParse(localStorage.getItem(KEY_CLIENTS_LOCAL));
  return Array.isArray(rows) ? rows : [];
}

function normalizeRow(r){
  return {
    id: Number(r.id || 0),
    companyName: String(r.companyName || "").trim(),
    companyAddress: String(r.companyAddress || "").trim(),
    companyRegNo: String(r.companyRegNo || "").trim(),
    companyPAN: String(r.companyPAN || "").trim().toUpperCase(),
    companyTAN: String(r.companyTAN || "").trim().toUpperCase(),
    companyGSTIN: String(r.companyGSTIN || "").trim().toUpperCase(),
    companyContactNo: String(r.companyContactNo || "").trim(),
    companyEmail: String(r.companyEmail || "").trim().toLowerCase(),
    userId: String(r.userId || "").trim().toLowerCase(),
    userPassword: String(r.userPassword || "").trim(),
    subscriptionPlanId: Number(r.subscriptionPlanId || 0),
    subscriptionTypeName: String(r.subscriptionTypeName || "").trim()
  };
}
function renderSubscriptionPlans(selectedId){
  const sel = document.getElementById("subscriptionPlanId");
  if(!sel) return;
  const rows = subscriptionPlans;
  if(!rows.length){
    sel.innerHTML = `<option value=\"\">No Subscription Plans</option>`;
    sel.value = "";
    return;
  }
  sel.innerHTML = rows.map((x) => `<option value="${Number(x.id)}">${escapeHtml(x.planName || ("Plan " + x.id))}</option>`).join("");
  const pick = Number(selectedId || rows[0].id || 0);
  const hit = rows.find((x) => Number(x.id) === pick);
  sel.value = String(hit ? hit.id : rows[0].id);
}

async function apiFetch(path, options = {}){
  const errors = [];
  for(const base of API_BASES){
    const url = `${base}${path}`;
    try {
      const res = await fetch(url, options);
      if(res.status === 404 || res.status === 405){ errors.push(`${url}:${res.status}`); continue; }
      return res;
    } catch (e){
      errors.push(`${url}:${e}`);
    }
  }
  throw new Error("API unavailable " + errors.join(" | "));
}
async function fetchSubscriptionPlans(){
  try {
    const res = await apiFetch("/subscription-plans");
    if(!res.ok) throw new Error("load plans failed");
    const data = await res.json();
    subscriptionPlans = Array.isArray(data.rows) ? data.rows : [];
  } catch (_e){
    subscriptionPlans = [];
  }
  renderSubscriptionPlans(subscriptionPlans[0]?.id || 0);
}

async function fetchClients(){
  try {
    const res = await apiFetch("/clients");
    if(!res.ok) throw new Error("load failed");
    const data = await res.json();
    clients = Array.isArray(data.rows) ? data.rows.map(normalizeRow) : [];
    saveLocal(clients);
  } catch (_e){
    clients = loadLocal();
  }
}

async function createClient(row){
  try {
    const res = await apiFetch("/clients", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(row)
    });
    if(!res.ok) throw new Error(await res.text());
  } catch (_e){
    const local = loadLocal();
    const nextId = local.reduce((max, x) => Math.max(max, Number(x.id || 0)), 0) + 1;
    local.unshift({ ...row, id: nextId });
    saveLocal(local);
  }
}

async function updateClient(row){
  try {
    const res = await apiFetch(`/clients/${encodeURIComponent(row.id)}`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(row)
    });
    if(!res.ok) throw new Error(await res.text());
  } catch (_e){
    const local = loadLocal();
    const idx = local.findIndex((x) => Number(x.id) === Number(row.id));
    if(idx < 0) throw new Error("Client not found");
    local[idx] = { ...local[idx], ...row };
    saveLocal(local);
  }
}

async function deleteClient(id){
  try {
    const res = await apiFetch(`/clients/${encodeURIComponent(id)}`, { method: "DELETE" });
    if(!res.ok) throw new Error(await res.text());
  } catch (_e){
    const local = loadLocal().filter((x) => Number(x.id) !== Number(id));
    saveLocal(local);
  }
}

function renderTable(){
  const q = String(document.getElementById("searchInput").value || "").toLowerCase().trim();
  const filtered = clients.filter((c) => {
    const txt = `${c.companyName} ${c.companyRegNo} ${c.companyPAN} ${c.companyGSTIN} ${c.companyContactNo} ${c.companyEmail} ${c.userId}`.toLowerCase();
    return q ? txt.includes(q) : true;
  });

  clientTbody.innerHTML = filtered.map((c, idx) => `
    <tr>
      <td>${idx + 1}</td>
      <td class="fw-semibold">${escapeHtml(c.companyName || "-")}</td>
      <td>${escapeHtml(c.userId || "-")}</td>
      <td>${escapeHtml(c.companyContactNo || "-")}</td>
      <td>${escapeHtml(c.companyPAN || "-")}</td>
      <td>${escapeHtml(c.companyGSTIN || "-")}</td>
      <td>${escapeHtml(c.companyRegNo || "-")}</td>
      <td class="text-end">
        <div class="btn-group">
          <button class="btn btn-outline-secondary btn-sm" title="Edit" aria-label="Edit" onclick="editClient(${Number(c.id)})"><i class="bi bi-pencil"></i></button>
          <button class="btn btn-outline-danger btn-sm" title="Delete" aria-label="Delete" onclick="removeClient(${Number(c.id)})"><i class="bi bi-trash"></i></button>
        </div>
      </td>
    </tr>
  `).join("");

  emptyState.classList.toggle("d-none", filtered.length !== 0);
  resultCount.textContent = `${filtered.length} record${filtered.length === 1 ? "" : "s"}`;
}

function escapeHtml(value){
  return String(value || "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function clearForm(){
  form.reset();
  form.classList.remove("was-validated");
  editId = null;
  modalTitle.textContent = "Add Client";
  if(userPasswordEl){
    userPasswordEl.required = true;
    userPasswordEl.placeholder = "Enter password";
  }
  renderSubscriptionPlans(subscriptionPlans[0]?.id || 0);
}

function getFormRow(){
  return normalizeRow({
    id: editId,
    companyName: document.getElementById("companyName").value,
    companyAddress: document.getElementById("companyAddress").value,
    companyRegNo: document.getElementById("companyRegNo").value,
    companyPAN: document.getElementById("companyPAN").value,
    companyTAN: document.getElementById("companyTAN").value,
    companyGSTIN: document.getElementById("companyGSTIN").value,
    companyContactNo: document.getElementById("companyContactNo").value,
    companyEmail: document.getElementById("companyEmail").value,
    userId: document.getElementById("userId").value,
    userPassword: document.getElementById("userPassword").value,
    subscriptionPlanId: Number(document.getElementById("subscriptionPlanId").value || 0)
  });
}

window.editClient = async function(id){
  const row = clients.find((x) => Number(x.id) === Number(id));
  if(!row) return;
  editId = Number(row.id);
  modalTitle.textContent = "Edit Client";
  document.getElementById("companyName").value = row.companyName || "";
  document.getElementById("companyAddress").value = row.companyAddress || "";
  document.getElementById("companyRegNo").value = row.companyRegNo || "";
  document.getElementById("companyPAN").value = row.companyPAN || "";
  document.getElementById("companyTAN").value = row.companyTAN || "";
  document.getElementById("companyGSTIN").value = row.companyGSTIN || "";
  document.getElementById("companyContactNo").value = row.companyContactNo || "";
  document.getElementById("companyEmail").value = row.companyEmail || "";
  document.getElementById("userId").value = row.userId || "";
  if(userPasswordEl){
    userPasswordEl.value = "";
    userPasswordEl.required = false;
    userPasswordEl.placeholder = "Leave blank to keep existing password";
  }
  renderSubscriptionPlans(row.subscriptionPlanId || subscriptionPlans[0]?.id || 0);
  modal.show();
};

window.removeClient = async function(id){
  if(!confirm("Delete this client?")) return;
  try {
    await deleteClient(id);
    await fetchClients();
    renderTable();
  } catch (e){
    alert("Delete failed: " + (e.message || e));
  }
};

form.addEventListener("submit", async (event) => {
  event.preventDefault();
  event.stopPropagation();
  if(!form.checkValidity()){
    form.classList.add("was-validated");
    return;
  }

  const row = getFormRow();
  try {
    if(editId) await updateClient(row);
    else await createClient(row);
    await fetchClients();
    renderTable();
    clearForm();
    modal.hide();
  } catch (e){
    alert("Save failed: " + (e.message || e));
  }
});

modalEl.addEventListener("hidden.bs.modal", clearForm);
document.getElementById("searchInput")?.addEventListener("input", renderTable);

(async function init(){
  await fetchSubscriptionPlans();
  await fetchClients();
  renderTable();
})();
