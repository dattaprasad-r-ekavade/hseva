(function () {
  "use strict";

  const yearEl = document.getElementById("yr");
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  const API_BASE = window.location.protocol === "file:" ? "http://127.0.0.1:8012" : "";
  const API_LOGIN = API_BASE + "/api/auth/login";
  const API_SESSION = API_BASE + "/api/auth/session";
  const KEY_AUTH = "hr_auth_session_v1";
  const form = document.getElementById("adminLoginForm");
  const username = document.getElementById("adminUserId");
  const password = document.getElementById("adminPassword");
  const msg = document.getElementById("adminLoginMsg");
  const toggleBtn = document.getElementById("toggleAdminPass");
  let isSubmitting = false;

  function showMsg(text, ok) {
    msg.className = `alert ${ok ? "alert-success" : "alert-danger"}`;
    msg.textContent = text;
    msg.classList.remove("d-none");
  }

  function clearMsg() {
    msg.classList.add("d-none");
    msg.textContent = "";
  }

  async function readJsonSafely(res) {
    const raw = await res.text();
    if (!raw) return {};
    try {
      return JSON.parse(raw);
    } catch (_e) {
      throw new Error("Server returned an invalid response. Please refresh and try again.");
    }
  }

  async function validateStoredSession(storedAuth) {
    if (!storedAuth || !storedAuth.token) return null;
    try {
      const res = await fetch(API_SESSION, {
        headers: { Authorization: `Bearer ${storedAuth.token}` },
        cache: "no-store"
      });
      const data = await readJsonSafely(res);
      if (!res.ok || !data?.valid) {
        localStorage.removeItem(KEY_AUTH);
        sessionStorage.removeItem(KEY_AUTH);
        return null;
      }
      const normalized = {
        token: storedAuth.token,
        user: data.user || storedAuth.user || {},
        at: storedAuth.at || new Date().toISOString()
      };
      sessionStorage.setItem(KEY_AUTH, JSON.stringify(normalized));
      localStorage.removeItem(KEY_AUTH);
      return normalized;
    } catch (_e) {
      return storedAuth;
    }
  }

  async function redirectIfSessionAlive() {
    const storedAuth = JSON.parse(sessionStorage.getItem(KEY_AUTH) || "null");
    if (!storedAuth) {
      try { localStorage.removeItem(KEY_AUTH); } catch (_e) {}
    }
    const liveAuth = await validateStoredSession(storedAuth);
    if (liveAuth && liveAuth.token) {
      const role = String(liveAuth?.user?.role || "").toLowerCase();
      window.location.replace(role === "super_admin" ? "super-admin-dashboard.html" : "../client/index.html");
      return true;
    }
    return false;
  }

  function resetLoginUi() {
    clearMsg();
    const btn = form.querySelector("button[type='submit']");
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = 'Sign In <i class="bi bi-arrow-right-short"></i>';
    }
    isSubmitting = false;
  }

  toggleBtn.addEventListener("click", function () {
    const isPass = password.type === "password";
    password.type = isPass ? "text" : "password";
    toggleBtn.innerHTML = isPass ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
  });

  redirectIfSessionAlive();

  // Prevent stale BFCache UI on browser back/forward restore.
  window.addEventListener("pageshow", async function () {
    resetLoginUi();
    await redirectIfSessionAlive();
  });

  form.addEventListener("submit", async function (e) {
    e.preventDefault();
    if (isSubmitting) return;
    clearMsg();
    let ok = true;
    [username, password].forEach(function (el) {
      if (!el.value.trim()) {
        ok = false;
        el.classList.add("is-invalid");
      } else {
        el.classList.remove("is-invalid");
      }
    });
    if (!ok) return;

    const btn = form.querySelector("button[type='submit']");
    const old = btn.innerHTML;
    isSubmitting = true;
    btn.disabled = true;
    btn.innerHTML = "Please wait...";
    try {
      const res = await fetch(API_LOGIN, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username: username.value.trim(), password: password.value })
      });
      const data = await readJsonSafely(res);
      if (!res.ok) throw new Error(data?.detail || "Login failed");
      const role = String(data?.user?.role || "").toLowerCase();
      if (role !== "super_admin") throw new Error("Only Super Admin can login here.");

      sessionStorage.setItem(KEY_AUTH, JSON.stringify({
        token: data.token,
        user: data.user,
        at: new Date().toISOString()
      }));
      localStorage.removeItem(KEY_AUTH);
      showMsg("Login successful. Redirecting...", true);
      setTimeout(function () {
        window.location.replace("super-admin-dashboard.html");
      }, 150);
    } catch (err) {
      showMsg(err.message || "Unable to login", false);
    } finally {
      isSubmitting = false;
      btn.disabled = false;
      btn.innerHTML = old;
    }
  });
})();
