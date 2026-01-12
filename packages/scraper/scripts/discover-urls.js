import { chromium } from 'playwright';

const args = process.argv.slice(2);
const source = args.find(a => a.startsWith('--source='))?.split('=')[1] || 'ontdek';

async function discoverUrls() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    });
    const page = await context.newPage();

    try {
        let urls = [];

        if (source === 'ontdek') {
            urls = await discoverOntdekUrls(page);
        } else if (source === 'klantenservice') {
            urls = await discoverKlantenserviceUrls(page);
        }

        console.log(JSON.stringify(urls));
    } catch (error) {
        console.error(JSON.stringify({ error: error.message }));
        process.exit(1);
    } finally {
        await browser.close();
    }
}

async function handleCookieConsent(page) {
    try {
        const cookieButton = await page.locator('button:has-text("Alle cookies accepteren")').first();
        if (await cookieButton.isVisible({ timeout: 3000 })) {
            await cookieButton.click();
            await page.waitForTimeout(1500);
        }
    } catch (e) {
        // Cookie banner not found
    }
}

async function discoverOntdekUrls(page) {
    const allUrls = new Set();

    // First, discover category pages from the main ontdek page
    await page.goto('https://www.probo.nl/ontdek', { waitUntil: 'networkidle', timeout: 30000 });
    await handleCookieConsent(page);
    await page.waitForTimeout(2000);

    // Find category pages (2 segments: ontdek/category)
    const categoryPages = await page.$$eval('a[href*="/ontdek/"]', links => {
        const categories = new Set();
        links.forEach(link => {
            const href = link.href;
            try {
                const path = new URL(href).pathname;
                const parts = path.split('/').filter(p => p);
                // Category pages have exactly 2 parts: ontdek / category
                if (parts.length === 2 && parts[0] === 'ontdek') {
                    const cleanUrl = href.split('#')[0].split('?')[0];
                    categories.add(cleanUrl);
                }
            } catch (e) {}
        });
        return Array.from(categories);
    });

    // Also collect any article links already visible on the main page
    const mainPageArticles = await page.$$eval('a[href*="/ontdek/"]', links => {
        const articles = [];
        links.forEach(link => {
            const href = link.href;
            try {
                const path = new URL(href).pathname;
                const parts = path.split('/').filter(p => p);
                // Article pages have 3 parts: ontdek / category / article-slug
                if (parts.length === 3 && parts[0] === 'ontdek') {
                    const cleanUrl = href.split('#')[0].split('?')[0];
                    articles.push(cleanUrl);
                }
            } catch (e) {}
        });
        return articles;
    });

    mainPageArticles.forEach(u => allUrls.add(u));

    // Now crawl each category page to find more articles
    for (const categoryUrl of categoryPages) {
        try {
            await page.goto(categoryUrl, { waitUntil: 'networkidle', timeout: 30000 });
            await handleCookieConsent(page);
            await page.waitForTimeout(2000);

            // Scroll down to load lazy content
            await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
            await page.waitForTimeout(1000);

            // Find all article links on this category page
            const urls = await page.$$eval('a[href*="/ontdek/"]', links => {
                const articleUrls = [];
                links.forEach(link => {
                    const href = link.href;
                    try {
                        const path = new URL(href).pathname;
                        const parts = path.split('/').filter(p => p);
                        // Article pages have 3 parts: ontdek / category / article-slug
                        if (parts.length === 3 && parts[0] === 'ontdek') {
                            const cleanUrl = href.split('#')[0].split('?')[0];
                            articleUrls.push(cleanUrl);
                        }
                    } catch (e) {}
                });
                return articleUrls;
            });

            urls.forEach(u => allUrls.add(u));

        } catch (e) {
            // Skip failed category pages
        }
    }

    return Array.from(allUrls);
}

async function discoverKlantenserviceUrls(page) {
    const allUrls = new Set();

    // Main klantenservice categories to crawl
    const categoryPages = [
        'https://www.probo.nl/klantenservice/bestanden',
        'https://www.probo.nl/klantenservice/bestelgemak',
        'https://www.probo.nl/klantenservice/bestellen',
        'https://www.probo.nl/klantenservice/bezorgen-afhalen',
        'https://www.probo.nl/klantenservice/betalen',
        'https://www.probo.nl/klantenservice/algemeen',
    ];

    for (const categoryUrl of categoryPages) {
        try {
            await page.goto(categoryUrl, { waitUntil: 'networkidle', timeout: 30000 });
            await handleCookieConsent(page);
            await page.waitForTimeout(3000);

            // Find all article links - looking for URLs with 3 path segments
            // e.g., /klantenservice/bestanden/article-name
            const urls = await page.$$eval('a', links => {
                const articleUrls = [];
                links.forEach(link => {
                    const href = link.href;
                    if (!href.includes('/klantenservice/')) return;

                    try {
                        const url = new URL(href);
                        const path = url.pathname;
                        const parts = path.split('/').filter(p => p);

                        // Must have exactly 3 parts: klantenservice / category / article
                        if (parts.length === 3 && parts[0] === 'klantenservice') {
                            const cleanUrl = href.split('#')[0].split('?')[0];
                            articleUrls.push(cleanUrl);
                        }
                    } catch (e) {
                        // Invalid URL, skip
                    }
                });
                return articleUrls;
            });

            urls.forEach(u => allUrls.add(u));

        } catch (e) {
            // Skip failed category pages
        }
    }

    return Array.from(allUrls);
}

discoverUrls();
