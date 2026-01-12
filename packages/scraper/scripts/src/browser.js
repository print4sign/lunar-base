import { chromium } from 'playwright';
import * as fs from 'fs';
import * as path from 'path';

export class BrowserManager {
    constructor(options = {}) {
        this.options = options;
        this.browser = null;
        this.context = null;
        this.cookies = [];
        this.cookiesFile = options.cookiesFile || null;
    }

    async launch() {
        this.browser = await chromium.launch({
            headless: this.options.headless !== false,
        });

        this.context = await this.browser.newContext({
            viewport: this.options.viewport || { width: 1920, height: 1080 },
            userAgent: this.options.userAgent || 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            locale: this.options.locale || 'en-US',
            timezoneId: this.options.timezone || 'Europe/Amsterdam',
        });

        // Block unnecessary resources for faster scraping
        if (this.options.blockResources) {
            await this.context.route('**/*', (route) => {
                const resourceType = route.request().resourceType();
                if (['image', 'stylesheet', 'font', 'media'].includes(resourceType)) {
                    route.abort();
                } else {
                    route.continue();
                }
            });
        }
    }

    async newPage() {
        const page = await this.context.newPage();

        // Set default timeout (increased for slow sites)
        page.setDefaultTimeout(this.options.timeout || 60000);
        page.setDefaultNavigationTimeout(90000);

        return page;
    }

    async setCookies(cookies) {
        this.cookies = cookies;
        if (this.context && cookies.length > 0) {
            await this.context.addCookies(cookies);
        }
        // Persist cookies to file if configured
        if (this.cookiesFile && cookies.length > 0) {
            await this.saveCookiesToFile(cookies);
        }
    }

    async getCookies() {
        if (this.context) {
            return await this.context.cookies();
        }
        return this.cookies;
    }

    async loadCookiesFromFile() {
        if (!this.cookiesFile) {
            return [];
        }
        try {
            if (fs.existsSync(this.cookiesFile)) {
                const data = fs.readFileSync(this.cookiesFile, 'utf8');
                const cookies = JSON.parse(data);
                console.error(`Loaded ${cookies.length} cookies from ${this.cookiesFile}`);
                return cookies;
            }
        } catch (e) {
            console.error(`Error loading cookies from file: ${e.message}`);
        }
        return [];
    }

    async saveCookiesToFile(cookies) {
        if (!this.cookiesFile) {
            return;
        }
        try {
            // Ensure directory exists
            const dir = path.dirname(this.cookiesFile);
            if (!fs.existsSync(dir)) {
                fs.mkdirSync(dir, { recursive: true });
            }
            fs.writeFileSync(this.cookiesFile, JSON.stringify(cookies, null, 2));
            console.error(`Saved ${cookies.length} cookies to ${this.cookiesFile}`);
        } catch (e) {
            console.error(`Error saving cookies to file: ${e.message}`);
        }
    }

    async initializeCookies() {
        // Load cookies from file if available
        const savedCookies = await this.loadCookiesFromFile();
        if (savedCookies.length > 0) {
            // Filter out expired cookies
            const now = Date.now() / 1000;
            const validCookies = savedCookies.filter(c => !c.expires || c.expires > now);
            if (validCookies.length > 0) {
                await this.context.addCookies(validCookies);
                this.cookies = validCookies;
                console.error(`Initialized context with ${validCookies.length} valid cookies`);
                return true;
            }
        }
        return false;
    }

    async clearCookies() {
        // Clear cookies from context
        if (this.context) {
            await this.context.clearCookies();
        }
        this.cookies = [];

        // Delete cookies file if it exists
        if (this.cookiesFile && fs.existsSync(this.cookiesFile)) {
            try {
                fs.unlinkSync(this.cookiesFile);
                console.error(`Deleted cookies file: ${this.cookiesFile}`);
            } catch (e) {
                console.error(`Error deleting cookies file: ${e.message}`);
            }
        }
        console.error('Cleared all cookies');
    }

    async close() {
        if (this.browser) {
            await this.browser.close();
            this.browser = null;
            this.context = null;
        }
    }
}
