const { chromium } = require('C:/Users/ABC/AppData/Roaming/npm/node_modules/@playwright/cli/node_modules/playwright-core');

(async () => {
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  await page.goto('http://localhost:5173/login');
  await page.fill('input[type="email"]', 'superadmin@qfunnel.local');
  await page.fill('input[type="password"]', 'Password123!');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/admin/**', { timeout: 10000 });
  await page.goto('http://localhost:5173/admin/platform/templates');
  await page.waitForSelector('text=Global Workflow Templates');
  await page.waitForTimeout(500);

  // Screenshot 1: Normal loaded state
  await page.screenshot({ path: 'C:/Users/ABC/.gemini/antigravity/brain/f9e046c7-e0f4-40cc-95ac-7614588020e0/templates_redesigned_normal.png' });
  console.log('Saved templates_redesigned_normal.png');

  // Select an empty category from the Other Categories dropdown
  const otherCatSelect = page.locator('select').nth(2); // 3rd select on page: mode, status, category
  if (await otherCatSelect.count() > 0) {
    const val = await otherCatSelect.locator('option').nth(1).getAttribute('value');
    if (val) {
      await otherCatSelect.selectOption(val);
      await page.waitForTimeout(400);
      await page.screenshot({ path: 'C:/Users/ABC/.gemini/antigravity/brain/f9e046c7-e0f4-40cc-95ac-7614588020e0/templates_redesigned_empty_category.png' });
      console.log('Saved templates_redesigned_empty_category.png');
    }
  }

  // Also test Search with no matches
  await page.fill('input[placeholder*="Search templates"]', 'xyz_nonexistent_term');
  await page.waitForTimeout(400);
  await page.screenshot({ path: 'C:/Users/ABC/.gemini/antigravity/brain/f9e046c7-e0f4-40cc-95ac-7614588020e0/templates_redesigned_search_empty.png' });
  console.log('Saved templates_redesigned_search_empty.png');

  await browser.close();
})();
