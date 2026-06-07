const { defineConfig } = require('@playwright/test');
const path = require('path');

const ROOT = path.resolve(__dirname, '..', '..');

// E2E runs against the embedded PHP server on the ISOLATED test database.
// reset_db() in the spec recreates schema+seed before each test.
module.exports = defineConfig({
  testDir: __dirname,
  timeout: 30000,
  expect: { timeout: 7000 },
  fullyParallel: false,
  workers: 1,
  use: {
    baseURL: 'http://localhost:8000',
    channel: 'chrome',     // system Google Chrome — no bundled browser download
    headless: true,
  },
  webServer: {
    command: 'php -S localhost:8000 -t public public/router.php',
    cwd: ROOT,
    url: 'http://localhost:8000/login',
    reuseExistingServer: false,
    env: { DB_NAME: process.env.DB_TEST_NAME || 'employee_manager_test' },
  },
});
