# HR Seva — Framework Migration Plan

**Document version:** 1.0  
**Date:** June 21, 2026  
**Goal:** Migrate from the current procedural PHP monolith + duplicate static HTML portals to **Laravel** or **PHP Slim**, reducing file count and improving performance while preserving product behaviour.

**Related docs:** `PROJECT_ASSESSMENT.md`, `backend/README.md`, `FACE_ATTENDANCE_SETUP.md`

---

## Table of contents

1. [Why migrate](#1-why-migrate)
2. [Current state snapshot](#2-current-state-snapshot)
3. [Target outcomes](#3-target-outcomes)
4. [Framework comparison: Laravel vs Slim](#4-framework-comparison-laravel-vs-slim)
5. [Recommendation](#5-recommendation)
6. [Target architecture](#6-target-architecture)
7. [File count reduction strategy](#7-file-count-reduction-strategy)
8. [Performance strategy](#8-performance-strategy)
9. [Database & multi-tenancy migration](#9-database--multi-tenancy-migration)
10. [Phased migration roadmap](#10-phased-migration-roadmap)
11. [Module mapping (current → new)](#11-module-mapping-current--new)
12. [Risk register](#12-risk-register)
13. [Success metrics](#13-success-metrics)
14. [Decision checklist](#14-decision-checklist)

---

## 1. Why migrate

The product works, but the implementation limits velocity, safety, and scale:

| Pain today | Impact |
|------------|--------|
| `backend/api.php` (~4,970 lines, ~95 routes) | Hard to review, test, and split work across developers |
| 83 portal HTML files (41 client + 42 super-admin) | Any layout/nav change touches dozens of files |
| 48 page-specific JS files + 2,040-line `app-common.js` | Duplicated API base URLs, auth, and fetch logic |
| Schema applied inline (`init_schema()` / `ALTER TABLE`) | Migration drift, slow cold requests |
| Payroll sheets in JSON blobs (`app_kv`) | Large memory use, no SQL reporting |
| No Composer, no DI, no test harness | Regressions in payroll/compliance go unnoticed |
| SQLite per-tenant files | OK for MVP; weak for concurrent writes and ops at scale |

A framework migration is not a rewrite for its own sake — it is how we **collapse duplicate files**, **centralize cross-cutting concerns**, and **make performance tunable** (cache, queue, DB).

---

## 2. Current state snapshot

| Asset | Count / size |
|-------|----------------|
| Client HTML/PHP pages | 41 |
| Super-admin HTML/PHP pages | 42 |
| Portal JS files (`assets/js/`) | 48 |
| `app-common.js` | ~2,040 lines |
| `backend/api.php` | ~4,970 lines |
| `backend/shift_module.php` | ~892 lines |
| Landing `index.html` | ~3,318 lines |
| API route branches | ~95 |
| DB model | SQLite central + `tenant_{id}/app.db` |
| Tests | Playwright UI audit in local `qa/` (gitignored) |

**Request path today:**

```
Static HTML → fetch(/api/...) → router.php / .htaccess → api.php → SQLite
```

---

## 3. Target outcomes

### File count (illustrative targets)

| Area | Today | After migration (target) | Reduction |
|------|-------|--------------------------|-----------|
| Portal page shells | 83 | 12–18 Blade views + 2 layouts | **~80%** |
| Backend entrypoints | 1 monolith + 1 module | 8–12 controllers + 8–10 services | Clearer, fewer “god” files |
| Route definitions | Inline in `api.php` | 6–8 route files or 1 cached group | Maintainable |
| JS page files | 48 | 8–12 modules (or Inertia/Vue chunks) | **~75%** |
| Duplicate asset trees | `assets/` + `assets-02/` | 1 `resources/` + `public/` | **~50%** |

### Performance (measurable targets)

| Metric | Today (est.) | Target |
|--------|--------------|--------|
| API p95 (simple GET, warm) | 80–200 ms | **< 50 ms** |
| Payroll generate (50 employees) | In-request, blocks UI | **Queued job < 30 s** |
| Cold schema init per request | Partial `init_schema()` | **Zero** (migrations only) |
| Portal first paint (authenticated) | Full HTML per page | Shared layout cache + smaller HTML |
| Concurrent tenant writes | SQLite lock contention | PostgreSQL + connection pooling (prod) |

---

## 4. Framework comparison: Laravel vs Slim

### Laravel 11

| Pros | Cons |
|------|------|
| Blade layouts eliminate 70+ duplicate HTML shells | Heavier runtime than Slim (~2–4× bootstrap) |
| Built-in auth, policies, gates, Sanctum/JWT packages | Learning curve if team is procedural-PHP only |
| Eloquent migrations, factories, seeders | Opinionated structure |
| Queues, cache, events, mail — payroll fits naturally | Hosting needs Composer + `artisan` deploy step |
| PHPUnit + Pest, route/config caching | Full migration is 3–5 months for this codebase |
| Large ecosystem (Excel export, multi-tenancy packages) | |

**Best when:** You want **one stack** for API + portal UI + jobs + tests, and plan to grow into a real SaaS.

### PHP Slim 4

| Pros | Cons |
|------|------|
| Minimal bootstrap — fast API layer | No built-in ORM, auth, or queue — you assemble pieces |
| Easy strangler pattern: wrap existing logic behind routes | Frontend duplication stays unless you add Twig/Blade separately |
| PSR-7/15 middleware — clean auth, tenant, rate-limit | More decisions (DI container, validation library) |
| Small deploy footprint | Still need a plan for 83 HTML files |
| Team can migrate API first, UI later | Less “batteries included” than Laravel |

**Best when:** You want **API-first migration** in 6–10 weeks and can defer portal consolidation.

### Side-by-side

| Criterion | Laravel | Slim 4 | Winner for HR Seva |
|-----------|---------|--------|-------------------|
| Reduce HTML file count | Blade + components | Needs Twig or keep static HTML | **Laravel** |
| API migration speed | Medium | Fast | **Slim** (phase 1 only) |
| Long-term maintainability | High | Medium | **Laravel** |
| Performance (raw API) | Good with Octane/cache | Slightly leaner cold start | **Slim** (marginal) |
| Performance (full product) | Queues + cache + normalized DB | Same possible, more DIY | **Laravel** |
| Multi-tenancy | Packages (stancl/tenancy, spatie) | Custom middleware | **Laravel** |
| Payroll background jobs | Native queues | Requires Redis + worker setup | **Laravel** |
| Team onboarding | Excellent docs | Thin docs, you write patterns | **Laravel** |

---

## 5. Recommendation

### Primary recommendation: **Laravel 11** (full migration)

HR Seva’s biggest file-count problem is **duplicate portal HTML**, not just `api.php`. Laravel Blade layouts + a single super-admin/client layout with role-based nav solves that directly. Slim alone does not.

### Alternative: **Slim 4 now → Laravel later** (strangler)

Use Slim if you must ship a safer API in **8–10 weeks** without touching the 83 HTML files yet:

```
Phase A (Slim): api.php → Slim routes + services (keep static frontends)
Phase B (Laravel): Portal → Blade + retire Slim, or mount Slim as legacy route group
```

Only choose this if the team cannot pause feature work for a full migration.

---

## 6. Target architecture

### Laravel target (recommended)

```
hrseva/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/          # Auth, Employee, Payroll, Compliance, ...
│   │   ├── Controllers/Web/          # Portal pages (thin — return views)
│   │   ├── Middleware/               # Auth, Tenant, Subscription, RateLimit
│   │   └── Requests/                 # Form validation
│   ├── Models/                       # Eloquent (central + tenant)
│   ├── Services/                     # PayrollEngine, ComplianceCalendar, FaceAttendance
│   ├── Jobs/                         # GeneratePayrollSheet, SendEnquiryEmail
│   ├── Policies/                     # RBAC per module
│   └── Support/                      # TenantManager, LegacyJsonSheetAdapter
├── database/
│   ├── migrations/                   # central + tenant migrations
│   └── seeders/
├── resources/
│   ├── views/
│   │   ├── layouts/                  # app.blade.php (1 client + 1 admin layout)
│   │   ├── components/               # sidebar, topbar, alerts, tables
│   │   └── pages/                    # payroll, attendance, employees, ...
│   └── js/                           # Vite modules (replaces 48 files → ~10)
├── routes/
│   ├── api.php
│   ├── web.php
│   └── tenant.php                    # tenant-scoped routes
├── public/                           # index.php, built assets, landing
└── tests/
    ├── Feature/Api/
    └── Unit/Services/
```

### Slim target (API-only phase)

```
hrseva-api/
├── public/index.php
├── src/
│   ├── Routes/           # auth.php, employees.php, payroll.php, ...
│   ├── Middleware/
│   ├── Services/         # Ported from api.php functions
│   └── Repositories/     # PDO or Doctrine DBAL
├── config/
└── tests/
```

Static `client/` and `super-admin/` folders remain until Phase B.

---

## 7. File count reduction strategy

### 7.1 Backend — break the monolith

Extract `api.php` by domain (same for Laravel or Slim):

| New unit | Absorbs from `api.php` |
|----------|------------------------|
| `AuthController` + `AuthService` | login, session, forgot, JWT |
| `ClientController` | clients, access-control, subscriptions |
| `EmployeeController` | employees, types, staff-users, roles |
| `AttendanceController` | daily attendance, sheets, statuses |
| `FaceAttendanceController` | face register/scan/settings |
| `PayrollController` | salary sheet, payslips, overrides |
| `ComplianceController` | PF, ESIC, ECR, returns, calendar |
| `LeaveController` | leaves, overtime |
| `FinanceController` | loan, advance, FNF, bonus, gratuity, incentive |
| `ShiftController` | shift_module.php |
| `EnquiryController` | public + admin enquiries |
| `SettingsController` | control, profile, SMTP |

**~12 controllers + ~10 services** replaces one 5,000-line file. Route files add ~6 files but remove ~95 inline `if ($path === ...)`.

### 7.2 Frontend — one layout, role-aware nav

**Today:** Each page duplicates sidebar, topbar, modals (~150 lines × 83 files).

**Target:**

```blade
{{-- resources/views/layouts/portal.blade.php --}}
<x-portal.sidebar :role="$role" :permissions="$permissions" />
<main>@yield('content')</main>
```

| Portal area | Blade views (target) |
|-------------|----------------------|
| Dashboard | 1 view (role switches widgets) |
| Employees | 2 views (master, profile) |
| Attendance + face | 3 views |
| Payroll + payslips | 2 views |
| Compliance (PF/ESIC/ECR) | 3 views |
| Leave, shift, overtime | 3 views |
| Finance (loan, advance, FNF, etc.) | 2 views (tabs) |
| Settings + admin | 4 views |
| **Total** | **~18 views** + **2 layouts** (client / super-admin) |

Super-admin-only pages (enquiries, SMTP, billing) become **route + policy gated sections**, not duplicate files.

### 7.3 JavaScript — Vite bundles

| Bundle | Replaces |
|--------|----------|
| `app.js` | `app-common.js` + auth/fetch/tenant |
| `payroll.js` | client-payroll-calc, payslips, pf-*, esic-*, ecr-* |
| `attendance.js` | client-attendance, face-attendance |
| `employees.js` | employee-master, types, roles |
| `admin.js` | module, subscriptions, enquiries, smtp |

**48 files → ~8 entrypoints** imported only where needed.

### 7.4 Landing page

Move `index.html` to `resources/views/landing.blade.php` or keep as static in `public/` until last. Strip template cruft (cart, Lorem ipsum) during migration — reduces ~3,318 lines to ~1,200.

---

## 8. Performance strategy

### 8.1 Quick wins (either framework)

| Change | Effect |
|--------|--------|
| Remove per-request `init_schema()` | Faster every API call |
| `route:cache` + `config:cache` (Laravel) | Faster routing |
| PHP OPcache enabled in prod | 20–40% CPU reduction |
| gzip / Brotli on static assets | Smaller transfers |
| Normalize hot JSON sheets to tables | Faster payroll queries, less RAM |

### 8.2 Laravel-specific

| Feature | Use in HR Seva |
|---------|----------------|
| **Queues** (`GeneratePayroll`, `ExportPfSheet`) | Stop blocking HTTP on heavy calc |
| **Cache** (Redis) | Control settings, access types, dashboard summary |
| **Octane** (Swoole/RoadRunner) | Optional — high concurrency API |
| **Eloquent eager loading** | Employee lists with types/roles |
| **Sanctum** | Replace custom JWT or wrap existing tokens during transition |

### 8.3 Slim-specific

| Feature | Use |
|---------|-----|
| Slim + PHP-DI | Singleton PDO per tenant request |
| Middleware stack | Auth → Tenant → Subscription (same order as today) |
| ReactPHP / external worker | Manual queue if not using Laravel |

### 8.4 Database performance path

| Stage | Setup |
|-------|--------|
| **Dev** | SQLite (keep parity during migration) |
| **Staging** | PostgreSQL single DB, `tenant_id` column |
| **Prod** | PostgreSQL + read replica optional; or `stancl/tenancy` DB-per-tenant on PostgreSQL |

Migrate JSON `app_kv` payroll sheets to:

- `payroll_sheets`, `payroll_sheet_lines`, `payslips` tables  
Enables indexed reports and smaller API payloads (pagination).

### 8.5 What will *not* improve performance alone

- Switching to Slim/Laravel without fixing schema-on-request and JSON blobs
- Blade layouts (helps maintainability, minor HTML size win)
- Deleting duplicate HTML without consolidating JS API calls

---

## 9. Database & multi-tenancy migration

### Option A — Keep SQLite per tenant (fastest migration)

- Laravel: custom `TenantConnection` resolver mirroring `db_path_for_client()`
- Pros: Minimal data migration
- Cons: Same SQLite scale limits

### Option B — PostgreSQL with `tenant_id` (recommended for prod)

- One schema, row-level `tenant_id` on employee/payroll tables
- Central tables: `clients`, `subscriptions`, `enquiries` (no tenant_id)
- Pros: Single backup, better concurrency, Laravel native
- Cons: One-time data migration script from N SQLite files

### Option C — Laravel tenancy package (DB per tenant on PostgreSQL)

- Good middle ground if legal/compliance wants hard isolation
- `stancl/tenancy` or `spatie/laravel-multitenancy`

### Migration script outline

1. Export central `storage/clients/app.db` → `clients`, `staff_users`, etc.
2. For each `tenant_{id}/app.db` → import with `tenant_id = id`
3. Verify row counts + sample payroll totals per tenant
4. Run parallel API tests (legacy vs new) on same inputs

---

## 10. Phased migration roadmap

### Overview timeline (Laravel full path)

| Phase | Duration | Deliverable |
|-------|----------|-------------|
| 0 — Prep | 1–2 weeks | Laravel app skeleton, CI, env, parity tests |
| 1 — API core | 3–4 weeks | Auth, clients, employees, health |
| 2 — Payroll & compliance | 4–6 weeks | Highest risk modules |
| 3 — Portal UI | 4–6 weeks | Blade layouts, retire duplicate HTML |
| 4 — Data migration | 2–3 weeks | PostgreSQL + tenant import |
| 5 — Cutover | 1–2 weeks | DNS, monitoring, rollback plan |
| **Total** | **15–23 weeks** | Production on Laravel |

### Phase 0 — Preparation (weeks 1–2)

- [ ] Create `hrseva-laravel/` branch or repo
- [ ] `composer create-project laravel/laravel`
- [ ] Port `.env` keys: `HR_APP_SECRET`, SMTP, `APP_URL`
- [ ] Add PHPUnit + copy 20 critical API tests from legacy behaviour
- [ ] Document all `/api/*` routes from `api.php` (OpenAPI optional)
- [ ] Docker Compose: PHP, PostgreSQL, Redis, Nginx

### Phase 1 — API strangler (weeks 3–6)

- [ ] Middleware: `Authenticate`, `ResolveTenant`, `CheckSubscription`
- [ ] Port auth (login, session, JWT/Sanctum) — **fix default credentials**
- [ ] Port clients, access-control, employees, profile, control
- [ ] Nginx: `/api/*` → Laravel; unported routes → legacy `api.php` (feature flag)
- [ ] **Exit criteria:** Client picker + employee CRUD pass parity tests

### Phase 2 — Domain APIs (weeks 7–12)

- [ ] Attendance + face attendance
- [ ] Shift roster (`shift_module.php` → `ShiftService`)
- [ ] Payroll, payslips, PF/ESIC/ECR (queue heavy generates)
- [ ] Leave, overtime, loans, advances, FNF, bonus, gratuity
- [ ] Enquiries + mail (Laravel Mail + queue)
- [ ] **Exit criteria:** Generate payroll for tenant 16 matches legacy output (± rounding rules)

### Phase 3 — Frontend consolidation (weeks 13–18)

- [ ] `layouts/portal.blade.php` + sidebar component (port `normalizeSuperAdminSidebar` logic to PHP)
- [ ] Vite + migrate `app-common.js` tenant fetch to `@vite` module
- [ ] Replace HTML pages module-by-module (start: dashboard, employees, attendance)
- [ ] Face attendance pages as Blade + same JS module
- [ ] Deprecate `client/` and `super-admin/` folders
- [ ] **Exit criteria:** Playwright audit in `qa/` passes on new URLs

### Phase 4 — Database upgrade (weeks 19–21)

- [ ] PostgreSQL migrations for all tables
- [ ] Import script from SQLite tenants
- [ ] Remove JSON sheet storage for new runs (keep read-only legacy adapter 30 days)

### Phase 5 — Production cutover (weeks 22–23)

- [ ] Load test payroll generate (50, 200, 500 employees)
- [ ] Security pass: policies on all `/clear` routes
- [ ] Rollback: keep legacy `api.php` read-only for 1 sprint
- [ ] Decommission `router.php` monolith path

### Slim fast path (API only, ~10 weeks)

| Week | Work |
|------|------|
| 1–2 | Slim skeleton, middleware, auth |
| 3–5 | Employees, clients, attendance |
| 6–8 | Payroll + compliance services |
| 9–10 | Parity tests, switch `/api` to Slim, keep HTML |

---

## 11. Module mapping (current → new)

| Current | Laravel destination |
|---------|---------------------|
| `backend/api.php` (auth) | `App\Http\Controllers\Api\AuthController` |
| `backend/api.php` (clients) | `Api\ClientController`, `Api\AccessControlController` |
| `backend/api.php` (employees) | `Api\EmployeeController` |
| `backend/api.php` (payroll/*) | `Api\PayrollController`, `Jobs\GeneratePayroll` |
| `backend/api.php` (pf-*, esic-*, ecr-*) | `Api\ComplianceController` |
| `backend/api.php` (face-attendance/*) | `Api\FaceAttendanceController` |
| `backend/shift_module.php` | `App\Services\Shift\ShiftService` |
| `backend/mail.php` | `App\Mail\*`, `config/mail.php` |
| `router.php` | `public/index.php` (Laravel front controller) |
| `.htaccess` API rules | Nginx try_files or Laravel `public/.htaccess` |
| `assets/js/app-common.js` | `resources/js/portal/app.js` |
| `client/*.html`, `super-admin/*.html` | `resources/views/pages/*.blade.php` |
| `index.html` | `resources/views/landing.blade.php` |
| `storage/clients/*.db` | PostgreSQL + `database/migrations` |

---

## 12. Risk register

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Payroll calculation drift | High | Critical | Golden-file tests per tenant/month |
| Multi-tenant data leak | Medium | Critical | Policy tests + `tenant_id` global scope |
| Long parallel run of two APIs | Medium | Medium | Strangler with route-level flags |
| Scope creep (rebuild + new features) | High | High | Freeze features during Phase 2 |
| Face attendance browser regressions | Medium | Medium | Keep same JS module initially |
| Team unfamiliar with Laravel | Medium | Medium | Phase 0 training, code review checklist |

---

## 13. Success metrics

| Metric | How to measure |
|--------|----------------|
| File count | Git tree: portal views < 25, JS entrypoints < 12 |
| API parity | 100+ PHPUnit cases green against legacy fixtures |
| Performance | p95 `/api/employees` < 50 ms; payroll job async |
| UI parity | Playwright super-admin audit: 0 errors |
| Ops | One `docker compose up` reproduces dev |
| Security | No default passwords; all destructive routes policy-gated |

---

## 14. Decision checklist

Before starting, confirm:

- [ ] **Laravel full** vs **Slim API-first** (see [§5](#5-recommendation))
- [ ] **SQLite parity** vs **PostgreSQL** for first production release
- [ ] **DB per tenant** vs **shared schema + tenant_id**
- [ ] **Keep custom JWT** vs **Laravel Sanctum**
- [ ] **Blade portal** vs **Inertia/Vue** (Blade recommended for file-count goal)
- [ ] Feature freeze window during payroll migration (Phase 2)
- [ ] Who owns parity test fixtures (finance/compliance sign-off)

---

## Appendix A — Immediate next steps (this week)

1. Approve Laravel vs Slim path with stakeholders.
2. Export complete route list from `backend/api.php` (script or manual spreadsheet).
3. Pick golden tenant (`tenant_16`) and month for payroll parity tests.
4. Create Laravel skeleton on branch `feat/laravel-migration`.
5. Port `GET /api/health`, `POST /api/auth/login`, `GET /api/employees` as proof of concept.

---

## Appendix B — Slim vs Laravel decision matrix (score)

| Criterion (weight) | Laravel | Slim |
|--------------------|---------|------|
| File count reduction (25%) | 9 | 5 |
| Performance at scale (20%) | 8 | 7 |
| Migration speed (15%) | 6 | 9 |
| Long-term maintainability (20%) | 9 | 6 |
| Team hiring / docs (10%) | 9 | 5 |
| HR module fit (queues, mail, Excel) (10%) | 9 | 6 |
| **Weighted total** | **8.3** | **6.4** |

---

*Update this plan when phases complete. Link epics/milestones from `PROJECT_ASSESSMENT.md` Phase 2 engineering items.*

**See also:** `MIGRATION_PLAN_NEXTJS.md` (Next.js + Supabase), `STACK_COMPARISON.md` (performance, hosting, capacity).
