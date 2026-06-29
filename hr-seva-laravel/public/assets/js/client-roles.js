const API_BASES = ["/api"];
const KEY_AUTH = "hr_auth_session_v1";
const KEY_SUPERADMIN_CLIENT_ID = "hr_superadmin_selected_client_id_v1";

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

let roleRows = [];
let employeeRows = [];
let staffRows = [];
let rolePermissions = {};
let editingRoleCode = "";
let editingStaffEmpId = "";
let clientAccessPermissions = {};

const msg = document.getElementById("msg");
const permGrid = document.getElementById("permGrid");
const roleNameInput = document.getElementById("roleName");
const roleCodeInfo = document.getElementById("roleCodeInfo");
const roleTbody = document.getElementById("roleTbody");
const roleCount = document.getElementById("roleCount");
const roleEmpty = document.getElementById("roleEmpty");

const staffEmpId = document.getElementById("staffEmpId");
const staffUsername = document.getElementById("staffUsername");
const staffPassword = document.getElementById("staffPassword");
const staffRoleCode = document.getElementById("staffRoleCode");
const staffStatus = document.getElementById("staffStatus");
const staffEditInfo = document.getElementById("staffEditInfo");
const staffTbody = document.getElementById("staffTbody");
const staffCount = document.getElementById("staffCount");
const staffEmpty = document.getElementById("staffEmpty");

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
  if(!msg) return;
  msg.className = `alert ${ok ? "alert-success" : "alert-danger"}`;
  msg.textContent = text;
  msg.classList.remove("d-none");
}

function escapeHtml(value){
  return String(value || "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

async function apiFetch(path, options = {}){
  const errors = [];
  for(const base of API_BASES){
    const url = `${base}${path}`;
    try {
      const res = await fetch(url, options);
      if(res.status === 404 || res.status === 405){
        errors.push(`${url}:${res.status}`);
        continue;
      }
      return res;
    } catch (e){
      errors.push(`${url}:${e}`);
    }
  }
  throw new Error("API unavailable " + errors.join(" | "));
}

function getAuthSession(){
  try { return JSON.parse(sessionStorage.getItem(KEY_AUTH) || "null"); }
  catch(_e){ return null; }
}

function resolveClientId(){
  const auth = getAuthSession();
  const tokenClientId = Number(auth?.user?.clientId || 0);
  if(tokenClientId > 0) return tokenClientId;
  const selected = Number(localStorage.getItem(KEY_SUPERADMIN_CLIENT_ID) || 0);
  return selected > 0 ? selected : 0;
}

function visiblePermFields(){
  return PERM_FIELDS.filter(([key]) => clientAccessPermissions[key] !== false);
}

function buildRolePermissionsForSave(){
  const selected = readPermGrid();
  const out = {};
  PERM_FIELDS.forEach(([key]) => {
    if(clientAccessPermissions[key] === false){
      out[key] = false;
      return;
    }
    out[key] = selected[key] !== false;
  });
  return out;
}

function setAllPerm(value){
  permGrid.querySelectorAll("input[data-key]").forEach((el) => { el.checked = value; });
}
function readPermGrid(){
  const out = {};
  permGrid.querySelectorAll("input[data-key]").forEach((el) => {
    out[el.dataset.key] = !!el.checked;
  });
  return out;
}
function renderPermGrid(){
  const fields = visiblePermFields();
  const btnAll = document.getElementById("btnAll");
  const btnNone = document.getElementById("btnNone");
  const btnSaveRole = document.getElementById("btnSaveRole");
  if(!fields.length){
    permGrid.innerHTML = '<div class="col-12"><div class="alert alert-warning mb-0">No module permissions are enabled by Super Admin for this client.</div></div>';
    if(btnAll) btnAll.disabled = true;
    if(btnNone) btnNone.disabled = true;
    if(btnSaveRole) btnSaveRole.disabled = true;
    return;
  }
  if(btnAll) btnAll.disabled = false;
  if(btnNone) btnNone.disabled = false;
  if(btnSaveRole) btnSaveRole.disabled = false;
  permGrid.innerHTML = fields.map(([key, label]) => {
    const checked = rolePermissions[key] !== false;
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

function employeeLabel(emp){
  const parts = [String(emp?.id || "").trim(), String(emp?.name || "").trim()].filter(Boolean);
  return parts.length ? parts.join(" | ") : "Employee";
}

function roleNameByCode(code){
  const found = roleRows.find((x) => String(x.code || "").toLowerCase() === String(code || "").toLowerCase());
  return found ? String(found.name || found.code || "") : String(code || "");
}

function renderRoleTable(){
  const rows = roleRows.slice();
  roleCount.textContent = String(rows.length);
  roleTbody.innerHTML = rows.map((r, idx) => {
    const enabled = visiblePermFields().reduce((n, [k]) => n + ((r.permissions || {})[k] === false ? 0 : 1), 0);
    return `
      <tr>
        <td>${idx + 1}</td>
        <td class="fw-semibold">${escapeHtml(r.code)}</td>
        <td>${escapeHtml(r.name)}</td>
        <td>${enabled}</td>
        <td class="text-center">
          <div class="btn-group">
            <button class="btn btn-outline-secondary btn-sm" title="Edit" aria-label="Edit" onclick="editRoleRow('${escapeHtml(r.code)}')"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-outline-danger btn-sm" title="Delete" aria-label="Delete" onclick="deleteRoleRow('${escapeHtml(r.code)}')"><i class="bi bi-trash"></i></button>
          </div>
        </td>
      </tr>
    `;
  }).join("");
  roleEmpty.classList.toggle("d-none", rows.length !== 0);
}

function renderStaffEmployeeOptions(){
  const active = editingStaffEmpId || String(staffEmpId.value || "").trim();
  const options = ['<option value="">Select employee</option>'];
  employeeRows.forEach((e) => {
    const id = String(e.id || "").trim();
    if(!id) return;
    options.push(`<option value="${escapeHtml(id)}">${escapeHtml(employeeLabel(e))}</option>`);
  });
  staffEmpId.innerHTML = options.join("");
  if(active) staffEmpId.value = active;
}

function renderStaffRoleOptions(){
  const active = String(staffRoleCode.value || "").trim();
  const options = ['<option value="">Select role</option>'];
  roleRows.forEach((r) => {
    options.push(`<option value="${escapeHtml(r.code)}">${escapeHtml(r.name)}</option>`);
  });
  staffRoleCode.innerHTML = options.join("");
  if(active && roleRows.some((r) => r.code === active)) staffRoleCode.value = active;
}

function renderStaffTable(){
  const rows = staffRows.slice();
  staffCount.textContent = String(rows.length);
  staffTbody.innerHTML = rows.map((r, idx) => {
    const statusBadge = String(r.status || "").toLowerCase() === "active"
      ? '<span class="badge text-bg-success">Active</span>'
      : '<span class="badge text-bg-secondary">Inactive</span>';
    return `
      <tr>
        <td>${idx + 1}</td>
        <td>
          <div class="fw-semibold">${escapeHtml(r.empName || "-")}</div>
          <div class="small text-muted">${escapeHtml(r.empId || "")}</div>
        </td>
        <td>${escapeHtml(r.dept || "-")}</td>
        <td>${escapeHtml(r.desig || "-")}</td>
        <td>${escapeHtml(r.username || "")}</td>
        <td>${escapeHtml(roleNameByCode(r.roleCode) || "")}</td>
        <td>${statusBadge}</td>
        <td class="text-center">
          <div class="btn-group">
            <button class="btn btn-outline-secondary btn-sm" title="Edit" aria-label="Edit" onclick="editStaffRow('${escapeHtml(r.empId)}')"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-outline-danger btn-sm" title="Delete" aria-label="Delete" onclick="deleteStaffRow('${escapeHtml(r.empId)}')"><i class="bi bi-trash"></i></button>
          </div>
        </td>
      </tr>
    `;
  }).join("");
  staffEmpty.classList.toggle("d-none", rows.length !== 0);
}

function resetRoleForm(){
  editingRoleCode = "";
  roleNameInput.value = "";
  roleCodeInfo.textContent = "Creating new role";
  rolePermissions = {};
  renderPermGrid();
  const btn = document.getElementById("btnSaveRole");
  if(btn) btn.innerHTML = '<i class="bi bi-save"></i> Create Role';
}

function resetStaffForm(){
  editingStaffEmpId = "";
  staffEmpId.disabled = false;
  staffEmpId.value = "";
  staffUsername.value = "";
  staffPassword.value = "";
  staffRoleCode.value = "";
  staffStatus.value = "Active";
  staffEditInfo.textContent = "Create staff login account";
  const btn = document.getElementById("btnSaveStaff");
  if(btn) btn.innerHTML = '<i class="bi bi-save"></i> Save Staff Access';
  renderStaffEmployeeOptions();
}

async function loadRoles(selectedCode = ""){
  const res = await apiFetch("/staff-roles");
  const data = await res.json();
  if(!res.ok) throw new Error(data?.detail || "Failed to load roles");
  roleRows = Array.isArray(data.rows) ? data.rows : [];
  renderRoleTable();
  renderStaffRoleOptions();

  if(selectedCode){
    const row = roleRows.find((x) => String(x.code || "").toLowerCase() === String(selectedCode).toLowerCase());
    if(row){
      editingRoleCode = row.code;
      roleNameInput.value = row.name || row.code;
      rolePermissions = Object.assign({}, row.permissions || {});
      roleCodeInfo.textContent = `Editing role: ${row.code}`;
      const btn = document.getElementById("btnSaveRole");
      if(btn) btn.innerHTML = '<i class="bi bi-pencil"></i> Update Role';
      renderPermGrid();
      return;
    }
  }

  if(!editingRoleCode){
    resetRoleForm();
  }
}

async function loadEmployees(){
  const res = await apiFetch("/employees");
  const data = await res.json();
  if(!res.ok) throw new Error(data?.detail || "Failed to load employees");
  employeeRows = Array.isArray(data.rows) ? data.rows : [];
  renderStaffEmployeeOptions();
}

async function loadStaffUsers(){
  const res = await apiFetch("/staff-users");
  const data = await res.json();
  if(!res.ok) throw new Error(data?.detail || "Failed to load staff users");
  staffRows = Array.isArray(data.rows) ? data.rows : [];
  renderStaffTable();
}

async function loadClientAccessPermissions(){
  const res = await apiFetch("/client-access-template");
  const data = await res.json();
  if(!res.ok) throw new Error(data?.detail || "Failed to load subscription permissions");
  clientAccessPermissions = Object.assign({}, data?.permissions || {});
}

async function saveRole(){
  const name = String(roleNameInput.value || "").trim();
  if(!name){
    showMsg("Role name is required", false);
    return;
  }
  const payload = { name, permissions: buildRolePermissionsForSave() };
  const isEdit = editingRoleCode !== "";
  const res = await apiFetch(isEdit ? `/staff-roles/${encodeURIComponent(editingRoleCode)}` : "/staff-roles", {
    method: isEdit ? "PUT" : "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload)
  });
  const data = await res.json();
  if(!res.ok) throw new Error(data?.detail || (isEdit ? "Failed to update role" : "Failed to create role"));
  await loadRoles(data?.row?.code || "");
  renderStaffRoleOptions();
  showMsg(isEdit ? "Role updated" : "Role created", true);
}

async function saveStaff(){
  const empId = String(staffEmpId.value || "").trim();
  const username = String(staffUsername.value || "").trim().toLowerCase();
  const roleCode = String(staffRoleCode.value || "").trim();
  const status = String(staffStatus.value || "Active").trim();
  const password = String(staffPassword.value || "").trim();

  if(!empId){
    showMsg("Employee is required", false);
    return;
  }
  if(!username){
    showMsg("Username is required", false);
    return;
  }
  if(!roleCode){
    showMsg("Role is required", false);
    return;
  }
  if(!editingStaffEmpId && !password){
    showMsg("Password is required for new staff account", false);
    return;
  }

  const targetEmpId = editingStaffEmpId || empId;
  const payload = { username, roleCode, status };
  if(password) payload.password = password;

  const res = await apiFetch(`/staff-users/${encodeURIComponent(targetEmpId)}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload)
  });
  const data = await res.json();
  if(!res.ok) throw new Error(data?.detail || "Failed to save staff access");

  await loadStaffUsers();
  resetStaffForm();
  showMsg("Staff role access saved", true);
}

window.editRoleRow = function(code){
  const row = roleRows.find((x) => String(x.code || "").toLowerCase() === String(code || "").toLowerCase());
  if(!row) return;
  editingRoleCode = row.code;
  roleNameInput.value = row.name || row.code;
  rolePermissions = Object.assign({}, row.permissions || {});
  roleCodeInfo.textContent = `Editing role: ${row.code}`;
  const btn = document.getElementById("btnSaveRole");
  if(btn) btn.innerHTML = '<i class="bi bi-pencil"></i> Update Role';
  renderPermGrid();
};

window.deleteRoleRow = async function(code){
  const row = roleRows.find((x) => String(x.code || "").toLowerCase() === String(code || "").toLowerCase());
  if(!row) return;
  if(!confirm(`Delete role "${row.name}"?`)) return;
  try {
    const res = await apiFetch(`/staff-roles/${encodeURIComponent(row.code)}`, { method: "DELETE" });
    const data = await res.json();
    if(!res.ok) throw new Error(data?.detail || "Delete failed");
    await loadRoles("");
    await loadStaffUsers();
    showMsg("Role deleted", true);
  } catch (e){
    showMsg(e.message || "Delete role failed", false);
  }
};

window.editStaffRow = function(empId){
  const row = staffRows.find((x) => String(x.empId || "").toLowerCase() === String(empId || "").toLowerCase());
  if(!row) return;
  editingStaffEmpId = String(row.empId || "");
  staffEmpId.disabled = true;
  staffEmpId.value = editingStaffEmpId;
  staffUsername.value = String(row.username || "");
  staffRoleCode.value = String(row.roleCode || "");
  staffStatus.value = String(row.status || "Active");
  staffPassword.value = "";
  staffEditInfo.textContent = `Editing account for ${row.empName || row.empId}`;
  const btn = document.getElementById("btnSaveStaff");
  if(btn) btn.innerHTML = '<i class="bi bi-pencil"></i> Update Staff Access';
};

window.deleteStaffRow = async function(empId){
  const row = staffRows.find((x) => String(x.empId || "").toLowerCase() === String(empId || "").toLowerCase());
  if(!row) return;
  if(!confirm(`Delete staff access for ${row.empName || row.empId}?`)) return;
  try {
    const res = await apiFetch(`/staff-users/${encodeURIComponent(row.empId)}`, { method: "DELETE" });
    const data = await res.json();
    if(!res.ok) throw new Error(data?.detail || "Delete failed");
    await loadStaffUsers();
    if(editingStaffEmpId && editingStaffEmpId.toLowerCase() === String(row.empId || "").toLowerCase()){
      resetStaffForm();
    }
    showMsg("Staff access removed", true);
  } catch (e){
    showMsg(e.message || "Delete staff access failed", false);
  }
};

document.getElementById("btnAll")?.addEventListener("click", () => setAllPerm(true));
document.getElementById("btnNone")?.addEventListener("click", () => setAllPerm(false));
document.getElementById("btnSaveRole")?.addEventListener("click", async () => {
  try { await saveRole(); }
  catch (e) { showMsg(e.message || "Save role failed", false); }
});
document.getElementById("btnResetRole")?.addEventListener("click", () => resetRoleForm());
document.getElementById("btnSaveStaff")?.addEventListener("click", async () => {
  try { await saveStaff(); }
  catch (e) { showMsg(e.message || "Save staff access failed", false); }
});
document.getElementById("btnResetStaff")?.addEventListener("click", () => resetStaffForm());

(async function init(){
  try {
    const resetBtn = document.getElementById("btnResetRole");
    if(resetBtn) resetBtn.classList.add("d-none");
    await loadClientAccessPermissions();
    rolePermissions = {};
    renderPermGrid();
    await Promise.all([loadRoles(""), loadEmployees(), loadStaffUsers()]);
    resetRoleForm();
    resetStaffForm();
  } catch (e){
    showMsg(e.message || "Unable to initialize roles page", false);
  }
})();
