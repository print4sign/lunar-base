export class ProductScraper {
    constructor(browser, options) {
        this.browser = browser;
        this.options = options;
        this.selectors = options.selectors || {};
        this.baseUrl = options.base_url;
        this.delay = options.delay || 1000;
        this.products = [];
        this.visitedUrls = new Set();
    }

    async scrape() {
        const page = await this.browser.newPage();

        try {
            // Scrape products from provided URLs (category pages)
            const urls = this.options.urls || [this.baseUrl];

            for (const url of urls) {
                await this.scrapeProductList(page, url);
            }

            return this.products;
        } finally {
            await page.close();
        }
    }

    async scrapeAll(categories) {
        const page = await this.browser.newPage();

        try {
            // Scrape products from all category URLs
            for (const category of categories) {
                await this.scrapeProductList(page, category.url, category.external_id);
            }

            return this.products;
        } finally {
            await page.close();
        }
    }

    async scrapeProductList(page, url, categoryId = null) {
        try {
            await page.goto(url, { waitUntil: 'networkidle' });
            await this.sleep(this.delay);

            // Find all product links on the page
            const productLinks = await this.findProductLinks(page);

            for (const link of productLinks) {
                if (!this.visitedUrls.has(link.url)) {
                    const product = await this.scrapeProduct(page, link.url, categoryId);
                    if (product) {
                        this.products.push(product);
                    }
                }
            }

            // Check for pagination
            const nextPage = await this.findNextPage(page);
            if (nextPage && !this.visitedUrls.has(nextPage)) {
                await this.scrapeProductList(page, nextPage, categoryId);
            }
        } catch (error) {
            console.error(`Error scraping product list ${url}:`, error.message);
        }
    }

    async scrapeProduct(page, url, categoryId) {
        if (this.visitedUrls.has(url)) {
            return null;
        }

        this.visitedUrls.add(url);

        try {
            await page.goto(url, { waitUntil: 'networkidle' });
            await this.sleep(this.delay);

            // Extract product information
            const product = await this.extractProductInfo(page, url, categoryId);

            return product;
        } catch (error) {
            console.error(`Error scraping product ${url}:`, error.message);
            return null;
        }
    }

    async extractProductInfo(page, url, categoryId) {
        try {
            // Extract name
            const nameSelector = this.selectors.name || 'h1.product-title, .product-name, [data-product-title]';
            const name = await page.$eval(nameSelector, el => el.textContent.trim()).catch(() => null);

            if (!name) {
                return null;
            }

            // Extract price
            const priceSelector = this.selectors.price || '.price, .product-price, [data-price]';
            const priceText = await page.$eval(priceSelector, el => el.textContent.trim()).catch(() => null);
            const price = this.parsePrice(priceText);

            // Extract sale price
            const salePriceSelector = this.selectors.sale_price || '.sale-price, .product-sale-price, [data-sale-price]';
            const salePriceText = await page.$eval(salePriceSelector, el => el.textContent.trim()).catch(() => null);
            const salePrice = this.parsePrice(salePriceText);

            // Extract description
            const descSelector = this.selectors.description || '.product-description, #description, [data-product-description]';
            const description = await page.$eval(descSelector, el => el.innerHTML).catch(() => null);

            // Extract short description
            const shortDescSelector = this.selectors.short_description || '.short-description, .product-excerpt';
            const shortDescription = await page.$eval(shortDescSelector, el => el.textContent.trim()).catch(() => null);

            // Extract SKU
            const skuSelector = this.selectors.sku || '.sku, [data-sku], .product-sku';
            const sku = await page.$eval(skuSelector, el => el.textContent.trim()).catch(() => null);

            // Extract images
            const imagesSelector = this.selectors.images || '.product-images img, .gallery img, [data-product-images] img';
            const images = await page.$$eval(imagesSelector, els =>
                els.map(el => el.src || el.dataset.src).filter(Boolean)
            ).catch(() => []);

            // Extract availability
            const availabilitySelector = this.selectors.availability || '.availability, .stock-status, [data-availability]';
            const availability = await page.$eval(availabilitySelector, el => el.textContent.trim()).catch(() => null);

            // Extract attributes
            const attributesSelector = this.selectors.attributes || '.product-attributes tr, .specifications li, .product-meta li';
            const attributes = await page.$$eval(attributesSelector, els => {
                return els.map(el => {
                    const cells = el.querySelectorAll('td, th');
                    if (cells.length >= 2) {
                        return {
                            name: cells[0].textContent.trim(),
                            value: cells[1].textContent.trim(),
                        };
                    }
                    const text = el.textContent.trim();
                    const [name, ...valueParts] = text.split(':');
                    return {
                        name: name?.trim(),
                        value: valueParts.join(':').trim(),
                    };
                }).filter(attr => attr.name && attr.value);
            }).catch(() => []);

            // Extract variants/options
            const variantsSelector = this.selectors.variants || '.product-variants select option, .variant-options input';
            const variants = await page.$$eval(variantsSelector, els =>
                els.map(el => ({
                    value: el.value || el.textContent?.trim(),
                    label: el.textContent?.trim() || el.getAttribute('aria-label'),
                    selected: el.selected || el.checked,
                })).filter(v => v.value)
            ).catch(() => []);

            // Get HTML content
            const contentSelector = this.selectors.content || 'main, article, .product-detail';
            const htmlContent = await page.$eval(contentSelector, el => el.innerHTML).catch(() => null);

            // Generate external ID
            const externalId = this.generateExternalId(url);

            // Detect currency from price text
            const currency = this.detectCurrency(priceText);

            return {
                external_id: externalId,
                name,
                url,
                description,
                short_description: shortDescription,
                price: price?.value,
                sale_price: salePrice?.value,
                currency,
                sku,
                stock: null, // Would need specific logic per site
                availability,
                images,
                attributes,
                variants,
                options: [],
                html_content: htmlContent,
                category_id: categoryId,
                meta: {
                    scraped_at: new Date().toISOString(),
                },
            };
        } catch (error) {
            console.error(`Error extracting product info:`, error.message);
            return null;
        }
    }

    async findProductLinks(page) {
        const linkSelector = this.selectors.link || 'a.product-link, a[href*="/product"], a[href*="/products/"]';
        const listSelector = this.selectors.list || '.product-list .product, .products-grid .product-item, .product-card';

        try {
            // Try to find product links within product containers
            let links = await page.$$eval(`${listSelector} a`, (els, baseUrl) => {
                const seen = new Set();
                return els
                    .map(el => ({ name: el.textContent?.trim(), url: el.href }))
                    .filter(link => {
                        if (!link.url || seen.has(link.url)) return false;
                        try {
                            const url = new URL(link.url);
                            const base = new URL(baseUrl);
                            if (url.hostname !== base.hostname) return false;
                            seen.add(link.url);
                            return true;
                        } catch {
                            return false;
                        }
                    });
            }, this.baseUrl);

            // Fallback to direct link selector
            if (links.length === 0) {
                links = await page.$$eval(linkSelector, (els, baseUrl) => {
                    const seen = new Set();
                    return els
                        .map(el => ({ name: el.textContent?.trim(), url: el.href }))
                        .filter(link => {
                            if (!link.url || seen.has(link.url)) return false;
                            try {
                                const url = new URL(link.url);
                                const base = new URL(baseUrl);
                                if (url.hostname !== base.hostname) return false;
                                seen.add(link.url);
                                return true;
                            } catch {
                                return false;
                            }
                        });
                }, this.baseUrl);
            }

            return links;
        } catch (error) {
            return [];
        }
    }

    async findNextPage(page) {
        const paginationSelectors = [
            'a.next',
            'a[rel="next"]',
            '.pagination a:has-text("Next")',
            '.pagination a:has-text(">")',
            '.pagination-next a',
        ];

        for (const selector of paginationSelectors) {
            try {
                const nextLink = await page.$eval(selector, el => el.href);
                if (nextLink) {
                    return nextLink;
                }
            } catch {
                continue;
            }
        }

        return null;
    }

    parsePrice(priceText) {
        if (!priceText) return null;

        // Remove currency symbols and whitespace
        const cleaned = priceText.replace(/[^\d.,]/g, '').trim();

        if (!cleaned) return null;

        // Handle European format (1.234,56) vs US format (1,234.56)
        let value;
        if (cleaned.includes(',') && cleaned.includes('.')) {
            // Determine which is decimal separator
            const lastComma = cleaned.lastIndexOf(',');
            const lastDot = cleaned.lastIndexOf('.');

            if (lastComma > lastDot) {
                // European format: 1.234,56
                value = parseFloat(cleaned.replace(/\./g, '').replace(',', '.'));
            } else {
                // US format: 1,234.56
                value = parseFloat(cleaned.replace(/,/g, ''));
            }
        } else if (cleaned.includes(',')) {
            // Could be European decimal (12,34) or US thousands (1,234)
            const parts = cleaned.split(',');
            if (parts[parts.length - 1].length === 2) {
                // Likely European decimal
                value = parseFloat(cleaned.replace(',', '.'));
            } else {
                // Likely US thousands separator
                value = parseFloat(cleaned.replace(/,/g, ''));
            }
        } else {
            value = parseFloat(cleaned);
        }

        return isNaN(value) ? null : { value, original: priceText };
    }

    detectCurrency(priceText) {
        if (!priceText) return null;

        const currencyPatterns = {
            'EUR': /[€]|EUR/i,
            'USD': /[$]|USD/i,
            'GBP': /[£]|GBP/i,
            'CHF': /CHF/i,
            'SEK': /SEK|kr/i,
            'NOK': /NOK/i,
            'DKK': /DKK/i,
            'PLN': /PLN|zł/i,
        };

        for (const [currency, pattern] of Object.entries(currencyPatterns)) {
            if (pattern.test(priceText)) {
                return currency;
            }
        }

        return null;
    }

    generateExternalId(url) {
        try {
            const urlObj = new URL(url);
            return urlObj.pathname.replace(/^\/|\/$/g, '').replace(/\//g, '-') || 'product';
        } catch {
            return url;
        }
    }

    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}
