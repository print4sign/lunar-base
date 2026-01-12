import { chromium } from 'playwright';

async function debugPage() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
    });
    const page = await context.newPage();

    await page.goto('https://www.probo.nl/klantenservice/bestanden', { waitUntil: 'networkidle', timeout: 60000 });
    
    // Accept cookies
    try {
        await page.locator('button:has-text("Alle cookies accepteren")').first().click({ timeout: 5000 });
    } catch (e) {}
    
    await page.waitForTimeout(5000);
    
    // Get ALL links that point to klantenservice articles
    const allLinks = await page.$$eval('a', els => 
        els.map(e => ({ 
            href: e.href, 
            text: e.textContent.trim().substring(0, 100),
            parentClass: e.parentElement?.className?.substring(0, 50) || ''
        }))
        .filter(l => l.href.includes('/klantenservice/') && l.text.length > 3)
    ).catch(() => []);
    
    console.log('All klantenservice links:');
    const unique = [...new Set(allLinks.map(l => l.href))];
    unique.forEach(u => console.log(' -', u));
    
    // Find the parent container of H2 elements
    const h2Info = await page.evaluate(() => {
        const h2s = document.querySelectorAll('h2');
        return Array.from(h2s).slice(0, 5).map(h2 => {
            const parent = h2.closest('a, article, div[class*="card"], div[class*="item"]');
            return {
                text: h2.textContent.trim(),
                parentTag: parent?.tagName,
                parentClass: parent?.className?.substring(0, 80),
                parentHref: parent?.href || null,
                siblingP: h2.nextElementSibling?.tagName === 'P' ? h2.nextElementSibling.textContent.substring(0, 100) : null
            };
        });
    });
    
    console.log('\nH2 parent info:');
    console.log(JSON.stringify(h2Info, null, 2));
    
    await browser.close();
}

debugPage();
