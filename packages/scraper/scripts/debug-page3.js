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
    
    // Get all div elements with substantial text
    const divs = await page.evaluate(() => {
        const allDivs = document.querySelectorAll('div');
        return Array.from(allDivs)
            .filter(d => d.innerText.length > 500 && d.innerText.length < 50000)
            .slice(0, 5)
            .map(d => ({
                class: d.className.substring(0, 100),
                textPreview: d.innerText.substring(0, 200),
                textLength: d.innerText.length
            }));
    });
    
    console.log('=== DIVS WITH CONTENT ===');
    console.log(JSON.stringify(divs, null, 2));
    
    // Try to find accordion/FAQ items
    const accordions = await page.$$eval('[class*="accordion"], [class*="faq"], [class*="collapse"], [class*="expand"]', 
        els => els.slice(0, 5).map(e => ({
            tag: e.tagName,
            class: e.className.substring(0, 80),
            textLength: e.innerText.length,
            html: e.outerHTML.substring(0, 300)
        }))).catch(() => []);
    
    console.log('\n=== ACCORDION ELEMENTS ===');
    console.log(JSON.stringify(accordions, null, 2));
    
    // Check for rich text / prose areas
    const richText = await page.$$eval('[class*="prose"], [class*="rich"], [class*="text-content"], [class*="body"]', 
        els => els.slice(0, 5).map(e => ({
            class: e.className.substring(0, 80),
            textPreview: e.innerText.substring(0, 200),
            textLength: e.innerText.length
        }))).catch(() => []);
    
    console.log('\n=== RICH TEXT ELEMENTS ===');
    console.log(JSON.stringify(richText, null, 2));
    
    await browser.close();
}

debugPage();
