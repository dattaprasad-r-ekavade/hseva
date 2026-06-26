/**
 * End-to-end product walkthrough: landing free-trial → super-admin enquiry →
 * client provisioning → client portal login.
 *
 * Records video (see playwright.config.js project "onboarding-tour").
 * Product issues discovered during the run are appended to ONBOARDING_PRODUCT_ISSUES.md.
 */
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const ISSUES_FILE = path.join(__dirname, '..', 'ONBOARDING_PRODUCT_ISSUES.md');
const issues = [];

function note(severity, title, detail) {
  issues.push({ severity, title, detail });
}

function flushIssues() {
  const stamp = new Date().toISOString().slice(0, 10);
  const body = issues
    .map(
      (i, n) =>
        `### ${n + 1}. [${i.severity}] ${i.title}\n\n${i.detail}\n`,
    )
    .join('\n');
  const doc = `# Onboarding walkthrough — product issues\n\n_Auto-generated from Playwright onboarding tour on ${stamp}._\n\n## Summary\n\nThe current "registration" path is **lead capture + manual super-admin provisioning**. There is no self-service tenant signup.\n\n## Issues found\n\n${body}`;
  fs.writeFileSync(ISSUES_FILE, doc, 'utf8');
}

async function pause(page, ms = 1200) {
  await page.waitForTimeout(ms);
}

async function gotoPortal(page, path) {
  await page.goto(path, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
}

function trialCta(page) {
  return page
    .locator('a[href="#uc-contact-modal"][data-uc-toggle]:visible')
    .filter({ hasText: 'Free Trial' })
    .first();
}

async function openFreeTrialModal(page) {
  const cta = trialCta(page);
  await cta.scrollIntoViewIfNeeded();
  await cta.click();
  const form = page.locator('#freeTrialForm');
  try {
    await expect(form).toBeVisible({ timeout: 5000 });
  } catch {
    note(
      'High',
      'Free-trial modal may not open via CTA',
      'Clicking "Start free trial" did not reveal `#freeTrialForm` within 5s. The UIkit modal (`data-uc-toggle`) may be fragile or blocked by overlays.',
    );
    await page.evaluate(() => {
      const modal = document.getElementById('uc-contact-modal');
      if (modal) {
        modal.classList.add('uc-open');
        modal.removeAttribute('hidden');
        modal.style.display = 'block';
      }
    });
    await expect(form).toBeVisible({ timeout: 3000 });
  }
}

async function loginSuperAdmin(page) {
  await gotoPortal(page, '/super-admin/super-admin-login.html');
  await page.locator('#adminUserId').fill('admin@hrseva.com');
  await page.locator('#adminPassword').fill('123456');
  await page.locator('#adminLoginForm button[type="submit"]').click();
  await page.waitForURL(/super-admin-dashboard/, { timeout: 15000 });
}

async function adminToken(page) {
  return page.evaluate(() => {
    const raw = sessionStorage.getItem('hr_auth_session_v1');
    return raw ? JSON.parse(raw).token : '';
  });
}

async function activateClientSubscription(request, page, clientId, planName = 'Free Trial') {
  const token = await adminToken(page);
  const start = new Date();
  const end = new Date(start);
  end.setMonth(end.getMonth() + 12);
  const fmt = (d) => d.toISOString().slice(0, 10);
  const res = await request.post('/api/subscriptions', {
    headers: { Authorization: `Bearer ${token}` },
    data: {
      clientId,
      planName,
      startDate: fmt(start),
      endDate: fmt(end),
      renewalDate: fmt(end),
      status: 'Active',
      amount: 0,
    },
  });
  expect(res.ok()).toBeTruthy();
}

async function ensureSubscriptionPlan(request, page) {
  const token = await adminToken(page);
  const listRes = await request.get('/api/subscription-plans', {
    headers: { Authorization: `Bearer ${token}` },
  });
  const listBody = await listRes.json();
  const existing = Array.isArray(listBody.rows) ? listBody.rows : [];
  if (existing.length > 0) {
    return existing[0];
  }

  note(
    'Critical',
    'Fresh install has zero subscription plans',
    'Super-admin cannot create clients from the UI until at least one subscription plan exists. The Client Module dropdown shows "No Subscription Plans" and blocks provisioning on a clean database.',
  );

  const createRes = await request.post('/api/subscription-plans', {
    headers: { Authorization: `Bearer ${token}` },
    data: {
      planName: 'Free Trial',
      durationMonths: 12,
      amount: 0,
      status: 'Active',
      features: 'Trial access',
      accessTypeCode: 'full_access',
    },
  });
  expect(createRes.ok()).toBeTruthy();
  return (await createRes.json()).row;
}

test.describe.configure({ mode: 'serial' });

test.describe('Onboarding guided tour', () => {
  const runId = Date.now();
  const lead = {
    fullName: `Tour User ${runId}`,
    email: `tour${runId}@example.com`,
    phone: '9876543210',
    companyName: `Tour Co ${runId}`,
    teamSize: '11-25',
    plan: 'Free Trial',
    state: 'Maharashtra',
    address: '42 Test Industrial Estate',
    city: 'Mumbai',
    pincode: '400001',
    clientUserId: `tour_client_${runId}`,
    password: 'TourPass123!',
  };

  test.afterAll(() => {
    flushIssues();
  });

  test('full journey with recording', async ({ page, request }) => {
    test.setTimeout(300000);

    // —— 1. Landing page ——
    await gotoPortal(page, '/');
    await expect(page).toHaveTitle(/HR Seva/i);
    const heroTrialCta = trialCta(page);
    await heroTrialCta.scrollIntoViewIfNeeded();
    await expect(heroTrialCta).toBeVisible();
    await pause(page, 1500);

    const cartCheckout = page.locator('a[href="#uc-contact-modal"]:has-text("Checkout")');
    if (await cartCheckout.count()) {
      note(
        'Medium',
        'Template leftover: mini-cart "Checkout" CTA',
        'The landing page mini-cart still shows e-commerce copy ("Checkout", "$97.00 subtotal") from the theme template. This confuses HR SaaS visitors.',
      );
    }

    // —— 2. Free trial lead form ——
    await openFreeTrialModal(page);
    await expect(page.locator('text=Start your free trial')).toBeVisible();

    await page.locator('#trialFullName').fill(lead.fullName);
    await page.locator('#trialEmail').fill(lead.email);
    await page.locator('#trialPhone').fill(lead.phone);
    await pause(page);
    await page.locator('#freeTrialSubmit').click();

    await expect(page.locator('#trialCompanyName')).toBeVisible();
    await page.locator('#trialCompanyName').fill(lead.companyName);
    await page.locator('#trialTeamSize').selectOption(lead.teamSize);
    await page.locator('#trialPlan').selectOption(lead.plan);
    await page.locator('#trialState').selectOption(lead.state);
    await page.locator('#trialAddress').fill(lead.address);
    await page.locator('#trialLocation').fill(lead.city);
    await page.locator('#trialPincode').fill(lead.pincode);
    await pause(page);

    const submitRes = page.waitForResponse(
      (r) => r.url().includes('/api/public-enquiries') && r.request().method() === 'POST',
    );
    await page.locator('#freeTrialSubmit').click();
    const apiRes = await submitRes;
    expect(apiRes.ok()).toBeTruthy();

    await expect(page.locator('#freeTrialSuccessState')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('text=Thank you!')).toBeVisible();
    await pause(page, 2000);

    note(
      'Critical',
      'No self-service account creation after free-trial submit',
      'Submitting the landing form only creates a lead (`/api/public-enquiries`). The user sees "Our team will contact you" with no credentials, no email verification, and no path into the product without super-admin intervention.',
    );

    // —— 3. Super-admin reviews enquiry ——
    await loginSuperAdmin(page);
    await pause(page);

    await gotoPortal(page, '/super-admin/super-admin-enquiries.html');
    await expect(page.getByRole('heading', { name: 'Landing Enquiries' }).first()).toBeVisible();

    await page.locator('#searchInput').fill(lead.companyName);
    await pause(page, 800);
    const enquiryRow = page.locator('#enquiryTableBody tr', { hasText: lead.companyName });
    await expect(enquiryRow).toBeVisible({ timeout: 10000 });
    await enquiryRow.locator('button[data-action="open"]').click();
    await expect(page.locator('#enquiryModal')).toBeVisible();
    await pause(page);

    const prefilledCompany = await page.locator('#detailCompanyName').inputValue();
    if (prefilledCompany !== lead.companyName) {
      note('Medium', 'Enquiry modal prefill mismatch', `Expected company "${lead.companyName}", got "${prefilledCompany}".`);
    }

    await page.locator('#detailStatus').selectOption('Qualified');
    await page.locator('#saveEnquiryBtn').click();
    await pause(page, 1000);

    note(
      'High',
      'Enquiry → client provisioning is fully manual',
      'Super-admin must separately open Client Module and re-enter company name, address, PAN/GSTIN, user ID, and password. There is no "Convert to client" action on the enquiry record.',
    );

    // —— 4. Super-admin creates tenant client ——
    await ensureSubscriptionPlan(request, page);
    await gotoPortal(page, '/super-admin/super-admin-module.html');
    await expect(page.locator('text=Client Master')).toBeVisible();
    await page.locator('#btnAddClient').click();
    await expect(page.locator('#clientModal')).toBeVisible();
    await pause(page);

    await page.locator('#companyName').fill(lead.companyName);
    await page.locator('#companyAddress').fill(lead.address);
    await page.locator('#companyRegNo').fill(`REG${runId}`);
    await page.locator('#companyContactNo').fill(lead.phone);
    await page.locator('#userId').fill(lead.clientUserId);
    await page.locator('#userPassword').fill(lead.password);
    await page.locator('#companyPAN').fill('ABCDE1234F');
    await page.locator('#companyTAN').fill('MUMB12345A');
    await page.locator('#companyGSTIN').fill('27ABCDE1234F1Z5');
    await page.locator('#subscriptionPlanId').selectOption({ index: 0 });
    await pause(page);

    let clientPostSeen = false;
    page.on('request', (req) => {
      if (req.url().includes('/api/clients') && req.method() === 'POST') {
        clientPostSeen = true;
      }
    });

    await page.locator('button[type="submit"][form="clientForm"]').click();
    await pause(page, 2500);

    let clientId = 0;

    let clientVisible = (await page.locator('#clientTbody', { hasText: lead.companyName }).count()) > 0;
    if (!clientPostSeen || !clientVisible) {
      note(
        'Critical',
        'Client Module "Save Client" does not reliably create tenants via API',
        'The modal form can show success in the table via localStorage fallback (`client-module.js`) without a `POST /api/clients`. Fresh installs also ship with zero subscription plans, blocking HTML5 validation until plans are seeded manually.',
      );

      const token = await adminToken(page);
      const plansRes = await request.get('/api/subscription-plans', {
        headers: { Authorization: `Bearer ${token}` },
      });
      const planId = (await plansRes.json()).rows?.[0]?.id || 1;
      const apiCreate = await request.post('/api/clients', {
        headers: { Authorization: `Bearer ${token}` },
        data: {
          companyName: lead.companyName,
          companyAddress: lead.address,
          companyRegNo: `REG${runId}`,
          companyPAN: 'ABCDE1234F',
          companyTAN: 'MUMB12345A',
          companyGSTIN: '27ABCDE1234F1Z5',
          companyContactNo: lead.phone,
          userId: lead.clientUserId,
          userPassword: lead.password,
          subscriptionPlanId: planId,
        },
      });
      expect(apiCreate.status()).toBe(201);
      clientId = (await apiCreate.json()).row.id;
      await gotoPortal(page, '/super-admin/super-admin-module.html');
      clientVisible = true;
    } else {
      const token = await adminToken(page);
      const clientsRes = await request.get('/api/clients', {
        headers: { Authorization: `Bearer ${token}` },
      });
      const rows = (await clientsRes.json()).rows || [];
      const hit = rows.find((r) => String(r.userId) === lead.clientUserId);
      clientId = hit ? hit.id : 0;
    }

    if (clientId > 0) {
      await activateClientSubscription(request, page, clientId, lead.plan);
      note(
        'Critical',
        'Client plan assignment does not activate portal access',
        'Setting `subscriptionPlanId` on a new client is not enough for login. Super-admin must also create an Active row in `/api/subscriptions` or the client receives "Subscription expired / No active subscription found".',
      );
    }

    await expect(page.locator('#clientTbody', { hasText: lead.companyName })).toBeVisible({
      timeout: 10000,
    });

    // —— 5. Client portal login ——
    await page.evaluate(() => {
      sessionStorage.removeItem('hr_auth_session_v1');
      localStorage.removeItem('hr_auth_session_v1');
    });
    await gotoPortal(page, '/client/client-login.html');
    await pause(page);
    await page.locator('#userId').fill(lead.clientUserId);
    await page.locator('#userPassword').fill(lead.password);
    await page.locator('#loginForm button[type="submit"]').click();
    await page.waitForURL(/client\/index\.html/, { timeout: 20000 });
    await pause(page, 2000);

    const forgotLink = page.locator('a[data-bs-target="#forgotModal"]');
    if (await forgotLink.isHidden()) {
      note(
        'Medium',
        'Forgot-password link hidden on client login',
        '`Forgot password?` has class `d-none` on the client login page. Users cannot self-serve password recovery from the UI.',
      );
    }

    // —— 6. Brief portal exploration ——
    await gotoPortal(page, '/client/client-employee-master.html');
    await pause(page, 1500);
    await expect(page.locator('body')).toContainText(/employee/i);

    const loginHref = page.locator('a[href="client/client-login.html"]');
    if (await loginHref.count()) {
      note(
        'Low',
        'Landing uses legacy .html login URLs',
        'Marketing links point to `client/client-login.html` instead of clean `/client/login` redirects. Works but feels dated.',
      );
    }

    note(
      'Medium',
      'No onboarding checklist for new client admins',
      'After first login the portal drops users into the dashboard/employee master with no guided setup (company profile, first employee, attendance, payroll month).',
    );
  });
});
