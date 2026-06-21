# HR Seva — Next.js + Supabase Migration Plan

**Document version:** 1.0  
**Date:** June 21, 2026  
**Goal:** Migrate from the current procedural PHP monolith + duplicate static HTML portals to **Next.js** (App Router) + **Supabase** (PostgreSQL, Auth, Storage), reducing file count and improving scalability while preserving product behaviour.

**Related docs:** `PROJECT_ASSESSMENT.md`, `MIGRATION_PLAN.md` (Laravel/Slim), `FACE_ATTENDANCE_SETUP.md`

---

## Table of contents

1. [Why Next.js + Supabase](#1-why-nextjs--supabase)
2. [Current state snapshot](#2-current-state-snapshot)
3. [Target outcomes](#3-target-outcomes)
4. [Stack overview](#4-stack-overview)
5. [Recommendation within JS ecosystem](#5-recommendation-within-js-ecosystem)
6. [Target architecture](#6-target-architecture)
7. [File count reduction strategy](#7-file-count-reduction-strategy)
8. [Performance & hosting strategy](#8-performance--hosting-strategy)
9. [Database, auth & multi-tenancy (Supabase)](#9-database-auth--multi-tenancy-supabase)
10. [Handling heavy workloads (payroll) on serverless](#10-handling-heavy-workloads-payroll-on-serverless)
11. [Phased migration roadmap](#11-phased-migration-roadmap)
12. [Module mapping (current → new)](#12-module-mapping-current--new)
13. [Risk register](#13-risk-register)
14. [Success metrics](#14-success-metrics)
15. [Decision checklist](#15-decision-checklist)
16. [Cross-stack comparison](#16-cross-stack-comparison)

---

## 1. Why Next.js + Supabase

| Driver | How Next.js + Supabase helps |
|--------|-------------------------------|
| **One language (TypeScript)** | Frontend + API routes + payroll logic in one codebase |
| **File count** | App Router layouts replace 83 duplicate HTML shells |
| **Hosting simplicity** | Vercel deploy from Git; no server patching for app layer |
| **Database** | Supabase PostgreSQL replaces SQLite; real concurrency |
| **Auth** | Supabase Auth + RLS replaces custom JWT + manual tenant headers |
| **Scale path** | Connection pooler, read replicas (Supabase), Vercel scaling |
| **Face attendance** | Still browser-based (face-api.js); Storage for optional face images |

| Trade-off | Reality |
|-----------|---------|
| **Payroll is CPU-heavy** | Poor fit for default Vercel serverless timeouts — needs background jobs |
| **Rewrite cost** | ~4,970 lines PHP + compliance rules → TypeScript (higher than Laravel port) |
| **Vendor coupling** | Vercel + Supabase billing and limits |
| **Excel / PDF exports** | Reimplement or keep client-side (SheetJS already used) |

---

## 2. Current state snapshot

| Asset | Count / size |
|-------|----------------|
| Client HTML/PHP pages | 41 |
| Super-admin HTML/PHP pages | 42 |
| Portal JS files | 48 |
| `backend/api.php` | ~4,970 lines |
| `backend/shift_module.php` | ~892 lines |
| Landing `index.html` | ~3,318 lines |
| API routes | ~95 |
| Database | SQLite central + per-tenant files |
| Auth | Custom JWT + `X-Client-Id` header |

**Request path today:**

```
Static HTML → fetch(/api/...) → PHP → SQLite
```

**Target path:**

```
Next.js (Vercel) → Server Actions / Route Handlers → Supabase (Postgres + RLS)
                 → Background worker (payroll jobs)
                 → Browser (face-api.js unchanged)
```

---

## 3. Target outcomes

### File count (illustrative targets)

| Area | Today | After Next.js migration | Reduction |
|------|-------|-------------------------|-----------|
| Portal page shells | 83 | ~15–20 route segments + 2 layouts | **~75–80%** |
| Backend | 1 PHP monolith | `app/api/` + `lib/services/` (~15–20 modules) | Structured, not fewer absolute files |
| Portal JS | 48 files | React components + hooks (~25–35 components, shared hooks) | **~40–60%** |
| Landing | 3,318-line HTML | React page + components | **~60%** (after template cleanup) |
| Asset trees | `assets/` + `assets-02/` | `public/` + `components/` | **~50%** |

### Performance targets

| Metric | Today (est.) | Target (Next.js + Supabase) |
|--------|--------------|----------------------------|
| Portal TTFB (cached) | 80–200 ms | **30–80 ms** (Vercel edge + SSR cache) |
| API p95 (simple CRUD) | 80–200 ms | **40–100 ms** (pooler + edge region) |
| Payroll generate (50 emp) | Blocks HTTP request | **Async job, UI polls** — no 10s serverless timeout |
| DB concurrent writes | SQLite lock | **PostgreSQL** — hundreds of concurrent connections (pooled) |
| Global static assets | Single origin | **CDN by default** on Vercel |

---

## 4. Stack overview

### Next.js 15 (App Router)

| Use for | Notes |
|---------|-------|
| Marketing landing | `app/(marketing)/page.tsx` |
| Client portal | `app/(portal)/client/...` |
| Super-admin portal | `app/(portal)/admin/...` |
| API | Route Handlers `app/api/...` or Server Actions |
| Shared UI | `components/portal/Sidebar.tsx`, layouts |
| Auth middleware | `middleware.ts` — session + role + tenant |

**UI library:** Tailwind + shadcn/ui (replaces Bootstrap CDN) — one design system.

### Supabase

| Product | HR Seva use |
|---------|-------------|
| **PostgreSQL** | All tenant + central data |
| **Auth** | Email/password, magic link; map roles to `app_metadata` |
| **RLS** | Row-level security per `tenant_id` — replaces manual tenant DB files |
| **Storage** | Optional face images, exports, payslip PDFs |
| **Edge Functions** | Webhooks, light transforms (not full payroll) |
| **Realtime** | Optional attendance live board |

### Supporting services (recommended)

| Service | Purpose |
|---------|---------|
| **Inngest** or **Trigger.dev** | Payroll generation, bulk exports (Vercel-friendly queues) |
| **Resend** or Supabase SMTP | Transactional email (enquiries, payslips) |
| **Sentry** | Error tracking |
| **Vercel Cron** | Compliance reminders, subscription expiry checks |

---

## 5. Recommendation within JS ecosystem

**Use Next.js App Router + Supabase**, not plain React SPA + separate API.

| Approach | Verdict |
|----------|---------|
| Next.js + Supabase | **Recommended** — SSR layouts, API colocated, Vercel-native |
| Vite React SPA + Supabase client only | Loses SEO for landing; more client-side auth complexity |
| Remix + Supabase | Viable alternative; smaller hiring pool than Next.js |
| NestJS API + Next frontend | Two deployables; more ops than needed for this product |

---

## 6. Target architecture

```
hrseva/
├── app/
│   ├── (marketing)/
│   │   ├── page.tsx                    # Landing
│   │   └── layout.tsx
│   ├── (auth)/
│   │   ├── login/client/page.tsx
│   │   └── login/admin/page.tsx
│   ├── (portal)/
│   │   ├── layout.tsx                  # Shared sidebar + topbar
│   │   ├── admin/
│   │   │   ├── dashboard/page.tsx
│   │   │   ├── employees/page.tsx
│   │   │   ├── payroll/page.tsx
│   │   │   └── ...
│   │   └── client/
│   │       ├── dashboard/page.tsx
│   │       └── ...
│   ├── api/
│   │   ├── employees/route.ts
│   │   ├── payroll/generate/route.ts   # Enqueues job only
│   │   └── webhooks/inngest/route.ts
│   └── middleware.ts                   # Auth, tenant, subscription
├── components/
│   ├── portal/                         # Sidebar, Topbar, DataTable
│   ├── payroll/
│   └── face-attendance/                # Wrap existing face-api.js
├── lib/
│   ├── supabase/
│   │   ├── server.ts                   # Server client (cookies)
│   │   ├── client.ts                   # Browser client
│   │   └── middleware.ts
│   ├── services/                       # PayrollEngine, Compliance, ...
│   └── jobs/                           # Inngest functions
├── supabase/
│   ├── migrations/
│   └── seed.sql
├── types/
└── public/
    └── vendor/face-api-models/           # Keep existing models
```

### Route groups replace duplicate HTML

| Current | Next.js |
|---------|---------|
| `client/client-attendance.html` + `super-admin/super-admin-attendance.html` | `app/(portal)/attendance/page.tsx` + role prop |
| 83 separate shells | 1 `layout.tsx` + ~18 unique pages |

---

## 7. File count reduction strategy

### 7.1 Layouts and shared components

```tsx
// app/(portal)/layout.tsx
export default function PortalLayout({ children }) {
  return (
    <PortalShell role={session.role} tenantId={session.tenantId}>
      {children}
    </PortalShell>
  );
}
```

Sidebar nav defined once in `lib/navigation.ts` (ports `app-common.js` link lists).

### 7.2 Server Components for read-heavy pages

- Employee list, dashboard KPIs, enquiry list → Server Components fetch Supabase directly
- Reduces client JS vs current 48 fetch-heavy files

### 7.3 Client Components only where needed

- Payroll calculator forms, face attendance webcam, Excel export buttons
- ~12–15 client islands vs 48 full page scripts

### 7.4 API surface

| Current PHP routes (~95) | Next.js |
|--------------------------|---------|
| Group by domain | `app/api/{domain}/route.ts` (~12–15 route files) |
| Business logic | `lib/services/*.ts` (~12 services) |

---

## 8. Performance & hosting strategy

### Vercel deployment model

| Layer | Handling |
|-------|----------|
| Static / ISR | Landing, help docs |
| SSR | Authenticated portal pages (per-request tenant context) |
| Route Handlers | CRUD API |
| Edge Middleware | Auth redirect, geo (optional) |
| Background jobs | **Not Vercel** — use Inngest/Trigger.dev or Supabase pg_cron + worker |

### Supabase deployment model

| Setting | Recommendation |
|---------|----------------|
| Region | `ap-south-1` (Mumbai) if available, else closest to users |
| Connection | **Supavisor pooler** (transaction mode) for serverless |
| RLS | Enabled on all tenant tables |
| Indexes | `tenant_id`, `emp_id`, payroll `(tenant_id, month, year)` |

### Caching

| Data | Strategy |
|------|----------|
| Control settings | `unstable_cache` / Redis (Vercel KV) — 5 min TTL |
| Dashboard summary | Per-tenant cache keyed by month |
| Employee list | Revalidate on mutation (tag-based) |
| Payslip PDFs | Supabase Storage + CDN URL |

---

## 9. Database, auth & multi-tenancy (Supabase)

### Schema strategy: shared database + `tenant_id`

Replaces `storage/clients/tenant_{id}/app.db`:

```sql
-- Every tenant-scoped table
CREATE TABLE employees (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  tenant_id uuid NOT NULL REFERENCES tenants(id),
  emp_id text NOT NULL,
  ...
  UNIQUE (tenant_id, emp_id)
);

ALTER TABLE employees ENABLE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON employees
  USING (tenant_id = (auth.jwt() -> 'app_metadata' ->> 'tenant_id')::uuid);
```

### Auth mapping

| Current | Supabase |
|---------|----------|
| Custom JWT | Supabase session JWT |
| `super_admin` role | `app_metadata.role = 'super_admin'` |
| Client user | `app_metadata.tenant_id` + `role` |
| `X-Client-Id` header | Super-admin picks tenant → stored in session / cookie |
| Login rate limit | Supabase built-in + middleware |

### Data migration from SQLite

1. Script: export central `app.db` → `tenants`, `clients`, `subscriptions`
2. Per `tenant_{n}/app.db` → import with `tenant_id = n`
3. Migrate `app_kv` JSON payroll sheets → `payroll_sheets` + `payroll_lines` tables
4. Validate totals for tenant 16 (golden tenant)

### Face attendance data

| Field | Storage |
|-------|---------|
| `face_descriptor` (128 floats) | `employee_faces.descriptor` (jsonb or float8[]) |
| Optional image | Supabase Storage bucket `face-images` (private, RLS) |

---

## 10. Handling heavy workloads (payroll) on serverless

**Problem:** `payroll_generate()` in PHP runs synchronously. Vercel Hobby/Pro serverless functions timeout at **10s / 60s**.

**Solution (required):**

```
UI → POST /api/payroll/generate → insert job row → Inngest event
Inngest worker (long timeout) → PayrollService.generate() → write DB → notify UI
UI polls /api/payroll/jobs/:id or Supabase Realtime
```

| Job type | Worker |
|----------|--------|
| Payroll generate | Inngest (up to 15 min+) |
| PF/ESIC sheet export | Inngest or client-side SheetJS (keep current approach) |
| Bulk email | Inngest + Resend |
| Compliance calendar cron | Vercel Cron → lightweight SQL updates |

**Do not** run 50–500 employee payroll inline in a Route Handler on Vercel.

---

## 11. Phased migration roadmap

| Phase | Duration | Deliverable |
|-------|----------|-------------|
| 0 — Foundation | 2 weeks | Next.js + Supabase project, schema v1, CI |
| 1 — Auth & tenants | 2–3 weeks | Login, RLS, client picker for super-admin |
| 2 — Core HR | 4–5 weeks | Employees, attendance, leave |
| 3 — Payroll & compliance | 6–8 weeks | Port PHP calc logic to TS + job queue |
| 4 — Portal UI | 4–6 weeks | Replace HTML modules with App Router pages |
| 5 — Face attendance | 2 weeks | Integrate existing JS in React wrapper |
| 6 — Data migration & cutover | 2–3 weeks | SQLite → Supabase, DNS to Vercel |
| **Total** | **22–29 weeks** | Production on Next.js + Supabase |

### Phase 0 (weeks 1–2)

- [ ] `create-next-app` with TypeScript, Tailwind, ESLint
- [ ] Supabase project (prod + staging)
- [ ] Define `tenants`, `employees`, `users` migrations
- [ ] Port `GET /api/health` equivalent + auth flow
- [ ] Set up Inngest (or Trigger.dev) dev environment

### Phase 1 (weeks 3–5)

- [ ] Supabase Auth: admin vs client roles
- [ ] Middleware: subscription expiry check
- [ ] Super-admin tenant switcher (replaces client picker)
- [ ] Port employees CRUD with RLS tests

### Phase 2 (weeks 6–10)

- [ ] Attendance, shift roster, leave, overtime
- [ ] Dashboard summary (server component)
- [ ] Enquiries + email (Resend)

### Phase 3 (weeks 11–18) — highest risk

- [ ] Port `payroll_generate`, statutory calcs from PHP → `lib/services/payroll/`
- [ ] Golden tests: tenant 16 month-by-month parity
- [ ] PF, ESIC, ECR, returns (normalize from JSON blobs)
- [ ] Queue all generate endpoints

### Phase 4 (weeks 19–24)

- [ ] Build portal pages module-by-module
- [ ] Retire `client/` and `super-admin/` HTML
- [ ] Landing page in React (remove template cruft)

### Phase 5–6 (weeks 25–29)

- [ ] Face attendance React pages
- [ ] Migration scripts + parallel run
- [ ] Playwright audit against new URLs
- [ ] Cutover + 30-day legacy read-only

---

## 12. Module mapping (current → new)

| Current | Next.js + Supabase |
|---------|-------------------|
| `backend/api.php` (auth) | Supabase Auth + `middleware.ts` |
| `backend/api.php` (employees) | `lib/services/employees.ts` + RLS |
| `backend/api.php` (payroll/*) | `lib/services/payroll/*` + Inngest job |
| `backend/shift_module.php` | `lib/services/shift.ts` |
| `backend/mail.php` | Resend + `lib/email/*` |
| `storage/clients/*.db` | Supabase Postgres migrations |
| `assets/js/app-common.js` | `components/portal/PortalShell.tsx` + hooks |
| `assets/js/face-attendance.js` | `components/face-attendance/FaceScanner.tsx` |
| `client/*.html`, `super-admin/*.html` | `app/(portal)/**/page.tsx` |
| `index.html` | `app/(marketing)/page.tsx` |
| Custom JWT | Supabase session cookies |

---

## 13. Risk register

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Payroll logic bugs during TS port | High | Critical | Golden-file tests from PHP output |
| Vercel timeout on heavy endpoints | High | High | Mandatory job queue from day 1 of payroll |
| Supabase connection exhaustion | Medium | High | Pooler only; never direct DB from serverless |
| RLS policy mistake → data leak | Medium | Critical | Automated policy tests per table |
| Higher rewrite cost than Laravel | High | Medium | Strangler: keep PHP API until module ported |
| Vendor lock-in (Vercel + Supabase) | Medium | Medium | Keep SQL portable; avoid proprietary APIs in core |
| Face-api CDN dependency | Low | Medium | Self-host models in `public/` (already done) |

---

## 14. Success metrics

| Metric | Target |
|--------|--------|
| Portal routes | < 25 unique `page.tsx` files |
| Payroll parity | 100% match on golden tenant for 12 months |
| API p95 (CRUD) | < 100 ms |
| Payroll job | Completes async; zero HTTP timeouts |
| Playwright audit | 0 navigation errors |
| RLS tests | 100% tenant tables covered |

---

## 15. Decision checklist

- [ ] Accept **22–29 week** timeline (longer than Laravel due to logic rewrite)
- [ ] Budget for **Supabase Pro** + **Vercel Pro** + **Inngest** (~$45–120+/mo at launch)
- [ ] Choose job runner: Inngest vs Trigger.dev vs small VPS worker
- [ ] Region: Supabase Mumbai / Singapore for India users
- [ ] Keep face matching in browser (yes) vs server (no change recommended)
- [ ] Team has TypeScript + React capacity
- [ ] Feature freeze during Phase 3 (payroll)

---

## 16. Cross-stack comparison

*Full hosting and capacity analysis — see `STACK_COMPARISON.md`.*

### Quick summary

| | Current PHP | Laravel | Next.js + Supabase |
|--|-------------|---------|-------------------|
| Migration effort | — | **Medium** (port PHP) | **High** (rewrite to TS) |
| File count reduction | — | **High** (Blade) | **High** (App Router) |
| Payroll fit | Sync in-request | Queues (native) | Queues (external) |
| Hosting ease | Hostinger simple | VPS/Forge | Vercel simple |
| Peak concurrency | Low | Medium–High | Medium (with pooler + jobs) |
| Best for India HR compliance port | Already works | **Fastest safe path** | Best if team is JS-first |

---

## Appendix A — Cost estimate (monthly, early production)

| Service | Tier | Est. cost |
|---------|------|-----------|
| Vercel | Pro | $20/seat |
| Supabase | Pro | $25+ |
| Inngest | Free → Pro | $0–50 |
| Resend | Starter | $0–20 |
| Domain + email | — | $5–15 |
| **Total** | | **~$50–130/mo** |

Compare: Hostinger Business PHP ~$4–15/mo, VPS ~$6–24/mo (more ops time).

---

## Appendix B — First week actions

1. Create Supabase staging project + enable RLS template.
2. `npx create-next-app@latest hrseva-web`
3. Implement login + middleware + empty portal layout.
4. Port employee list read path with one RLS policy.
5. Spike: run PHP `payroll_generate` output vs planned TS interface (shape only).

---

*Paired with `MIGRATION_PLAN.md` (Laravel) and `STACK_COMPARISON.md` (performance, hosting, user capacity).*
