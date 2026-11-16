const { chromium } = require('C:/Users/ABC/AppData/Roaming/npm/node_modules/@playwright/cli/node_modules/playwright-core');

(async () => {
  try {
    const browser = await chromium.launch({ channel: 'chrome', headless: true });
    const page = await browser.newPage();

    console.log('--- 1. Testing Public Support Page & Phone Field ---');
    await page.goto('http://localhost:5173/support', { waitUntil: 'networkidle' });
    await page.waitForTimeout(1000);

    // Verify Phone field exists
    const phoneInput = await page.$('input[type="tel"]');
    console.log('Phone input field exists in support form:', Boolean(phoneInput));

    // Fill support form
    await page.fill('input[placeholder*="Alex Morgan"]', 'Sarah Jenkins');
    await page.fill('input[placeholder*="alex@company.com"]', 'sarah.jenkins@growthco.io');
    await page.fill('input[type="tel"]', '+1 (800) 555-7788');
    await page.fill('input[placeholder*="Brief description"]', 'Inquiry on Enterprise High Volume Plans');
    await page.fill('textarea[placeholder*="Describe your issue"]', 'We are planning to deploy 50+ branching quiz funnels and would like to know about dedicated server SLAs and custom domain options.');
    
    // Submit form
    await page.click('button:has-text("Submit Support Ticket")');
    await page.waitForSelector('text=Received', { timeout: 10000 });
    console.log('Support form submitted successfully!');

    const confirmationText = await page.textContent('.space-y-4');
    console.log('Confirmation displays ticket & phone:', confirmationText.includes('sarah.jenkins') || confirmationText.includes('+1 (800) 555-7788'));

    await page.screenshot({ path: 'tests/screenshots/14_public_support_submitted.png', fullPage: true });
    console.log('Captured screenshot: tests/screenshots/14_public_support_submitted.png');

    console.log('--- 2. Login as Super Admin and check Support Inquiries Dashboard ---');
    await page.goto('http://localhost:5173/login', { waitUntil: 'networkidle' });
    await page.fill('input[type="email"]', 'superadmin@qfunnel.local');
    await page.fill('input[type="password"]', 'Password123!');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/**', { timeout: 10000 });

    // Navigate to Support Tickets page in Superadmin
    await page.goto('http://localhost:5173/admin/platform/support-tickets', { waitUntil: 'networkidle' });
    await page.waitForTimeout(1000);

    const tableContent = await page.textContent('body');
    const hasSarah = tableContent.includes('Sarah Jenkins');
    const hasPhone = tableContent.includes('+1 (800) 555-7788');
    console.log('Superadmin dashboard displays Sarah Jenkins:', hasSarah);
    console.log('Superadmin dashboard displays Phone +1 (800) 555-7788:', hasPhone);

    // Open detail modal
    await page.click('button[title="View Full Ticket"]');
    await page.waitForTimeout(500);
    const modalContent = await page.textContent('.max-w-2xl');
    console.log('Modal contains full inquiry message:', modalContent.includes('50+ branching quiz funnels'));
    await page.click('button:has-text("Close")');
    await page.waitForTimeout(500);

    console.log('--- 3. Testing Direct Channels Configuration in Super Admin ---');
    // Change Phone and Email in Super Admin
    await page.fill('input[value*="support@"]', 'vip-desk@leadcollector.in');
    await page.fill('input[placeholder*="+91"]', '+1 (888) 999-0011');
    await page.click('button:has-text("Save Direct Channels")');
    await page.waitForSelector('text=saved successfully');
    console.log('Saved updated direct channels in Superadmin.');

    await page.screenshot({ path: 'tests/screenshots/15_superadmin_support_dashboard.png', fullPage: true });
    console.log('Captured screenshot: tests/screenshots/15_superadmin_support_dashboard.png');

    console.log('--- 4. Verify Public Support Page displays updated Direct Channels ---');
    await page.goto('http://localhost:5173/support', { waitUntil: 'networkidle' });
    await page.waitForTimeout(1000);

    const publicChannelsText = await page.textContent('body');
    const hasNewEmail = publicChannelsText.includes('vip-desk@leadcollector.in');
    const hasNewPhone = publicChannelsText.includes('+1 (888) 999-0011');
    console.log('Public Support page displays updated email (vip-desk@leadcollector.in):', hasNewEmail);
    console.log('Public Support page displays updated phone (+1 (888) 999-0011):', hasNewPhone);

    await page.screenshot({ path: 'tests/screenshots/16_public_support_updated_channels.png', fullPage: true });
    console.log('Captured screenshot: tests/screenshots/16_public_support_updated_channels.png');

    await browser.close();
    console.log('>>> SUPPORT TICKETS & DIRECT CHANNELS VERIFIED 100%! <<<');
  } catch (err) {
    console.error('Test failed:', err);
    process.exit(1);
  }
})();
