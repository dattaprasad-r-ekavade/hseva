(function () {
  "use strict";

  const API_BASES = ["/api", "/backend/api.php?path=/api", "/backend/api.php?path=/api"];
  const state = { rows: [], filtered: [], editingId: 0 };
  const modalEl = document.getElementById("enquiryModal");
  const modal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;

  function applyTheme(theme) {
    document.documentElement.setAttribute("data-bs-theme", theme);
    localStorage.setItem("hr_portal_theme", theme);
    const icon = document.getElementById("themeIcon");
    if (icon) icon.className = theme === "dark" ? "bi bi-sun" : "bi bi-moon";
  }

  function esc(v) {
    return String(v || "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#39;");
  }

  function statusClass(status) {
    return String(status || "new").toLowerCase().replaceAll(" ", "-");
  }

  function parseLandingMessage(message) {
    const parsed = { plan: "", state: "", address: "", location: "", pincode: "", websiteUrl: "" };
    String(message || "").split(/\r?\n/).forEach((line) => {
      const text = String(line || "").trim();
      if (!text) return;
      if (text.startsWith("Selected plan:")) parsed.plan = text.replace("Selected plan:", "").trim();
      else if (text.startsWith("State:")) parsed.state = text.replace("State:", "").trim();
      else if (text.startsWith("Role:")) parsed.state = parsed.state || text.replace("Role:", "").trim();
      else if (text.startsWith("Full Address:")) parsed.address = text.replace("Full Address:", "").trim();
      else if (text.startsWith("Location:")) parsed.location = text.replace("Location:", "").trim();
      else if (text.startsWith("Pincode:")) parsed.pincode = text.replace("Pincode:", "").trim();
      else if (text.startsWith("Website URL:")) parsed.websiteUrl = text.replace("Website URL:", "").trim();
    });
    return parsed;
  }

  function fmtDate(value) {
    const raw = String(value || "").trim();
    if (!raw) return "-";
    const d = new Date(raw);
    if (Number.isNaN(d.getTime())) return raw;
    return d.toLocaleString("en-IN", { day: "2-digit", month: "short", year: "numeric", hour: "numeric", minute: "2-digit" });
  }

  function fmtSlot(row) {
    const date = String(row.preferredDate || "").trim();
    const time = String(row.preferredTime || "").trim();
    const parts = [];
    if (date) {
      const d = new Date(date + "T00:00:00");
      parts.push(Number.isNaN(d.getTime()) ? date : d.toLocaleDateString("en-IN", { day: "2-digit", month: "short", year: "numeric" }));
    }
    if (time) parts.push(time);
    return parts.join(" | ") || "-";
  }

  function showMsg(text, ok = true) {
    const box = document.getElementById("enquiryMsg");
    if (!box) return;
    box.className = `alert ${ok ? "alert-success" : "alert-danger"}`;
    box.textContent = text;
    box.classList.remove("d-none");
  }

  async function apiFetch(path, options = {}) {
    const errors = [];
    for (const base of API_BASES) {
      try {
        const res = await fetch(`${base}${path}`, { cache: "no-store", ...options });
        if (res.status === 404 || res.status === 405) {
          errors.push(`${base}${path}:${res.status}`);
          continue;
        }
        return res;
      } catch (err) {
        errors.push(String(err));
      }
    }
    throw new Error(errors.join(" | ") || "API unavailable");
  }

  function renderStats() {
    const rows = state.rows;
    const today = new Date().toISOString().slice(0, 10);
    const total = rows.length;
    const countBy = (status) => rows.filter((r) => String(r.status || "").toLowerCase() === status).length;
    const open = rows.filter((r) => !["closed"].includes(String(r.status || "").toLowerCase())).length;
    const fresh = rows.filter((r) => String(r.createdAt || "").slice(0, 10) === today).length;
    const write = (id, value) => {
      const el = document.getElementById(id);
      if (el) el.textContent = String(value);
    };
    write("statTotal", total);
    write("statNew", countBy("new"));
    write("statContacted", countBy("contacted"));
    write("statOpen", open);
    write("statFresh", fresh);
    write("statScheduled", countBy("demo scheduled"));
    write("statQualified", countBy("qualified"));
  }

  function filterRows() {
    const status = String(document.getElementById("statusFilter")?.value || "").toLowerCase();
    const q = String(document.getElementById("searchInput")?.value || "").trim().toLowerCase();
    state.filtered = state.rows.filter((row) => {
      if (status && String(row.status || "").toLowerCase() !== status) return false;
      if (!q) return true;
      const hay = [
        row.fullName,
        row.companyName,
        row.workEmail,
        row.phoneNo,
        row.teamSize,
        row.sourcePage,
        row.message,
        parseLandingMessage(row.message).plan,
        parseLandingMessage(row.message).state,
        parseLandingMessage(row.message).address,
        parseLandingMessage(row.message).location,
        parseLandingMessage(row.message).pincode,
        parseLandingMessage(row.message).websiteUrl
      ].join(" ").toLowerCase();
      return hay.includes(q);
    });
  }

  function renderTable() {
    filterRows();
    const tbody = document.getElementById("enquiryTableBody");
    if (!tbody) return;
    const rows = state.filtered;
    if (!rows.length) {
      tbody.innerHTML = `
        <tr>
          <td colspan="8" class="enquiry-empty">
            <div class="enquiry-empty-icon"><i class="bi bi-inbox"></i></div>
            <div class="enquiry-empty-title">No enquiries found</div>
            <div class="enquiry-empty-copy">New landing page bookings and product enquiries will appear here.</div>
          </td>
        </tr>
      `;
      return;
    }
    tbody.innerHTML = rows.map((row) => `
      <tr>
        <td>${esc(row.fullName || "-")}</td>
        <td>${esc(row.workEmail || "-")}</td>
        <td>${esc(row.phoneNo || "-")}</td>
        <td>${esc(row.companyName || "-")}</td>
        <td>${esc(row.teamSize || "-")}</td>
        <td>${esc(parseLandingMessage(row.message).plan || "Free Trial")}</td>
        <td>${esc(parseLandingMessage(row.message).location || "-")}</td>
        <td class="text-end d-flex justify-content-end gap-2">
          <button class="btn btn-outline-primary btn-sm" data-action="open" data-id="${Number(row.id)}">Edit</button>
          <button class="btn btn-outline-danger btn-sm" data-action="delete" data-id="${Number(row.id)}">Delete</button>
        </td>
      </tr>
    `).join("");
  }

  function render() {
    renderStats();
    renderTable();
  }

  async function loadRows() {
    const res = await apiFetch("/admin-enquiries");
    const data = await res.json();
    if (!res.ok) throw new Error(data?.detail || "Failed to load enquiries");
    state.rows = Array.isArray(data.rows) ? data.rows : [];
    render();
  }

  function buildMessageFromForm() {
    const plan = document.getElementById("detailPlan").value || "";
    const stateName = document.getElementById("detailState").value || "";
    const address = document.getElementById("detailAddress").value || "";
    const location = document.getElementById("detailLocation").value || "";
    const pincode = document.getElementById("detailPincode").value || "";
    const websiteUrl = document.getElementById("detailWebsiteUrl").value || "";
    return [
      plan ? `Selected plan: ${plan}` : "",
      stateName ? `State: ${stateName}` : "",
      address ? `Full Address: ${address}` : "",
      location ? `Location: ${location}` : "",
      pincode ? `Pincode: ${pincode}` : "",
      websiteUrl ? `Website URL: ${websiteUrl}` : ""
    ].filter(Boolean).join("\n");
  }

  function setCreateMode() {
    state.editingId = 0;
    document.getElementById("enquiryUpdateForm")?.reset();
    document.getElementById("enquiryId").value = "";
    document.getElementById("detailStatus").value = "New";
    document.getElementById("detailPlan").value = "Free Trial";
    document.getElementById("enquiryModalTitle").textContent = "Add Landing Enquiry";
    document.getElementById("enquiryModalSub").textContent = "Create a manual enquiry entry";
    document.getElementById("deleteEnquiryBtn").hidden = true;
    document.getElementById("saveEnquiryBtn").textContent = "Create Enquiry";
  }

  function fillModal(row) {
    state.editingId = Number(row.id || 0);
    document.getElementById("enquiryId").value = String(row.id || "");
    document.getElementById("detailFullName").value = row.fullName || "";
    document.getElementById("detailCompanyName").value = row.companyName || "";
    document.getElementById("detailEmail").value = row.workEmail || "";
    document.getElementById("detailPhone").value = row.phoneNo || "";
    document.getElementById("detailTeamSize").value = row.teamSize || "";
    const parsed = parseLandingMessage(row.message);
    document.getElementById("detailPlan").value = parsed.plan || "Free Trial";
    document.getElementById("detailState").value = parsed.state || "";
    document.getElementById("detailAddress").value = parsed.address || "";
    document.getElementById("detailLocation").value = parsed.location || "";
    document.getElementById("detailPincode").value = parsed.pincode || "";
    document.getElementById("detailWebsiteUrl").value = parsed.websiteUrl || "";
    document.getElementById("detailStatus").value = row.status || "New";
    document.getElementById("detailAdminNote").value = row.adminNote || "";
    document.getElementById("enquiryModalTitle").textContent = row.companyName || "Enquiry Details";
    document.getElementById("enquiryModalSub").textContent = `Received ${fmtDate(row.createdAt)}`;
    document.getElementById("deleteEnquiryBtn").hidden = false;
    document.getElementById("saveEnquiryBtn").textContent = "Save Update";
  }

  async function saveUpdate(ev) {
    ev.preventDefault();
    const id = Number(document.getElementById("enquiryId").value || 0);
    const fullName = document.getElementById("detailFullName").value.trim();
    const companyName = document.getElementById("detailCompanyName").value.trim();
    const workEmail = document.getElementById("detailEmail").value.trim();
    const phoneNo = document.getElementById("detailPhone").value.trim();
    if (!fullName) throw new Error("Full name is required");
    if (!companyName) throw new Error("Company name is required");
    if (!workEmail && !phoneNo) throw new Error("Business email or phone is required");
    const payload = {
      fullName,
      companyName,
      workEmail,
      phoneNo,
      teamSize: document.getElementById("detailTeamSize").value || "",
      productInterest: "Free Trial",
      preferredDate: new Date().toISOString().slice(0, 10),
      preferredTime: "",
      modules: [],
      message: buildMessageFromForm(),
      sourcePage: "landing-free-trial",
      status: document.getElementById("detailStatus").value || "New",
      adminNote: document.getElementById("detailAdminNote").value || ""
    };
    const res = await apiFetch(id > 0 ? `/admin-enquiries/${id}` : "/admin-enquiries", {
      method: id > 0 ? "PUT" : "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data?.detail || `Failed to ${id > 0 ? "save" : "create"} enquiry`);
    await loadRows();
    showMsg(`Enquiry ${id > 0 ? "updated" : "created"} successfully.`, true);
    if (modal) modal.hide();
  }

  async function deleteRow(id) {
    const res = await apiFetch(`/admin-enquiries/${id}`, { method: "DELETE" });
    const data = await res.json();
    if (!res.ok) throw new Error(data?.detail || "Failed to delete enquiry");
    await loadRows();
    showMsg("Enquiry deleted successfully.", true);
    if (modal) modal.hide();
  }

  function bindTable() {
    document.getElementById("enquiryTableBody")?.addEventListener("click", (ev) => {
      const btn = ev.target.closest("[data-action]");
      if (!btn) return;
      const id = Number(btn.getAttribute("data-id") || 0);
      const row = state.rows.find((x) => Number(x.id) === id);
      if (!row) return;
      const action = btn.getAttribute("data-action");
      if (action === "delete") {
        if (window.confirm(`Delete enquiry for ${row.companyName || row.fullName || "this lead"}?`)) {
          deleteRow(id).catch((err) => showMsg(err.message || "Failed to delete enquiry", false));
        }
        return;
      }
      fillModal(row);
      if (modal) modal.show();
    });
  }

  function init() {
    applyTheme(localStorage.getItem("hr_portal_theme") || "light");
    document.getElementById("themeToggle")?.addEventListener("click", () => {
      const current = document.documentElement.getAttribute("data-bs-theme") || "light";
      applyTheme(current === "dark" ? "light" : "dark");
    });
    document.getElementById("statusFilter")?.addEventListener("change", renderTable);
    document.getElementById("searchInput")?.addEventListener("input", renderTable);
    document.getElementById("createEnquiryBtn")?.addEventListener("click", () => {
      setCreateMode();
      if (modal) modal.show();
    });
    document.getElementById("deleteEnquiryBtn")?.addEventListener("click", async () => {
      const id = Number(document.getElementById("enquiryId").value || 0);
      if (id <= 0) return;
      if (!window.confirm("Delete this enquiry?")) return;
      try {
        await deleteRow(id);
      } catch (err) {
        showMsg(err.message || "Failed to delete enquiry", false);
      }
    });
    document.getElementById("enquiryUpdateForm")?.addEventListener("submit", async (ev) => {
      try {
        await saveUpdate(ev);
      } catch (err) {
        showMsg(err.message || "Failed to save enquiry", false);
      }
    });
    bindTable();
    setCreateMode();
    loadRows().catch((err) => showMsg(err.message || "Failed to load enquiries", false));
  }

  document.addEventListener("DOMContentLoaded", init);
})();
