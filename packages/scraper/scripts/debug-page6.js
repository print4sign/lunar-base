import { chromium } from 'playwright';

async function debugPage() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
    });
    const page = await context.newPage();

    // Navigate and wait for full load
    await page.goto('https://www.probo.nl/klantenservice/bestanden', { waitUntil: 'networkidle', timeout: 60000 });
    
    // Accept cookies
    try {
        const btn = await page.locator('button:has-text("Alle cookies accepteren")').first();
        if (await btn.isVisible({ timeout: 5000 })) {
            await btn.click();
        }
    } catch (e) {}
    
    // Wait longer for SPA content to load
    console.log('Waiting for content to load...');
    await page.waitForTimeout(5000);
    
    // Check if there's an h1 now
    const h1 = await page.textContent('h1').catch(() => 'NO H1');
    console.log('H1:', h1);
    
    // Wait for specific elements that might indicate content is loaded
    try {
        await page.waitForSelector('h2', { timeout: 10000 });
        console.log('H2 found!');
    } catch (e) {
        console.log('No H2 found');
    }
    
    // Get all h2 texts
    const h2s = await page.$$eval('h2', els => els.map(e => e.textContent.trim()).filter(t => t.length > 3)).catch(() => []);
    console.log('H2s:', h2s.slice(0, 10));
    
    // Get the full body HTML length
    const bodyLength = await page.evaluate(() => document.body.innerHTML.length);
    console.log('Body HTML length:', bodyLength);
    
    // Find all links on the page
    const links = await page.$$eval('a[href*="/klantenservice/"]', els => 
        els.map(e => ({ href: e.href, text: e.textContent.trim().substring(0, 50) }))
            .filter(l => l.text.length > 3 && !l.href.endsWith('/klantenservice/'))
    ).catch(() => []);
    console.log('\nKlantenservice links:', JSON.stringify(links.slice(0, 10), null, 2));
    
    await browser.close();
}

debugPage();
