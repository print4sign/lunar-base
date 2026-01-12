import { chromium } from 'playwright';

async function debugPage() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
    });
    const page = await context.newPage();

    await page.goto('https://www.probo.nl/klantenservice/bestanden', { waitUntil: 'networkidle', timeout: 30000 });
    
    // Accept cookies
    try {
        const btn = await page.locator('button:has-text("Alle cookies accepteren")').first();
        if (await btn.isVisible({ timeout: 2000 })) {
            await btn.click();
            await page.waitForTimeout(2000);
        }
    } catch (e) {}
    
    await page.waitForTimeout(3000);
    
    // Dump the entire main element's structure
    const structure = await page.evaluate(() => {
        const main = document.querySelector('main');
        if (!main) return 'NO MAIN';
        
        function getStructure(el, depth = 0) {
            if (depth > 4) return '';
            const tag = el.tagName?.toLowerCase() || 'text';
            const cls = el.className?.toString().substring(0, 50) || '';
            const textLen = el.innerText?.length || 0;
            const children = el.children?.length || 0;
            
            let result = `${'  '.repeat(depth)}<${tag} class="${cls}" text=${textLen} children=${children}>\n`;
            
            if (children > 0 && depth < 4) {
                for (const child of el.children) {
                    result += getStructure(child, depth + 1);
                }
            }
            
            return result;
        }
        
        return getStructure(main);
    });
    
    console.log('=== STRUCTURE ===');
    console.log(structure.substring(0, 5000));
    
    // Get all elements with class containing specific keywords
    const contentElements = await page.evaluate(() => {
        const keywords = ['article', 'content', 'text', 'body', 'faq', 'answer'];
        const results = [];
        
        for (const kw of keywords) {
            const els = document.querySelectorAll(`[class*="${kw}"]`);
            els.forEach(el => {
                if (el.innerText.length > 100 && el.innerText.length < 5000) {
                    results.push({
                        keyword: kw,
                        class: el.className.substring(0, 80),
                        text: el.innerText.substring(0, 200)
                    });
                }
            });
        }
        
        return results.slice(0, 10);
    });
    
    console.log('\n=== CONTENT ELEMENTS ===');
    console.log(JSON.stringify(contentElements, null, 2));
    
    await browser.close();
}

debugPage();
