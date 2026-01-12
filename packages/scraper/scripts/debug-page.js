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
    
    // Get all text content from the page
    const mainHtml = await page.innerHTML('main').catch(() => 'NO MAIN FOUND');
    
    // Find all headings
    const headings = await page.$$eval('h1, h2, h3', els => els.map(e => ({
        tag: e.tagName,
        text: e.textContent.trim().substring(0, 100),
        class: e.className
    }))).catch(() => []);
    
    // Find potential content containers
    const containers = await page.$$eval('[class*="content"], [class*="article"], [class*="faq"], [class*="accordion"], [class*="text"]', 
        els => els.slice(0, 10).map(e => ({
            tag: e.tagName,
            class: e.className,
            textLength: e.textContent.length
        }))).catch(() => []);
    
    console.log('=== HEADINGS ===');
    console.log(JSON.stringify(headings, null, 2));
    
    console.log('\n=== CONTENT CONTAINERS ===');
    console.log(JSON.stringify(containers, null, 2));
    
    console.log('\n=== MAIN HTML (first 2000 chars) ===');
    console.log(mainHtml.substring(0, 2000));
    
    await browser.close();
}

debugPage();
