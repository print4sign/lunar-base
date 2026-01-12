export class CategoryScraper {
    constructor(browser, options) {
        this.browser = browser;
        this.options = options;
        this.selectors = options.selectors || {};
        this.baseUrl = options.base_url;
        this.delay = options.delay || 1000;
        this.categories = [];
        this.visitedUrls = new Set();
    }

    async scrape() {
        const page = await this.browser.newPage();

        try {
            // Start from the base URL or a specific category URL
            const startUrl = this.options.start_url || this.baseUrl;
            await this.scrapeCategories(page, startUrl, null, 0);

            return this.categories;
        } finally {
            await page.close();
        }
    }

    async scrapeCategories(page, url, parentId, depth) {
        if (this.visitedUrls.has(url)) {
            return;
        }

        const maxDepth = this.options.max_depth || 5;
        if (depth > maxDepth) {
            return;
        }

        this.visitedUrls.add(url);

        try {
            await page.goto(url, { waitUntil: 'networkidle' });
            await this.delay && this.sleep(this.delay);

            // Extract category info from current page
            const categoryInfo = await this.extractCategoryInfo(page, url, parentId, depth);

            if (categoryInfo) {
                this.categories.push(categoryInfo);

                // Find subcategory links
                const subcategoryLinks = await this.findSubcategoryLinks(page);

                for (let i = 0; i < subcategoryLinks.length; i++) {
                    const link = subcategoryLinks[i];
                    if (!this.visitedUrls.has(link.url)) {
                        await this.scrapeCategories(page, link.url, categoryInfo.external_id, depth + 1);
                    }
                }
            }
        } catch (error) {
            console.error(`Error scraping category ${url}:`, error.message);
        }
    }

    async extractCategoryInfo(page, url, parentId, depth) {
        try {
            // Extract category name
            const nameSelector = this.selectors.name || 'h1, .category-title, .collection-title';
            const name = await page.$eval(nameSelector, el => el.textContent.trim()).catch(() => null);

            if (!name) {
                return null;
            }

            // Extract description
            const descSelector = this.selectors.description || '.category-description, .collection-description';
            const description = await page.$eval(descSelector, el => el.textContent.trim()).catch(() => null);

            // Extract image
            const imageSelector = this.selectors.image || '.category-image img, .collection-image img';
            const imageUrl = await page.$eval(imageSelector, el => el.src).catch(() => null);

            // Extract breadcrumbs
            const breadcrumbSelector = this.selectors.breadcrumbs || '.breadcrumb a, .breadcrumbs a';
            const breadcrumbs = await page.$$eval(breadcrumbSelector, els =>
                els.map(el => ({
                    name: el.textContent.trim(),
                    url: el.href,
                }))
            ).catch(() => []);

            // Generate external ID from URL
            const externalId = this.generateExternalId(url);

            return {
                external_id: externalId,
                name,
                url,
                description,
                image_url: imageUrl,
                parent_id: parentId,
                depth,
                position: this.categories.filter(c => c.parent_id === parentId).length,
                breadcrumbs,
                meta: {
                    scraped_at: new Date().toISOString(),
                },
            };
        } catch (error) {
            console.error(`Error extracting category info:`, error.message);
            return null;
        }
    }

    async findSubcategoryLinks(page) {
        const linkSelector = this.selectors.list || '.category-list a, nav.categories a, .subcategories a';

        try {
            const links = await page.$$eval(linkSelector, (els, baseUrl) => {
                return els.map(el => ({
                    name: el.textContent.trim(),
                    url: el.href,
                })).filter(link => {
                    // Filter out external links and non-category links
                    try {
                        const url = new URL(link.url);
                        const base = new URL(baseUrl);
                        return url.hostname === base.hostname && link.name.length > 0;
                    } catch {
                        return false;
                    }
                });
            }, this.baseUrl);

            return links;
        } catch (error) {
            return [];
        }
    }

    generateExternalId(url) {
        try {
            const urlObj = new URL(url);
            return urlObj.pathname.replace(/^\/|\/$/g, '').replace(/\//g, '-') || 'home';
        } catch {
            return url;
        }
    }

    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}
