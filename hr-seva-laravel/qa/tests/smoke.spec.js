const { test, expect } = require('@playwright/test');

const AUTH_KEY = 'hr_auth_session_v1';

async function loginSuperAdmin(request) {
  const res = await request.post('/api/auth/login', {
    data: { username: 'admin@hrseva.com', password: '123456' },
  });
  expect(res.ok()).toBeTruthy();
  return res.json();
}

async function seedAuth(page, session) {
  await page.addInitScript(
    ({ key, payload }) => {
      sessionStorage.setItem(key, JSON.stringify(payload));
    },
    {
      key: AUTH_KEY,
      payload: {
        token: session.token,
        user: session.user,
        savedAt: Date.now(),
      },
    },
  );
}

test.describe('HR Seva Laravel smoke', () => {
  test('landing page loads', async ({ page }) => {
    const res = await page.goto('/');
    expect(res?.status()).toBeLessThan(400);
    await expect(page).toHaveTitle(/HR Seva/i);
  });

  test('client login page loads', async ({ page }) => {
    const res = await page.goto('/client/login');
    expect(res?.status()).toBeLessThan(400);
    await expect(page.locator('body')).toContainText(/login|sign in|client/i);
  });

  test('super-admin login page loads', async ({ page }) => {
    const res = await page.goto('/super-admin/login');
    expect(res?.status()).toBeLessThan(400);
    await expect(page.locator('body')).toContainText(/login|sign in|super/i);
  });

  test('clean URL redirects to legacy portal page', async ({ request }) => {
    const res = await request.get('/client/employees', { maxRedirects: 0 });
    expect([301, 302, 307, 308]).toContain(res.status());
    expect(res.headers()['location']).toMatch(/client-employee-master\.html/);
  });

  test('api health endpoint', async ({ request }) => {
    const res = await request.get('/api/health');
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    expect(body.status).toBe('ok');
  });

  test('api auth login contract', async ({ request }) => {
    const body = await loginSuperAdmin(request);
    expect(body.token).toBeTruthy();
    expect(body.user.role).toBe('super_admin');
  });
});

test.describe('Authenticated portal flows', () => {
  test('super-admin clients page renders with session', async ({ page, request }) => {
    const session = await loginSuperAdmin(request);
    await seedAuth(page, session);

    const res = await page.goto('/super-admin/super-admin-module.html');
    expect(res?.status()).toBeLessThan(400);
    await expect(page.locator('body')).toContainText(/client/i);
  });

  test('super-admin dashboard API with bearer token', async ({ request }) => {
    const session = await loginSuperAdmin(request);
    const res = await request.get('/api/dashboard/summary?month=6&year=2026', {
      headers: { Authorization: `Bearer ${session.token}` },
    });
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    expect(body).toHaveProperty('period');
    expect(body).toHaveProperty('employees');
  });

  test('tenant compliance API after client bootstrap', async ({ request }) => {
    const session = await loginSuperAdmin(request);
    const uid = `pwadmin_${Date.now()}`;

    const clientRes = await request.post('/api/clients', {
      headers: { Authorization: `Bearer ${session.token}` },
      data: {
        companyName: 'Playwright Co',
        companyAddress: '1 Test St',
        companyRegNo: 'REG',
        companyPan: 'PAN',
        companyTan: 'TAN',
        companyGstin: 'GST',
        companyContactNo: '9999999999',
        userId: uid,
        userPassword: 'secret123',
      },
    });
    expect(clientRes.status()).toBe(201);
    const clientId = String((await clientRes.json()).row.id);

    const tasksRes = await request.get('/api/compliance/tasks?month=6&year=2026', {
      headers: {
        Authorization: `Bearer ${session.token}`,
        'X-Client-Id': clientId,
      },
    });
    expect(tasksRes.ok()).toBeTruthy();
    const tasks = await tasksRes.json();
    expect(Array.isArray(tasks.rows)).toBeTruthy();
    expect(tasks.rows.length).toBeGreaterThan(0);
  });

  test('face attendance settings API', async ({ request }) => {
    const session = await loginSuperAdmin(request);
    const uid = `faceadmin_${Date.now()}`;

    const clientRes = await request.post('/api/clients', {
      headers: { Authorization: `Bearer ${session.token}` },
      data: {
        companyName: 'Face Att Co',
        companyAddress: '2 Test St',
        companyRegNo: 'REG2',
        companyPan: 'PAN2',
        companyTan: 'TAN2',
        companyGstin: 'GST2',
        companyContactNo: '8888888888',
        userId: uid,
        userPassword: 'secret123',
      },
    });
    expect(clientRes.status()).toBe(201);
    const clientId = String((await clientRes.json()).row.id);

    const settingsRes = await request.get('/api/face-attendance/settings', {
      headers: {
        Authorization: `Bearer ${session.token}`,
        'X-Client-Id': clientId,
      },
    });
    expect(settingsRes.ok()).toBeTruthy();
    const settings = await settingsRes.json();
    expect(settings.row.inAllowedFrom).toBe('08:00');
  });
});

test.describe('Catch-all module APIs', () => {
  async function bootstrapTenant(request, session, prefix) {
    const uid = `${prefix}_${Date.now()}`;
    const clientRes = await request.post('/api/clients', {
      headers: { Authorization: `Bearer ${session.token}` },
      data: {
        companyName: `${prefix} Co`,
        companyAddress: '1 Test St',
        companyRegNo: 'REG',
        companyPan: 'PAN',
        companyTan: 'TAN',
        companyGstin: 'GST',
        companyContactNo: '9999999999',
        userId: uid,
        userPassword: 'secret123',
      },
    });
    expect(clientRes.status()).toBe(201);
    return String((await clientRes.json()).row.id);
  }

  test('overtime, advances, loans, and PF return endpoints', async ({ request }) => {
    const session = await loginSuperAdmin(request);
    const clientId = await bootstrapTenant(request, session, 'catchall');

    const headers = {
      Authorization: `Bearer ${session.token}`,
      'X-Client-Id': clientId,
    };

    const overtimeRes = await request.get('/api/overtime?month=6&year=2026', { headers });
    expect(overtimeRes.ok()).toBeTruthy();
    const overtime = await overtimeRes.json();
    expect(Array.isArray(overtime.rows)).toBeTruthy();

    const advancesRes = await request.get('/api/advances', { headers });
    expect(advancesRes.ok()).toBeTruthy();
    const advances = await advancesRes.json();
    expect(Array.isArray(advances.rows)).toBeTruthy();

    const shiftsRes = await request.get('/api/shifts?active=1', { headers });
    expect(shiftsRes.ok()).toBeTruthy();
    const shifts = await shiftsRes.json();
    expect(Array.isArray(shifts.rows)).toBeTruthy();

    const loansRes = await request.get('/api/loans', { headers });
    expect(loansRes.ok()).toBeTruthy();
    const loans = await loansRes.json();
    expect(Array.isArray(loans.rows)).toBeTruthy();

    const pfSheetsRes = await request.get('/api/pf-return/sheets', { headers });
    expect(pfSheetsRes.ok()).toBeTruthy();
    const pfSheets = await pfSheetsRes.json();
    expect(Array.isArray(pfSheets.rows)).toBeTruthy();

    const legacyRes = await request.get('/api/this-route-should-not-exist', { headers });
    expect(legacyRes.status()).toBe(404);
  });
});
