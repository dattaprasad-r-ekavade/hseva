(function () {
  "use strict";

  const API_BASES = ["/api"];

  function applyTheme(theme) {
    document.documentElement.setAttribute("data-bs-theme", theme);
    localStorage.setItem("hr_portal_theme", theme);
    const icon = document.getElementById("themeIcon");
    if (icon) icon.className = theme === "dark" ? "bi bi-sun" : "bi bi-moon";
  }

  function fmtDate(value) {
    const raw = String(value || "").trim();
    if (!raw) return "-";
    const d = new Date(raw);
    if (Number.isNaN(d.getTime())) return raw;
    return d.toLocaleString("en-IN", { day: "2-digit", month: "short", year: "numeric", hour: "numeric", minute: "2-digit" });
  }

  function setMsg(text, ok) {
    const box = document.getElementById("smtpMsg");
    if (!box) return;
    box.className = `alert ${ok ? "alert-success" : "alert-danger"}`;
    box.textContent = text;
    box.classList.remove("d-none");
  }

  function clearMsg() {
    const box = document.getElementById("smtpMsg");
    if (!box) return;
    box.className = "alert d-none";
    box.textContent = "";
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

  function fillSummary(row) {
    document.getElementById("smtpStatusText").textContent = row.enabled ? "Enabled" : "Disabled";
    document.getElementById("smtpHostText").textContent = row.host || "-";
    document.getElementById("smtpFromText").textContent = row.fromEmail || "-";
    document.getElementById("smtpSavedText").textContent = fmtDate(row.__lastSaved);
    document.getElementById("smtpPasswordHint").textContent = row.hasPassword
      ? "A password is already stored. Leave the field empty to keep it."
      : "No password is stored yet.";
  }

  function fillForm(row) {
    document.getElementById("smtpEnabled").checked = !!row.enabled;
    document.getElementById("smtpHost").value = row.host || "";
    document.getElementById("smtpPort").value = row.port || 465;
    document.getElementById("smtpEncryption").value = row.encryption || "ssl";
    document.getElementById("smtpUsername").value = row.username || "";
    document.getElementById("smtpPassword").value = "";
    document.getElementById("smtpFromEmail").value = row.fromEmail || "";
    document.getElementById("smtpFromName").value = row.fromName || "HR Seva";
    document.getElementById("smtpReplyTo").value = row.replyTo || "";
    document.getElementById("smtpAdminEmails").value = row.adminEmails || "";
    fillSummary(row);
  }

  async function loadSettings(showToast) {
    const res = await apiFetch("/admin-smtp-settings");
    const data = await res.json();
    if (!res.ok) throw new Error(data?.detail || "Failed to load SMTP settings");
    fillForm(data.row || {});
    if (showToast) setMsg("SMTP settings reloaded.", true);
  }

  async function saveSettings(ev) {
    ev.preventDefault();
    clearMsg();
    const saveBtn = document.getElementById("smtpSaveBtn");
    saveBtn.disabled = true;
    saveBtn.textContent = "Saving...";
    try {
      const payload = {
        enabled: document.getElementById("smtpEnabled").checked,
        host: document.getElementById("smtpHost").value.trim(),
        port: Number(document.getElementById("smtpPort").value || 0),
        encryption: document.getElementById("smtpEncryption").value,
        username: document.getElementById("smtpUsername").value.trim(),
        password: document.getElementById("smtpPassword").value,
        fromEmail: document.getElementById("smtpFromEmail").value.trim(),
        fromName: document.getElementById("smtpFromName").value.trim(),
        replyTo: document.getElementById("smtpReplyTo").value.trim(),
        adminEmails: document.getElementById("smtpAdminEmails").value.trim()
      };
      const res = await apiFetch("/admin-smtp-settings", {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data?.detail || "Failed to save SMTP settings");
      fillForm(data.row || {});
      setMsg("SMTP settings saved successfully.", true);
    } catch (err) {
      setMsg(err.message || "Failed to save SMTP settings", false);
    } finally {
      saveBtn.disabled = false;
      saveBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Save Settings';
    }
  }

  async function sendTest(ev) {
    ev.preventDefault();
    clearMsg();
    const btn = document.getElementById("smtpTestBtn");
    btn.disabled = true;
    btn.textContent = "Sending...";
    try {
      const email = document.getElementById("smtpTestEmail").value.trim();
      if (!email) throw new Error("Test email is required");
      const res = await apiFetch("/admin-smtp-settings/test", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email })
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data?.detail || "Failed to send test email");
      setMsg(data?.message || "Test email sent successfully.", true);
    } catch (err) {
      setMsg(err.message || "Failed to send test email", false);
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-envelope-check me-1"></i>Send Test Email';
    }
  }

  function init() {
    applyTheme(localStorage.getItem("hr_portal_theme") || "light");
    document.getElementById("themeToggle")?.addEventListener("click", () => {
      const current = document.documentElement.getAttribute("data-bs-theme") || "light";
      applyTheme(current === "dark" ? "light" : "dark");
    });
    document.getElementById("smtpForm")?.addEventListener("submit", saveSettings);
    document.getElementById("smtpTestForm")?.addEventListener("submit", sendTest);
    document.getElementById("smtpReloadBtn")?.addEventListener("click", () => {
      loadSettings(true).catch((err) => setMsg(err.message || "Failed to reload SMTP settings", false));
    });
    loadSettings(false).catch((err) => setMsg(err.message || "Failed to load SMTP settings", false));
  }

  document.addEventListener("DOMContentLoaded", init);
})();
