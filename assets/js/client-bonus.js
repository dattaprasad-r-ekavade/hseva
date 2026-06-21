const $ = (id) => document.getElementById(id);
const htmlEl = document.documentElement;
const KEY_AUTH = "hr_auth_session_v1";
const KEY_SUPERADMIN_CLIENT_ID = "hr_superadmin_selected_client_id_v1";
const API_CONTROL = "/api/control";
const API_BONUS_GEN = "/api/bonus/generate";
const API_BONUS_SHEETS = "/api/bonus/sheets";
const API_BONUS_CLEAR = "/api/bonus/clear";

let previewRows = [];
let bonusRows = [];
let controlState = {};

function applyTheme(theme){
  htmlEl.setAttribute("data-bs-theme", theme);
  localStorage.setItem("hr_portal_theme", theme);
  if ($("themeIcon")) $("themeIcon").className = theme === "dark" ? "bi bi-sun" : "bi bi-moon";
}
applyTheme(localStorage.getItem("hr_portal_theme") || "light");
$("themeToggle")?.addEventListener("click", () => {
  const current = htmlEl.getAttribute("data-bs-theme") || "light";
  applyTheme(current === "dark" ? "light" : "dark");
});

function safeParse(value, fallback = null){
  try { return JSON.parse(value); } catch(_e){ return fallback; }
}
function money(value){
  return Number(value || 0).toLocaleString("en-IN", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function fmtDateTime(value){
  const d = new Date(value || "");
  return Number.isNaN(d.getTime()) ? "-" : d.toLocaleString();
}
function authSession(){
  return safeParse(sessionStorage.getItem(KEY_AUTH), null);
}
function selectedClientId(){
  return Number(localStorage.getItem(KEY_SUPERADMIN_CLIENT_ID) || 0);
}
function isSuperAdminPage(){
  return String(window.location.pathname || "").toLowerCase().includes("/super-admin/");
}
function hasClientContext(){
  const auth = authSession();
  const tokenClientId = Number(auth?.user?.clientId || 0);
  if(tokenClientId > 0) return true;
  if(isSuperAdminPage()) return selectedClientId() > 0;
  return false;
}
function calcBonus(minimumWage, months, bonusPct){
  return (Number(minimumWage || 0) * Number(months || 0) * Number(bonusPct || 0)) / 100;
}

function setScopeState(){
  const scoped = hasClientContext();
  const notice = $("clientScopeNotice");
  const form = $("bonusForm");
  if(notice){
    notice.classList.toggle("d-none", scoped);
    notice.textContent = isSuperAdminPage() ? "Select a client from the top client picker to view or generate bonus data." : "";
  }
  if(form){
    Array.from(form.querySelectorAll("input, select, button")).forEach((el) => { el.disabled = !scoped; });
  }
  if($("btnClearAll")) $("btnClearAll").disabled = !scoped;
  if($("btnSavePreview")) $("btnSavePreview").disabled = !scoped || !previewRows.length;
  if(!scoped){
    if($("bonusTable")) $("bonusTable").innerHTML = `<tr><td colspan="6" class="text-center text-muted-3 py-4">Select a client to view bonus records.</td></tr>`;
    $("previewWrap")?.classList.add("d-none");
  }
}

async function fetchJson(url, options){
  const res = await fetch(url, options);
  const data = await res.json();
  if(!res.ok) throw new Error(data?.detail || "Request failed");
  return data;
}

async function loadControl(){
  controlState = await fetchJson(API_CONTROL, { headers: { Accept: "application/json" } });
  if($("controlBadge")) $("controlBadge").textContent = String(controlState?.bonusEnabled || "Yes");
  if($("controlFormula")) $("controlFormula").textContent = `${money(controlState?.bonusMinimumWage || 0)} x ${Number(controlState?.bonusMultiplierMonths || 0)} x ${Number(controlState?.bonusPercent || 0)}%`;
}

function renderPreview(){
  const tbody = $("previewTable");
  if(!tbody) return;
  if(!previewRows.length){
    $("previewWrap")?.classList.add("d-none");
    if($("btnSavePreview")) $("btnSavePreview").disabled = true;
    return;
  }
  $("previewWrap")?.classList.remove("d-none");
  if($("previewTitle")) $("previewTitle").textContent = `Generated bonus preview for ${$("monthInput")?.selectedOptions?.[0]?.text || "-"} ${$("yearInput")?.value || ""}`.trim();
  tbody.innerHTML = previewRows.map((row, idx) => `
    <tr>
      <td>${idx + 1}</td>
      <td><div class="fw-semibold">${row.employeeName || "-"}</div><div class="small text-muted-3">${row.empId || "-"}</div></td>
      <td><input class="form-control form-control-sm text-end" type="number" min="0" step="0.01" value="${Number(row.minimumWage || 0)}" data-field="minimumWage" data-idx="${idx}"></td>
      <td><input class="form-control form-control-sm text-end" type="number" min="0" step="0.01" value="${Number(row.multiplierMonths || 0)}" data-field="multiplierMonths" data-idx="${idx}"></td>
      <td><input class="form-control form-control-sm text-end" type="number" min="0" max="100" step="0.01" value="${Number(row.bonusPct || 0)}" data-field="bonusPct" data-idx="${idx}"></td>
      <td class="fw-semibold">Rs ${money(row.bonusAmount || 0)}</td>
    </tr>
  `).join("");
  if($("btnSavePreview")) $("btnSavePreview").disabled = !hasClientContext();
}

function renderRows(){
  const tbody = $("bonusTable");
  if(!tbody || !hasClientContext()) return;
  if(!bonusRows.length){
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted-3 py-4">No bonus sheets generated yet.</td></tr>`;
    return;
  }
  tbody.innerHTML = bonusRows.map((row, idx) => `
    <tr>
      <td>${idx + 1}</td>
      <td>${row.period || "-"}</td>
      <td>${Number(row.rowCount || 0)}</td>
      <td class="fw-semibold">Rs ${money(row.totalBonus || 0)}</td>
      <td>${fmtDateTime(row.generatedAt)}</td>
      <td class="text-end d-flex justify-content-end gap-1">
        <button class="btn btn-sm btn-outline-primary" type="button" onclick="viewBonusRow('${String(row.id || "")}')"><i class="bi bi-eye"></i></button>
        <button class="btn btn-sm btn-outline-danger" type="button" onclick="deleteBonusRow('${String(row.id || "")}')"><i class="bi bi-trash"></i></button>
      </td>
    </tr>
  `).join("");
}

async function loadRows(){
  const data = await fetchJson(API_BONUS_SHEETS, { headers: { Accept: "application/json" } });
  bonusRows = Array.isArray(data.rows) ? data.rows : [];
  renderRows();
}

window.viewBonusRow = async function(id){
  if(!id) return;
  const data = await fetchJson(`${API_BONUS_SHEETS}/${encodeURIComponent(id)}`, { headers: { Accept: "application/json" } });
  previewRows = Array.isArray(data?.sheet?.rows) ? data.sheet.rows.map((row) => ({ ...row })) : [];
  if($("monthInput")) $("monthInput").value = String(data?.sheet?.month || "");
  if($("yearInput")) $("yearInput").value = String(data?.sheet?.year || "");
  renderPreview();
};

window.deleteBonusRow = async function(id){
  if(!id) return;
  if(!confirm("Delete this bonus sheet?")) return;
  await fetchJson(`${API_BONUS_SHEETS}/${encodeURIComponent(id)}`, { method: "DELETE" });
  await loadRows();
};

async function generatePreview(){
  const month = Number($("monthInput")?.value || 0);
  const year = Number($("yearInput")?.value || 0);
  if(month < 1 || month > 12 || year < 2000){
    alert("Select valid month and year.");
    return;
  }
  const submitBtn = $("bonusForm")?.querySelector('button[type="submit"]');
  const doneProcessing = window.HRCommon?.setProcessingState?.(submitBtn, {
    busyText: "Generating...",
    message: "Please wait, we are preparing the bonus preview."
  });
  try {
    const data = await fetchJson(API_BONUS_GEN, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ month, year })
    });
    previewRows = Array.isArray(data?.sheet?.rows) ? data.sheet.rows.map((row) => ({ ...row })) : [];
    renderPreview();
    doneProcessing?.(`Bonus preview ready for ${month}/${year}.`, false);
  } catch (e) {
    doneProcessing?.(e?.message || "Unable to generate bonus preview.", true);
    alert(e?.message || "Unable to generate bonus preview.");
  }
}

async function savePreview(){
  if(!previewRows.length){
    alert("Generate preview first.");
    return;
  }
  const month = Number($("monthInput")?.value || 0);
  const year = Number($("yearInput")?.value || 0);
  await fetchJson(API_BONUS_SHEETS, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ month, year, rows: previewRows })
  });
  await loadRows();
  alert("Bonus sheet saved.");
}

async function clearAll(){
  if(!confirm("Clear all bonus history?")) return;
  await fetchJson(API_BONUS_CLEAR, { method: "POST" });
  previewRows = [];
  renderPreview();
  await loadRows();
}

document.addEventListener("input", (event) => {
  const target = event.target;
  if(!target?.matches?.("[data-field][data-idx]")) return;
  const idx = Number(target.getAttribute("data-idx"));
  const field = String(target.getAttribute("data-field") || "");
  if(!previewRows[idx]) return;
  previewRows[idx][field] = Number(target.value || 0);
  previewRows[idx].bonusAmount = calcBonus(previewRows[idx].minimumWage, previewRows[idx].multiplierMonths, previewRows[idx].bonusPct);
  renderPreview();
});

document.addEventListener("DOMContentLoaded", async () => {
  try {
    if($("monthInput") && !$("monthInput").value) $("monthInput").value = String(new Date().getMonth() + 1);
    if($("yearInput") && !$("yearInput").value) $("yearInput").value = String(new Date().getFullYear());
    setScopeState();
    await loadControl();
    if(String(controlState?.bonusEnabled || "Yes").toLowerCase() === "no"){
      $("clientScopeNotice")?.classList.remove("d-none");
      if($("clientScopeNotice")) $("clientScopeNotice").textContent = "Bonus module is disabled in control page.";
    }
    if(hasClientContext()) await loadRows();
    $("bonusForm")?.addEventListener("submit", async (event) => {
      event.preventDefault();
      await generatePreview();
    });
    $("btnSavePreview")?.addEventListener("click", savePreview);
    $("btnClearAll")?.addEventListener("click", clearAll);
    window.addEventListener("hr:client-changed", async () => {
      previewRows = [];
      renderPreview();
      setScopeState();
      await loadControl();
      if(hasClientContext()) await loadRows();
    });
    window.addEventListener("hr:control-updated", async () => {
      await loadControl();
    });
  } catch(e){
    alert(e.message || "Unable to load bonus module.");
  }
});
