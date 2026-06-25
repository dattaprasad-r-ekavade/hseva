const $ = (id) => document.getElementById(id);

const yrEl = $("yr");
if (yrEl) yrEl.textContent = new Date().getFullYear();

const KEY = "hr_client_compliance_challan_v1";
const API_BASES = ["/api"];
const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

const htmlEl = document.documentElement;
function applyTheme(theme) {
  htmlEl.setAttribute("data-bs-theme", theme);
  localStorage.setItem("hr_portal_theme", theme);
  const icon = $("themeIcon");
  if (icon) icon.className = theme === "dark" ? "bi bi-sun" : "bi bi-moon";
  if ($("themeText")) $("themeText").textContent = "";
}
applyTheme(localStorage.getItem("hr_portal_theme") || "light");
$("themeToggle")?.addEventListener("click", () => {
  const cur = htmlEl.getAttribute("data-bs-theme") || "light";
  applyTheme(cur === "dark" ? "light" : "dark");
});

let allRows = [];
let editId = null;
let usingServer = false;
const entryModalEl = $("entryModal");
const entryModal = entryModalEl ? bootstrap.Modal.getOrCreateInstance(entryModalEl) : null;

function safeParse(s) { try { return JSON.parse(s); } catch (_e) { return null; } }
function round2(n) { return Math.round(Number(n||0)); }
function loadLocal() { return safeParse(localStorage.getItem(KEY)) || []; }
function saveLocal(rows) { localStorage.setItem(KEY, JSON.stringify(Array.isArray(rows) ? rows : [])); }
function getYear() { return Number($("yearSel")?.value || 0); }

function setStorageMode(t) {
  const el = $("storageMode");
  if (el) el.textContent = t;
}

function purgeStaleRows() {
  const src = loadLocal();
  if (!src.length) return;
  const cleaned = src.filter((r) => {
    const y = Number(r.year || 0);
    const m = Number(r.month || 0);
    const due = String(r.dueDate || "").trim();
    const status = String(r.status || "").toLowerCase().trim();
    const amount = Number(r.amount || 0);
    const type = String(r.type || "").toLowerCase();
    const matches = (
      y === 2026 &&
      m === 1 &&
      due === "2026-02-28" &&
      status === "completed" &&
      amount === 23343 &&
      type.includes("567467")
    );
    return !matches;
  });
  if (cleaned.length !== src.length) saveLocal(cleaned);
}

async function apiFetch(path, init) {
  for (let i = 0; i < API_BASES.length; i++) {
    const base = API_BASES[i];
    try {
      const res = await fetch(`${base}${path}`, Object.assign({ cache: "no-store" }, init || {}));
      if (res.status === 404 || res.status === 405) continue;
      return res;
    } catch (_e) {
      // try next base
    }
  }
  throw new Error("API unavailable");
}

function normalizeRow(raw) {
  const month = Number(raw?.month || 0);
  const year = Number(raw?.year || 0);
  return {
    id: String(raw?.id || ""),
    month,
    year,
    type: String(raw?.type || ""),
    dueDate: String(raw?.dueDate || ""),
    status: String(raw?.status || "Pending"),
    amount: round2(Number(raw?.amount || 0)),
    notes: String(raw?.notes || ""),
    pdfDataUrl: String(raw?.pdfDataUrl || ""),
    createdAt: String(raw?.createdAt || new Date().toISOString()),
    updatedAt: String(raw?.updatedAt || new Date().toISOString())
  };
}

async function fetchServerRows() {
  const res = await apiFetch("/compliance/challans");
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(String(data?.detail || "Failed to fetch compliance challans."));
  return (Array.isArray(data?.rows) ? data.rows : []).map(normalizeRow);
}

async function upsertServerRow(row) {
  const payload = {
    id: String(row.id || ""),
    month: Number(row.month || 0),
    year: Number(row.year || 0),
    type: String(row.type || ""),
    dueDate: String(row.dueDate || ""),
    status: String(row.status || "Pending"),
    amount: round2(Number(row.amount || 0)),
    notes: String(row.notes || ""),
    pdfDataUrl: String(row.pdfDataUrl || ""),
    createdAt: String(row.createdAt || ""),
    updatedAt: String(row.updatedAt || "")
  };
  const res = await apiFetch("/compliance/challans", {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify(payload)
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(String(data?.detail || "Failed to save compliance challan."));
  return normalizeRow(data?.row || payload);
}

async function deleteServerRow(id) {
  const res = await apiFetch(`/compliance/challans/${encodeURIComponent(String(id))}`, { method: "DELETE" });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(String(data?.detail || "Failed to delete compliance challan."));
}

async function migrateLocalToServer() {
  const localRows = loadLocal();
  if (!localRows.length) return;
  let serverRows = [];
  try {
    serverRows = await fetchServerRows();
  } catch (_e) {
    return;
  }
  for (let i = 0; i < localRows.length; i++) {
    const row = normalizeRow(localRows[i]);
    const exists = serverRows.some((s) =>
      String(s.id) === String(row.id) ||
      (
        Number(s.month) === Number(row.month) &&
        Number(s.year) === Number(row.year) &&
        String(s.type) === String(row.type) &&
        String(s.dueDate) === String(row.dueDate) &&
        String(s.status) === String(row.status) &&
        round2(Number(s.amount || 0)) === round2(Number(row.amount || 0)) &&
        String(s.notes || "") === String(row.notes || "")
      )
    );
    if (exists) continue;
    try {
      await upsertServerRow(row);
    } catch (_e) {
      return;
    }
  }
  saveLocal([]);
}

async function loadAllRows() {
  usingServer = false;
  allRows = [];
  try {
    allRows = await fetchServerRows();
    usingServer = true;
    setStorageMode("Server synced");
  } catch (_e) {
    allRows = loadLocal().map(normalizeRow);
    setStorageMode("Browser localStorage");
  }
  allRows.sort((a, b) => String(b.updatedAt || "").localeCompare(String(a.updatedAt || "")));
}

function getFilteredRows() {
  const legacyYear = getYear();
  const legacyMonth = $("filterMonth")?.value || "";
  const listMonth = $("listMonth")?.value || "";
  const listYear = $("listYear")?.value || "";
  const listStatus = $("listStatus")?.value || "";
  const q = String($("listSearch")?.value || "").trim().toLowerCase();

  const monthValue = listMonth || legacyMonth;
  const yearValue = listYear || String(legacyYear || "");

  return allRows.filter((r) => {
    if (yearValue && String(r.year) !== String(yearValue)) return false;
    if (monthValue && String(r.month) !== String(monthValue)) return false;
    if (listStatus && String(r.status || "") !== listStatus) return false;
    if (q) {
      const searchable = `${r.type || ""} ${r.dueDate || ""} ${r.notes || ""} ${r.status || ""} ${r.amount || ""}`.toLowerCase();
      if (!searchable.includes(q)) return false;
    }
    return true;
  });
}

function esc(s) {
  return String(s ?? "").replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#39;");
}
function money(n) {
  return Number(n || 0).toLocaleString("en-IN", { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}
function fmtDateTime(v) {
  if (!v) return "-";
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return "-";
  return d.toLocaleString(undefined, {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit"
  });
}

function toDataUrl(file) {
  return new Promise((resolve, reject) => {
    const fr = new FileReader();
    fr.onload = () => resolve(String(fr.result || ""));
    fr.onerror = () => reject(new Error("Failed to read PDF"));
    fr.readAsDataURL(file);
  });
}
function openPdfDataUrl(dataUrl) {
  try {
    const byteString = atob(String(dataUrl).split(",")[1] || "");
    const mimeString = String(dataUrl).split(",")[0].split(":")[1]?.split(";")[0] || "application/pdf";
    const ab = new ArrayBuffer(byteString.length);
    const ia = new Uint8Array(ab);
    for (let i = 0; i < byteString.length; i++) ia[i] = byteString.charCodeAt(i);
    const blob = new Blob([ab], { type: mimeString });
    const blobUrl = URL.createObjectURL(blob);
    const w = window.open(blobUrl, "_blank");
    if (!w) alert("Popup blocked. Please allow popups for this site.");
    setTimeout(() => URL.revokeObjectURL(blobUrl), 30000);
  } catch (_e) {
    window.open(dataUrl, "_blank");
  }
}

function renderTable() {
  const tbody = $("tbody");
  const rowCount = $("rowCount");
  if (!tbody || !rowCount) return;

  const rows = getFilteredRows().sort((a, b) => String(a.dueDate || "").localeCompare(String(b.dueDate || "")));
  rowCount.textContent = `${rows.length} rows`;
  tbody.innerHTML = rows.map((r, idx) => `
      <tr>
        <td class="fw-semibold">${idx + 1}</td>
        <td>${monthNames[Number(r.month) - 1] || r.month}</td>
        <td>${esc(r.year || "-")}</td>
        <td>${esc(r.type)}</td>
        <td>${esc(r.dueDate || "-")}</td>
        <td>${fmtDateTime(r.createdAt || (r.dueDate ? `${r.dueDate}T00:00:00` : ""))}</td>
        <td>${esc(r.status || "Pending")}</td>
        <td class="text-end">${money(r.amount)}</td>
        <td>${r.pdfDataUrl ? `<button class="btn btn-outline-secondary btn-sm" onclick="viewPdf('${r.id}')">View</button>` : "-"}</td>
        <td>${esc(r.notes || "-")}</td>
        <td class="text-center">
          <div class="d-inline-flex justify-content-center gap-1">
            <button class="btn btn-outline-secondary btn-sm" title="View" ${r.pdfDataUrl ? `onclick="viewPdf('${r.id}')"` : "disabled"}>View</button>
            <button class="btn btn-outline-success btn-sm" title="Download" ${r.pdfDataUrl ? `onclick="downloadPdf('${r.id}')"` : "disabled"}>Download</button>
            <button class="btn btn-outline-danger btn-sm" title="Delete" onclick="deleteRow('${r.id}')">Delete</button>
          </div>
        </td>
      </tr>
    `).join("");
}

function fillMonthSelect(elId) {
  const el = $(elId);
  if (!el) return;
  el.innerHTML = monthNames.map((m, i) => `<option value="${i + 1}">${m}</option>`).join("");
}

function openAdd() {
  editId = null;
  if ($("entryTitle")) $("entryTitle").textContent = "Add Compliance challan";
  $("entryForm")?.reset();
  if ($("eMonth")) $("eMonth").value = $("filterMonth")?.value || String(new Date().getMonth() + 1);
  if ($("eStatus")) $("eStatus").value = "Pending";
  entryModal?.show();
}

window.editRow = function (id) {
  const r = allRows.find((x) => String(x.id) === String(id));
  if (!r) return;
  editId = r.id;
  if ($("entryTitle")) $("entryTitle").textContent = "Edit Compliance challan";
  if ($("eMonth")) $("eMonth").value = String(r.month);
  if ($("eType")) $("eType").value = r.type || "";
  if ($("eDue")) $("eDue").value = r.dueDate || "";
  if ($("eStatus")) $("eStatus").value = r.status || "Pending";
  if ($("eAmount")) $("eAmount").value = Number(r.amount || 0);
  if ($("eNotes")) $("eNotes").value = r.notes || "";
  if ($("ePdf")) $("ePdf").value = "";
  entryModal?.show();
};

window.deleteRow = async function (id) {
  if (!confirm("Delete this record?")) return;
  if (usingServer) {
    try {
      await deleteServerRow(id);
      await loadAllRows();
    } catch (e) {
      alert(String(e?.message || "Failed to delete record."));
      return;
    }
  } else {
    allRows = allRows.filter((x) => String(x.id) !== String(id));
    saveLocal(allRows);
  }
  renderTable();
};

window.viewPdf = function (id) {
  const r = allRows.find((x) => String(x.id) === String(id));
  if (!r?.pdfDataUrl) {
    alert("No PDF attached.");
    return;
  }
  openPdfDataUrl(r.pdfDataUrl);
};

window.downloadPdf = function (id) {
  const r = allRows.find((x) => String(x.id) === String(id));
  if (!r?.pdfDataUrl) {
    alert("No PDF attached.");
    return;
  }
  const a = document.createElement("a");
  a.href = r.pdfDataUrl;
  a.download = `compliance_challan_${r.year || ""}_${String(r.month || "").padStart(2, "0")}_${r.id}.pdf`;
  document.body.appendChild(a);
  a.click();
  a.remove();
};

async function saveEntry() {
  const month = Number($("eMonth")?.value || 0);
  const type = String($("eType")?.value || "").trim();
  const dueDate = $("eDue")?.value || "";
  const status = $("eStatus")?.value || "Pending";
  const amount = Number($("eAmount")?.value || 0);
  const notes = String($("eNotes")?.value || "").trim();
  if (!month || !type || !dueDate) {
    alert("Fill Month, Challan Type, Due Date");
    return;
  }

  let existingPdf = "";
  if (editId) {
    const old = allRows.find((x) => String(x.id) === String(editId));
    existingPdf = old?.pdfDataUrl || "";
  }
  const file = $("ePdf")?.files?.[0];
  let pdfDataUrl = existingPdf;
  if (file) {
    const isPdf = file.type === "application/pdf" || file.name.toLowerCase().endsWith(".pdf");
    if (!isPdf) {
      alert("Upload PDF file only.");
      return;
    }
    pdfDataUrl = await toDataUrl(file);
  }

  const nowIso = new Date().toISOString();
  const previous = editId ? allRows.find((x) => String(x.id) === String(editId)) : null;
  const row = {
    id: String(editId || ""),
    year: getYear(),
    month,
    type,
    dueDate,
    status,
    amount: round2(amount),
    notes,
    pdfDataUrl,
    createdAt: previous?.createdAt || nowIso,
    updatedAt: nowIso
  };

  if (usingServer) {
    try {
      await upsertServerRow(row);
      await loadAllRows();
    } catch (e) {
      alert(String(e?.message || "Failed to save compliance challan."));
      return;
    }
  } else if (editId) {
    const idx = allRows.findIndex((x) => String(x.id) === String(editId));
    if (idx >= 0) allRows[idx] = normalizeRow(Object.assign({}, allRows[idx], row));
    saveLocal(allRows);
  } else {
    allRows.unshift(normalizeRow(Object.assign({ id: String(Date.now()) }, row)));
    saveLocal(allRows);
  }

  entryModal?.hide();
  renderTable();
}

function exportCsv() {
  const y = getYear();
  const rows = allRows.filter((r) => Number(r.year) === y);
  const head = ["Year", "Month", "Challan_Type", "Due_Date", "Status", "Amount", "Notes", "Has_PDF"];
  const data = rows.map((r) => [r.year, monthNames[Number(r.month) - 1] || r.month, r.type || "", r.dueDate || "", r.status || "", r.amount || 0, r.notes || "", r.pdfDataUrl ? "Yes" : "No"]);
  const csv = [head, ...data].map((row) => row.map((v) => `"${String(v).replaceAll('"', '""')}"`).join(",")).join("\n");
  const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
  const a = document.createElement("a");
  a.href = URL.createObjectURL(blob);
  a.download = `compliance_challan_${y}.csv`;
  document.body.appendChild(a);
  a.click();
  a.remove();
}

$("btnAdd")?.addEventListener("click", openAdd);
$("btnSaveEntry")?.addEventListener("click", () => { saveEntry(); });
$("btnExport")?.addEventListener("click", exportCsv);
$("yearSel")?.addEventListener("change", renderTable);
$("filterMonth")?.addEventListener("change", renderTable);
$("listSearch")?.addEventListener("input", renderTable);
$("listMonth")?.addEventListener("change", renderTable);
$("listYear")?.addEventListener("change", renderTable);
$("listStatus")?.addEventListener("change", renderTable);
$("clearListFilters")?.addEventListener("click", () => {
  if ($("listSearch")) $("listSearch").value = "";
  if ($("listMonth")) $("listMonth").value = "";
  if ($("listYear")) $("listYear").value = "";
  if ($("listStatus")) $("listStatus").value = "";
  renderTable();
});

(async function init() {
  purgeStaleRows();
  const now = new Date();
  const y0 = now.getFullYear();

  if ($("yearSel")) {
    $("yearSel").innerHTML = [y0, y0 - 1, y0 + 1, y0 - 2]
      .map((y) => `<option value="${y}" ${y === y0 ? "selected" : ""}>${y}</option>`)
      .join("");
  }
  if ($("filterMonth")) {
    $("filterMonth").innerHTML += monthNames.map((m, i) => `<option value="${i + 1}">${m}</option>`).join("");
  }
  if ($("listMonth")) {
    $("listMonth").innerHTML = '<option value="">All</option>' + monthNames.map((m, i) => `<option value="${i + 1}">${m}</option>`).join("");
  }
  if ($("listYear")) {
    $("listYear").innerHTML = '<option value="">All</option>' + [y0, y0 - 1, y0 + 1, y0 - 2].map((y) => `<option value="${y}">${y}</option>`).join("");
  }
  fillMonthSelect("eMonth");

  await migrateLocalToServer();
  await loadAllRows();
  renderTable();
})();

