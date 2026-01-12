export class PageScraper {
    constructor(browser, options) {
        this.browser = browser;
        this.options = options;
        this.selectors = options.selectors || {};
        this.baseUrl = options.base_url;
        this.delay = options.delay || 1000;
    }

    async scrape(url) {
        const page = await this.browser.newPage();

        try {
            const response = await page.goto(url, { waitUntil: 'networkidle' });
            await this.sleep(this.delay);

            const pageData = await this.extractPageInfo(page, url, response);

            return pageData;
        } finally {
            await page.close();
        }
    }

    async scrapeMultiple(urls) {
        const pages = [];

        for (const url of urls) {
            const pageData = await this.scrape(url);
            if (pageData) {
                pages.push(pageData);
            }
        }

        return pages;
    }

    async extractPageInfo(page, url, response) {
        try {
            // Get title
            const titleSelector = this.selectors.title || 'title';
            const title = await page.$eval(titleSelector, el => el.textContent?.trim()).catch(() => null);

            // Get main content HTML
            const contentSelector = this.selectors.content || 'main, article, .content, #content';
            const htmlContent = await page.$eval(contentSelector, el => el.innerHTML).catch(() => null);

            // Get text content (cleaned)
            const textContent = await page.$eval(contentSelector, el => el.textContent?.trim()).catch(() => null);

            // Get structured data (JSON-LD)
            const structuredData = await page.$$eval('script[type="application/ld+json"]', scripts => {
                return scripts.map(script => {
                    try {
                        return JSON.parse(script.textContent);
                    } catch {
                        return null;
                    }
                }).filter(Boolean);
            }).catch(() => []);

            // Get meta tags
            const metaTags = await page.$$eval('meta', metas => {
                const tags = {};
                metas.forEach(meta => {
                    const name = meta.getAttribute('name') || meta.getAttribute('property');
                    const content = meta.getAttribute('content');
                    if (name && content) {
                        tags[name] = content;
                    }
                });
                return tags;
            }).catch(() => ({}));

            // Get response headers
            const headers = {};
            if (response) {
                const responseHeaders = response.headers();
                for (const [key, value] of Object.entries(responseHeaders)) {
                    headers[key.toLowerCase()] = value;
                }
            }

            // Determine page type
            const type = this.detectPageType(url, structuredData, metaTags);

            return {
                url,
                title,
                type,
                html_content: htmlContent,
                text_content: textContent,
                structured_data: structuredData,
                status_code: response?.status() || null,
                headers,
                meta_tags: metaTags,
            };
        } catch (error) {
            console.error(`Error extracting page info:`, error.message);
            return {
                url,
                title: null,
                type: 'other',
                html_content: null,
                text_content: null,
                structured_data: [],
                status_code: null,
                headers: {},
                meta_tags: {},
                error: error.message,
            };
        }
    }

    detectPageType(url, structuredData, metaTags) {
        // Check structured data for type
        for (const data of structuredData) {
            const type = data['@type'];
            if (type === 'Product' || type === 'ProductGroup') {
                return 'product';
            }
            if (type === 'CollectionPage' || type === 'CategoryPage') {
                return 'category';
            }
        }

        // Check URL patterns
        const urlLower = url.toLowerCase();
        if (urlLower.includes('/product') || urlLower.includes('/item/') || urlLower.includes('/p/')) {
            return 'product';
        }
        if (urlLower.includes('/category') || urlLower.includes('/collection') || urlLower.includes('/c/')) {
            return 'category';
        }

        // Check meta tags
        const ogType = metaTags['og:type'];
        if (ogType === 'product') {
            return 'product';
        }

        return 'page';
    }

    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}
