document.getElementById("yr").textContent = new Date().getFullYear();

    const API_BASE = window.location.protocol === "file:" ? "http://127.0.0.1:8012" : "";
    const API_LOGIN = API_BASE + "/api/auth/login";
    const API_FORGOT = API_BASE + "/api/auth/forgot";
    const API_SESSION = API_BASE + "/api/auth/session";
    const KEY_AUTH = "hr_auth_session_v1";
    const KEY_REMEMBER = "hr_auth_remember_v1";

    const form = document.getElementById("loginForm");
    const user = document.getElementById("userId");
    const pass = document.getElementById("userPassword");
    const remember = document.getElementById("remember");
    const loginMsg = document.getElementById("loginMsg");
    const toggleBtn = document.getElementById("togglePass");

    const forgotModal = document.getElementById("forgotModal");
    const forgotInput = forgotModal.querySelector("input[type='email']");
    const forgotSendBtn = forgotModal.querySelector(".btn.btn-primary");
    const path = (window.location.pathname || "").toLowerCase();
    const isClientFolder = path.includes("/client/");
    const adminHome = isClientFolder ? "../super-admin/super-admin-dashboard.html" : "super-admin/super-admin-dashboard.html";
    const clientHome = isClientFolder ? "index.html" : "client/index.html";
    let isSubmitting = false;

    function showMsg(text, ok = false){
      loginMsg.className = `alert ${ok ? "alert-success" : "alert-danger"}`;
      loginMsg.textContent = text;
      loginMsg.classList.remove("d-none");
    }

    function clearMsg(){
      loginMsg.classList.add("d-none");
      loginMsg.textContent = "";
    }

    async function readJsonSafely(res){
      const raw = await res.text();
      if(!raw) return {};
      try {
        return JSON.parse(raw);
      } catch (_e) {
        throw new Error("Server returned an invalid response. Please refresh and try again.");
      }
    }

    async function validateStoredSession(storedAuth){
      if(!storedAuth || !storedAuth.token) return null;
      try {
        const res = await fetch(API_SESSION, {
          headers: { Authorization: `Bearer ${storedAuth.token}` },
          cache: "no-store"
        });
        const data = await readJsonSafely(res);
        if(!res.ok || !data?.valid){
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
      } catch(_e){
        return storedAuth;
      }
    }

    async function redirectIfSessionAlive(){
      const storedAuth = JSON.parse(sessionStorage.getItem(KEY_AUTH) || "null");
      if(!storedAuth){
        try { localStorage.removeItem(KEY_AUTH); } catch(_e) {}
      }
      const liveAuth = await validateStoredSession(storedAuth);
      if(liveAuth && liveAuth.token){
        const role = String(liveAuth?.user?.role || "").toLowerCase();
        window.location.replace(role === "super_admin" ? adminHome : clientHome);
        return true;
      }
      return false;
    }

    function resetLoginUi(){
      clearMsg();
      const btn = form.querySelector("button[type='submit']");
      if(btn){
        btn.disabled = false;
        btn.innerHTML = 'Login <i class="bi bi-arrow-right-short"></i>';
      }
      isSubmitting = false;
    }

    toggleBtn.addEventListener("click", () => {
      const isPass = pass.type === "password";
      pass.type = isPass ? "text" : "password";
      toggleBtn.innerHTML = isPass ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    });

    const remembered = localStorage.getItem(KEY_REMEMBER);
    if(remembered){
      user.value = remembered;
      remember.checked = true;
    }

    redirectIfSessionAlive();

    // Prevent stale BFCache UI (e.g. "Login successful. Redirecting...") on back navigation.
    window.addEventListener("pageshow", async () => {
      resetLoginUi();
      await redirectIfSessionAlive();
    });

    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      if(isSubmitting) return;
      clearMsg();
      let ok = true;
      [user, pass].forEach((el) => {
        if (!el.value.trim()) {
          ok = false;
          el.classList.add("is-invalid");
        } else {
          el.classList.remove("is-invalid");
        }
      });
      if(!ok) return;

      const btn = form.querySelector("button[type='submit']");
      const old = btn.innerHTML;
      isSubmitting = true;
      btn.disabled = true;
      btn.innerHTML = 'Please wait...';
      try {
        const res = await fetch(API_LOGIN, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ username: user.value.trim(), password: pass.value })
        });
        const data = await readJsonSafely(res);
        if(!res.ok) throw new Error(data.detail || "Login failed");

        sessionStorage.setItem(KEY_AUTH, JSON.stringify({
          token: data.token,
          user: data.user,
          at: new Date().toISOString()
        }));
        localStorage.removeItem(KEY_AUTH);
        if(remember.checked) localStorage.setItem(KEY_REMEMBER, user.value.trim());
        else localStorage.removeItem(KEY_REMEMBER);

        showMsg("Login successful. Redirecting...", true);
        const role = String(data?.user?.role || "").toLowerCase();
        setTimeout(() => { window.location.replace(role === "super_admin" ? adminHome : clientHome); }, 150);
      } catch(err){
        showMsg(err.message || "Unable to login");
      } finally {
        isSubmitting = false;
        btn.disabled = false;
        btn.innerHTML = old;
      }
    });

    forgotSendBtn.addEventListener("click", async () => {
      const email = (forgotInput.value || "").trim();
      if(!email){
        alert("Enter registered email");
        return;
      }
      try {
        const res = await fetch(API_FORGOT, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ email })
        });
        const data = await res.json();
        if(!res.ok) throw new Error(data.detail || "Unable to process");
        alert(data.message || "Reset instruction sent.");
        bootstrap.Modal.getOrCreateInstance(forgotModal).hide();
      } catch(err){
        alert(err.message || "Unable to process");
      }
    });
