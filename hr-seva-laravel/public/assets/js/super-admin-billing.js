const API_BASES = ["/api", "/backend/api.php?path=/api", "/backend/api.php?path=/api"];
const tbody = document.getElementById("billingTbody");
const paidAmount = document.getElementById("paidAmount");
const pendingAmount = document.getElementById("pendingAmount");
const totalAmount = document.getElementById("totalAmount");
const planName = document.getElementById("billPlanName");
const BILL_PRINT_CSS_URL = new URL("../assets/css/super-admin-billing-print.css", window.location.href).href;
const BILL_PRINT_JS_URL = new URL("../assets/js/super-admin-billing-print.js", window.location.href).href;
let billRows = [];

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

function money(n){
  return new Intl.NumberFormat("en-IN", { style: "currency", currency: "INR", maximumFractionDigits: 0 }).format(Number(n || 0));
}
function monthLabel(dateInput){
  const d = dateInput ? new Date(dateInput) : new Date();
  if (Number.isNaN(d.getTime())) return "-";
  return d.toLocaleDateString("en-IN", { month: "short", year: "numeric" });
}
function dueDate(dateInput){
  const d = dateInput ? new Date(dateInput) : new Date();
  if (Number.isNaN(d.getTime())) return "-";
  d.setMonth(d.getMonth() + 1, 10);
  return d.toLocaleDateString("en-IN", { day: "2-digit", month: "short", year: "numeric" });
}

async function apiFetch(path){
  for(const base of API_BASES){
    try {
      const r = await fetch(`${base}${path}`, { cache: "no-store" });
      if(r.status === 404 || r.status === 405) continue;
      return r;
    } catch(_e){}
  }
  throw new Error("API unavailable");
}

function planAmount(accessType){
  const t = String(accessType || "").toLowerCase();
  if(t.includes("full")) return 5000;
  if(t.includes("payroll")) return 3500;
  if(t.includes("compliance")) return 3000;
  if(t.includes("read")) return 2000;
  return 3200;
}

function monthKeysBetween(startISO, endISO){
  const start = new Date(startISO);
  const end = new Date(endISO);
  if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return [];
  const keys = [];
  const cur = new Date(start.getFullYear(), start.getMonth(), 1);
  const last = new Date(end.getFullYear(), end.getMonth(), 1);
  let guard = 0;
  while (cur <= last && guard < 240) {
    const y = cur.getFullYear();
    const m = String(cur.getMonth() + 1).padStart(2, "0");
    keys.push(`${y}-${m}-01`);
    cur.setMonth(cur.getMonth() + 1);
    guard++;
  }
  return keys;
}

function buildRowsFromSubscriptions(subscriptions, clients){
  const clientMap = new Map((clients || []).map((c) => [Number(c.id || 0), c]));
  const rows = [];
  const now = new Date();
  subscriptions.forEach((s) => {
    const clientId = Number(s.clientId || 0);
    const c = clientMap.get(clientId) || {};
    const months = monthKeysBetween(s.startDate, s.endDate);
    const baseTotal = Number(s.amount || 0);
    const fallbackBase = planAmount(c.accessType || "");
    const monthlyBase = months.length > 0
      ? (baseTotal > 0 ? baseTotal / months.length : fallbackBase)
      : (baseTotal > 0 ? baseTotal : fallbackBase);
    const monthRows = months.length ? months : [s.startDate || new Date().toISOString().slice(0, 10)];

    monthRows.forEach((mk) => {
      const billDate = new Date(mk);
      const gst = Math.round(monthlyBase * 0.18);
      const total = Math.round(monthlyBase + gst);
      const status = billDate < new Date(now.getFullYear(), now.getMonth(), 1) ? "Paid" : "Pending";
      const yyyymm = `${billDate.getFullYear()}${String(billDate.getMonth() + 1).padStart(2, "0")}`;
      rows.push({
        clientName: String(s.clientName || c.companyName || "-"),
        userId: String(s.userId || c.userId || "-"),
        invoiceNo: `SUB-${String(s.id || "").padStart(4, "0")}-${yyyymm}`,
        billingMonth: monthLabel(mk),
        amount: monthlyBase,
        gst,
        total,
        status,
        dueDate: dueDate(mk)
      });
    });
  });

  rows.sort((a, b) => {
    const ad = new Date(`01 ${a.billingMonth}`);
    const bd = new Date(`01 ${b.billingMonth}`);
    return bd - ad || String(a.clientName).localeCompare(String(b.clientName));
  });
  return rows;
}

function renderSummary(rows){
  const paid = rows.filter((x) => String(x.status).toLowerCase() === "paid").reduce((a, x) => a + Number(x.total || 0), 0);
  const pending = rows.filter((x) => String(x.status).toLowerCase() !== "paid").reduce((a, x) => a + Number(x.total || 0), 0);
  const total = paid + pending;
  if (paidAmount) paidAmount.textContent = money(paid);
  if (pendingAmount) pendingAmount.textContent = money(pending);
  if (totalAmount) totalAmount.textContent = money(total);
  if (planName) planName.textContent = "All Clients";
}

function renderRows(rows){
  if(!rows.length){
    tbody.innerHTML = '<tr><td colspan="11" class="text-center text-muted-3 py-3">No subscription billing rows found.</td></tr>';
    return;
  }
  tbody.innerHTML = rows.map((r, idx) => `
    <tr>
      <td class="fw-semibold">${idx + 1}</td>
      <td class="fw-semibold">${r.clientName}</td>
      <td>${r.userId}</td>
      <td>${r.invoiceNo}</td>
      <td>${r.billingMonth}</td>
      <td class="text-end">${money(r.amount)}</td>
      <td class="text-end">${money(r.gst)}</td>
      <td class="text-end fw-semibold">${money(r.total)}</td>
      <td><span class="status-pill ${String(r.status).toLowerCase() === "paid" ? "paid" : "pending"}">${r.status}</span></td>
      <td>${r.dueDate}</td>
      <td class="text-end">
        <button type="button" class="btn btn-outline-primary btn-sm btn-pdf" data-idx="${idx}">PDF Bill</button>
      </td>
    </tr>
  `).join("");
}

function openPdfBill(row){
  const popup = window.open("", "_blank");
  if (!popup) return;
  const html = `
    <!doctype html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>${row.invoiceNo}</title>
      <link rel="stylesheet" href="${BILL_PRINT_CSS_URL}">
    </head>
    <body>
      <h2>Client Invoice</h2>
      <div><strong>Invoice No:</strong> ${row.invoiceNo}</div>
      <div><strong>Client:</strong> ${row.clientName}</div>
      <div><strong>User ID:</strong> ${row.userId}</div>
      <div><strong>Billing Month:</strong> ${row.billingMonth}</div>
      <div><strong>Due Date:</strong> ${row.dueDate}</div>
      <table>
        <tr><th>Description</th><th>Amount</th></tr>
        <tr><td>Subscription Charges</td><td>${money(row.amount)}</td></tr>
        <tr><td>GST (18%)</td><td>${money(row.gst)}</td></tr>
        <tr><th>Total</th><th>${money(row.total)}</th></tr>
      </table>
      <script src="${BILL_PRINT_JS_URL}"><\/script>
    </body>
    </html>
  `;
  popup.document.open();
  popup.document.write(html);
  popup.document.close();
}

tbody?.addEventListener("click", (ev) => {
  const btn = ev.target.closest(".btn-pdf");
  if (!btn) return;
  const idx = Number(btn.getAttribute("data-idx") || -1);
  if (idx < 0 || idx >= billRows.length) return;
  openPdfBill(billRows[idx]);
});

async function init(){
  try {
    const [clientsRes, subsRes] = await Promise.all([apiFetch("/clients"), apiFetch("/subscriptions")]);
    const clientsData = await clientsRes.json();
    const subsData = await subsRes.json();
    const clients = Array.isArray(clientsData.rows) ? clientsData.rows : [];
    const subs = Array.isArray(subsData.rows) ? subsData.rows : [];
    billRows = buildRowsFromSubscriptions(subs, clients);
    renderSummary(billRows);
    renderRows(billRows);
  } catch (_e){
    billRows = [];
    renderSummary([]);
    renderRows([]);
  }
}

init();
setInterval(() => {
  if (document.visibilityState === "visible") init();
}, 30000);

