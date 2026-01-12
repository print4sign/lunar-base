import { chromium } from 'playwright';

async function debug() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
    });
    const page = await context.newPage();

    await page.goto('https://www.probo.nl/klantenservice/bestanden', { waitUntil: 'networkidle', timeout: 30000 });
    
    // Accept cookies
    try {
        await page.locator('button:has-text("Alle cookies accepteren")').first().click({ timeout: 3000 });
        await page.waitForTimeout(1500);
    } catch (e) {}
    
    await page.waitForTimeout(3000);
    
    // Get ALL hrefs first
    const allHrefs = await page.$$eval('a', links => links.map(l => l.href).filter(h => h.includes('klantenservice')));
    
    console.log('All klantenservice hrefs:', allHrefs.slice(0, 20));
    
    await browser.close();
}

debug();
