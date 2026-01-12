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
    
    // Get all links
    const urls = await page.$$eval('a[href*="/klantenservice/"]', links => {
        const articleUrls = [];
        links.forEach(link => {
            const href = link.href;
            const path = new URL(href).pathname;
            const parts = path.split('/').filter(p => p);
            
            console.log('Parts:', parts);
            
            // Must be a sub-article
            if (parts.length >= 3 && parts[0] === 'klantenservice') {
                const cleanUrl = href.split('#')[0].split('?')[0];
                if (!cleanUrl.endsWith('/klantenservice/' + parts[1])) {
                    articleUrls.push(cleanUrl);
                }
            }
        });
        return articleUrls;
    });
    
    console.log('Found URLs:', urls);
    
    await browser.close();
}

debug();
