import { chromium } from 'playwright';

const url = process.argv[2] || 'https://www.probo.nl/klantenservice/bestanden';

async function debugPage() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
    });
    const page = await context.newPage();

    await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
    await page.waitForTimeout(2000);
    
    // Try to find and click cookie accept button with various methods
    const cookieSelectors = [
        'button:has-text("Alle cookies accepteren")',
        'button:has-text("Accepteren")',
        'button:has-text("Accept")',
        '.cookiewall button',
        '[class*="cookie"] button',
        'button.primary',
    ];
    
    for (const selector of cookieSelectors) {
        try {
            const btn = await page.locator(selector).first();
            if (await btn.isVisible({ timeout: 1000 })) {
                console.log('Found cookie button with:', selector);
                await btn.click();
                await page.waitForTimeout(2000);
                break;
            }
        } catch (e) {}
    }
    
    // Wait for cookie wall to disappear
    await page.waitForTimeout(2000);
    
    // Now get all visible text content
    const pageText = await page.evaluate(() => {
        // Get visible text only
        const walker = document.createTreeWalker(
            document.body,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );
        
        let text = '';
        let node;
        while (node = walker.nextNode()) {
            const parent = node.parentElement;
            if (parent && window.getComputedStyle(parent).display !== 'none' && 
                !parent.closest('.cookie, .modal, form, nav, header, footer')) {
                const trimmed = node.textContent.trim();
                if (trimmed.length > 10) {
                    text += trimmed + '\n';
                }
            }
        }
        return text;
    });
    
    console.log('\n=== VISIBLE TEXT ===');
    console.log(pageText.substring(0, 3000));
    
    // Get H1 and the content after it
    const h1Text = await page.textContent('h1').catch(() => 'NO H1');
    console.log('\n=== H1 ===', h1Text);
    
    // Get all H2s that might be FAQ items
    const h2s = await page.$$eval('h2', els => els.map(e => e.textContent.trim()).filter(t => t.length > 5 && t.length < 200));
    console.log('\n=== H2s (FAQ titles) ===');
    console.log(h2s.slice(0, 20));
    
    await browser.close();
}

debugPage();
