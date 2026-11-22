const { chromium } = require('C:/Users/ABC/AppData/Roaming/npm/node_modules/@playwright/cli/node_modules/playwright-core');
const fs = require('fs');
const path = require('path');

const BASE_URL = process.env.BASE_URL || 'http://localhost:5173';
const SCREENSHOT_DIR = path.join(__dirname, 'screenshots');
if (!fs.existsSync(SCREENSHOT_DIR)) {
  fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

async function testSweetAlert() {
  console.log('🚀 Starting SweetAlert2 Visual Verification...');
  const browser = await chromium.launch({
    channel: 'chrome',
    headless: true,
  });

  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
  });

  const page = await context.newPage();

  try {
    console.log('Logging into Super Admin...');
    await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });
    await page.fill('input[type="email"]', 'superadmin@qfunnel.local');
    await page.fill('input[type="password"]', 'Password123!');
    await page.click('button[type="submit"]');

    await page.waitForURL(url => url.pathname.includes('/admin/platform') || url.pathname.includes('/admin/dashboard'), { timeout: 10000 });
    await page.goto(`${BASE_URL}/admin/platform/templates`, { waitUntil: 'networkidle' });
    console.log('Navigated to /admin/platform/templates');

    // Click Manage Categories
    await page.click('button:has-text("Manage Categories")');
    await page.waitForSelector('text=Workflow Template Categories', { timeout: 10000 });
    console.log('Opened Manage Categories modal');

    // Add a temporary category
    const testCatName = `Test Swal ${Date.now()}`;
    await page.fill('input[placeholder*="Dental & Healthcare"]', testCatName);
    await page.click('button:has-text("Add")');
    await page.waitForTimeout(1000);

    // Find the first empty category delete button
    console.log('Looking for delete button (button[title="Delete empty category"])...');
    const deleteBtn = page.locator('button[title="Delete empty category"]').first();
    await deleteBtn.waitFor({ state: 'visible', timeout: 5000 });
    await deleteBtn.click();

    // Verify SweetAlert popup is visible!
    await page.waitForSelector('.swal2-popup', { state: 'visible', timeout: 5000 });
    console.log('🎉 SweetAlert2 popup successfully appeared!');

    const details = await page.evaluate(() => {
      const swalContainer = document.querySelector('.swal2-container');
      const swalPopup = document.querySelector('.swal2-popup');
      const modal = document.querySelector('.fixed.inset-0.z-50');
      return {
        containerParent: swalContainer ? swalContainer.parentElement.tagName : null,
        containerZIndex: swalContainer ? window.getComputedStyle(swalContainer).zIndex : null,
        containerPosition: swalContainer ? window.getComputedStyle(swalContainer).position : null,
        containerClasses: swalContainer ? swalContainer.className : null,
        popupClasses: swalPopup ? swalPopup.className : null,
        popupBg: swalPopup ? window.getComputedStyle(swalPopup).backgroundColor : null,
        popupZIndex: swalPopup ? window.getComputedStyle(swalPopup).zIndex : null,
        modalZIndex: modal ? window.getComputedStyle(modal).zIndex : null,
      };
    });
    console.log('SWAL DETAILS:', JSON.stringify(details, null, 2));

    // Capture screenshot of the SweetAlert modal!
    const screenshotPath = path.join(SCREENSHOT_DIR, 'sweetalert_confirm_dialog.png');
    await page.screenshot({ path: screenshotPath });
    console.log(`📸 Saved screenshot to: ${screenshotPath}`);

    // Confirm the deletion inside SweetAlert
    const confirmBtn = page.locator('.swal2-confirm');
    console.log('Clicking confirm button in SweetAlert...');
    await confirmBtn.click();
    await page.waitForTimeout(1000);

    console.log('✅ SweetAlert2 successfully handled user confirmation and deleted the test category!');
  } catch (err) {
    console.error('❌ Error during SweetAlert test:', err);
    process.exit(1);
  } finally {
    await browser.close();
  }
}

testSweetAlert();
