# HR Seva — Stack Comparison: Performance, Hosting & Capacity

**Document version:** 1.0  
**Date:** June 21, 2026  
**Compares:** Current PHP setup · Laravel migration · Next.js + Supabase migration

**Related:** `PROJECT_ASSESSMENT.md`, `MIGRATION_PLAN.md`, `MIGRATION_PLAN_NEXTJS.md`

---

## Executive summary

| Question | Answer |
|----------|--------|
| **Best performance for HR Seva workloads?** | **Laravel on a VPS** (or managed PHP) with PostgreSQL + Redis queues — payroll runs server-side without serverless timeouts. |
| **Easiest hosting / lowest ops?** | **Next.js on Vercel + Supabase** — if you accept job queues for payroll and higher monthly cost. |
| **Cheapest at small scale?** | **Current PHP on Hostinger** — fine for pilots; hits walls quickly. |
| **Best overall for this product today?** | **Laravel on VPS** balances cost, payroll fit, migration effort, and scale. |
| **Best if team is React/TS only?** | **Next.js + Supabase** — budget extra time and job infrastructure. |

**Capacity in one line:**

| Stack | Comfortable zone | Stretch zone |
|-------|------------------|--------------|
| Current PHP + SQLite (Hostinger) | 10–30 tenants, ~50 concurrent users | 50 tenants with pain |
| Current PHP + SQLite (VPS 4GB) | 30–80 tenants, ~100 concurrent users | 150 tenants |
| Laravel + PostgreSQL (VPS 4–8GB) | 100–400 tenants, ~200–400 concurrent users | 800+ tenants (tuned) |
| Next.js + Supabase (Pro tiers) | 100–300 tenants, ~150–300 concurrent users | 500+ tenants (with workers + plan upgrades) |

*Definitions below. Numbers assume typical HR portal usage: mostly daytime, read-heavy, payroll run monthly per tenant.*

---

## 1. Definitions

| Term | Meaning |
|------|---------|
| **Tenant** | One client company on the platform |
| **Registered users** | Admin + employee logins across all tenants |
| **Concurrent users** | Users with active sessions making requests in the same minute |
| **Payroll burst** | Month-end: many tenants generate salary sheets in a short window |
| **Without issue** | p95 API < 2s for CRUD, payroll completes without timeout, no SQLite lock errors, <1% error rate |

---

## 2. Hosting options compared

### 2.1 Current stack — Hostinger (shared PHP)

| Aspect | Detail |
|--------|--------|
| **Setup** | Upload files, Apache + PHP 8.x, `.htaccess` |
| **Cost** | ~$3–15/mo (shared hosting) |
| **Pros** | Cheapest, familiar, mail on same host |
| **Cons** | Shared CPU/RAM, no Redis/queue, no SSH on basic plans, SQLite on shared disk is fragile |
| **Payroll** | Runs in HTTP request — long generates may hit `max_execution_time` (30–120s) |
| **SSL / CDN** | Included; not edge-global like Vercel |

**Verdict:** Good for **demo, internal pilot, 5–15 tenants**. Not for growth SaaS.

---

### 2.2 Current stack / Laravel — VPS (Hetzner, DigitalOcean, Hostinger VPS)

| Spec example | 2 vCPU, 4 GB RAM, 80 GB SSD (~$12–24/mo) |
|--------------|------------------------------------------|
| **Stack** | Nginx + PHP-FPM 8.2 + PostgreSQL + Redis |
| **Pros** | Full control, long-running workers, predictable CPU, queue workers for payroll |
| **Cons** | You patch OS, backups, monitoring; need deploy script (Forge, Ploi, or Ansible) |
| **Laravel extras** | `php artisan queue:work`, Horizon, Octane (optional) |

**Verdict:** **Best price/performance for HR Seva** at small–medium scale.

---

### 2.3 Laravel — Managed PHP (Laravel Forge + VPS)

| Aspect | Detail |
|--------|--------|
| **Cost** | Forge $12/mo + VPS $12–48/mo |
| **Pros** | Zero-downtime deploy, queues, SSL, backups |
| **Cons** | Still your VPS bill; not fully serverless |

**Verdict:** Same capacity as VPS; less ops time.

---

### 2.4 Next.js — Vercel

| Tier | Limits relevant to HR Seva |
|------|---------------------------|
| **Hobby** | Serverless 10s timeout — **unsuitable for payroll** |
| **Pro** | 60s function timeout, better bandwidth, team features |
| **Enterprise** | Custom limits, SLA |

| Aspect | Detail |
|--------|--------|
| **Pros** | Git push deploy, global CDN, preview URLs, great for marketing + portal SSR |
| **Cons** | Serverless timeouts, cold starts (100–500ms on API), cost scales with invocations |
| **Payroll** | **Must** use external job runner (Inngest, Trigger.dev, or VPS worker) |

**Verdict:** Excellent **frontend hosting**; **not** a complete backend for payroll alone.

---

### 2.5 Supabase (with Next.js)

| Tier | Direct connections | Pooler | DB size |
|------|-------------------|--------|---------|
| **Free** | ~60 | Limited | 500 MB |
| **Pro** | ~200 | Supavisor pooler | 8 GB included |
| **Team** | Higher | Dedicated options | More |

| Aspect | Detail |
|--------|--------|
| **Pros** | Managed Postgres, Auth, Storage, RLS for multi-tenancy, backups |
| **Cons** | Connection limits on serverless (must use pooler), egress costs, region choice |
| **India latency** | Pick Mumbai/Singapore region when available |

**Verdict:** Strong database + auth layer; pair with job worker for payroll.

---

## 3. Performance comparison

### 3.1 Request latency (typical CRUD — employee list, 50 rows)

| Stack | Hosting | p50 | p95 | Notes |
|-------|---------|-----|-----|-------|
| Current PHP | Hostinger shared | 120–300 ms | 400–800 ms | No opcode cache tuning, shared neighbors |
| Current PHP | VPS + OPcache | 40–100 ms | 100–200 ms | SQLite still single-writer |
| Laravel | VPS + PG + Redis cache | 30–80 ms | 60–150 ms | Eloquent + indexes |
| Next.js + Supabase | Vercel Pro + pooler | 50–120 ms | 100–250 ms | Cold start adds variance on API routes |

**Winner (CRUD):** Laravel on VPS (slightly) — warm PHP-FPM is very fast for simple queries.

---

### 3.2 Payroll generate (50 employees, synchronous today)

| Stack | Behaviour | User experience |
|-------|-----------|-----------------|
| Current PHP | In-request, 2–15s CPU | Browser waits; may timeout on Hostinger |
| Laravel + queue | HTTP 202 → job 5–30s | Spinner + notification — **best UX** |
| Next.js + Vercel only | **Fails** at 10–60s | Must use Inngest/worker |
| Next.js + Inngest + Supabase | Job 5–30s | Same as Laravel queue |

**Winner (payroll):** **Tie: Laravel queues vs Next.js + Inngest** — both async. **Loser: current sync PHP on shared hosting.**

---

### 3.3 Month-end burst (20 tenants run payroll within 1 hour)

| Stack | Handling |
|-------|----------|
| Current SQLite | Serial lock contention; **degrades badly** |
| Laravel + PG + 2 queue workers | Processes jobs in parallel — **stable** |
| Next.js + Supabase + Inngest | Scales workers — **stable** (higher $) |

**Winner:** Laravel VPS or Next.js with dedicated job tier.

---

### 3.4 Static assets & landing page

| Stack | Score |
|-------|-------|
| Current | Single server, no global CDN unless Cloudflare added |
| Laravel | Same; use Cloudflare or S3+CDN |
| **Next.js + Vercel** | **Best** — edge CDN by default |

**Winner:** Next.js + Vercel for marketing/portal asset delivery.

---

### 3.5 Face attendance (browser-side)

All stacks equal — face-api.js runs in browser; only API save/load differs. No meaningful performance winner.

---

## 4. Capacity estimates

*Assumptions: average tenant has 25–75 employees; 5–15% of users online peak hour; payroll once per month per tenant.*

### 4.1 Current PHP + SQLite

#### Hostinger shared (~$5–15/mo)

| Metric | Comfortable | Stretch | Breaking point |
|--------|-------------|---------|----------------|
| Tenants | **10–20** | 30–40 | 50+ |
| Registered users | 500–1,500 | 2,500 | 4,000+ |
| Concurrent users | **20–40** | 60–80 | 100+ (503/timeouts) |
| Payroll burst | 2–3 tenants/hour | 5 | Lock errors, timeouts |

**Issues first:** SQLite write locks, `max_execution_time`, shared CPU throttling, disk I/O on `storage/clients/`.

#### VPS 2 vCPU / 4 GB (~$12–20/mo)

| Metric | Comfortable | Stretch |
|--------|-------------|---------|
| Tenants | **30–60** | 80–100 |
| Registered users | 2,000–5,000 | 8,000 |
| Concurrent users | **80–120** | 150–200 |
| Payroll burst | 8–10 tenants/hour | 15 |

**Issues first:** Single server disk, no horizontal scale, backup of N SQLite files.

---

### 4.2 Laravel + PostgreSQL + Redis (VPS 4 GB)

| Metric | Comfortable | Stretch | With tuning (8 GB + Horizon) |
|--------|-------------|---------|------------------------------|
| Tenants | **100–200** | 400 | 800+ |
| Registered users | 8,000–20,000 | 40,000 | 80,000+ |
| Concurrent users | **150–250** | 400 | 600+ |
| Payroll burst | 20 tenants/hour queued | 50 | 100+ with workers |

**Tuning:** 2–4 queue workers, Redis cache for control/dashboard, DB indexes, optional read replica.

---

### 4.3 Laravel (managed scale — Forge + 8 GB VPS or similar)

| Metric | Comfortable | Stretch |
|--------|-------------|---------|
| Tenants | **300–500** | 1,000 |
| Concurrent users | **400–600** | 1,000 |
| Notes | Add second app server + load balancer beyond 500 tenants | |

---

### 4.4 Next.js + Supabase + Vercel (Pro tiers)

**Stack:** Vercel Pro + Supabase Pro + Inngest Pro (~$70–150/mo)

| Metric | Comfortable | Stretch | Breaking point |
|--------|-------------|---------|----------------|
| Tenants | **80–150** | 300 | 500+ without plan upgrades |
| Registered users | 6,000–15,000 | 35,000 | Connection/egress limits |
| Concurrent users | **120–200** | 350 | Pooler saturation |
| Payroll burst | 15 jobs/min (Inngest) | 40 | Worker concurrency cap |

**Upgrade path:** Supabase Team, Vercel Enterprise, dedicated Inngest concurrency, read replicas.

#### Next.js + Supabase (Free tiers — development only)

| Metric | Limit |
|--------|-------|
| Tenants | **1–3** demo |
| Concurrent users | **5–10** |
| Payroll | Not production-safe |

---

## 5. Cost vs capacity matrix (monthly)

| Setup | Est. cost/mo | Tenants (comfortable) | Ops hours/mo |
|-------|--------------|----------------------|--------------|
| Current PHP, Hostinger shared | $5–15 | 10–20 | 1–2 |
| Current PHP, VPS 4GB | $12–24 | 30–60 | 4–8 |
| Laravel, VPS 4GB + Redis | $15–30 | 100–200 | 4–8 |
| Laravel, Forge + VPS 8GB | $40–70 | 300–500 | 2–4 |
| Next.js, Vercel Pro + Supabase Pro + Inngest | $70–150 | 80–150 | 2–4 |
| Next.js, Enterprise scale | $300+ | 500+ | 4–8 |

---

## 6. Which is better? Decision matrix

### Performance (weighted for HR Seva: payroll + compliance + portal)

| Rank | Stack | Score | Why |
|------|-------|-------|-----|
| 1 | **Laravel + VPS + PG + queues** | 9/10 | Long jobs, no serverless timeout, mature queue story |
| 2 | **Next.js + Supabase + Inngest** | 7.5/10 | Good after jobs added; cold starts, connection care |
| 3 | **Current PHP + VPS** | 6/10 | Works; SQLite and sync payroll limit burst |
| 4 | **Current PHP + Hostinger** | 4/10 | Fine for pilot only |

### Hosting simplicity (less DevOps)

| Rank | Stack | Score |
|------|-------|-------|
| 1 | **Next.js + Vercel + Supabase** | 9/10 |
| 2 | **Current PHP + Hostinger** | 8/10 (until it breaks) |
| 3 | **Laravel + Forge** | 7/10 |
| 4 | **Laravel + raw VPS** | 5/10 |

### Migration effort (from current codebase)

| Rank | Stack | Effort |
|------|-------|--------|
| 1 | **Laravel** | 15–23 weeks — port PHP |
| 2 | **Next.js + Supabase** | 22–29 weeks — rewrite logic in TS |
| 3 | Stay + harden current | 2–4 weeks — security only |

### Total cost of ownership (3-year, small SaaS)

| Stack | Dev cost | Infra cost | Risk |
|-------|----------|------------|------|
| Laravel VPS | Lower rewrite | Low–medium | Low |
| Next.js + Supabase | Higher rewrite | Medium–high | Medium (vendor limits) |
| Stay on Hostinger | None | Lowest | High (scale ceiling) |

---

## 7. Recommendations by stage

### Stage A — Pilot (0–20 tenants, proving product)

**Use:** Current PHP on **Hostinger** or local VPS  
**Action:** Security fixes from `PROJECT_ASSESSMENT.md` only  
**Capacity:** Enough for demos and first paying clients  

### Stage B — Early SaaS (20–100 tenants)

**Use:** **Laravel on VPS 4GB** + PostgreSQL  
**Why:** Best balance — port PHP payroll logic, async jobs, ~$25/mo infra  
**Avoid:** Staying on SQLite; Vercel-only without job queue  

### Stage C — Growth (100–400 tenants)

**Use:** Laravel + Forge + 8GB VPS + Redis + 2 workers  
**Or:** Next.js + Supabase Team + Inngest if team is JS-native  
**Capacity:** 200–400 tenants comfortable  

### Stage D — Scale (400+ tenants)

**Use:** Horizontal app servers, PG read replica, dedicated queue cluster  
**Stacks:** Laravel scales traditionally; Next.js needs deliberate worker + DB tier upgrades  
**Neither:** Hostinger shared or SQLite  

---

## 8. Final verdict

### Performance winner

**Laravel on a VPS with PostgreSQL and queue workers** — HR Seva is payroll- and compliance-heavy with long CPU-bound jobs. PHP-FPM on a dedicated server handles this naturally. Next.js matches only when you add Inngest (or similar) and respect Supabase pooling.

### Hosting winner (ease)

**Next.js on Vercel + Supabase** — push-to-deploy, managed DB/auth, no OS patching. You pay more and must architect around serverless limits.

### Hosting winner (budget)

**Hostinger VPS ($12–20/mo) running Laravel** beats Vercel+Supabase on monthly cost per tenant served.

### Capacity winner (users served without issue)

| Scenario | Winner |
|----------|--------|
| Same monthly budget ~$25 | **Laravel VPS** — more tenants than Vercel Free/Hobby |
| Same monthly budget ~$100 | **Laravel Forge 8GB** ≈ **Next.js Pro stack** — Laravel slightly more tenants for payroll bursts |
| Unlimited budget, JS team | **Next.js + Supabase Enterprise** — scales with spend |

### Practical recommendation for HR Seva

1. **Short term:** Keep current setup for dev; deploy pilot on **Hostinger VPS** (not shared) if needed — 30–60 tenants max.  
2. **Medium term:** **Migrate to Laravel + PostgreSQL on VPS** — lowest risk, best payroll fit, reuses PHP domain logic.  
3. **Choose Next.js + Supabase** only if: team is already React/TS, you budget **$80+/mo** infra, and you commit to **Inngest from day one** for payroll.

---

## 9. Reference table — all stacks at a glance

| | Current (Hostinger) | Current (VPS) | Laravel (VPS 4GB) | Next.js + Supabase (Pro) |
|--|---------------------|---------------|-------------------|--------------------------|
| **Monthly cost** | $5–15 | $12–24 | $20–40 | $70–150 |
| **Tenants (comfortable)** | 10–20 | 30–60 | 100–200 | 80–150 |
| **Concurrent users** | 20–40 | 80–120 | 150–250 | 120–200 |
| **Payroll model** | Sync HTTP | Sync HTTP | Queued | Queued (Inngest) |
| **DB** | SQLite | SQLite | PostgreSQL | Supabase PG |
| **Migration weeks** | 0 | 0 | 15–23 | 22–29 |
| **Global CDN** | Optional | Optional | Optional | Built-in |
| **Multi-tenant isolation** | File per DB | File per DB | PG + policies | RLS |
| **Face attendance** | Works | Works | Works | Works |

---

*Revisit capacity numbers after load testing with tenant 16 payroll generate and 50 concurrent Playwright sessions.*
