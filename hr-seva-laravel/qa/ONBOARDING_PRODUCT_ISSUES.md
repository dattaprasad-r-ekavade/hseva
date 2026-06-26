# Onboarding walkthrough — product issues

_Auto-generated from Playwright onboarding tour on 2026-06-26._

## Summary

The current "registration" path is **lead capture + manual super-admin provisioning**. There is no self-service tenant signup.

## Issues found

### 1. [Medium] Template leftover: mini-cart "Checkout" CTA

The landing page mini-cart still shows e-commerce copy ("Checkout", "$97.00 subtotal") from the theme template. This confuses HR SaaS visitors.

### 2. [Critical] No self-service account creation after free-trial submit

Submitting the landing form only creates a lead (`/api/public-enquiries`). The user sees "Our team will contact you" with no credentials, no email verification, and no path into the product without super-admin intervention.

### 3. [High] Enquiry → client provisioning is fully manual

Super-admin must separately open Client Module and re-enter company name, address, PAN/GSTIN, user ID, and password. There is no "Convert to client" action on the enquiry record.

### 4. [Critical] Client Module "Save Client" does not reliably create tenants via API

The modal form can show success in the table via localStorage fallback (`client-module.js`) without a `POST /api/clients`. Fresh installs also ship with zero subscription plans, blocking HTML5 validation until plans are seeded manually.

### 5. [Critical] Client plan assignment does not activate portal access

Setting `subscriptionPlanId` on a new client is not enough for login. Super-admin must also create an Active row in `/api/subscriptions` or the client receives "Subscription expired / No active subscription found".

### 6. [Medium] Forgot-password link hidden on client login

`Forgot password?` has class `d-none` on the client login page. Users cannot self-serve password recovery from the UI.

### 7. [Medium] No onboarding checklist for new client admins

After first login the portal drops users into the dashboard/employee master with no guided setup (company profile, first employee, attendance, payroll month).

---

## Additional findings (manual review)

### 8. [Critical] Fresh install has zero subscription plans

Super-admin cannot provision clients until subscription plans are created manually (Subscriptions screen or API). The Client Module dropdown shows "No Subscription Plans".

### 9. [High] Dev/QA bootstrap gap

A clean Laravel checkout needs `database/database.sqlite` + `php artisan migrate` before pages work (`SESSION_DRIVER=database`). Without it the landing page returns 500.

### 10. [Low] Landing uses legacy `.html` login URLs

Marketing links point to `client/client-login.html` instead of clean `/client/login` redirects.

## Recommended product backlog (prioritized)

| Priority | Item |
|----------|------|
| P0 | Self-service trial signup **or** clear post-submit expectations + SLA email |
| P0 | Auto-create active subscription when client + plan are saved |
| P0 | Seed default subscription plans on install |
| P1 | "Convert enquiry → client" wizard (prefill from lead) |
| P1 | Fix Client Module save to always hit API (remove silent localStorage fallback) |
| P2 | Remove theme e-commerce mini-cart remnants |
| P2 | Client onboarding checklist after first login |
| P2 | Enable forgot-password flow on client login |

## Run the tour locally

```bash
cd hr-seva-laravel/qa
npm install
npx playwright install chromium
npm run test:tour      # records video + writes this issues file
npm run tour:gif       # builds artifacts/onboarding-guided-tour.gif|.mp4
```
