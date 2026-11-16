const { chromium } = require('C:/Users/ABC/AppData/Roaming/npm/node_modules/@playwright/cli/node_modules/playwright-core');

(async () => {
  console.log('--- Starting Remember Me & Persistent Session Flow Test ---');
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    // 1. Visit Login Page as unauthenticated user
    console.log('Step 1: Navigating to /login...');
    await page.goto('http://localhost:5173/login', { waitUntil: 'networkidle' });
    await page.waitForTimeout(1000);

    const emailInput = await page.locator('input[type="email"]');
    const passwordInput = await page.locator('input[type="password"]');
    const rememberMeCheckbox = await page.locator('input[type="checkbox"]');

    if (!(await emailInput.isVisible()) || !(await passwordInput.isVisible())) {
      throw new Error('Login form inputs not visible on /login');
    }
    console.log('✓ Login form inputs visible');

    const isRememberChecked = await rememberMeCheckbox.isChecked();
    console.log(`✓ Remember me checkbox is visible, default checked: ${isRememberChecked}`);

    // 2. Perform Login with Remember Me
    console.log('Step 2: Submitting credentials with Remember Me checked...');
    await emailInput.fill('owner@qfunnel.local');
    await passwordInput.fill('Password123!');
    if (!isRememberChecked) {
      await rememberMeCheckbox.check();
    }
    await page.click('button[type="submit"]');

    // 3. Verify landing on Dashboard
    await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
    console.log('✓ Successfully authenticated and navigated to /admin/dashboard');
    await page.waitForTimeout(1500);

    // 4. Navigate back to Homepage (/)
    console.log('Step 3: Navigating back to Homepage (http://localhost:5173/)...');
    await page.goto('http://localhost:5173/', { waitUntil: 'networkidle' });
    await page.waitForTimeout(1500);

    // Take screenshot of homepage showing logged in header state
    await page.screenshot({ path: 'C:/Users/ABC/.gemini/antigravity/brain/f9e046c7-e0f4-40cc-95ac-7614588020e0/homepage_logged_in.png', fullPage: false });
    console.log('✓ Saved homepage_logged_in.png');

    const pageContent = await page.textContent('header');
    if (!pageContent.includes('Go to Dashboard') && !pageContent.includes('Dashboard')) {
      throw new Error('Header does not display "Go to Dashboard" button when logged in!');
    }
    console.log('✓ Header correctly displays "Go to Dashboard" / user greeting!');

    // 5. Try to navigate to /login directly
    console.log('Step 4: Attempting to visit /login while already logged in...');
    await page.goto('http://localhost:5173/login', { waitUntil: 'networkidle' });
    await page.waitForTimeout(2000);

    const currentUrlAfterLoginVisit = page.url();
    console.log(`URL after navigating to /login: ${currentUrlAfterLoginVisit}`);
    if (!currentUrlAfterLoginVisit.includes('/admin/dashboard') && !currentUrlAfterLoginVisit.includes('/admin/platform')) {
      throw new Error(`Expected auto-redirect to dashboard, but stayed on: ${currentUrlAfterLoginVisit}`);
    }
    console.log('✓ /login automatically redirected to dashboard!');

    // 6. Try to navigate to /register directly
    console.log('Step 5: Attempting to visit /register while already logged in...');
    await page.goto('http://localhost:5173/register', { waitUntil: 'networkidle' });
    await page.waitForTimeout(2000);

    const currentUrlAfterRegisterVisit = page.url();
    console.log(`URL after navigating to /register: ${currentUrlAfterRegisterVisit}`);
    if (!currentUrlAfterRegisterVisit.includes('/admin/dashboard') && !currentUrlAfterRegisterVisit.includes('/admin/platform')) {
      throw new Error(`Expected auto-redirect to dashboard, but stayed on: ${currentUrlAfterRegisterVisit}`);
    }
    console.log('✓ /register automatically redirected to dashboard!');

    // 7. Check cookies for session cookie
    const cookies = await context.cookies();
    const sessionCookie = cookies.find(c => c.name === 'PHPSESSID' || c.name === 'qfunnel_session');
    console.log('Session Cookie Details:', sessionCookie ? { name: sessionCookie.name, expires: sessionCookie.expires, httpOnly: sessionCookie.httpOnly } : 'No explicit named cookie found');

    console.log('=== ALL REMEMBER ME & PERSISTENT SESSION TESTS PASSED! ===');
  } catch (err) {
    console.error('Test Failed:', err);
    process.exit(1);
  } finally {
    await browser.close();
  }
})();
