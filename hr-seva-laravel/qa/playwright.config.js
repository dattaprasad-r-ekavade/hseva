const { defineConfig } = require('@playwright/test');
const path = require('path');

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
  projects: [
    {
      name: 'smoke',
      testIgnore: /onboarding-tour/,
    },
    {
      name: 'onboarding-tour',
      testMatch: /onboarding-tour/,
      timeout: 180000,
      use: {
        headless: true,
        video: 'on',
        viewport: { width: 1440, height: 900 },
        launchOptions: {
          slowMo: 80,
        },
      },
      outputDir: path.join(__dirname, 'artifacts', 'onboarding-tour'),
    },
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
});
