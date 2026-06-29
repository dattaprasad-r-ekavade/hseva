/**
 * Comprehensive E2E audit: forms, APIs, portal pages, face attendance stack.
 * Writes qa/E2E_AUDIT_REPORT.md with failures.
 */
const fs = require('fs');
const path = require('path');
const { test, expect } = require('@playwright/test');
const {
  loginSuperAdmin,
  seedAuth,
  bootstrapTenant,
  seedEmployee,
  attachDiagnostics,
  gotoAndSettle,
} = require('../helpers/hr-api');

const REPORT = path.join(__dirname, '..', 'E2E_AUDIT_REPORT.md');
const findings = [];

function record(area, severity, title, detail) {
  findings.push({ area, severity, title, detail });
}

function writeReport() {
  const stamp = new Date().toISOString();
  const grouped = {};
  for (const f of findings) {
    grouped[f.area] = grouped[f.area] || [];
    grouped[f.area].push(f);
  }
  let body = `# E2E audit report\n\n_Generated ${stamp}_\n\n`;
  for (const [area, items] of Object.entries(grouped)) {
    body += `## ${area}\n\n`;
    items.forEach((item, i) => {
      body += `### ${i + 1}. [${item.severity}] ${item.title}\n\n${item.detail}\n\n`;
    });
  }
  if (!findings.length) {
    body += 'All checked flows passed.\n';
  }
  fs.writeFileSync(REPORT, body, 'utf8');
}

test.afterAll(() => writeReport());

test.describe('E2E comprehensive audit', () => {
  test.describe.configure({ mode: 'serial' });

  test('public landing free-trial form submits', async ({ page }) => {
    const runId = Date.now();
    await gotoAndSettle(page, '/');
    await expect(page).toHaveTitle(/HR Seva/i);

    const cta = page
      .locator('a[href="#uc-contact-modal"][data-uc-toggle]:visible')
      .filter({ hasText: 'Free Trial' })
      .first();
    await cta.click();
    await expect(page.locator('#freeTrialForm')).toBeVisible();

    await page.locator('#trialFullName').fill(`E2E User ${runId}`);
    await page.locator('#trialEmail').fill(`e2e${runId}@example.com`);
    await page.locator('#trialPhone').fill('9876501234');
    await page.locator('#freeTrialSubmit').click();

    await page.locator('#trialCompanyName').fill(`E2E Co ${runId}`);
    await page.locator('#trialTeamSize').selectOption('11-25');
    await page.locator('#trialPlan').selectOption('Free Trial');
    await page.locator('#trialState').selectOption('Maharashtra');
    await page.locator('#trialAddress').fill('1 E2E Street');
    await page.locator('#trialLocation').fill('Mumbai');
    await page.locator('#trialPincode').fill('400001');

    const submitRes = page.waitForResponse(
      (r) => r.url().includes('/api/public-enquiries') && r.request().method() === 'POST',
    );
    await page.locator('#freeTrialSubmit').click();
    const res = await submitRes;
    expect(res.ok()).toBeTruthy();
    await expect(page.locator('#freeTrialSuccessState')).toBeVisible();
  });

  test('tenant APIs and form-backed modules respond', async ({ request }) => {
    const session = await loginSuperAdmin(request);
    const tenant = await bootstrapTenant(request, session.token, 'api');
    const headers = tenant.tenantHeaders;

    const employeeRes = await seedEmployee(request, headers);
    expect(employeeRes.status()).toBe(201);

    const checks = [
      ['GET', '/api/employees'],
      ['GET', '/api/face-attendance/settings'],
      ['GET', '/api/face-attendance/registrations'],
      ['GET', '/api/face-attendance/sheet?month=6&year=2026'],
      ['GET', '/api/face-attendance/report?month=6&year=2026'],
      ['GET', '/api/attendance/daily?month=6&year=2026'],
      ['GET', '/api/leaves'],
      ['GET', '/api/compliance/tasks?month=6&year=2026'],
      ['GET', '/api/control'],
      ['GET', '/api/dashboard/summary?month=6&year=2026'],
    ];

    for (const [method, path] of checks) {
      const res = await request.fetch(`/api${path.replace('/api', '')}`, {
        method,
        headers,
      });
      if (!res.ok()) {
        record('API', 'High', `${method} ${path} failed`, `Status ${res.status()}: ${await res.text()}`);
      }
      expect(res.ok(), `${method} ${path}`).toBeTruthy();
    }

    const settingsPut = await request.put('/api/face-attendance/settings', {
      headers: { ...headers, 'Content-Type': 'application/json' },
      data: { graceTime: 12, faceMatchThreshold: 0.5 },
    });
    expect(settingsPut.ok()).toBeTruthy();
    const settingsBody = await settingsPut.json();
    const modelUrl = settingsBody.row?.modelUrl || '';
    expect(modelUrl).toMatch(/^https?:\/\//);

    const manifest = await request.get(`${modelUrl.replace(/\/$/, '')}/tiny_face_detector_model-weights_manifest.json`);
    if (!manifest.ok()) {
      record(
        'Face attendance',
        'Critical',
        'Face model manifest not reachable',
        `GET ${modelUrl}/tiny_face_detector_model-weights_manifest.json → ${manifest.status()}`,
      );
    }
    expect(manifest.ok()).toBeTruthy();
  });

  test('client portal core pages load without critical errors', async ({ page, request }) => {
    const session = await loginSuperAdmin(request);
    const tenant = await bootstrapTenant(request, session.token, 'ui');
    await seedEmployee(request, tenant.tenantHeaders);

    await seedAuth(page, tenant.clientSession);
    const diag = attachDiagnostics(page);

    const pages = [
      '/client/index.html',
      '/client/client-employee-master.html',
      '/client/client-attendance.html',
      '/client/client-payroll-calc.html',
      '/client/client-compliance-calendar.html',
      '/client/face-attendance-settings.php',
      '/client/face-attendance-registration.php',
      '/client/face-attendance-sheet.php',
      '/client/scan-attendance.php',
      '/client/my-face-attendance.php',
      '/client/monthly-attendance-report.php',
    ];

    for (const p of pages) {
      await gotoAndSettle(page, p);
      expect(page.url()).toContain(p.split('/').pop());
      const snap = diag.snapshot();
      const critical = snap.consoleErrors.filter(
        (e) =>
          !e.includes('favicon') &&
          !e.includes('getUserMedia') &&
          !e.includes('NotAllowedError') &&
          !e.includes('Permission'),
      );
      if (critical.length) {
        record('Client UI', 'Medium', `Console errors on ${p}`, critical.join('\n'));
      }
      const apiFails = snap.failedRequests.filter((r) => r.includes('/api/'));
      if (apiFails.length) {
        record('Client UI', 'High', `API failures on ${p}`, apiFails.join('\n'));
        expect(apiFails, `API failures on ${p}`).toEqual([]);
      }
    }
  });

  test('face attendance settings form saves via UI', async ({ page, request }) => {
    const session = await loginSuperAdmin(request);
    const tenant = await bootstrapTenant(request, session.token, 'face');
    await seedAuth(page, { token: session.token, user: session.user });

    await gotoAndSettle(page, '/super-admin/face-attendance-settings.php');
    await expect(page.locator('#settingsForm')).toBeVisible();
    await expect(page.locator('#modelUrl')).toHaveValue(/https:\/\//);

    await page.locator('#graceTime').fill('15');
    const saveRes = page.waitForResponse(
      (r) => r.url().includes('/api/face-attendance/settings') && r.request().method() === 'PUT',
    );
    await page.locator('#settingsForm button[type="submit"]').click();
    const res = await saveRes;
    expect(res.ok()).toBeTruthy();
    await expect(page.locator('#pageStatus')).toContainText(/saved successfully/i);
  });

  test('face scan page loads face-api and exposes scan controls', async ({ page, request }) => {
    const session = await loginSuperAdmin(request);
    const tenant = await bootstrapTenant(request, session.token, 'scan');
    await seedEmployee(request, tenant.tenantHeaders);
    await seedAuth(page, tenant.clientSession);

    const diag = attachDiagnostics(page);
    await gotoAndSettle(page, '/client/scan-attendance.php');

    await expect(page.locator('body')).toHaveAttribute('data-face-page', 'scan');
    await expect(page.locator('#btnScanNow')).toBeVisible();
    await expect(page.locator('#cameraVideo')).toBeVisible();

    const faceApiLoaded = await page.evaluate(() => typeof window.faceapi !== 'undefined');
    if (!faceApiLoaded) {
      record('Face attendance', 'Critical', 'face-api.js not loaded on scan page', 'window.faceapi is undefined');
    }
    expect(faceApiLoaded).toBeTruthy();

    const modelManifestOk = await page.evaluate(async () => {
      try {
        const res = await fetch('/api/face-attendance/settings');
        const data = await res.json();
        const base = String(data.row?.modelUrl || '').replace(/\/$/, '');
        const m = await fetch(`${base}/tiny_face_detector_model-weights_manifest.json`);
        return m.ok;
      } catch {
        return false;
      }
    });
    if (!modelManifestOk) {
      record(
        'Face attendance',
        'Critical',
        'Face models unreachable from browser',
        'Model manifest fetch failed — registration/scan cannot work.',
      );
    }
    expect(modelManifestOk).toBeTruthy();

    const snap = diag.snapshot();
    const blocking = snap.failedRequests.filter(
      (r) => r.includes('/assets/') || (r.includes('face-api') && !r.includes('getUserMedia')),
    );
    if (blocking.length) {
      record('Face attendance', 'High', 'Asset failures on scan page', blocking.join('\n'));
    }
  });

  test('employee master form can create employee in UI', async ({ page, request }) => {
    const session = await loginSuperAdmin(request);
    const tenant = await bootstrapTenant(request, session.token, 'emp');
    await seedAuth(page, tenant.clientSession);

    await gotoAndSettle(page, '/client/client-employee-master.html');
    await page.locator('button[data-bs-target="#addEmployeeModal"]').click();
    await expect(page.locator('#empForm')).toBeVisible();

    const empId = `E${String(Date.now()).slice(-6)}`;
    await page.locator('#empId').fill(empId);
    await page.locator('#empName').fill('UI Created');
    await page.locator('#doj').fill('2024-01-01');
    await page.locator('#dept').fill('Ops');
    await page.locator('#designation').fill('Staff');
    await page.locator('#baseCtc').fill('30000');
    await page.waitForTimeout(500);
    const typeOptions = page.locator('#employmentType option');
    if ((await typeOptions.count()) > 1) {
      await page.locator('#employmentType').selectOption({ index: 1 });
    } else {
      record(
        'Forms',
        'High',
        'Employee types not seeded',
        'Employment Type dropdown is empty — Add Employee form cannot validate.',
      );
    }

    const createRes = page.waitForResponse(
      (r) => r.url().includes('/api/employees') && ['POST', 'PUT'].includes(r.request().method()),
      { timeout: 15000 },
    );
    await page.locator('button[form="empForm"][type="submit"]').click();
    const res = await createRes;
    if (!res.ok()) {
      record('Forms', 'High', 'Employee master UI save failed', `${res.status()} ${await res.text()}`);
    }
    expect(res.ok()).toBeTruthy();
  });

  test('super-admin enquiry form accepts manual entry', async ({ page, request }) => {
    const session = await loginSuperAdmin(request);
    await seedAuth(page, session);

    await gotoAndSettle(page, '/super-admin/super-admin-enquiries.html');
    await page.locator('#createEnquiryBtn').click();
    await expect(page.locator('#enquiryModal')).toBeVisible();

    const runId = Date.now();
    await page.locator('#detailFullName').fill(`Manual ${runId}`);
    await page.locator('#detailCompanyName').fill(`Manual Co ${runId}`);
    await page.locator('#detailEmail').fill(`manual${runId}@example.com`);
    await page.locator('#detailPhone').fill('9123456789');

    const saveRes = page.waitForResponse(
      (r) => r.url().includes('/api/admin-enquiries') && r.request().method() === 'POST',
    );
    await page.locator('#saveEnquiryBtn').click();
    const res = await saveRes;
    expect(res.ok()).toBeTruthy();
  });
});
