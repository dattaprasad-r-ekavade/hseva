const { defineConfig } = require('@playwright/test');
const path = require('path');

const baseURL = process.env.BASE_URL || 'http://127.0.0.1:8012';

module.exports = defineConfig({
  testDir: './tests',
  timeout: 120000,
  retries: 0,
  use: {
    baseURL,
    headless: true,
    trace: 'on-first-retry',
    viewport: { width: 1440, height: 900 },
  },
  projects: [
    { name: 'smoke', testMatch: /smoke\.spec\.js/ },
    { name: 'e2e', testMatch: /e2e-comprehensive\.spec\.js/, timeout: 300000 },
  ],
  webServer: process.env.SKIP_WEB_SERVER
    ? undefined
    : {
        command: 'bash scripts/serve-for-qa.sh',
        cwd: __dirname,
        url: baseURL,
        reuseExistingServer: !process.env.CI,
        timeout: 180000,
      },
  outputDir: path.join(__dirname, 'artifacts', 'test-results'),
});
