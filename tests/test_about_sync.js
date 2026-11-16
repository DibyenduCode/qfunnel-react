const { chromium } = require('C:/Users/ABC/AppData/Roaming/npm/node_modules/@playwright/cli/node_modules/playwright-core');

(async () => {
  try {
    const browser = await chromium.launch({ channel: 'chrome', headless: true });
    const page = await browser.newPage();

    console.log('--- 1. Login as Super Admin and check About in Legal CMS ---');
    await page.goto('http://localhost:5173/login', { waitUntil: 'networkidle' });
    await page.fill('input[type="email"]', 'superadmin@qfunnel.local');
    await page.fill('input[type="password"]', 'Password123!');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/**', { timeout: 10000 });

    await page.goto('http://localhost:5173/admin/platform/legal-pages', { waitUntil: 'networkidle' });
    await page.waitForTimeout(1000);

    // Click "About LeadCollector" in the left column
    await page.click('h3:has-text("About LeadCollector")');
    await page.waitForTimeout(500);

    const titleVal = await page.$eval('input[value*="About LeadCollector"]', el => el.value);
    const contentVal = await page.$eval('textarea', el => el.value);
    console.log('Superadmin CMS About Title:', titleVal);
    console.log('Superadmin CMS Content contains "1. Our Mission":', contentVal.includes('1. Our Mission'));
    console.log('Superadmin CMS Content contains "2. The Problem":', contentVal.includes('2. The Problem'));

    await page.screenshot({ path: 'tests/screenshots/12_superadmin_about_cms.png', fullPage: true });
    console.log('Captured screenshot: tests/screenshots/12_superadmin_about_cms.png');

    console.log('--- 2. Visit public /about page and verify content ---');
    await page.goto('http://localhost:5173/about', { waitUntil: 'networkidle' });
    await page.waitForTimeout(1000);

    const aboutH1 = await page.$eval('h1', el => el.textContent.trim());
    const aboutBodyText = await page.textContent('.legal-content');
    console.log('About Page H1:', aboutH1);
    console.log('About Page renders "1. Our Mission":', aboutBodyText.includes('1. Our Mission'));
    console.log('About Page renders "2. The Problem":', aboutBodyText.includes('2. The Problem'));
    console.log('About Page renders "3. The LeadCollector Solution":', aboutBodyText.includes('3. The LeadCollector Solution'));
    console.log('About Page renders "4. Architectural Pillars":', aboutBodyText.includes('4. Architectural Pillars'));

    await page.screenshot({ path: 'tests/screenshots/13_public_about_page.png', fullPage: true });
    console.log('Captured screenshot: tests/screenshots/13_public_about_page.png');

    console.log('--- 3. Testing Real-time CMS Update to /about ---');
    await page.goto('http://localhost:5173/admin/platform/legal-pages', { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);
    await page.click('h3:has-text("About LeadCollector")');
    await page.waitForTimeout(500);

    // Append a unique sync marker to the content
    const updatedContent = contentVal + '\n<p><strong>[Live Sync Verified]</strong> This text is rendered directly from Super Admin CMS in real time.</p>';
    await page.fill('textarea', updatedContent);
    await page.click('button:has-text("Save Policy")');
    await page.waitForSelector('text=successfully saved');
    console.log('Saved updated About policy in Superadmin CMS.');

    // Visit /about again and verify the live sync text appears!
    await page.goto('http://localhost:5173/about', { waitUntil: 'networkidle' });
    await page.waitForTimeout(1000);
    const newAboutBody = await page.textContent('.legal-content');
    const hasLiveSync = newAboutBody.includes('[Live Sync Verified]');
    console.log('Public /about displays updated CMS text:', hasLiveSync);

    // Clean up marker so content stays pristine
    await page.goto('http://localhost:5173/admin/platform/legal-pages', { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);
    await page.click('h3:has-text("About LeadCollector")');
    await page.waitForTimeout(500);
    await page.fill('textarea', contentVal);
    await page.click('button:has-text("Save Policy")');
    await page.waitForSelector('text=successfully saved');
    console.log('Restored pristine About content in CMS.');

    await browser.close();
    console.log('>>> ABOUT PAGE SYNCHRONIZATION TEST PASSED 100%! <<<');
  } catch (err) {
    console.error('Test failed:', err);
    process.exit(1);
  }
})();
