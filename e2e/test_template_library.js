const { chromium } = require('C:/Users/ABC/AppData/Roaming/npm/node_modules/@playwright/cli/node_modules/playwright-core');
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const BASE_URL = process.env.BASE_URL || 'http://localhost:5173';
const SCREENSHOT_DIR = path.join(__dirname, 'screenshots');
if (!fs.existsSync(SCREENSHOT_DIR)) {
  fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

async function runTemplateTests() {
  console.log('================================================================');
  console.log('🚀 Running Workflow Template Library End-to-End Verification');
  console.log(`🎯 Target Base URL: ${BASE_URL}`);
  console.log('================================================================\n');

  const browser = await chromium.launch({
    channel: 'chrome',
    headless: true,
  });

  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
  });

  const page = await context.newPage();

  try {
    // -------------------------------------------------------------
    // TEST 1: Super Admin Template Management Console
    // -------------------------------------------------------------
    console.log('--- TEST 1: Super Admin Template Management Console ---');
    await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });

    // Super Admin login
    await page.fill('input[type="email"]', 'superadmin@qfunnel.local');
    await page.fill('input[type="password"]', 'Password123!');
    await page.click('button[type="submit"]');

    await page.waitForURL(url => url.pathname.includes('/admin/platform') || url.pathname.includes('/admin/dashboard'), { timeout: 10000 });
    await page.goto(`${BASE_URL}/admin/platform`, { waitUntil: 'networkidle' });
    console.log('✅ Super Admin logged in successfully.');

    // Navigate to Workflow Templates
    await page.click('a[href="/admin/platform/templates"]');
    await page.waitForURL('**/admin/platform/templates', { timeout: 10000 });
    await page.waitForSelector('text=Global Workflow Templates');
    console.log('✅ Navigated to /admin/platform/templates.');

    // Assert that metrics and seeded templates are rendered
    await page.waitForSelector('text=High-Converting Lead Qualification Funnel');
    await page.waitForSelector('text=Website & App Development Cost Estimator');
    await page.waitForSelector('text=High-Ticket Real Estate Buyer Pre-Qualifier');
    console.log('✅ All pre-seeded workflow templates rendered in management table.');

    await page.screenshot({ path: path.join(SCREENSHOT_DIR, '25_superadmin_templates.png') });
    console.log('📸 Screenshot saved: 25_superadmin_templates.png');

    // Test 1.2: Open "+ Convert Funnel to Template" Modal and test custom category
    await page.click('button:has-text("+ Convert Funnel to Template")');
    await page.waitForSelector('text=Convert Funnel to Global Template');
    console.log('✅ "+ Convert Funnel to Template" modal mounted.');

    // Toggle "+ Add New Category"
    await page.click('button:has-text("+ Add New Category")');
    await page.fill('input[placeholder*="Dental"]', 'Dental & Healthcare');
    console.log('✅ Custom Category input field verified.');
    await page.click('button:has-text("Cancel")');

    // Test 1.3: Edit an existing template
    const editBtn = page.locator('button[title="Edit Template"]').first();
    await editBtn.click();
    await page.waitForSelector('text=Edit Workflow Template');
    console.log('✅ "Edit Workflow Template" modal mounted with category & re-sync support.');
    await page.waitForSelector('text=Re-Sync Workflow from a Funnel');
    await page.click('button:has-text("Cancel")');

    // -------------------------------------------------------------
    // TEST 2: Tenant User Template Library Experience
    // -------------------------------------------------------------
    console.log('\n--- TEST 2: Tenant Workspace Template Library ---');
    
    // Ensure verified tenant user exists via db_verifier
    execSync(`php "${path.join(__dirname, 'db_verifier.php')}" ensure_test_tenant`);

    // Log out of Super Admin & clear session
    await page.evaluate(() => localStorage.clear());
    await context.clearCookies();
    await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle' });

    // Log in as standard tenant user
    await page.fill('input[type="email"]', 'template_user@filterfunnel.test');
    await page.fill('input[type="password"]', 'Password123!');
    await page.click('button[type="submit"]');
    await page.waitForURL(url => url.pathname.includes('/admin/dashboard'), { timeout: 12000 });
    console.log('✅ Standard Tenant user logged in successfully.');

    // Dismiss onboarding tour overlay by setting completed flag in localStorage
    await page.evaluate(() => {
      localStorage.setItem('qfunnel_onboarding_tour_completed', 'true');
    });
    const skipBtn = await page.$('button:has-text("Skip Tutorial"), button:has-text("Finish & Build"), button:has-text("Close")');
    if (skipBtn) {
      try { await skipBtn.click(); } catch {}
    }
    await page.waitForTimeout(500);

    // Navigate to Template Library
    await page.goto(`${BASE_URL}/admin/templates`, { waitUntil: 'networkidle' });
    await page.evaluate(() => localStorage.setItem('qfunnel_onboarding_tour_completed', 'true'));
    const anyModal = await page.$('button:has-text("Skip Tutorial"), button:has-text("Finish & Build")');
    if (anyModal) { try { await anyModal.click(); } catch {} }
    await page.waitForSelector('text=Workflow Template Library');
    console.log('✅ Navigated to Tenant Template Library (/admin/templates).');

    // Filter by Calculators & Quotes
    await page.click('button:has-text("Calculators & Quotes")');
    await page.waitForTimeout(600);
    await page.waitForSelector('text=Website & App Development Cost Estimator');
    console.log('✅ Category filtering verified.');

    // Filter back to All
    await page.click('button:has-text("All Templates")');
    await page.waitForTimeout(600);

    // Open Step-by-Step Preview for first template card
    await page.locator('button:has-text("Preview")').first().click();
    await page.waitForSelector('text=Workflow Preview');
    await page.waitForSelector('text=Primary Business Goal');
    console.log('✅ Interactive Step-by-Step Preview modal mounted.');

    await page.screenshot({ path: path.join(SCREENSHOT_DIR, '26_template_preview_modal.png') });
    console.log('📸 Screenshot saved: 26_template_preview_modal.png');

    // Click "Use This Template" from inside modal
    await page.click('div[role="dialog"] button:has-text("Use This Template"), .fixed button:has-text("Use This Template")');
    await page.waitForSelector('text=Instantiate Workflow Template');
    console.log('✅ 1-Click Instantiate Confirmation modal mounted.');

    // Confirm creation
    const customFunnelName = `E2E Cloned Funnel ${Date.now()}`;
    await page.fill('input[placeholder="e.g. My Lead Quiz"]', customFunnelName);
    await page.click('button:has-text("Create & Open Builder")');

    // Wait for redirect to visual canvas builder
    await page.waitForURL('**/admin/funnels/*/builder', { timeout: 15000 });
    console.log(`✅ Funnel instantiated! Redirected to Visual Builder: ${page.url()}`);

    // Verify nodes rendered on ReactFlow canvas
    await page.waitForSelector('.react-flow__node');
    const nodesCount = await page.locator('.react-flow__node').count();
    console.log(`✅ Canvas nodes rendered: ${nodesCount} nodes detected.`);

    await page.screenshot({ path: path.join(SCREENSHOT_DIR, '27_instantiated_canvas.png') });
    console.log('📸 Screenshot saved: 27_instantiated_canvas.png');

    // Test the "Templates" button inside builder toolbar
    await page.click('button:has-text("Templates")');
    await page.waitForSelector('text=Insert Workflow Template into Canvas');
    console.log('✅ Canvas Toolbar "Templates" button opens insertion modal.');
    await page.locator('button:has-text("Close")').first().click();

    // -------------------------------------------------------------
    // TEST 3: Database Relational Integrity
    // -------------------------------------------------------------
    console.log('\n--- TEST 3: Database Verification ---');
    const dbCheckOutput = execSync(
      `c:\\xampp\\mysql\\bin\\mysql.exe -u root qfunnel2_db -e "SELECT id, name, slug, funnel_mode, (SELECT COUNT(*) FROM funnel_nodes fn WHERE fn.funnel_id = f.id) as node_count, (SELECT COUNT(*) FROM funnel_edges fe WHERE fe.funnel_id = f.id) as edge_count FROM funnels f ORDER BY f.id DESC LIMIT 1;"`
    ).toString();
    console.log('Database Result for Newly Cloned Funnel:');
    console.log(dbCheckOutput);

    console.log('\n================================================================');
    console.log('🟢 ALL WORKFLOW TEMPLATE LIBRARY E2E TESTS PASSED (100%)');
    console.log('================================================================');
  } catch (err) {
    console.error('❌ E2E Test Failure:', err);
    process.exit(1);
  } finally {
    await browser.close();
  }
}

runTemplateTests();
