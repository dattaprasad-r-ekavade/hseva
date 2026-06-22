(function () {
  "use strict";

  const API_BASES = ["/api", "/backend/api.php?path=/api"];
  let wizardStep = 1;

  function setupMotionPreferences() {
    if (!window.matchMedia) return false;
    const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    document.documentElement.classList.toggle("reduced-motion", prefersReducedMotion.matches);
    prefersReducedMotion.addEventListener?.("change", (event) => {
      document.documentElement.classList.toggle("reduced-motion", event.matches);
    });
    return prefersReducedMotion.matches;
  }

  function setupRevealStagger() {
    document.querySelectorAll(".feature-grid, .pricing-grid, .faq-grid, .metric-grid, .trusted-grid, .benefit-grid, .side-stack").forEach((group) => {
      group.querySelectorAll(".reveal, article, div").forEach((item, index) => {
        item.style.setProperty("--reveal-delay", `${Math.min(index, 5) * 80}ms`);
      });
    });
  }

  function observeReveal() {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) entry.target.classList.add("in");
      });
    }, { threshold: 0.12 });

    document.querySelectorAll(".reveal").forEach((el) => io.observe(el));
  }

  function setupHeroMotion() {
    const stage = document.querySelector(".hero-stage");
    if (!stage || document.documentElement.classList.contains("reduced-motion")) return;
    const layers = stage.querySelectorAll(".float-card, .hero-visual-card");
    stage.addEventListener("pointermove", (ev) => {
      const rect = stage.getBoundingClientRect();
      const x = ((ev.clientX - rect.left) / rect.width) - 0.5;
      const y = ((ev.clientY - rect.top) / rect.height) - 0.5;
      layers.forEach((layer, index) => {
        const depth = index === 1 ? 10 : 18;
        const moveX = x * depth;
        const moveY = y * depth;
        layer.style.transform = `translate3d(${moveX}px, ${moveY}px, 0)`;
      });
    });
    stage.addEventListener("pointerleave", () => {
      layers.forEach((layer) => {
        layer.style.transform = "";
      });
    });
  }

  function setupNav() {
    const btn = document.getElementById("navToggle");
    const nav = document.getElementById("navLinks");
    if (!btn || !nav) return;
    const setOpen = (open) => {
      nav.classList.toggle("open", open);
      btn.classList.toggle("active", open);
      btn.setAttribute("aria-expanded", open ? "true" : "false");
    };
    btn.setAttribute("aria-expanded", "false");
    btn.addEventListener("click", (ev) => {
      ev.stopPropagation();
      setOpen(!nav.classList.contains("open"));
    });
    nav.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", () => setOpen(false));
    });
    document.addEventListener("click", (ev) => {
      if (!nav.classList.contains("open")) return;
      if (nav.contains(ev.target) || btn.contains(ev.target)) return;
      setOpen(false);
    });
    window.addEventListener("resize", () => {
      if (window.innerWidth > 860) setOpen(false);
    });
  }

  function scrollToHash(hash, push = true) {
    if (!hash) return;
    const target = hash === "#top" ? document.body : document.querySelector(hash);
    if (!target) return;
    const header = document.querySelector(".site-header");
    // Keep a very small breathing gap under the sticky header.
    const offset = Math.max(8, (header?.offsetHeight || 0) - 6);
    const rect = target.getBoundingClientRect();
    const top = Math.max(0, window.scrollY + rect.top - offset);
    window.scrollTo({ top, behavior: "smooth" });
    if (push) window.history.pushState(null, "", hash);
  }

  function setupAnchorScroll() {
    document.querySelectorAll('a[href^="#"]').forEach((link) => {
      link.addEventListener("click", (ev) => {
        const href = link.getAttribute("href");
        if (!href || href === "#") return;
        const target = href === "#top" ? document.body : document.querySelector(href);
        if (!target) return;
        ev.preventDefault();
        scrollToHash(href);
      });
    });

    if (window.location.hash) {
      window.requestAnimationFrame(() => {
        scrollToHash(window.location.hash, false);
      });
    }
  }

  function setupFaq() {
    document.querySelectorAll(".faq-card").forEach((card, index) => {
      const body = card.querySelector("p");
      const btn = card.querySelector(".faq-toggle");
      if (!body || !btn) return;
      if (index === 0) card.classList.add("open");
      else body.style.display = "none";
      btn.addEventListener("click", () => {
        const open = body.style.display !== "none";
        document.querySelectorAll(".faq-card").forEach((item) => {
          item.classList.remove("open");
          const p = item.querySelector("p");
          if (p) p.style.display = "none";
        });
        if (!open) {
          body.style.display = "block";
          card.classList.add("open");
        }
      });
    });
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

  function showFormMsg(text, ok) {
    const box = document.getElementById("formMsg");
    if (!box) return;
    box.className = `form-msg ${ok ? "success" : "error"}`;
    box.textContent = text;
    box.classList.remove("hidden");
  }

  function collectModules() {
    return Array.from(document.querySelectorAll('input[name="modules"]:checked')).map((el) => el.value);
  }

  function setMinDate() {
    const el = document.getElementById("preferredDate");
    if (!el) return;
    const now = new Date();
    const m = String(now.getMonth() + 1).padStart(2, "0");
    const d = String(now.getDate()).padStart(2, "0");
    el.min = `${now.getFullYear()}-${m}-${d}`;
  }

  function resetForm(form) {
    form.reset();
    setMinDate();
  }

  function clearFormMsg() {
    const box = document.getElementById("formMsg");
    if (!box) return;
    box.textContent = "";
    box.className = "form-msg hidden";
  }

  function openDemoModal() {
    const modal = document.getElementById("demoModal");
    if (!modal) return;
    modal.classList.remove("hidden");
    modal.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
    wizardStep = 1;
    clearFormMsg();
    renderWizardStep();
  }

  function closeDemoModal() {
    const modal = document.getElementById("demoModal");
    if (!modal) return;
    modal.classList.add("hidden");
    modal.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
  }

  function validateWizardStep(step) {
    if (step === 1) {
      const fullName = document.getElementById("fullName")?.value || "";
      const companyName = document.getElementById("companyName")?.value || "";
      const workEmail = document.getElementById("workEmail")?.value || "";
      const phoneNo = document.getElementById("phoneNo")?.value || "";
      if (!fullName.trim()) return "Please enter your full name.";
      if (!companyName.trim()) return "Please enter your company name.";
      if (!workEmail.trim() && !phoneNo.trim()) return "Please provide at least an email or phone number.";
    }
    if (step === 2) {
      const productInterest = document.getElementById("productInterest")?.value || "";
      const preferredDate = document.getElementById("preferredDate")?.value || "";
      if (!productInterest.trim()) return "Please select the product area you want to discuss.";
      if (!preferredDate.trim()) return "Please choose your preferred demo date.";
    }
    return "";
  }

  function renderWizardStep() {
    const steps = document.querySelectorAll(".demo-step");
    steps.forEach((stepEl, index) => {
      stepEl.classList.toggle("active", index + 1 === wizardStep);
    });
    const dots = document.querySelectorAll(".wizard-dot");
    dots.forEach((dot, index) => {
      dot.classList.toggle("active", index + 1 <= wizardStep);
    });
    const railSteps = document.querySelectorAll(".wizard-step-item");
    railSteps.forEach((item, index) => {
      item.classList.toggle("active", index + 1 === wizardStep);
    });
    const text = document.getElementById("demoStepText");
    const progressLabel = document.getElementById("wizardProgressLabel");
    const prevBtn = document.getElementById("demoPrevBtn");
    const nextBtn = document.getElementById("demoNextBtn");
    const submitBtn = document.getElementById("submitBtn");
    const labels = {
      1: "Step 1 of 3: Tell us how to reach you.",
      2: "Step 2 of 3: Set the session scope and preferred timing.",
      3: "Step 3 of 3: Choose modules and add any notes."
    };
    if (text) text.textContent = labels[wizardStep] || "";
    if (progressLabel) progressLabel.textContent = `Step ${wizardStep} of 3`;
    if (prevBtn) prevBtn.classList.toggle("hidden", wizardStep === 1);
    if (nextBtn) nextBtn.classList.toggle("hidden", wizardStep === 3);
    if (submitBtn) submitBtn.classList.toggle("hidden", wizardStep !== 3);
  }

  function setupDemoWizard() {
    document.querySelectorAll("[data-open-demo='true']").forEach((trigger) => {
      trigger.addEventListener("click", (ev) => {
        ev.preventDefault();
        openDemoModal();
      });
    });
    document.getElementById("closeDemoModal")?.addEventListener("click", closeDemoModal);
    document.querySelectorAll("[data-close-demo='true']").forEach((el) => {
      el.addEventListener("click", closeDemoModal);
    });
    document.getElementById("demoPrevBtn")?.addEventListener("click", () => {
      wizardStep = Math.max(1, wizardStep - 1);
      clearFormMsg();
      renderWizardStep();
    });
    document.getElementById("demoNextBtn")?.addEventListener("click", () => {
      const msg = validateWizardStep(wizardStep);
      if (msg) return showFormMsg(msg, false);
      wizardStep = Math.min(3, wizardStep + 1);
      clearFormMsg();
      renderWizardStep();
    });
    document.querySelectorAll(".wizard-step-item").forEach((item, index) => {
      item.addEventListener("click", () => {
        if (index + 1 > wizardStep) return;
        wizardStep = index + 1;
        clearFormMsg();
        renderWizardStep();
      });
    });
    document.addEventListener("keydown", (ev) => {
      if (ev.key === "Escape") closeDemoModal();
    });
    renderWizardStep();
  }

  async function submitDemoForm(ev) {
    ev.preventDefault();
    const form = ev.currentTarget;
    const submitBtn = document.getElementById("submitBtn");
    const payload = {
      fullName: document.getElementById("fullName")?.value || "",
      companyName: document.getElementById("companyName")?.value || "",
      workEmail: document.getElementById("workEmail")?.value || "",
      phoneNo: document.getElementById("phoneNo")?.value || "",
      teamSize: document.getElementById("teamSize")?.value || "",
      productInterest: document.getElementById("productInterest")?.value || "",
      preferredDate: document.getElementById("preferredDate")?.value || "",
      preferredTime: document.getElementById("preferredTime")?.value || "",
      modules: collectModules(),
      message: document.getElementById("message")?.value || "",
      sourcePage: "landing"
    };

    if (!payload.fullName.trim()) return showFormMsg("Please enter your full name.", false);
    if (!payload.companyName.trim()) return showFormMsg("Please enter your company name.", false);
    if (!payload.workEmail.trim() && !payload.phoneNo.trim()) return showFormMsg("Please provide at least an email or phone number.", false);
    if (!payload.productInterest.trim()) return showFormMsg("Please select the product area you want to discuss.", false);
    if (!payload.preferredDate.trim()) return showFormMsg("Please choose your preferred demo date.", false);

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = "Sending...";
    }

    try {
      const res = await apiFetch("/public-enquiries", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data?.detail || "Failed to submit enquiry");
      showFormMsg("Your demo request has been received. Our team will connect with you soon.", true);
      resetForm(form);
      wizardStep = 1;
      renderWizardStep();
      setTimeout(closeDemoModal, 900);
    } catch (err) {
      showFormMsg(err.message || "Unable to submit your enquiry right now.", false);
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = "Book Demo / Send Enquiry";
      }
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    setupMotionPreferences();
    setupRevealStagger();
    observeReveal();
    setupHeroMotion();
    setupNav();
    setupAnchorScroll();
    setupFaq();
    setupDemoWizard();
    setMinDate();
    document.getElementById("demoForm")?.addEventListener("submit", submitDemoForm);
  });
})();
