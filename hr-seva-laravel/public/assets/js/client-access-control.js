const API_BASES = ["/api"];
const KEY_AUTH = "hr_auth_session_v1";
const KEY_SUPERADMIN_CLIENT_ID = "hr_superadmin_selected_client_id_v1";
const KEY_SUPERADMIN_CLIENT_LABEL = "hr_superadmin_selected_client_label_v1";

const PERM_FIELDS = [
  ["dashboard", "Dashboard"],
  ["clientModule", "Client Module"],
  ["employeeMaster", "Employee Master"],
  ["employeeType", "Employee Type"],
  ["salarySheet", "Salary Sheet"],
  ["payslips", "Payslips"],
  ["compliance", "Compliance Calendar"],
  ["attendance", "Attendance"],
  ["attendanceStatus", "Attendance Status"],
  ["shiftRoster", "Shift / Roster"],
  ["leaveManagement", "Leave Management"],
  ["fnf", "FNF"],
  ["advanceSalary", "Advance Salary"],
  ["gratuity", "Gratuity"],
  ["bonus", "Bonus"],
  ["incentive", "Incentive"],
  ["pfSheet", "PF Sheet"],
  ["pfReturn", "PF Return"],
  ["esicSheet", "ESIC Sheet"],
  ["esicReturn", "ESIC Return"],
  ["ecrSheet", "ECR Sheet"],
  ["controlPage", "Control Page"],
  ["companyProfile", "Company Profile"],
  ["subscriptions", "Subscriptions"],
  ["billing", "Billing"],
  ["invoices", "Invoices"],
  ["accessControl", "Roles"]
];
let permissions = {};
let accessType = "custom";
let accessTypes = [];
let editingTypeCode = "";
let currentClientId = 0;

const newAccessTypeName = document.getElementById("newAccessTypeName");
const permGrid = document.getElementById("permGrid");
const accessTypeTbody = document.getElementById("accessTypeTbody");
const accessTypeCount = document.getElementById("accessTypeCount");
const accessTypeEmpty = document.getElementById("accessTypeEmpty");
const msg = document.getElementById("msg");
const accessTargetInfo = document.getElementById("accessTargetInfo");

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
  msg.className = `alert ${ok ? "alert-success" : "alert-danger"}`;
  msg.textContent = text;
  msg.classList.remove("d-none");
}
function getAuthSession(){
  try { return JSON.parse(sessionStorage.getItem(KEY_AUTH) || "null"); }
  catch(_e){ return null; }
}
function resolveClientId(){
  const auth = getAuthSession();
  const role = String(auth?.user?.role || "").toLowerCase();
  const tokenClientId = Number(auth?.user?.clientId || 0);
  if(tokenClientId > 0) return tokenClientId;
  if(role === "super_admin"){
    const selected = Number(localStorage.getItem(KEY_SUPERADMIN_CLIENT_ID) || 0);
    if(selected > 0) return selected;
  }
  return 0;
}
function syncSessionPermissionsIfCurrentClient(permissions){
  const auth = getAuthSession();
  if(!auth || !auth.user) return;
  const cid = Number(auth.user.clientId || 0);
  if(cid <= 0 || cid !== currentClientId) return;
  auth.user.permissions = Object.assign({}, permissions || {});
  sessionStorage.setItem(KEY_AUTH, JSON.stringify(auth));
}

async function apiFetch(path, options = {}){
  const errors = [];
  for(const base of API_BASES){
    const url = `${base}${path}`;
    try {
      const res = await fetch(url, options);
      if(res.status === 404 || res.status === 405){ errors.push(`${url}:${res.status}`); continue; }
      return res;
    } catch (e){ errors.push(`${url}:${e}`); }
  }
  throw new Error("API unavailable " + errors.join(" | "));
}

function renderAccessTypeOptions(selectedCode){
  const rows = accessTypes.length ? accessTypes : [{ code: "custom", name: "Custom" }];
  const target = String(selectedCode || "custom").toLowerCase();
  const hit = rows.find((r) => String(r.code).toLowerCase() === target);
  accessType = hit ? hit.code : (rows[0]?.code || "custom");
}
function renderAccessTypeTable(){
  if(!accessTypeTbody) return;
  const rows = accessTypes.filter((x) => String(x.code || "").toLowerCase() !== "custom");
  accessTypeCount.textContent = String(rows.length);
  accessTypeTbody.innerHTML = rows.map((r, idx) => {
    const enabled = Object.values(r.permissions || {}).filter(Boolean).length;
    const isSystem = !!r.isSystem;
    const displayName = String(r.name || r.code || "").trim() || "Custom";
    return `
      <tr>
        <td>${idx + 1}</td>
        <td class="fw-semibold">${escapeHtml(displayName)}</td>
        <td>${enabled}</td>
        <td>${isSystem ? '<span class="badge text-bg-secondary">Yes</span>' : '<span class="badge text-bg-light">No</span>'}</td>
        <td class="text-end">
          <div class="btn-group">
            <button class="btn btn-outline-primary btn-sm" title="Apply" aria-label="Apply" onclick="applyTypeRow('${escapeHtml(r.code)}')"><i class="bi bi-check2-circle"></i></button>
            <button class="btn btn-outline-secondary btn-sm" title="Edit" aria-label="Edit" onclick="editTypeRow('${escapeHtml(r.code)}')"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-outline-danger btn-sm" title="Delete" aria-label="Delete" ${isSystem ? "disabled" : ""} onclick="deleteTypeRow('${escapeHtml(r.code)}')"><i class="bi bi-trash"></i></button>
          </div>
        </td>
      </tr>
    `;
  }).join("");
  accessTypeEmpty.classList.toggle("d-none", rows.length !== 0);
}

function renderPermGrid(){
  permGrid.innerHTML = PERM_FIELDS.map(([key, label]) => {
    const checked = permissions[key] !== false;
    return `
      <div class="col-md-4 col-lg-3">
        <label class="perm-item">
          <input type="checkbox" class="form-check-input me-2" data-key="${key}" ${checked ? "checked" : ""}>
          <span>${label}</span>
        </label>
      </div>
    `;
  }).join("");
}
function applyAccessType(type){
  const t = String(type || "").toLowerCase();
  const row = accessTypes.find((x) => String(x.code || "").toLowerCase() === t);
  if(!row) return;
  permissions = Object.assign({}, row.permissions || {});
  accessType = row.code;
  renderPermGrid();
}
async function loadClientAccess(){
  currentClientId = resolveClientId();
  if(currentClientId <= 0){
    if(accessTargetInfo){
      accessTargetInfo.textContent = "Select a client from the top search picker to manage access.";
    }
    permissions = Object.assign({}, accessTypes.find((x) => String(x.code).toLowerCase() === "full_access")?.permissions || {});
    accessType = "full_access";
    renderPermGrid();
    return;
  }
  const res = await apiFetch(`/access-control/${currentClientId}`);
  const data = await res.json();
  if(!res.ok) throw new Error(data?.detail || "Failed to load client access");
  const row = data?.row || {};
  permissions = Object.assign({}, row.permissions || {});
  accessType = String(row.accessType || "custom");
  renderPermGrid();
  if(accessTargetInfo){
    const label = String(localStorage.getItem(KEY_SUPERADMIN_CLIENT_LABEL) || "").trim();
    accessTargetInfo.textContent = label
      ? `Editing access permissions for ${label}.`
      : "Editing access permissions for the selected client.";
  }
}
async function saveClientAccess(){
  if(currentClientId <= 0){
    showMsg("Select a client first from top client picker.", false);
    return;
  }
  const payload = { accessType: accessType || "custom", permissions: readPermGrid() };
  const res = await apiFetch(`/access-control/${currentClientId}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload)
  });
  const data = await res.json();
  if(!res.ok) throw new Error(data?.detail || "Failed to save access");
  const row = data?.row || {};
  permissions = Object.assign({}, row.permissions || payload.permissions || {});
  renderPermGrid();
  syncSessionPermissionsIfCurrentClient(permissions);
  showMsg("Access permissions updated.", true);
}

function readPermGrid(){
  const out = {};
  permGrid.querySelectorAll("input[data-key]").forEach((el) => {
    out[el.dataset.key] = !!el.checked;
  });
  return out;
}

function setAllPerm(value){
  permGrid.querySelectorAll("input[data-key]").forEach((el) => { el.checked = value; });
}

function escapeHtml(value){
  return String(value || "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

async function loadAccessTypes(selectedCode){
  const res = await apiFetch("/access-types");
  if(!res.ok) throw new Error("Failed to load access types");
  const data = await res.json();
  accessTypes = Array.isArray(data.rows) ? data.rows : [];
  if(!accessTypes.some((x) => String(x.code || "").toLowerCase() === "custom")){
    accessTypes.push({ code: "custom", name: "Custom", permissions: {} });
  }
  renderAccessTypeOptions(selectedCode);
  renderAccessTypeTable();
}
async function createAccessTypeFromSelection(){
  const name = String(newAccessTypeName?.value || "").trim();
  if(!name){
    showMsg("Enter access type name first.", false);
    return;
  }
  const payload = { name, permissions: readPermGrid() };
  const isEdit = editingTypeCode !== "";
  const res = await apiFetch(isEdit ? `/access-types/${encodeURIComponent(editingTypeCode)}` : "/access-types", {
    method: isEdit ? "PUT" : "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload)
  });
  const data = await res.json();
  if(!res.ok) throw new Error(data?.detail || (isEdit ? "Failed to update access type" : "Failed to create access type"));
  await loadAccessTypes(data?.row?.code || "custom");
  applyAccessType(data?.row?.code || "custom");
  if(newAccessTypeName) newAccessTypeName.value = "";
  editingTypeCode = "";
  const btn = document.getElementById("btnCreateType");
  if(btn) btn.innerHTML = '<i class="bi bi-plus-lg"></i> Save As New Type';
  showMsg(isEdit ? "Access type updated." : "Access type created.", true);
}
window.applyTypeRow = function(code){
  applyAccessType(code);
};
window.editTypeRow = function(code){
  const row = accessTypes.find((x) => String(x.code || "").toLowerCase() === String(code || "").toLowerCase());
  if(!row) return;
  editingTypeCode = row.code;
  if(newAccessTypeName) newAccessTypeName.value = row.name || row.code;
  applyAccessType(row.code);
  const btn = document.getElementById("btnCreateType");
  if(btn) btn.innerHTML = '<i class="bi bi-pencil"></i> Update Type';
};
window.deleteTypeRow = async function(code){
  const row = accessTypes.find((x) => String(x.code || "").toLowerCase() === String(code || "").toLowerCase());
  if(!row || row.isSystem) return;
  if(!confirm(`Delete access type "${row.name}"?`)) return;
  try {
    const res = await apiFetch(`/access-types/${encodeURIComponent(row.code)}`, { method: "DELETE" });
    const data = await res.json();
    if(!res.ok) throw new Error(data?.detail || "Delete failed");
    await loadAccessTypes("custom");
    showMsg("Access type deleted.", true);
  } catch (e){
    showMsg(e.message || "Delete access type failed", false);
  }
};

document.getElementById("btnAll")?.addEventListener("click", () => setAllPerm(true));
document.getElementById("btnNone")?.addEventListener("click", () => setAllPerm(false));
document.getElementById("btnSaveAccess")?.addEventListener("click", async () => {
  try { await saveClientAccess(); }
  catch (e) { showMsg(e.message || "Save access failed", false); }
});
document.getElementById("btnCreateType")?.addEventListener("click", async () => {
  try { await createAccessTypeFromSelection(); }
  catch (e) { showMsg(e.message || "Create access type failed", false); }
});
permGrid.addEventListener("change", () => {
  accessType = "custom";
});

(async function init(){
  try {
    await loadAccessTypes("custom");
    await loadClientAccess();
  } catch (e){
    showMsg(e.message || "Unable to initialize", false);
  }
})();
