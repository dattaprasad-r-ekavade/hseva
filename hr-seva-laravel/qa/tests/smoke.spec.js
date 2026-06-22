const { test, expect } = require('@playwright/test');

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
    const res = await request.post('/api/auth/login', {
      data: { username: 'admin@hrseva.com', password: '123456' },
    });
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    expect(body.token).toBeTruthy();
    expect(body.user.role).toBe('super_admin');
  });
});
