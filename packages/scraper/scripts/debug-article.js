import { chromium } from 'playwright';

async function debug() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
    });
    const page = await context.newPage();

    await page.goto('https://www.probo.nl/klantenservice/bestanden/bestanden-opmaken', { waitUntil: 'networkidle', timeout: 30000 });
    
    // Accept cookies
    try {
        await page.locator('button:has-text("Alle cookies accepteren")').first().click({ timeout: 3000 });
        await page.waitForTimeout(2000);
    } catch (e) {}
    
    await page.waitForTimeout(3000);
    
    // Get all paragraph texts
    const paragraphs = await page.$$eval('p', els => 
        els.map(e => e.textContent.trim())
            .filter(t => t.length > 50)
            .slice(0, 10)
    );
    console.log('=== PARAGRAPHS ===');
    paragraphs.forEach(p => console.log('-', p.substring(0, 150)));
    
    // Get all div content
    const contentDivs = await page.evaluate(() => {
        const divs = document.querySelectorAll('div');
        return Array.from(divs)
            .filter(d => {
                const text = d.innerText?.trim() || '';
                return text.length > 200 && text.length < 10000 && !d.querySelector('nav, header, footer');
            })
            .slice(0, 5)
            .map(d => ({
                class: d.className?.substring(0, 60) || '',
                text: d.innerText?.substring(0, 300) || ''
            }));
    });
    
    console.log('\n=== CONTENT DIVS ===');
    console.log(JSON.stringify(contentDivs, null, 2));
    
    // Look for article-like structures
    const articles = await page.$$eval('article, [class*="article"], [class*="content"], [class*="body"]', els =>
        els.slice(0, 3).map(e => ({
            tag: e.tagName,
            class: e.className?.substring(0, 60),
            textLen: e.innerText?.length || 0
        }))
    );
    console.log('\n=== ARTICLE ELEMENTS ===');
    console.log(JSON.stringify(articles, null, 2));
    
    await browser.close();
}

debug();
