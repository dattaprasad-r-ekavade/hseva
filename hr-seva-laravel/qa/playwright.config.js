const { defineConfig } = require('@playwright/test');

const baseURL = process.env.BASE_URL || 'http://127.0.0.1:8012';

module.exports = defineConfig({
  testDir: './tests',
  timeout: 30000,
  retries: 0,
  use: {
    baseURL,
    headless: true,
    trace: 'on-first-retry',
  },
  webServer: process.env.SKIP_WEB_SERVER
    ? undefined
    : {
        command: 'php artisan serve --host=127.0.0.1 --port=8012',
        cwd: '..',
        url: baseURL,
        reuseExistingServer: true,
        timeout: 120000,
      },
});
