import { chromium } from 'playwright';

const url = process.argv[2] || 'https://www.probo.nl/klantenservice/bestanden';

async function debugPage() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
    });
    const page = await context.newPage();

    await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
    
    // Accept cookies if visible
    try {
        const cookieButton = await page.locator('button:has-text("Alle cookies accepteren")').first();
        if (await cookieButton.isVisible({ timeout: 2000 })) {
            await cookieButton.click();
            await page.waitForTimeout(1000);
        }
    } catch (e) {}
    
    await page.waitForTimeout(3000);
    
    // Look for specific Probo content structures
    const sections = await page.$$eval('section', els => els.map(e => ({
        class: e.className,
        textLength: e.innerText.length
    }))).catch(() => []);
    
    console.log('=== SECTIONS ===');
    console.log(JSON.stringify(sections.slice(0, 10), null, 2));
    
    // Get the main content area without modals
    const mainContent = await page.evaluate(() => {
        const main = document.querySelector('main');
        if (!main) return 'NO MAIN';
        
        // Clone and remove modals, forms, etc
        const clone = main.cloneNode(true);
        clone.querySelectorAll('.modal, form, input, button, nav, header, footer, [class*="quote"]').forEach(el => el.remove());
        
        return clone.innerHTML;
    });
    
    console.log('\n=== CLEANED MAIN (first 3000 chars) ===');
    console.log(mainContent.substring(0, 3000));
    
    await browser.close();
}

debugPage();
