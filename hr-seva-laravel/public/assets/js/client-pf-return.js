const $ = (id) => document.getElementById(id);

const yrEl = $("yr");
if (yrEl) yrEl.textContent = new Date().getFullYear();

const KEY_PF_RETURNS = "hr_client_pf_returns_v2";
const API_BASES = ["/api"];

function round2(n) { return Math.round(Number(n||0)); }
function money(n) { return round2(n).toLocaleString("en-IN", { maximumFractionDigits:0 }); }
function safeParse(s) { try { return JSON.parse(s); } catch (_e) { return null; } }
function monthKey(y, m) { return `${y}-${String(m).padStart(2, "0")}`; }

const htmlEl = document.documentElement;
function applyTheme(theme) {
  htmlEl.setAttribute("data-bs-theme", theme);
  localStorage.setItem("hr_portal_theme", theme);
  const icon = $("themeIcon");
  if (icon) icon.className = theme === "dark" ? "bi bi-sun" : "bi bi-moon";
}
applyTheme(localStorage.getItem("hr_portal_theme") || "light");
$("themeToggle")?.addEventListener("click", () => {
  applyTheme((htmlEl.getAttribute("data-bs-theme") || "light") === "dark" ? "light" : "dark");
});

function setStorageMode(t) {
  const el = $("storageMode");
  if (el) el.textContent = t;
}
function loadLocalReturns() { return safeParse(localStorage.getItem(KEY_PF_RETURNS)) || []; }
function saveLocalReturns(rows) { localStorage.setItem(KEY_PF_RETURNS, JSON.stringify(rows)); }
function purgeStaleRows() {
  const src = loadLocalReturns();
  if (!src.length) return;
  const cleaned = src.filter((x) => {
    const period = String(x.period || "").trim();
    const challan = String(x.challanNo || "").toLowerCase();
    const amount = Number(x.amount || 0);
    const hitChallan = challan.includes("999991891");
    return !(period === "2026-01" && hitChallan && amount === 12454);
  });
  if (cleaned.length !== src.length) saveLocalReturns(cleaned);
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

function normalizeServerRows(rawRows) {
  const src = Array.isArray(rawRows) ? rawRows : [];
  return src.map((r) => ({
    id: String(r.id || ""),
    source: "server",
    month: Number(r.month || 0),
    year: Number(r.year || 0),
    period: String(r.period || monthKey(Number(r.year || 0), Number(r.month || 0))),
    challanNo: String(r.challanNo || "-"),
    paidDate: String(r.paidDate || "-"),
    amount: round2(Number(r.amount || 0)),
    pdfDataUrl: String(r.pdfDataUrl || ""),
    createdOn: String(r.createdOn || new Date().toISOString())
  }));
}

async function fetchServerRows() {
  const res = await apiFetch("/pf-return/challans");
  const data = await res.json();
  if (!res.ok) throw new Error(String(data?.detail || "Failed to fetch PF challans."));
  return normalizeServerRows(data?.rows || []);
}

async function saveServerRow(row) {
  const payload = {
    month: Number(row.month || 0),
    year: Number(row.year || 0),
    challanNo: String(row.challanNo || ""),
    paidDate: String(row.paidDate || ""),
    amount: round2(Number(row.amount || 0)),
    pdfDataUrl: String(row.pdfDataUrl || "")
  };
  const res = await apiFetch("/pf-return/challans", {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify(payload)
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(String(data?.detail || "Failed to save PF challan on server."));
  return data?.row || null;
}

async function migrateLocalToServer() {
  const localRows = loadLocalReturns();
  if (!localRows.length) return;
  let serverRows = [];
  try {
    serverRows = await fetchServerRows();
  } catch (_e) {
    return;
  }

  for (let i = 0; i < localRows.length; i++) {
    const r = localRows[i];
    const exists = serverRows.some((s) =>
      String(s.period) === String(r.period) &&
      String(s.challanNo) === String(r.challanNo) &&
      String(s.paidDate) === String(r.paidDate) &&
      round2(Number(s.amount || 0)) === round2(Number(r.amount || 0))
    );
    if (exists) continue;
    try {
      await saveServerRow(r);
    } catch (_e) {
      return;
    }
  }

  saveLocalReturns([]);
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

let returnList = [];
let usingServer = false;

function renderList() {
  const body = $("returnListBody");
  const countEl = $("returnListCount");
  if (!body || !countEl) return;
  if (!returnList.length) {
    body.innerHTML = `<tr><td colspan="7" class="text-center text-muted-3 py-3">No PF challan found.</td></tr>`;
    countEl.textContent = "0 saved";
    return;
  }
  body.innerHTML = returnList.map((x, i) => `
    <tr>
      <td class="fw-semibold">${i + 1}</td>
      <td class="fw-semibold">${x.period || monthKey(x.year, x.month)}</td>
      <td class="mono">${x.challanNo || "-"}</td>
      <td>${x.paidDate || "-"}</td>
      <td class="text-end">Rs ${money(x.amount || 0)}</td>
      <td class="text-end text-muted-3">${new Date(x.createdOn).toLocaleString()}</td>
      <td class="text-center">
        <div class="btn-group">
          <button class="btn btn-outline-primary btn-sm" title="View" aria-label="View" onclick="viewReturn('${x.id}')"><i class="bi bi-eye"></i></button>
          <button class="btn btn-outline-secondary btn-sm" title="Download" aria-label="Download" onclick="downloadReturn('${x.id}')"><i class="bi bi-download"></i></button>
          <button class="btn btn-outline-danger btn-sm" title="Delete" aria-label="Delete" onclick="deleteReturn('${x.id}')"><i class="bi bi-trash"></i></button>
        </div>
      </td>
    </tr>
  `).join("");
  countEl.textContent = `${returnList.length} saved`;
}

async function loadAllRows() {
  usingServer = false;
  returnList = [];
  try {
    returnList = await fetchServerRows();
    usingServer = true;
    setStorageMode("Server synced");
  } catch (_e) {
    returnList = loadLocalReturns().map((r) => Object.assign({ source: "local" }, r));
    setStorageMode("Browser localStorage");
  }
  returnList.sort((a, b) => {
    const ad = new Date(a.createdOn || 0).getTime() || 0;
    const bd = new Date(b.createdOn || 0).getTime() || 0;
    return bd - ad;
  });
}

window.viewReturn = function (id) {
  const row = returnList.find((x) => String(x.id) === String(id));
  if (!row?.pdfDataUrl) return alert("No PDF found.");
  openPdfDataUrl(row.pdfDataUrl);
};

window.downloadReturn = function (id) {
  const row = returnList.find((x) => String(x.id) === String(id));
  if (!row?.pdfDataUrl) return alert("No PDF found.");
  const a = document.createElement("a");
  a.href = row.pdfDataUrl;
  a.download = `pf_challan_${row.period}_${row.challanNo}.pdf`;
  document.body.appendChild(a);
  a.click();
  a.remove();
};

window.deleteReturn = async function (id) {
  if (!confirm("Delete this PF challan?")) return;
  if (usingServer) {
    try {
      const res = await apiFetch(`/pf-return/challans/${encodeURIComponent(String(id))}`, { method: "DELETE" });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(String(data?.detail || "Failed to delete from server."));
    } catch (e) {
      alert(String(e?.message || "Failed to delete from server."));
      return;
    }
  } else {
    returnList = returnList.filter((x) => String(x.id) !== String(id));
    saveLocalReturns(returnList);
  }
  await loadAllRows();
  renderList();
};

$("challanForm")?.addEventListener("submit", async (e) => {
  e.preventDefault();
  const month = Number($("monthSel")?.value || 0);
  const year = Number($("yearSel")?.value || 0);
  const challanNo = String($("challanNo")?.value || "").trim();
  const paidDate = $("paidDate")?.value || "";
  const amount = Number($("amount")?.value || 0);
  const file = $("challanPdf")?.files?.[0];
  if (!month || !year || !challanNo || !paidDate || !amount || !file) return alert("Please fill all fields.");
  const isPdf = file.type === "application/pdf" || file.name.toLowerCase().endsWith(".pdf");
  if (!isPdf) return alert("Upload PDF only.");
  const pdfDataUrl = await toDataUrl(file);

  const row = {
    month,
    year,
    period: monthKey(year, month),
    challanNo,
    paidDate,
    amount: round2(amount),
    pdfDataUrl,
    createdOn: new Date().toISOString()
  };

  if (usingServer) {
    try {
      await saveServerRow(row);
    } catch (err) {
      alert(String(err?.message || "Failed to save challan on server."));
      return;
    }
  } else {
    returnList.unshift(Object.assign({ id: Date.now(), source: "local" }, row));
    saveLocalReturns(returnList);
  }

  e.target.reset();
  await loadAllRows();
  renderList();
  alert("PF challan uploaded successfully.");
});

$("btnRefresh")?.addEventListener("click", async () => {
  await loadAllRows();
  renderList();
});

$("btnClearAll")?.addEventListener("click", async () => {
  if (!confirm("Clear PF challan history?")) return;
  if (usingServer) {
    try {
      const res = await apiFetch("/pf-return/challans/clear", { method: "POST" });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(String(data?.detail || "Failed to clear server PF history."));
    } catch (err) {
      alert(String(err?.message || "Failed to clear server PF history."));
      return;
    }
  } else {
    saveLocalReturns([]);
  }
  await loadAllRows();
  renderList();
});

(async function init() {
  purgeStaleRows();
  await migrateLocalToServer();
  await loadAllRows();
  renderList();
})();

