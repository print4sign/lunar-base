#!/usr/bin/env node
import { BrowserManager } from './browser.js';
import { AuthHandler } from './auth.js';
import { CategoryScraper } from './scrapers/category.js';
import { ProductScraper } from './scrapers/product.js';
import { PageScraper } from './scrapers/page.js';
import { ProboScraper } from './scrapers/probo.js';

// Read configuration from stdin (passed by PHP)
let configBuffer = '';

process.stdin.setEncoding('utf8');
process.stdin.on('data', chunk => {
    configBuffer += chunk;
});

process.stdin.on('end', async () => {
    try {
        const options = JSON.parse(configBuffer);
        const result = await runScraper(options);

        // Output JSON result to stdout for PHP to read
        // Use console.log which handles flushing properly
        const output = JSON.stringify(result);
        await new Promise((resolve, reject) => {
            process.stdout.write(output, 'utf8', (err) => {
                if (err) reject(err);
                else resolve();
            });
        });
        process.exit(0);
    } catch (error) {
        const errorResult = {
            success: false,
            error: error.message,
            stack: error.stack,
        };
        await new Promise((resolve, reject) => {
            process.stderr.write(JSON.stringify(errorResult), 'utf8', (err) => {
                if (err) reject(err);
                else resolve();
            });
        });
        process.exit(1);
    }
});

async function runScraper(options) {
    const browser = new BrowserManager({
        headless: options.headless !== false,
        viewport: options.viewport,
        userAgent: options.user_agent,
        timeout: options.timeout || 30000,
        blockResources: options.block_resources || false,
        cookiesFile: options.cookies_file || null,
    });

    await browser.launch();

    // Initialize cookies from file if available
    if (options.cookies_file) {
        await browser.initializeCookies();
    }

    try {
        // Handle authentication if configured
        let authResult = { success: true };
        if (options.auth && options.auth.type !== 'none') {
            const auth = new AuthHandler(browser, options.auth);
            authResult = await auth.authenticate();

            if (!authResult.success) {
                return {
                    success: false,
                    error: `Authentication failed: ${authResult.message}`,
                    auth: authResult,
                };
            }
        }

        let result = {
            success: true,
            categories: [],
            products: [],
            pages: [],
            auth: authResult,
            stats: {
                started_at: new Date().toISOString(),
            },
        };

        const scraperOptions = {
            base_url: options.base_url,
            selectors: options.selectors || {},
            delay: options.delay || 1000,
            max_depth: options.max_depth || 5,
            urls: options.urls || [],
            start_url: options.start_url,
        };

        switch (options.command) {
            case 'scrape-categories':
                const categoryScraper = new CategoryScraper(browser, scraperOptions);
                result.categories = await categoryScraper.scrape();
                break;

            case 'scrape-products':
                const productScraper = new ProductScraper(browser, scraperOptions);
                result.products = await productScraper.scrape();
                break;

            case 'scrape-page':
                const pageScraper = new PageScraper(browser, scraperOptions);
                if (options.url) {
                    result.pages = [await pageScraper.scrape(options.url)];
                } else if (options.urls && options.urls.length > 0) {
                    result.pages = await pageScraper.scrapeMultiple(options.urls);
                }
                break;

            case 'full-scrape':
                // Sequential full scrape
                const catScraper = new CategoryScraper(browser, scraperOptions);
                result.categories = await catScraper.scrape();

                const prodScraper = new ProductScraper(browser, scraperOptions);
                result.products = await prodScraper.scrapeAll(result.categories);
                break;

            case 'test-auth':
                // Just test authentication
                result.auth_test = authResult;
                break;

            case 'probo-scrape':
                // Probo.nl specialized scraper
                const proboScraper = new ProboScraper(browser, options);
                const proboResult = await proboScraper.fullScrape(options.start_url || options.url);
                result.success = proboResult.success;
                result.products = proboResult.products || [];
                result.category = proboResult.category;
                if (proboResult.error) {
                    result.error = proboResult.error;
                }
                break;

            case 'probo-product':
                // Scrape single Probo product
                const proboProductScraper = new ProboScraper(browser, options);
                await proboProductScraper.authenticate();
                const product = await proboProductScraper.scrapeProductPage(options.url);
                result.products = product ? [product] : [];
                break;

            case 'probo-category':
                // Scrape Probo category listing
                const proboCatScraper = new ProboScraper(browser, options);
                await proboCatScraper.authenticate();
                const catResult = await proboCatScraper.scrapeCategory(options.url);
                result.category = catResult.category;
                result.product_urls = catResult.products;
                break;

            case 'probo-multi':
                // Scrape multiple Probo products for attribute discovery
                const proboMultiScraper = new ProboScraper(browser, options);
                const multiResult = await proboMultiScraper.scrapeMultipleProducts(options.urls || []);
                result.success = multiResult.success;
                result.products = multiResult.products || [];
                result.discovered_attributes = multiResult.discovered_attributes;
                result.stats = { ...result.stats, ...multiResult.stats };
                if (multiResult.error) {
                    result.error = multiResult.error;
                }
                break;

            case 'probo-all':
                // Scrape all Probo products from sitemap URLs
                const proboAllScraper = new ProboScraper(browser, options);
                const allResult = await proboAllScraper.scrapeAllProducts(options.urls || []);
                result.success = allResult.success;
                result.products = allResult.products || [];
                result.discovered_attributes = allResult.discovered_attributes;
                result.stats = { ...result.stats, ...allResult.stats };
                if (allResult.error) {
                    result.error = allResult.error;
                }
                break;

            default:
                throw new Error(`Unknown command: ${options.command}`);
        }

        result.stats.completed_at = new Date().toISOString();
        result.stats.categories_count = result.categories.length;
        result.stats.products_count = result.products.length;
        result.stats.pages_count = result.pages.length;

        return result;
    } finally {
        await browser.close();
    }
}
