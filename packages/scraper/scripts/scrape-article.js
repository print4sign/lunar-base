const { chromium } = require('playwright');

const args = process.argv.slice(2);
const url = args.find(a => a.startsWith('--url='))?.split('=')[1];
const headless = !args.includes('--no-headless');
const username = args.find(a => a.startsWith('--username='))?.split('=')[1] || process.env.PROBO_USERNAME;
const password = args.find(a => a.startsWith('--password='))?.split('=')[1] || process.env.PROBO_PASSWORD;

async function scrapeArticle() {
    if (!url) {
        console.error(JSON.stringify({ error: 'No URL provided' }));
        process.exit(1);
    }

    const browser = await chromium.launch({ headless });
    const context = await browser.newContext();
    const page = await context.newPage();

    try {
        // Login if credentials provided
        if (username && password) {
            await page.goto('https://www.probo.nl/login');
            await page.fill('input[name="email"]', username);
            await page.fill('input[name="password"]', password);
            await page.click('button[type="submit"]');
            await page.waitForNavigation({ waitUntil: 'networkidle' });
        }

        // Navigate to article
        await page.goto(url, { waitUntil: 'networkidle' });

        // Determine article type and scrape accordingly
        const isKlantenservice = url.includes('/klantenservice/');
        const isBlog = url.includes('/ontdek/');

        let data;

        if (isKlantenservice) {
            data = await scrapeKlantenserviceArticle(page);
        } else if (isBlog) {
            data = await scrapeBlogArticle(page);
        } else {
            throw new Error('Unknown article type');
        }

        data.source_url = url;
        data.category = isKlantenservice ? 'klantenservice' : 'blog';

        console.log(JSON.stringify(data));
    } catch (error) {
        console.error(JSON.stringify({ error: error.message }));
        process.exit(1);
    } finally {
        await browser.close();
    }
}

async function scrapeKlantenserviceArticle(page) {
    const title = await page.textContent('h1').catch(() => null);
    const body = await page.innerHTML('article, .article-content, main .prose').catch(() => null);
    const subcategory = await page.textContent('.breadcrumb li:nth-child(2)').catch(() => null);

    return {
        title: { nl: title?.trim() },
        body: { nl: cleanHtml(body) },
        excerpt: { nl: extractExcerpt(body) },
        subcategory: subcategory?.trim().toLowerCase(),
    };
}

async function scrapeBlogArticle(page) {
    const title = await page.textContent('h1').catch(() => null);
    const body = await page.innerHTML('article, .article-content, main .prose').catch(() => null);
    const excerpt = await page.textContent('.article-excerpt, .intro, p:first-of-type').catch(() => null);
    const image = await page.getAttribute('article img, .article-image img', 'src').catch(() => null);
    const tags = await page.$$eval('.tags a, .article-tags a', els => els.map(e => e.textContent.trim())).catch(() => []);
    const subcategory = await page.textContent('.article-category, .category-badge').catch(() => null);

    return {
        title: { nl: title?.trim() },
        body: { nl: cleanHtml(body) },
        excerpt: { nl: excerpt?.trim() },
        featured_image: image,
        tags: tags,
        subcategory: subcategory?.trim().toLowerCase(),
    };
}

function cleanHtml(html) {
    if (!html) return null;
    return html
        .replace(/<script[^>]*>[\s\S]*?<\/script>/gi, '')
        .replace(/<style[^>]*>[\s\S]*?<\/style>/gi, '')
        .replace(/\s+/g, ' ')
        .trim();
}

function extractExcerpt(html, maxLength = 200) {
    if (!html) return null;
    const text = html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength).replace(/\s+\S*$/, '') + '...';
}

scrapeArticle();
