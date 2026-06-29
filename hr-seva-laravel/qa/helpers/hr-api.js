const AUTH_KEY = 'hr_auth_session_v1';

async function loginSuperAdmin(request) {
  const res = await request.post('/api/auth/login', {
    data: { username: 'admin@hrseva.com', password: '123456' },
  });
  if (!res.ok()) {
    throw new Error(`Super-admin login failed: ${res.status()}`);
  }
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

async function ensureSubscriptionPlan(request, token) {
  const listRes = await request.get('/api/subscription-plans', {
    headers: { Authorization: `Bearer ${token}` },
  });
  const rows = (await listRes.json()).rows || [];
  if (rows.length > 0) {
    return rows[0];
  }

  const createRes = await request.post('/api/subscription-plans', {
    headers: { Authorization: `Bearer ${token}` },
    data: {
      planName: 'E2E Plan',
      durationMonths: 12,
      amount: 0,
      status: 'Active',
      features: 'Full access',
      accessTypeCode: 'full_access',
    },
  });
  if (!createRes.ok()) {
    throw new Error(`Create subscription plan failed: ${createRes.status()}`);
  }
  return (await createRes.json()).row;
}

async function activateSubscription(request, token, clientId, planName = 'E2E Plan') {
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
  if (!res.ok()) {
    throw new Error(`Activate subscription failed: ${res.status()}`);
  }
  return (await res.json()).row;
}

async function bootstrapTenant(request, token, prefix = 'e2e') {
  const plan = await ensureSubscriptionPlan(request, token);
  const uid = `${prefix}_${Date.now()}`;
  const clientRes = await request.post('/api/clients', {
    headers: { Authorization: `Bearer ${token}` },
    data: {
      companyName: `${prefix} Co`,
      companyAddress: '1 Test Street',
      companyRegNo: 'REG1',
      companyPan: 'ABCDE1234F',
      companyTan: 'MUMB12345A',
      companyGstin: '27ABCDE1234F1Z5',
      companyContactNo: '9876543210',
      userId: uid,
      userPassword: 'E2ePass123!',
      subscriptionPlanId: plan.id,
    },
  });
  if (clientRes.status() !== 201) {
    throw new Error(`Create client failed: ${clientRes.status()} ${await clientRes.text()}`);
  }
  const client = (await clientRes.json()).row;
  await activateSubscription(request, token, client.id, plan.planName || 'E2E Plan');

  const loginRes = await request.post('/api/auth/login', {
    data: { username: uid, password: 'E2ePass123!' },
  });
  if (!loginRes.ok()) {
    throw new Error(`Client login failed: ${loginRes.status()} ${await loginRes.text()}`);
  }
  const clientSession = await loginRes.json();

  return {
    clientId: String(client.id),
    userId: uid,
    password: 'E2ePass123!',
    clientSession,
    tenantHeaders: {
      Authorization: `Bearer ${token}`,
      'X-Client-Id': String(client.id),
    },
    clientHeaders: {
      Authorization: `Bearer ${clientSession.token}`,
      'X-Client-Id': String(client.id),
    },
  };
}

async function seedEmployee(request, headers, id = 'E2E001') {
  const res = await request.post('/api/employees', {
    headers: { ...headers, 'Content-Type': 'application/json' },
    data: {
      id,
      name: 'E2E Employee',
      status: 'Active',
      dept: 'HR',
      desig: 'Executive',
      type: 'Full-time',
      mobile: '9999999999',
      email: 'e2e@example.com',
      doj: '2024-01-01',
      pf: 'Yes',
      uan: '',
      esi: 'Yes',
      esiNo: '',
      baseCtc: 30000,
    },
  });
  return res;
}

function attachDiagnostics(page) {
  const consoleErrors = [];
  const failedRequests = [];

  page.on('console', (msg) => {
    if (msg.type() === 'error') {
      consoleErrors.push(msg.text());
    }
  });
  page.on('pageerror', (err) => {
    consoleErrors.push(`PAGEERROR: ${err.message}`);
  });
  page.on('response', (res) => {
    const url = res.url();
    if (res.status() >= 400 && (url.includes('/api/') || url.includes('/assets/'))) {
      failedRequests.push(`${res.status()} ${res.request().method()} ${url}`);
    }
  });

  return {
    consoleErrors,
    failedRequests,
    snapshot() {
      return {
        consoleErrors: [...consoleErrors],
        failedRequests: [...failedRequests],
      };
    },
  };
}

async function gotoAndSettle(page, path) {
  await page.goto(path, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
}

module.exports = {
  AUTH_KEY,
  loginSuperAdmin,
  seedAuth,
  bootstrapTenant,
  seedEmployee,
  attachDiagnostics,
  gotoAndSettle,
};
