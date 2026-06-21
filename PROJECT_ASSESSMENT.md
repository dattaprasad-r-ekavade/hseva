# HR Seva — Project Assessment & Roadmap to 10/10

**Document version:** 1.0  
**Date:** June 21, 2026  
**Scope:** Full-stack review (backend, frontend, product, security, DevOps)  
**Reviewer lens:** Senior Software Developer + Product Engineer

---

## Executive summary

HR Seva is a **PHP + SQLite multi-tenant HR portal** with a marketing landing page, client portal, and super-admin portal. It delivers substantial India-focused HR functionality: employee master, attendance, shift roster, payroll, PF/ESIC/ECR compliance, loans, advances, subscriptions, and browser-based face attendance.

**Overall rating: 6 / 10**

The product depth is ahead of the engineering foundation. It is a credible functional MVP for small agencies or early pilots, but not yet production-grade enterprise SaaS without security hardening, test coverage, and architectural refactoring.

| Dimension | Score | Summary |
|-----------|-------|---------|
| Product depth | 7.5 / 10 | Real payroll, compliance, attendance modules |
| Product honesty (marketing vs shipped) | 4 / 10 | Landing overpromises recruitment, helpdesk, outsourced HR |
| Backend architecture | 4 / 10 | ~5,000-line monolith in `backend/api.php` |
| Security | 4 / 10 | Default credentials, weak secret fallback, inconsistent RBAC |
| Frontend maintainability | 5 / 10 | ~70 duplicate HTML shells, dual asset trees |
| Engineering quality (tests, CI, deploy) | 2 / 10 | No PHPUnit, no CI, no Docker |
| UX & polish | 6 / 10 | Solid portal shell; landing has template debt |
| Differentiation | 7 / 10 | India compliance + face attendance (not marketed on landing) |

---

## Table of contents

1. [Architecture overview](#1-architecture-overview)
2. [How the project runs](#2-how-the-project-runs)
3. [What is working well](#3-what-is-working-well)
4. [Critical gaps](#4-critical-gaps)
5. [Detailed findings by area](#5-detailed-findings-by-area)
6. [Product: landing vs portal](#6-product-landing-vs-portal)
7. [Security assessment](#7-security-assessment)
8. [Roadmap to 10/10](#8-roadmap-to-1010)
9. [Quick wins (first 30 days)](#9-quick-wins-first-30-days)
10. [Milestone scorecard](#10-milestone-scorecard)

---

## 1. Architecture overview

### Stack

| Layer | Path | Technology |
|-------|------|------------|
| Marketing landing | `index.html` | Uni-core theme, `assets-02/` (jQuery, GSAP, Swiper) |
| Client portal | `client/` | Bootstrap 5.3 CDN, `assets/` |
| Super-admin portal | `super-admin/` | Same as client |
| Shared portal logic | `assets/js/app-common.js` | Auth, RBAC, tenant headers, sidebar (~2,040 lines) |
| Backend API | `backend/api.php` | Procedural PHP monolith (~4,970 lines) |
| Extracted module | `backend/shift_module.php` | Shift roster domain logic |
| Email | `backend/mail.php` | Custom SMTP client |
| Routing (dev) | `router.php` | PHP built-in server |
| Routing (prod) | `.htaccess` | Apache rewrite to `backend/api.php` |

**No build toolchain:** No Composer, npm, bundler, or framework.

### Database layout

```
storage/clients/
├── app.db                    # Central DB (clients, subscriptions, enquiries, SMTP)
└── tenant_{id}/
    └── app.db                # Per-tenant DB (employees, payroll, attendance, etc.)
```

Legacy path `backend/app.db` is migrated automatically to central storage if present.

### Multi-tenancy model

- **Central DB** holds: clients, staff users, access types, public enquiries, subscription plans, SMTP settings.
- **Per-tenant DB** holds: employees, attendance, payroll sheets, loans, face descriptors, etc.
- Super-admin switches tenants via `X-Client-Id` header (injected by `app-common.js` from session/localStorage).
- Physical file isolation per tenant is good for backups; operational complexity grows with client count.

### Request flow

```
Browser → index.html / client/*.html / super-admin/*.html
       → fetch("/api/...") with Bearer token + X-Client-Id
       → router.php (dev) or .htaccess (Apache)
       → backend/api.php
       → SQLite (central or tenant DB)
```

---

## 2. How the project runs

### Development (recommended)

```powershell
cd D:\Projects\hrseva
php -S 127.0.0.1:8012 router.php
```

Or double-click `run-php.bat`.

**URLs:**
- Landing: `http://127.0.0.1:8012/`
- Client login: `http://127.0.0.1:8012/client/client-login.html`
- Super-admin: `http://127.0.0.1:8012/super-admin/super-admin-login.html`

### Production (XAMPP / Apache)

Copy project to `htdocs`, start Apache. `.htaccess` handles API routing and blocks direct access to `storage/` and `.db` files.

### Requirements

| Requirement | Notes |
|-------------|-------|
| PHP 8+ | With `pdo_sqlite` extension |
| Web server | Apache (with mod_rewrite) or PHP built-in server |
| Browser | For face attendance: camera + `localhost`/`https` |
| Internet | First face-attendance load pulls face-api.js from CDN |

### Configuration

| Setting | Location | Required? |
|---------|----------|-----------|
| Database | Auto-created at `storage/clients/app.db` | No manual setup |
| JWT secret | Env `HR_APP_SECRET` or default in code | **Must set in production** |
| SMTP | `backend/mail-config.php` or env vars | Optional (disabled by default) |
| Default admin | Seeded in `backend/api.php` | **Must change in production** |

**SMTP environment variables (optional):**
- `HR_SMTP_ENABLED`, `HR_SMTP_HOST`, `HR_SMTP_PORT`, `HR_SMTP_ENCRYPTION`
- `HR_SMTP_USERNAME`, `HR_SMTP_PASSWORD`
- `HR_SMTP_FROM_EMAIL`, `HR_SMTP_FROM_NAME`, `HR_SMTP_REPLY_TO`, `HR_SMTP_ADMIN_EMAILS`

Copy `backend/mail-config.example.php` → `backend/mail-config.php` for local SMTP setup.

---

## 3. What is working well

### Product & domain

1. **End-to-end flows exist** — Landing enquiry → admin onboarding → client tenant → payroll/compliance/attendance.
2. **India-focused compliance** — PF sheet/return, ESIC sheet/return, ECR, bonus, gratuity, FNF, professional tax.
3. **Face attendance** — Real browser-based module using face-api.js; IN/OUT rules, monthly reports, admin registration. Differentiator not reflected on landing.
4. **Subscription & billing** — Plans, client subscriptions, platform-level billing pages.
5. **Role-based access** — Access types, custom permissions, employee portal vs admin roles.

### Engineering positives

1. **`declare(strict_types=1)`** throughout PHP.
2. **PDO prepared statements** — Heavy use reduces SQL injection risk.
3. **JWT authentication** with `hash_equals` signature verification.
4. **Login rate limiting** — 5 failures / 15-minute block (`auth_login_attempts`).
5. **Subscription expiry** blocks client/employee access.
6. **Permissions re-resolved from DB** on each request (not frozen in JWT).
7. **`.htaccess` security baseline** — Blocks `storage/`, denies `.db`/`.env`, CSP and security headers.
8. **Centralized frontend auth** — `app-common.js` wraps `fetch` with `Authorization` + `X-Client-Id`.
9. **Tenant SQLite isolation** — Physical separation per client.
10. **Git hygiene** — `.gitignore` excludes DBs, runtime storage, local mail config.

---

## 4. Critical gaps

### Security (highest priority)

| Issue | Location | Risk |
|-------|----------|------|
| Default super-admin password `123456` | `backend/api.php` (`DEFAULT_AUTH_USERS`) | Trivial compromise |
| Password pre-filled on login page | `super-admin/super-admin-login.html` | Encourages weak credentials |
| Default JWT secret | `app_secret()` → `'change-this-secret-before-production'` | Token forgery if env unset |
| Unguarded destructive routes | e.g. `POST /api/clients/clear` | Any auth user may wipe data |
| Plaintext password fallback | Legacy client/staff accounts | Credential exposure |
| Biometric PII in SQLite | `employee_faces` table | Sensitive data at rest, unencrypted |
| Face scan trusts client descriptor | `face_attendance_scan()` | API spoofing without liveness |
| Permissive directory permissions | `mkdir(..., 0777)` | Shared-host risk |
| No API rate limit (except login) | Public enquiry, forgot-password | Spam/abuse |

### Engineering

| Issue | Impact |
|-------|--------|
| No automated tests | Payroll/compliance regressions undetected |
| No CI/CD | No quality gate on commits |
| No Docker / deploy automation | Inconsistent environments |
| 5,000-line `api.php` | Hard to review, test, and onboard |
| Schema migrations inline | `init_schema()` + `ALTER TABLE` on every request path |
| No `.env.example` | Onboarding friction, secret misconfiguration |

### Product & UX

| Issue | Impact |
|-------|--------|
| Marketing overpromises | Recruitment, helpdesk, outsourced HR not in codebase |
| Face attendance not on landing | Hidden strength |
| Landing template debt | Cart, favorites, Lorem ipsum, dead blog links |
| ~70 duplicate HTML page shells | Layout change touches many files |
| Dual asset trees | `assets/` vs `assets-02/` confusion |

### Scalability

| Limit | Cause |
|-------|-------|
| Single-host SQLite | No horizontal scaling across app servers |
| Per-tenant file sprawl | Backup/monitoring complexity |
| JSON blob payroll sheets in `app_kv` | Memory-heavy, hard to query/audit |
| Face matching O(n) | Scans all `employee_faces` rows |

---

## 5. Detailed findings by area

### 5.1 Backend (`backend/api.php`)

**Pattern:** Procedural monolith — helpers at top, domain logic in middle, flat `if ($path === '/api/...')` router at bottom (~95 route branches).

**Strengths:**
- Consistent JSON helpers (`j()`, `bad()`, `meth()`)
- Role-specific context helpers for face attendance, advances, loans, overtime
- `shift_module.php` shows viable extraction pattern

**Weaknesses:**
- God file — routing, schema, auth, payroll, compliance, face attendance, email in one file
- Inconsistent authorization — admin routes use `require_super_admin()`; many `/clear` routes only need any valid token
- Sparse module permission checks outside advances/loans
- Short helper names (`j`, `s`, `f`, `b`) hurt readability at scale
- Payroll/attendance stored as JSON blobs in `app_kv` — not normalized

**When `req_client_id()` is 0** (super-admin without `X-Client-Id`), `db()` points at central DB — risk of reading/writing wrong data.

**`client_delete()`** removes central rows but not `tenant_{id}/` folder.

### 5.2 Frontend

**Portal pattern:** Multi-page static HTML with one page-specific JS file per feature. Sidebar HTML on each page is largely stale; `app-common.js` replaces it at runtime with canonical nav.

**Duplication:**
- `client-attendance.html` vs `super-admin-attendance.html` — near-identical shells
- Super-admin often loads `client-*.js` files (naming confusion)
- `API_BASES` fallback `["/api", "/backend/api.php?path=/api"]` copied in ~20 files

**Landing (`index.html`, ~3,318 lines):**
- Active sections: hero, sponsors, features, pricing, FAQ, CTA
- Commented out: testimonials, blog
- Template cruft: shopping cart, favorites, newsletter modals, Lorem ipsum disclaimers
- Free-trial form posts to `/public-enquiries` with inline fetch logic

### 5.3 Face attendance module

**Files:**
- Pages: `client/scan-attendance.php`, `face-attendance-*.php` (+ super-admin mirrors)
- Logic: `assets/js/face-attendance.js`, `assets/css/face-attendance.css`
- Schema: `backend/sql/face_attendance_schema.sql`
- Docs: `FACE_ATTENDANCE_SETUP.md`

**Flow:**
1. `data-face-page` on `<body>` selects mode (register, settings, sheet, report, scan, my-attendance)
2. Loads face-api.js (CDN) + models from `/assets/vendor/face-api-models`
3. Webcam → 128-d descriptor → PHP match → IN/OUT with time-window rules

**Gaps:** Not advertised on landing; limited screen-reader feedback; biometric data unencrypted at rest.

### 5.4 DevOps & quality

| Item | Status |
|------|--------|
| Unit/integration tests | None |
| GitHub Actions / CI | None |
| Docker / compose | None |
| `.env.example` | None (only `mail-config.example.php`) |
| Composer / dependencies | Zero-vendor PHP |
| Formal migration runner | SQL files exist but schema applied inline |
| Deployment docs | `backend/README.md` only |

---

## 6. Product: landing vs portal

### Implemented in portal (matches much of pricing)

- Employee master & types
- Shift roster
- Manual attendance sheet
- Face attendance (built, not marketed)
- Leave management
- Overtime, advance salary, loan
- Salary sheet, payslips
- PF / ESIC / ECR sheets & returns
- FNF, bonus, gratuity, incentive
- Compliance calendar
- Roles & access control
- Subscriptions & billing
- Employee self-service profile
- Dashboard KPIs
- Excel exports on several modules
- Landing enquiry capture + email notifications

### Advertised but missing or service-only

| Landing claim | Status |
|---------------|--------|
| Recruitment / hiring | Marketing only — no ATS |
| HR Consultancy / Outsourced HR | Service positioning, no software module |
| Employee Helpdesk Support | Listed in pricing — no ticketing UI |
| HR Workflows & Automation | Vague; only "Sheet Workflow" widget on dashboard |
| On-Call HR Assistance | Service promise, not product feature |
| Dedicated analytics hub | Per-module exports only |
| Mobile app | Responsive web only |
| Face attendance | Built but not on landing or plan lists |
| Blog | Section commented out; dead links |

### Pricing alignment

Free Trial / Starter / Growth tiers list portal features that largely exist. Hidden "View more" items (helpdesk, workflows, on-call HR) are aspirational or outsourced services, not shipped software.

---

## 7. Security assessment

### Authentication flow

1. `POST /api/auth/login` → custom HS256 JWT (12h TTL)
2. Frontend stores session in `sessionStorage` (`hr_auth_session_v1`)
3. `app-common.js` injects `Authorization: Bearer` + `X-Client-Id` on all API calls
4. Staff permissions = intersect(company access type, role permissions)

### `.htaccess` protections

- Serves real files directly
- Routes `/api/*` to `backend/api.php`
- Blocks `storage/` and `backend/*` except `api.php`
- Denies `*.db`, `*.env`, `*.log`, etc.
- Security headers: `X-Content-Type-Options`, `X-Frame-Options`, CSP, HSTS (HTTPS)

**Dev server note:** `php -S` does not apply `.htaccess` — use Apache for production parity.

### SMTP configuration precedence

1. Environment variables
2. Database (`app_kv.smtp_settings`)
3. `backend/mail-config.php`

### Recommended immediate fixes

1. Guard `/api/clients/clear` and audit all `/clear` routes with `require_super_admin()`
2. Set `HR_APP_SECRET` in production; rotate if ever exposed
3. Remove default `123456` admins; force password change on first login
4. Fail fast when super-admin tenant ops run without `X-Client-Id > 0`
5. Encrypt or externalize face descriptors; add scan rate limiting
6. Change storage dir permissions from `0777` to `0750`
7. Delete tenant folders on client deletion

---

## 8. Roadmap to 10/10

### Phase 1 — Trust & safety (6 → 7) · 2–4 weeks

**Goal:** Safe to put real client data in production.

- [ ] Remove default credentials; force password change on first super-admin login
- [ ] Require `HR_APP_SECRET` in production; fail startup if unset
- [ ] Audit every `/clear` and destructive route — enforce `require_super_admin()` and module permissions
- [ ] Remove pre-filled password from `super-admin-login.html`
- [ ] Add rate limiting on public endpoints (`/public-enquiries`, forgot-password)
- [ ] Create `.env.example` documenting all secrets
- [ ] Encrypt face descriptors at rest; add scan rate limits
- [ ] Fix directory permissions (`0750`); delete tenant folders on `client_delete()`
- [ ] Security review checklist sign-off before first production client

### Phase 2 — Engineering foundation (7 → 8) · 4–8 weeks

**Goal:** Team can ship without fear.

- [ ] Split `api.php` into domains: `auth`, `clients`, `employees`, `payroll`, `compliance`, `face-attendance`, `enquiries`
- [ ] Introduce thin router or lightweight framework (Slim, Laravel modules)
- [ ] Wire `backend/migrations/` to formal migration runner
- [ ] Add PHPUnit smoke tests: login, tenant isolation, payroll generation, destructive endpoint auth
- [ ] GitHub Actions CI: PHP lint, PHPUnit, optional security scan
- [ ] Docker Compose for reproducible dev and deploy
- [ ] Centralize `API_BASES` in one frontend config module
- [ ] Update `backend/README.md` with accurate DB paths and env vars

### Phase 3 — Product integrity (8 → 8.5) · 4–6 weeks

**Goal:** What you sell matches what you ship.

- [ ] Add face attendance to landing feature lists and pricing tiers
- [ ] Remove or clearly label "services" (consultancy, on-call HR) vs software features
- [ ] Decide: build or remove recruitment, helpdesk, workflows from marketing
- [ ] Clean `index.html` — remove cart, favorites, Lorem ipsum, dead blog links
- [ ] Merge or clearly document `assets/` vs `assets-02/` boundaries
- [ ] Add in-app onboarding checklist (employee setup → face registration → first payroll)

### Phase 4 — Maintainable frontend (8.5 → 9) · 6–10 weeks

**Goal:** One layout change does not touch 70 files.

- [ ] Extract shared layout (sidebar, topbar, modals) into PHP includes or templating layer
- [ ] Evaluate single portal shell with route-based views or small SPA for admin areas
- [ ] Rename JS files to reflect actual usage (not `client-*.js` on super-admin pages)
- [ ] Accessibility pass: skip links, live regions for face scan, reduce motion on landing
- [ ] Consistent API error UX across all modules
- [ ] Remove duplicate static sidebar HTML (rely on `app-common.js` canonical list only)

### Phase 5 — Scale & enterprise readiness (9 → 10) · 8–16 weeks

**Goal:** Multi-tenant SaaS that enterprises can trust.

| Area | Action |
|------|--------|
| Database | PostgreSQL/MySQL with `tenant_id`, or managed SQLite + backup strategy |
| Auth | Refresh tokens, MFA for admins, audit log for sensitive actions |
| Observability | Structured logging, error tracking (Sentry), health metrics |
| Async work | Queue for payroll generation, email, report exports |
| Compliance | Data retention policy, export/delete, SOC2-oriented controls |
| Deploy | Staging/prod environments, automated backups, rollback playbook |
| Mobile | PWA or native app for employee self-service |
| Analytics | Dedicated dashboard beyond per-module exports |
| API | OpenAPI/Swagger documentation for integrations |

### Phase 6 — Product excellence (polish to 10)

- [ ] Helpdesk module (if kept in pricing): tickets, SLAs, employee portal integration
- [ ] Workflow engine: leave approval chains, payroll sign-off, compliance reminders
- [ ] Recruitment ATS (if kept in marketing): jobs, applicants, pipeline
- [ ] Penetration test + security disclosure policy
- [ ] Customer success metrics: activation funnel, time-to-first-payroll, NPS

---

## 9. Quick wins (first 30 days)

If only five things get done this month:

1. **Fix security defaults** — passwords, JWT secret, `/clear` route authorization
2. **Add 20–30 PHPUnit tests** — auth, payroll, tenant isolation
3. **Clean landing page** — align pricing copy with shipped features
4. **Split `api.php`** — at least 5 domain files
5. **Add Docker + CI** — every push validated automatically

### Additional low-effort improvements

| Task | Effort | Impact |
|------|--------|--------|
| Add `(optional)` to Website URL field placeholder | 5 min | UX clarity |
| Create root `README.md` with run instructions | 1 hr | Onboarding |
| Add `HR_APP_SECRET` to `.env.example` | 30 min | Security |
| Remove Lorem ipsum from landing disclaimers | 1 hr | Professional polish |
| Document face attendance on pricing section | 2 hr | Product honesty |
| Delete tenant folder on `client_delete()` | 2 hr | Data hygiene |

---

## 10. Milestone scorecard

| Milestone | Rating | Description |
|-----------|--------|-------------|
| **Today** | **6 / 10** | Rich MVP, risky for production |
| After Phase 1 (security) | **7 / 10** | Pilot-ready with real clients |
| After Phase 2 (engineering) | **8 / 10** | Team can scale development |
| After Phases 3–4 (product + frontend) | **8.5–9 / 10** | Credible commercial SaaS |
| After Phases 5–6 (scale + missing modules) | **10 / 10** | Enterprise-grade, marketing-aligned |

---

## Appendix A — Key file reference

| Purpose | Path |
|---------|------|
| Landing page | `index.html` |
| API monolith | `backend/api.php` |
| Shift module | `backend/shift_module.php` |
| Email | `backend/mail.php`, `backend/mail-config.example.php` |
| Dev router | `router.php` |
| Apache config | `.htaccess` |
| Portal shared JS | `assets/js/app-common.js` |
| Face attendance | `assets/js/face-attendance.js`, `FACE_ATTENDANCE_SETUP.md` |
| Client portal | `client/` |
| Super-admin portal | `super-admin/` |
| Runtime storage | `storage/clients/` (gitignored) |
| API docs | `backend/README.md` |
| Git ignore rules | `.gitignore` |
| Dev launcher | `run-php.bat` |

## Appendix B — Default credentials (change immediately)

| Role | Username | Password |
|------|----------|----------|
| Super admin | `admin` | `123456` |
| Super admin | `admin@hrseva.com` | `123456` |

**Do not use these in production.**

## Appendix C — API surface (summary)

Full endpoint list: `backend/README.md`

Core groups:
- Auth: `/api/auth/login`, `/api/auth/forgot`, `/api/auth/session`
- Clients & access control
- Employees, leaves, attendance
- Payroll, PF, ESIC, ECR, payslips, FNF
- Face attendance: `/api/face-attendance/*`
- Public: `/api/public-enquiries`
- Health: `/api/health`

---

*This document should be updated as phases complete. Link PRs and tickets to checklist items when work begins.*

---

## Appendix D — Super-admin UI audit (Playwright)

**Test date:** June 21, 2026  
**Tool:** Playwright (`@playwright/test`) + Chromium headless  
**Script:** `qa/tests/super-admin-ui-audit.spec.js`  
**Report output:** `qa/tests/super-admin-ui-report.json` (local only, `qa/` is gitignored)  
**Run command:** `cd qa && npm run test:ui-audit` (requires PHP server on `http://127.0.0.1:8012`)

### Test setup

| Item | Value |
|------|-------|
| Login URL | `/super-admin/super-admin-login.html` |
| Credentials | `admin@hrseva.com` / `123456` |
| Viewport | 1440 × 900 |
| Client context | Auto-selected first client: **CHEESENAAN IMPEX PRIVATE LIMITED** |
| Pages crawled | **39** super-admin HTML/PHP pages |

### Summary

| Result | Count |
|--------|-------|
| Pages tested | 39 |
| Clean (no warnings) | 0 |
| Warnings only | 39 |
| Hard errors (HTTP 4xx/5xx, login redirect, broken layout) | 0 |

**Verdict:** All super-admin pages load and render after login. No page crashed or redirected back to login. Issues found are **cross-cutting UI polish** and **expected headless limitations** for camera features—not blocking navigation failures.

### Global issues (appear on most/all pages)

| Severity | Issue | Pages affected | Location / fix |
|----------|-------|----------------|----------------|
| Warning | **Support menu is a dead link** (`href="#"`) | 39 / 39 | `assets/js/app-common.js` — account dropdown in `ensureSuperAdminHeaderActions()` |
| Info | **Notification bell has no handler** — decorative button | 39 / 39 | `assets/js/app-common.js` — bell injected without click handler |
| Warning | **Horizontal overflow** on wide tables/forms | 1 / 39 | `super-admin-module.html` — client list table exceeds viewport |

### Page-specific issues

| Page | Issue | Severity | Notes |
|------|-------|----------|-------|
| `face-attendance-registration.php` | Visible alert: **"Not supported"** | Warning | Headless Chromium has no camera; test in real browser with permissions |
| `scan-attendance.php` | Camera module inactive in headless | Info | Expected; not a production bug |
| `super-admin-view-loan.html` | Alert: **"Loan ID is missing."** | Warning | Detail page accessed without `?id=` query param — should redirect or show empty state, not error |
| `super-admin-ecr-sheet.html` | Not linked in sidebar nav | Info | Page loads fine but missing from `normalizeSuperAdminSidebar()` links |
| `employee-profile.html` | Opens without employee context | Info | Detail page; needs `?emp_id=` from Employee Master |

### What passed

- Login flow works; redirects to `super-admin-dashboard.html`
- Client picker loads and selects a tenant for API calls (`X-Client-Id`)
- Sidebar and main content render on all module pages
- No HTTP 404/500 on page navigation
- No unexpected redirect to login after session established
- Account dropdown Profile link correctly uses `super-admin-profile.html` (super-admin header path)
- Logout link points to `super-admin-logout.html`
- Face attendance settings, sheet, and monthly report pages render without layout breakage
- Platform pages (Enquiries, Subscriptions, Billing, SMTP Control) load for super-admin

### Recommended UI fixes (priority order)

1. **Wire Support link** — point to help page, mailto, or in-app helpdesk when built; remove `href="#"`
2. **Notification bell** — hide until implemented, or open notifications panel / mark as disabled with tooltip
3. **Loan detail guard** — `super-admin-view-loan.html` should redirect to loan list when `id` is missing
4. **Client Module overflow** — add responsive table wrapper (`table-responsive`) on `super-admin-module.html`
5. **Sidebar completeness** — add `super-admin-ecr-sheet.html` to nav (or merge under PF sheet if intentional)
6. **Face attendance messaging** — distinguish “camera not available” vs “browser not supported” for clearer UX
7. **Employee profile deep links** — guard `employee-profile.html` when opened without `emp_id`

### Re-running the audit

```powershell
# Terminal 1 — start app
php -S 127.0.0.1:8012 router.php

# Terminal 2 — run audit
cd qa
npm install
npx playwright install chromium
npm run test:ui-audit
```

Optional: set `HR_BASE_URL` for a different host/port.

---

________________________

Add DoB for Employee master.
