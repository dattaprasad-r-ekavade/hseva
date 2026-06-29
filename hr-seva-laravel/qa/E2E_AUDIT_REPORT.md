# E2E audit report

_Last run: 2026-06-29 — Playwright project `e2e` (7/7 passed)_

## Executive summary

A full end-to-end audit was run across **public forms**, **tenant APIs**, **client portal pages**, **face attendance stack**, and **super-admin forms**. The primary root cause of face detection pages failing was **missing ML model files** at the default path `/assets/vendor/face-api-models` (not shipped in the repo). That default has been updated to a working CDN URL.

## Fixes applied in this branch

| Area | Issue | Fix |
|------|-------|-----|
| Face attendance | Default `modelUrl` pointed at missing local directory | Default → `https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@0.22.2/weights` |
| Face JS | Same broken fallback in `face-attendance.js` | Updated `MODEL_URL_FALLBACK` |
| QA bootstrap | Fresh clone missing `.env`, `APP_KEY`, SQLite | `qa/scripts/serve-for-qa.sh` copies `.env.example`, runs `key:generate`, migrates |

## E2E test coverage (`npm run test:e2e`)

| Test | Result |
|------|--------|
| Landing free-trial form → `POST /api/public-enquiries` | ✅ |
| Tenant APIs (employees, face settings/sheet/report, attendance, leaves, compliance, control, dashboard) | ✅ |
| Face model manifest reachable from browser | ✅ |
| Client portal pages (11 routes incl. all face pages) load without API 4xx | ✅ |
| Face settings form `PUT` via UI | ✅ |
| Scan page: `data-face-page`, `face-api.js`, model manifest | ✅ |
| Employee master form `POST /api/employees` | ✅ |
| Super-admin manual enquiry form `POST` | ✅ |

## Remaining product issues (still to pick up)

### P0 — Face attendance (runtime)

1. **Camera required** — Registration and scan pages need `getUserMedia` (real browser, HTTPS or localhost). Headless/automated runs cannot fully test face capture; manual browser test still required.
2. **Existing tenants may have stale `modelUrl`** — DB rows created before this fix may still store `/assets/vendor/face-api-models`. Super-admin should open **Face Attendance Settings** and save once, or run a migration to update `model_url` in tenant settings.

### P0 — Onboarding / provisioning

3. **Client login blocked without active subscription** — Assigning a subscription *plan* on the client record is not enough; an **Active** row in `subscriptions` is required or login returns *"Subscription expired"*.
4. **Zero subscription plans on fresh install** — Client Module cannot provision until super-admin creates at least one plan.

### P1 — Forms & data integrity

5. **Client Module save may use localStorage fallback** — UI can appear successful without `POST /api/clients` when API fails (`client-module.js`).
6. **Employee types dropdown** — If employee types master is empty, Add Employee form cannot submit (employment type required).

### P1 — Dev / deploy bootstrap

7. **Missing `.env` on clone** — Without `cp .env.example .env` + `php artisan key:generate`, all web routes return 500 (*No application encryption key*).
8. **Laravel SQLite session DB** — `database/database.sqlite` must exist with migrations when `SESSION_DRIVER=database`.

### P2 — UX / polish

9. Landing theme still has e-commerce mini-cart copy ("Checkout", "$97.00").
10. Forgot-password link hidden on client login (`d-none`).
11. No post-login onboarding checklist for new client admins.

## How to re-run

```bash
cd hr-seva-laravel
cp .env.example .env   # first time only
php artisan key:generate
php composer.phar install

cd qa
npm install
npx playwright install chromium
npm run test:e2e       # comprehensive audit
npm run test           # smoke tests (use fresh DB or expect pollution if server shared)
```

## Manual face attendance checklist (browser)

1. Log in as client admin (tenant with **active subscription**).
2. Open **Face Attendance → Settings** — confirm Model URL is HTTPS CDN, save.
3. Open **Employee Face Registration** — allow camera, register one employee face.
4. Open **Scan Attendance** — confirm IN/OUT scan recognizes registered face.
5. Verify rows appear on **Face Attendance Sheet**.
