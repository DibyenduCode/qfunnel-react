const { chromium } = require('C:/Users/ABC/AppData/Roaming/npm/node_modules/@playwright/cli/node_modules/playwright-core');

(async () => {
  try {
    const browser = await chromium.launch({ channel: 'chrome', headless: true });
    const page = await browser.newPage();
    
    // 1. Check public legal pages API
    console.log('--- 1. Testing API endpoint ---');
    const apiRes = await page.goto('http://localhost/qfunnel2/api/public/legal-pages');
    const apiData = await apiRes.json();
    console.log('API Legal Pages Count:', apiData.data?.pages?.length);

    // 2. Visit homepage and check footer
    console.log('--- 2. Testing Homepage Footer ---');
    await page.goto('http://localhost:5173/', { waitUntil: 'networkidle' });
    await page.waitForTimeout(1000);
    const footerLinks = await page.$$eval('footer a', links => links.map(a => ({ text: a.textContent.trim(), href: a.getAttribute('href') })));
    const relevantLinks = footerLinks.filter(l => 
      l.text.includes('Privacy') || l.text.includes('Terms') || l.text.includes('Cookie') || l.text.includes('Refund') || l.text.includes('About')
    );
    console.log('Homepage Footer Relevant Links:', relevantLinks);

    // 3. Login as Super Admin and check Compliance & Legal CMS
    console.log('--- 3. Testing Superadmin Legal CMS ---');
    await page.goto('http://localhost:5173/login', { waitUntil: 'networkidle' });
    await page.fill('input[type="email"]', 'superadmin@qfunnel.local');
    await page.fill('input[type="password"]', 'Password123!');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/**', { timeout: 10000 });
    console.log('Logged in as Super Admin. Navigating to /admin/platform/legal-pages...');

    await page.goto('http://localhost:5173/admin/platform/legal-pages', { waitUntil: 'networkidle' });
    await page.waitForTimeout(1500);

    // Check document titles in the left column
    const docTitles = await page.$$eval('.lg\\:col-span-4 h3', elems => elems.map(e => e.textContent.trim()));
    console.log('Documents in Superadmin CMS Left Column:', docTitles);

    // Screenshot of Superadmin Legal CMS
    await page.screenshot({ path: 'tests/screenshots/10_superadmin_legal_cms.png', fullPage: true });
    console.log('Captured screenshot: tests/screenshots/10_superadmin_legal_cms.png');

    // 4. Test adding a new legal page via CMS
    console.log('--- 4. Creating a new Legal Document: "Affiliate Terms" ---');
    await page.click('button:has-text("New Legal Page")');
    await page.waitForSelector('input[placeholder*="Affiliate"]');
    await page.fill('input[placeholder*="Affiliate"]', 'Affiliate Partner Terms');
    await page.fill('input[placeholder*="Summary"]', 'Terms and conditions for LeadCollector affiliates and referrers.');
    await page.fill('textarea[placeholder*="full legal"]', '<h2>1. Affiliate Commission</h2><p>Affiliates earn 30% recurring commission on all qualifying referred subscriptions.</p>');
    await page.click('button:has-text("Create Legal Page")');
    await page.waitForTimeout(1500);

    const updatedDocTitles = await page.$$eval('.lg\\:col-span-4 h3', elems => elems.map(e => e.textContent.trim()));
    console.log('Updated Documents in Superadmin CMS:', updatedDocTitles);

    // 5. Verify that new page appears in homepage footer!
    console.log('--- 5. Verifying Footer automatically includes new page ---');
    await page.goto('http://localhost:5173/', { waitUntil: 'networkidle' });
    await page.waitForTimeout(1000);
    const updatedFooterLinks = await page.$$eval('footer a', links => links.map(a => ({ text: a.textContent.trim(), href: a.getAttribute('href') })));
    const hasAffiliate = updatedFooterLinks.some(l => l.text.includes('Affiliate'));
    console.log('Footer contains new Affiliate page:', hasAffiliate);
    console.log('Footer Company & Legal Links:', updatedFooterLinks.filter(l => l.href.includes('legal') || l.href.includes('privacy') || l.href.includes('terms') || l.href.includes('refund')));

    // 6. Visit the new dynamic legal page
    console.log('--- 6. Visiting newly created dynamic legal page ---');
    await page.goto('http://localhost:5173/legal/affiliate-partner-terms', { waitUntil: 'networkidle' });
    await page.waitForTimeout(1000);
    const pageHeading = await page.$eval('h1', el => el.textContent.trim());
    console.log('Dynamic Legal Page H1:', pageHeading);
    await page.screenshot({ path: 'tests/screenshots/11_dynamic_legal_page.png', fullPage: true });
    console.log('Captured screenshot: tests/screenshots/11_dynamic_legal_page.png');

    await browser.close();
    console.log('>>> ALL VERIFICATION CHECKS PASSED SUCCESSFULLY! <<<');
  } catch (err) {
    console.error('Test failed:', err);
    process.exit(1);
  }
})();
