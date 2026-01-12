/**
 * Probo.nl specialized scraper
 * Handles Probo's Magento-based e-commerce structure
 */
export class ProboScraper {
    constructor(browser, options) {
        this.browser = browser;
        this.options = options;
        this.baseUrl = 'https://www.probo.nl';
        this.loginUrl = 'https://www.probo.nl/customer/account/login';
        this.delay = options.delay || 2000;
        this.products = [];
        this.categories = [];
        this.visitedUrls = new Set();
        // Track if cookies have been accepted to avoid repeated checks
        this.cookiesAccepted = false;
    }

    async authenticate() {
        // Always start fresh - clear any existing cookies to avoid stale session issues
        console.error('Clearing any existing cookies for fresh login...');
        await this.browser.clearCookies();
        this.cookiesAccepted = false;

        const page = await this.browser.newPage();

        try {
            console.error('Navigating to login page...');
            // Navigate to login page
            await page.goto(this.loginUrl, {
                waitUntil: 'domcontentloaded',
                timeout: 90000
            });
            await this.sleep(3000);

            // Check if we got redirected instead of reaching the login page
            let currentUrl = page.url();
            if (currentUrl.includes('_redirect=') || (currentUrl !== this.loginUrl && !currentUrl.includes('/login'))) {
                console.error(`Got redirected away from login page to: ${currentUrl}`);
                // Try navigating to login page again
                await page.goto(this.loginUrl, {
                    waitUntil: 'networkidle',
                    timeout: 90000
                });
                await this.sleep(3000);
                currentUrl = page.url();
                console.error(`After retry, now on: ${currentUrl}`);
            }

            // Wait for and dismiss any modal overlay that appears
            console.error('Waiting for modal overlay to appear...');
            await this.sleep(2000);

            // Handle Probo's cookie wall modal - the button is hidden by animation, use JS click
            try {
                console.error('Looking for cookie wall modal...');

                // Wait for the modal to appear and animate
                await this.sleep(4000);

                // Try to click "Alle cookies accepteren" button using JavaScript (bypasses visibility check)
                const clicked = await page.evaluate(() => {
                    // Find by data-bind attribute which is unique to this button
                    const acceptAllBtn = document.querySelector('button[data-bind="click: acceptAll"]');
                    if (acceptAllBtn) {
                        acceptAllBtn.click();
                        return 'acceptAll';
                    }
                    // Fallback: find by text content
                    const buttons = document.querySelectorAll('.probocookiewall button, .cookiewall__modal button');
                    for (const btn of buttons) {
                        if (btn.textContent.includes('Alle cookies accepteren')) {
                            btn.click();
                            return 'textMatch';
                        }
                    }
                    // Fallback: click primary button
                    const primaryBtn = document.querySelector('.probocookiewall .button--primary, .cookiewall__step .button--primary');
                    if (primaryBtn) {
                        primaryBtn.click();
                        return 'primary';
                    }
                    return null;
                });

                if (clicked) {
                    console.error(`Clicked cookie button via JS: ${clicked}`);
                    await this.sleep(2000);
                } else {
                    console.error('No cookie button found via JS');
                }
            } catch (e) {
                console.error('Cookie wall handling error:', e.message);
            }

            // Check if modal overlay is still visible
            const overlay = await page.$('.modals-overlay');
            if (overlay) {
                const isVisible = await overlay.isVisible().catch(() => false);
                if (isVisible) {
                    console.error('Modal overlay still visible, pressing Escape...');
                    await page.keyboard.press('Escape');
                    await this.sleep(1000);
                }
            }

            // Accept cookies if present
            await this.acceptCookies(page);
            await this.sleep(1000);

            // Check if already logged in (maybe via cookies loaded to context)
            const isLoggedIn = await this.checkLoggedIn(page);
            if (isLoggedIn) {
                console.error('Already logged in to Probo');
                return { success: true, message: 'Already logged in' };
            }

            // Fill in login form
            const { username, password } = this.options.auth?.credentials || {};

            if (!username || !password) {
                throw new Error('Username and password are required for Probo login');
            }

            console.error('Waiting for login form to be visible...');

            // Wait for page to be interactive (with timeout)
            try {
                await page.waitForLoadState('networkidle', { timeout: 30000 });
            } catch (e) {
                console.error('Network idle timeout, continuing anyway...');
            }
            await this.sleep(2000);

            // Try multiple selectors for the email field
            const emailSelectors = [
                '#email',
                'input[name="login[username]"]',
                'input[name="username"]',
                'input[type="email"]',
                '.field.email input',
                '#login-form input[type="email"]',
            ];

            let emailField = null;
            for (const selector of emailSelectors) {
                try {
                    emailField = await page.waitForSelector(selector, { timeout: 3000, state: 'visible' });
                    if (emailField) {
                        console.error(`Found email field with selector: ${selector}`);
                        break;
                    }
                } catch (e) {
                    continue;
                }
            }

            if (!emailField) {
                // Take a screenshot for debugging
                console.error('Could not find email field. Page HTML sample:');
                const bodyHtml = await page.$eval('body', el => el.innerHTML.substring(0, 2000)).catch(() => 'N/A');
                console.error(bodyHtml);
                throw new Error('Could not find login email field');
            }

            console.error('Filling login form...');

            // Clear and fill email field
            await emailField.click();
            await this.sleep(500);
            await emailField.fill(username);
            await this.sleep(500);

            // Find and fill password field
            const passwordSelectors = [
                '#pass',
                'input[name="login[password]"]',
                'input[name="password"]',
                'input[type="password"]',
                '.field.password input',
            ];

            let passwordField = null;
            for (const selector of passwordSelectors) {
                try {
                    passwordField = await page.$(selector);
                    if (passwordField) {
                        const isVisible = await passwordField.isVisible();
                        if (isVisible) {
                            console.error(`Found password field with selector: ${selector}`);
                            break;
                        }
                    }
                } catch (e) {
                    continue;
                }
            }

            if (!passwordField) {
                throw new Error('Could not find password field');
            }

            await passwordField.click();
            await this.sleep(500);
            await passwordField.fill(password);
            await this.sleep(500);

            // Find and click submit button
            const submitSelectors = [
                '#send2',
                'button[type="submit"]',
                '.action.login.primary',
                'button.action.login',
                '#login-form button[type="submit"]',
            ];

            let submitButton = null;
            for (const selector of submitSelectors) {
                try {
                    submitButton = await page.$(selector);
                    if (submitButton) {
                        const isVisible = await submitButton.isVisible();
                        if (isVisible) {
                            console.error(`Found submit button with selector: ${selector}`);
                            break;
                        }
                    }
                } catch (e) {
                    continue;
                }
            }

            if (!submitButton) {
                throw new Error('Could not find submit button');
            }

            console.error('Clicking login button...');
            await submitButton.click();

            // Wait for navigation with timeout
            try {
                await page.waitForLoadState('networkidle', { timeout: 30000 });
            } catch (e) {
                console.error('Network idle timeout after login click, continuing...');
            }
            await this.sleep(4000);

            // Debug: Check what's on the page after login click
            const pageUrl = page.url();
            console.error(`Current URL after login attempt: ${pageUrl}`);

            // Check for login error message first
            const errorMsg = await page.$eval('.message-error, .messages .error-msg, .message.error', el => el.textContent.trim()).catch(() => null);
            if (errorMsg) {
                console.error(`Login error message found: ${errorMsg}`);
                throw new Error(`Login failed: ${errorMsg}`);
            }

            // Look for definitive logged-in indicators
            const logoutLink = await page.$('a[href*="/customer/account/logout"]').catch(() => null);
            const customerWelcome = await page.$('.customer-welcome').catch(() => null);

            console.error(`Logout link found: ${logoutLink !== null}`);
            console.error(`Customer welcome found: ${customerWelcome !== null}`);

            const loginSuccess = logoutLink !== null || customerWelcome !== null;

            if (loginSuccess) {
                console.error('Successfully logged in to Probo');

                // If we're on a redirect URL, navigate to the actual dashboard to establish session
                const currentUrl = page.url();
                if (currentUrl.includes('_redirect=')) {
                    console.error('Detected redirect URL, navigating to dashboard to establish session...');
                    try {
                        await page.goto('https://www.probo.nl/customer/account/', {
                            waitUntil: 'domcontentloaded',
                            timeout: 30000
                        });
                        await this.sleep(2000);
                        console.error(`Now on: ${page.url()}`);
                    } catch (e) {
                        console.error('Dashboard navigation failed:', e.message);
                    }
                }

                // Save cookies for session persistence
                const cookies = await page.context().cookies();
                console.error(`Saved ${cookies.length} cookies from login session`);

                // Log all cookie names for debugging
                const cookieNames = cookies.map(c => `${c.name}=${c.value.substring(0, 20)}...`);
                console.error(`All cookies: ${JSON.stringify(cookieNames)}`);

                // Look for session/PHPSESSID cookie specifically
                const sessionCookie = cookies.find(c => c.name === 'PHPSESSID' || c.name.includes('session'));
                if (sessionCookie) {
                    console.error(`Session cookie found: ${sessionCookie.name}`);
                } else {
                    console.error('WARNING: No session cookie found!');
                }

                // Explicitly set cookies on browser manager for future pages
                await this.browser.setCookies(cookies);

                return { success: true, message: 'Form login successful', cookies: cookies.length };
            } else {
                console.error('No logout link or customer welcome found - login may have failed');
                throw new Error('Login failed - no logged-in indicators found after submission');
            }
        } finally {
            await page.close();
        }
    }

    async checkLoggedIn(page) {
        const currentUrl = page.url();
        console.error(`checkLoggedIn: current URL is ${currentUrl}`);

        // Primary check: look for 'logged-in' class on body element (most reliable)
        const hasLoggedInClass = await page.evaluate(() => {
            return document.body.classList.contains('logged-in');
        }).catch(() => false);

        if (hasLoggedInClass) {
            console.error('Detected logged-in class on body - user is logged in');
            return true;
        }

        // Check if redirected to dashboard (successful login) - NOT just /customer/account/login
        if (currentUrl.includes('/customer/account/') && !currentUrl.includes('/login') && !currentUrl.includes('/create')) {
            console.error('Detected successful login via URL redirect to account area');
            return true;
        }

        // Look for logout link - this is also a reliable indicator
        const logoutLink = await page.$('a[href*="/customer/account/logout"]').catch(() => null);
        if (logoutLink) {
            console.error('Detected logout link - user is logged in');
            return true;
        }

        // Look for customer welcome message in header
        const customerWelcome = await page.$('.customer-welcome').catch(() => null);
        if (customerWelcome) {
            console.error('Detected customer welcome - user is logged in');
            return true;
        }

        // Check if login form is present (indicates NOT logged in)
        const loginForm = await page.$('#login-form, .form-login').catch(() => null);
        if (loginForm) {
            console.error('Login form is present - user is NOT logged in');
            return false;
        }

        console.error('No definitive login indicators found - assuming not logged in');
        return false;
    }

    async acceptCookies(page) {
        // Skip if we've already accepted cookies in this session
        if (this.cookiesAccepted) {
            return;
        }

        try {
            console.error('Checking for cookie consent banners...');

            // Wait for potential cookie wall modal to appear (they often have a delay)
            console.error('Waiting for cookie modal to appear...');
            await this.sleep(2000);

            // Use JavaScript to click the cookie button (bypasses visibility issues caused by animations)
            const clicked = await page.evaluate(() => {
                // Check if cookie already accepted (no modal present)
                const cookieModal = document.querySelector('.probocookiewall, .cookiewall__modal');
                if (!cookieModal) {
                    return 'no_modal';
                }

                // Find by data-bind attribute which is unique to Probo's "accept all" button
                const acceptAllBtn = document.querySelector('button[data-bind="click: acceptAll"]');
                if (acceptAllBtn) {
                    acceptAllBtn.click();
                    return 'acceptAll';
                }

                // Fallback: find by text content
                const buttons = document.querySelectorAll('.probocookiewall button, .cookiewall__modal button, .cookiewall__step button');
                for (const btn of buttons) {
                    if (btn.textContent.includes('Alle cookies accepteren') || btn.textContent.includes('accepteren')) {
                        btn.click();
                        return 'textMatch';
                    }
                }

                // Fallback: click primary button in cookie wall
                const primaryBtn = document.querySelector('.probocookiewall .button--primary, .cookiewall__step .button--primary');
                if (primaryBtn) {
                    primaryBtn.click();
                    return 'primary';
                }

                return null;
            });

            if (clicked === 'no_modal') {
                console.error('No cookie modal present, already accepted');
                this.cookiesAccepted = true;
                return;
            } else if (clicked) {
                console.error(`Clicked cookie button via JS: ${clicked}`);
                this.cookiesAccepted = true;
                await this.sleep(1500);
                return;
            }

            // Fallback for non-Probo cookie banners
            const genericSelectors = [
                '#onetrust-accept-btn-handler',
                '.cc-accept',
                '.cookie-accept',
                '#accept-cookies',
                '.js-cookie-consent-agree',
                '[data-action="accept-cookies"]',
                '#CybotCookiebotDialogBodyLevelButtonLevelOptinAllowAll',
            ];

            for (const selector of genericSelectors) {
                try {
                    const btn = await page.$(selector);
                    if (btn) {
                        const isVisible = await btn.isVisible().catch(() => false);
                        if (isVisible) {
                            console.error(`Found generic cookie button: ${selector}`);
                            await btn.click();
                            this.cookiesAccepted = true;
                            await this.sleep(1500);
                            console.error('Cookie consent accepted');
                            return;
                        }
                    }
                } catch (e) {
                    continue;
                }
            }

            // No banner found means it's already accepted
            console.error('No cookie banner found or already accepted');
            this.cookiesAccepted = true;
        } catch (e) {
            console.error('Error handling cookies:', e.message);
        }
    }

    /**
     * Scrape a product page.
     */
    async scrapeProductPage(url) {
        let page = await this.browser.newPage();

        // Capture the raw HTML response before JavaScript execution
        let rawResponseHtml = null;
        page.on('response', async (response) => {
            try {
                if (response.url() === url && response.status() === 200) {
                    const contentType = response.headers()['content-type'] || '';
                    if (contentType.includes('text/html')) {
                        rawResponseHtml = await response.text();
                    }
                }
            } catch (e) {
                // Ignore errors from response handling
            }
        });

        try {
            console.error(`Scraping Probo product: ${url}`);
            await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 90000 });
            try {
                await page.waitForLoadState('networkidle', { timeout: 30000 });
            } catch (e) {
                console.error('Network idle timeout on product page, continuing...');
            }
            await this.sleep(this.delay);

            // Check if we got redirected to the dashboard redirect page
            let currentUrl = page.url();
            let redirectAttempts = 0;
            const maxRedirectAttempts = 3;

            while ((currentUrl.includes('_redirect=') || currentUrl === 'https://www.probo.nl/' || currentUrl === 'https://www.probo.nl') && redirectAttempts < maxRedirectAttempts) {
                redirectAttempts++;
                console.error(`Redirect attempt ${redirectAttempts}/${maxRedirectAttempts}: Detected redirect/home URL: ${currentUrl}`);

                // If we've tried twice, the cookies are likely stale - clear them and re-authenticate
                if (redirectAttempts >= 2) {
                    console.error('Redirect loop detected - clearing stale cookies and re-authenticating...');
                    await page.close();
                    await this.browser.clearCookies();

                    // Re-authenticate with fresh session
                    const authResult = await this.authenticate();
                    if (!authResult.success) {
                        throw new Error('Re-authentication after cookie clear failed: ' + (authResult.message || 'Unknown error'));
                    }

                    // Create new page and try again
                    page = await this.browser.newPage();
                    rawResponseHtml = null;
                    page.on('response', async (response) => {
                        try {
                            if (response.url() === url && response.status() === 200) {
                                const contentType = response.headers()['content-type'] || '';
                                if (contentType.includes('text/html')) {
                                    rawResponseHtml = await response.text();
                                }
                            }
                        } catch (e) {
                            // Ignore errors from response handling
                        }
                    });
                }

                // Navigate to the product page
                console.error(`Navigating to product page: ${url}`);
                await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 90000 });
                try {
                    await page.waitForLoadState('networkidle', { timeout: 30000 });
                } catch (e) {
                    console.error('Network idle timeout, continuing...');
                }
                await this.sleep(this.delay);

                currentUrl = page.url();
            }

            if (redirectAttempts >= maxRedirectAttempts && (currentUrl.includes('_redirect=') || !currentUrl.includes(new URL(url).pathname))) {
                throw new Error(`Failed to navigate to product page after ${maxRedirectAttempts} attempts. Stuck on: ${currentUrl}`);
            }

            // Accept cookies if needed
            await this.acceptCookies(page);

            // Verify we're still logged in by checking body.logged-in class
            let isLoggedIn = await this.checkLoggedIn(page);
            console.error(`Logged in status on product page: ${isLoggedIn}`);

            // If not logged in, re-authenticate and reload the page
            if (!isLoggedIn) {
                console.error('Not logged in on product page, re-authenticating...');
                await page.close();

                // Re-authenticate
                const authResult = await this.authenticate();
                if (!authResult.success) {
                    throw new Error('Re-authentication failed: ' + (authResult.message || 'Unknown error'));
                }

                // Create new page and navigate again
                const newPage = await this.browser.newPage();

                // Set up response listener again for the new page
                rawResponseHtml = null;
                newPage.on('response', async (response) => {
                    try {
                        if (response.url() === url && response.status() === 200) {
                            const contentType = response.headers()['content-type'] || '';
                            if (contentType.includes('text/html')) {
                                rawResponseHtml = await response.text();
                            }
                        }
                    } catch (e) {
                        // Ignore errors from response handling
                    }
                });

                await newPage.goto(url, { waitUntil: 'domcontentloaded', timeout: 90000 });
                try {
                    await newPage.waitForLoadState('networkidle', { timeout: 30000 });
                } catch (e) {
                    console.error('Network idle timeout on retry, continuing...');
                }
                await this.sleep(this.delay);

                // Verify login status after re-auth
                isLoggedIn = await this.checkLoggedIn(newPage);
                console.error(`Logged in status after re-auth: ${isLoggedIn}`);

                if (!isLoggedIn) {
                    await newPage.close();
                    throw new Error('Still not logged in after re-authentication');
                }

                const product = await this.extractProboProduct(newPage, url, rawResponseHtml);
                await newPage.close();
                return product;
            }

            const product = await this.extractProboProduct(page, url, rawResponseHtml);

            // Fetch additional product data from Probo API if we have an api_code
            if (product && product.api_code) {
                const apiData = await this.fetchProboApiProduct(product.api_code);
                if (apiData) {
                    product.translations = apiData.translations || {};
                    product.article_group_name = apiData.article_group_name || null;
                    product.api_active = apiData.active || false;
                    product.api_active_to = apiData.active_to || null;
                    product.api_replaced_by = apiData.replaced_by_product || null;

                    // Set translated names if available
                    if (apiData.translations) {
                        product.name_translations = {};
                        for (const [lang, trans] of Object.entries(apiData.translations)) {
                            product.name_translations[lang] = {
                                title: trans.title || '',
                                description: trans.description || '',
                            };
                        }
                    }
                }
            }

            return product;
        } finally {
            await page.close().catch(() => {}); // Ignore if already closed
        }
    }

    /**
     * Fetch product data from Probo API
     * @param {string} apiCode - The product API code (e.g., 'banner', 'doormat-with-border')
     * @returns {Object|null} - API response data or null on error
     */
    async fetchProboApiProduct(apiCode) {
        try {
            console.error(`Fetching Probo API data for: ${apiCode}`);

            const response = await fetch(`https://api.proboprints.com/products/product/${apiCode}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer MGJjMDQyODkwNDUxNDJhYmFmYjgxOWQzYTliMTg2ZDM6RjZFZDkxOGM2QTE4NGU2MjlDN0VhMDEwMjdlMDg3Y0Q=',
                },
            });

            if (!response.ok) {
                console.error(`Probo API error: ${response.status} ${response.statusText}`);
                return null;
            }

            const data = await response.json();
            console.error(`Probo API success: ${data.code} - translations: ${Object.keys(data.translations || {}).join(', ')}`);
            return data;
        } catch (error) {
            console.error(`Error fetching Probo API: ${error.message}`);
            return null;
        }
    }

    /**
     * Scrape all URLs from sitemap, detecting and scraping only product pages.
     * Logs in once, then processes each URL to check if it's a product page.
     */
    async scrapeAllProducts(urls) {
        console.error(`\n=== Starting Probo All Products Scrape ===`);
        console.error(`Total URLs to process: ${urls.length}`);

        // First authenticate once
        const authResult = await this.authenticate();
        if (!authResult.success) {
            return {
                success: false,
                error: authResult.message || 'Authentication failed',
            };
        }

        const products = [];
        const allAttributes = new Map();
        const stats = {
            total_urls: urls.length,
            products_scraped: 0,
            categories_skipped: 0,
            failed: 0,
            started_at: new Date().toISOString(),
        };

        for (let i = 0; i < urls.length; i++) {
            const url = urls[i];
            const progress = `[${i + 1}/${urls.length}]`;

            try {
                // Check if this is a product page
                const isProduct = await this.isProductPage(url);

                if (!isProduct) {
                    console.error(`${progress} SKIP (category): ${url}`);
                    stats.categories_skipped++;
                    continue;
                }

                console.error(`${progress} Scraping product: ${url}`);

                // Scrape the product (both languages)
                const product = await this.scrapeProductPage(url);

                if (product) {
                    products.push(product);
                    stats.products_scraped++;

                    // Collect unique attributes
                    const attrs = product.attributes || [];
                    for (const attr of attrs) {
                        if (attr.name && !allAttributes.has(attr.name)) {
                            allAttributes.set(attr.name, {
                                name: attr.name,
                                sample_value: attr.value,
                                found_in: url,
                            });
                        }
                    }

                    console.error(`${progress} OK: ${product.name} (${attrs.length} attrs)`);
                } else {
                    console.error(`${progress} FAILED: Could not extract product data`);
                    stats.failed++;
                }
            } catch (error) {
                console.error(`${progress} ERROR: ${url} - ${error.message}`);
                stats.failed++;
            }

            // Progress summary every 50 URLs
            if ((i + 1) % 50 === 0) {
                console.error(`\n--- Progress: ${i + 1}/${urls.length} URLs processed ---`);
                console.error(`    Products: ${stats.products_scraped}, Skipped: ${stats.categories_skipped}, Failed: ${stats.failed}`);
                console.error(`    Unique attributes discovered: ${allAttributes.size}\n`);
            }

            // Small delay between requests to be nice to the server
            await this.sleep(500);
        }

        stats.completed_at = new Date().toISOString();
        stats.skipped_count = stats.categories_skipped;
        stats.failed_count = stats.failed;

        console.error(`\n=== Scrape Complete ===`);
        console.error(`Products scraped: ${stats.products_scraped}`);
        console.error(`Categories skipped: ${stats.categories_skipped}`);
        console.error(`Failed: ${stats.failed}`);
        console.error(`Unique attributes: ${allAttributes.size}`);

        return {
            success: true,
            products,
            discovered_attributes: Array.from(allAttributes.values()),
            stats,
        };
    }

    /**
     * Quick check if a URL is a product page without fully scraping it.
     * Uses lightweight indicators to determine page type.
     */
    async isProductPage(url) {
        const page = await this.browser.newPage();

        try {
            await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });
            await this.sleep(1000);

            // Check for product-specific elements (fast check)
            const isProduct = await page.evaluate(() => {
                // Product page indicators
                const productIndicators = [
                    '.product-info-main',
                    '.product-info-price',
                    '.product-add-form',
                    'button#product-addtocart-button',
                    '[data-product-id]',
                    '.product-options-wrapper',
                    'form#product_addtocart_form',
                ];

                for (const selector of productIndicators) {
                    if (document.querySelector(selector)) {
                        return true;
                    }
                }

                // Category page indicators - if we see these, it's NOT a product
                const categoryIndicators = [
                    '.products-grid',
                    '.product-items',
                    '.category-products',
                ];

                for (const selector of categoryIndicators) {
                    if (document.querySelector(selector)) {
                        return false;
                    }
                }

                // If no clear indicators, check if there's a price display (product-like)
                const hasPrice = document.querySelector('.price-wrapper .price, .product-info-price');
                return !!hasPrice;
            });

            return isProduct;
        } catch (error) {
            console.error(`Error checking page type for ${url}: ${error.message}`);
            // If we can't load the page, assume it's not a product
            return false;
        } finally {
            await page.close();
        }
    }

    /**
     * Scrape multiple product URLs to discover all attribute types.
     * Useful for initial setup to understand the full attribute schema.
     */
    async scrapeMultipleProducts(urls) {
        console.error(`Scraping ${urls.length} products for attribute discovery...`);

        // First authenticate
        const authResult = await this.authenticate();
        if (!authResult.success) {
            return {
                success: false,
                error: authResult.message || 'Authentication failed',
            };
        }

        const products = [];
        const allAttributes = new Map(); // Track unique attributes across products

        for (const url of urls) {
            console.error(`\n--- Scraping product ${products.length + 1}/${urls.length}: ${url} ---`);

            try {
                const product = await this.scrapeProductPage(url);

                if (product) {
                    products.push(product);

                    // Collect unique attributes
                    const attrs = product.attributes || [];
                    for (const attr of attrs) {
                        if (attr.name && !allAttributes.has(attr.name)) {
                            allAttributes.set(attr.name, {
                                name: attr.name,
                                sample_value: attr.value,
                                found_in: url,
                            });
                        }
                    }

                    console.error(`Scraped: ${product.name} (${attrs.length} attributes)`);
                } else {
                    console.error(`Failed to scrape: ${url}`);
                }
            } catch (error) {
                console.error(`Error scraping ${url}: ${error.message}`);
            }

            // Small delay between products
            await this.sleep(1000);
        }

        // Log discovered attributes
        console.error(`\n=== Discovered ${allAttributes.size} unique attributes ===`);
        for (const [name, info] of allAttributes) {
            console.error(`  - ${name}: "${info.sample_value}" (from ${info.found_in})`);
        }

        return {
            success: true,
            products,
            discovered_attributes: Array.from(allAttributes.values()),
            stats: {
                total_products: products.length,
                total_attributes: allAttributes.size,
                scraped_at: new Date().toISOString(),
            },
        };
    }

    async extractProboProduct(page, url, rawResponseHtml = null) {
        try {
            // Extract product data from raw response HTML (captured before JS execution)
            // This is needed because x-magento-init scripts are processed and removed from DOM
            let extractedApiCode = null;
            let proboMenuLabel = null;
            let proboMenuDeliveryTime = null;
            let hidePricelist = null;
            let proboHasSample = null;

            const htmlSource = rawResponseHtml || await page.content();

            if (htmlSource) {
                // Extract probo_api_code
                const apiCodeMatch = htmlSource.match(/"probo_api_code"\s*:\s*"([a-z0-9-]+)"/i);
                if (apiCodeMatch) {
                    extractedApiCode = apiCodeMatch[1];
                    console.error(`Extracted probo_api_code: ${extractedApiCode}`);
                }

                // Extract probo_menu_label (e.g., "Nieuw", "Sale")
                const menuLabelMatch = htmlSource.match(/"probo_menu_label"\s*:\s*"([^"]+)"/i);
                if (menuLabelMatch) {
                    proboMenuLabel = menuLabelMatch[1];
                    console.error(`Extracted probo_menu_label: ${proboMenuLabel}`);
                }

                // Extract probo_menu_delivery_time (in hours, e.g., "120")
                const deliveryTimeMatch = htmlSource.match(/"probo_menu_delivery_time"\s*:\s*"(\d+)"/i);
                if (deliveryTimeMatch) {
                    proboMenuDeliveryTime = parseInt(deliveryTimeMatch[1], 10);
                    console.error(`Extracted probo_menu_delivery_time: ${proboMenuDeliveryTime}`);
                }

                // Extract hide_pricelist ("0" or "1")
                const hidePricelistMatch = htmlSource.match(/"hide_pricelist"\s*:\s*"([01])"/i);
                if (hidePricelistMatch) {
                    hidePricelist = hidePricelistMatch[1] === '1';
                    console.error(`Extracted hide_pricelist: ${hidePricelist}`);
                }

                // Extract probo_has_sample ("0" or "1")
                const hasSampleMatch = htmlSource.match(/"probo_has_sample"\s*:\s*"([01])"/i);
                if (hasSampleMatch) {
                    proboHasSample = hasSampleMatch[1] === '1';
                    console.error(`Extracted probo_has_sample: ${proboHasSample}`);
                }
            } else {
                console.error('No HTML source available for extraction');
            }

            // Log the product header content
            const headerContent = await page.$eval('header.flex.flex-column', el => ({
                html: el.outerHTML,
                h1: el.querySelector('h1')?.textContent?.trim(),
                subtitle: el.querySelector('span.text-14')?.textContent?.trim(),
            })).catch(() => null);

            if (headerContent) {
                console.error('=== Product Header Content ===');
                console.error(`H1: ${headerContent.h1}`);
                console.error(`Subtitle: ${headerContent.subtitle}`);
            } else {
                console.error('Could not find header.flex.flex-column element');
            }

            // Product name - try multiple selectors
            let name = null;
            const nameSelectors = ['.page-title span', 'h1.product-name', 'h1.page-title', 'h1'];
            for (const selector of nameSelectors) {
                name = await page.$eval(selector, el => el.textContent.trim()).catch(() => null);
                if (name) {
                    console.error(`Found product name with selector: ${selector}`);
                    break;
                }
            }

            if (!name) {
                console.error('Could not find product name');
                return null;
            }

            // Menu passport label - subtitle under the product name (e.g., "In verschillende modellen en formaten")
            const menuPassportLabel = await page.$eval('header.flex.flex-column span.text-14.text-gy-60', el => el.textContent.trim()).catch(() => null);
            if (menuPassportLabel) {
                console.error(`Found menu_passport_label: ${menuPassportLabel}`);
            }

            // SKU / Article number - try multiple sources
            let sku = await page.$eval('.product.attribute.sku .value, .sku-value', el => el.textContent.trim()).catch(() => null);

            // Try to get SKU from form attribute
            if (!sku) {
                sku = await page.$eval('[data-product-sku]', el => el.dataset.productSku).catch(() => null);
            }

            // Try to get from URL pattern
            if (!sku) {
                const urlPath = new URL(url).pathname.replace(/^\/|\/$/g, '');
                if (urlPath) {
                    sku = urlPath.toUpperCase().replace(/-/g, '-');
                }
            }

            console.error(`Extracted SKU: ${sku}`);

            // Price - Probo shows prices for logged-in customers
            const priceText = await page.$eval('.price-wrapper .price, .product-info-price .price', el => el.textContent.trim()).catch(() => null);
            const price = this.parsePrice(priceText);

            // Description - try Probo specific selector first, then fallback
            let description = await page.$eval('#omschrijving .product__description', el => el.innerHTML).catch(() => null);
            if (!description) {
                description = await page.$eval('.product.attribute.description .value, .product-description', el => el.innerHTML).catch(() => null);
            }

            // Short description - first try the USP checkmark list, then fallback
            let shortDescription = null;

            // Try to get USPs from the checkmark list (mt-24 section with green checkmarks)
            const usps = await page.$$eval('.mt-24.text-gy-100 .flex.flex-row.gap-8 p', els =>
                els.map(el => el.textContent.trim()).filter(Boolean)
            ).catch(() => []);

            if (usps.length > 0) {
                shortDescription = usps.join(' | ');
            } else {
                // Fallback to standard short description
                shortDescription = await page.$eval('.product.attribute.overview .value, .short-description', el => el.textContent.trim()).catch(() => null);
            }

            // Pros and Cons (Plus- en minpunten) - extract from the dedicated section
            const prosAndCons = await page.evaluate(() => {
                const pros = [];
                const cons = [];

                // Find the pros/cons container - look for the "Plus- en minpunten" heading
                const containers = document.querySelectorAll('.flex.flex-column');
                let prosConsContainer = null;

                for (const container of containers) {
                    const heading = container.querySelector('h3');
                    if (heading && heading.textContent.includes('Plus- en minpunten')) {
                        prosConsContainer = container;
                        break;
                    }
                }

                if (!prosConsContainer) {
                    return { pros: [], cons: [] };
                }

                // Find all items with icons
                const items = prosConsContainer.querySelectorAll('.flex.flex-row.mb-8');

                for (const item of items) {
                    const iconSpan = item.querySelector('span.flex');
                    const textSpan = item.querySelector('span.ml-8');

                    if (!iconSpan || !textSpan) continue;

                    const text = textSpan.textContent.trim();
                    if (!text) continue;

                    // Check if it's a pro (green icon with plus - text-gn-100) or con (gray icon with minus - text-gy-30)
                    if (iconSpan.classList.contains('text-gn-100')) {
                        pros.push(text);
                    } else if (iconSpan.classList.contains('text-gy-30')) {
                        cons.push(text);
                    }
                }

                return { pros, cons };
            }).catch(() => ({ pros: [], cons: [] }));

            // Images - extract from the slider with alt text from .image-alt-tag span
            let images = await page.$$eval('[data-component-slider="content_article_slider"] .slick-slide:not(.slick-cloned)', els =>
                els.map(el => ({
                    url: el.querySelector('img')?.src || el.querySelector('img')?.dataset?.lazy || '',
                    alt: el.querySelector('.image-alt-tag span')?.textContent.trim() || el.querySelector('img')?.alt || '',
                })).filter(img => img.url)
            ).catch(() => []);

            // Fallback: try gallery-placeholder images
            if (images.length === 0) {
                images = await page.$$eval('.gallery-placeholder img, .product.media img, .fotorama__img', els =>
                    els.map(el => ({
                        url: el.src || el.dataset.src || el.dataset.fullSrc,
                        alt: el.alt || '',
                    })).filter(img => img.url)
                ).catch(() => []);
            }

            // Main product image
            const mainImage = await page.$eval('.product.media .gallery-placeholder img, .fotorama__stage img', el => ({
                url: el.src || el.dataset.src,
                alt: el.alt || '',
            })).catch(() => null);

            if (mainImage?.url && !images.some(img => img.url === mainImage.url)) {
                images.unshift(mainImage);
            }

            // Fallback: extract images from Fotorama gallery data (includes caption as alt)
            const fotoramaImages = await page.$$eval('[data-gallery-role="gallery-placeholder"] script[type="text/x-magento-init"]', els => {
                const result = [];
                for (const el of els) {
                    try {
                        const data = JSON.parse(el.textContent);
                        const galleryData = data['[data-gallery-role=gallery-placeholder]']?.['mage/gallery/gallery']?.data;
                        if (galleryData) {
                            for (const item of galleryData) {
                                const url = item.full || item.img;
                                if (url) {
                                    result.push({
                                        url,
                                        alt: item.caption || item.alt || '',
                                    });
                                }
                            }
                        }
                    } catch (e) {}
                }
                return result;
            }).catch(() => []);

            if (fotoramaImages.length > 0) {
                // Merge, avoiding duplicates by URL
                const existingUrls = new Set(images.map(img => img.url));
                for (const img of fotoramaImages) {
                    if (!existingUrls.has(img.url)) {
                        images.push(img);
                        existingUrls.add(img.url);
                    }
                }
            }

            // Fallback: extract from HTML using regex for product media (no alt available)
            if (images.length === 0) {
                const htmlForImages = await page.$eval('#maincontent', el => el.innerHTML).catch(() => '');
                const matches = htmlForImages.match(/https?:\/\/[^"'\s>]+\/media\/catalog\/product[^"'\s>]+\.(jpg|jpeg|png|webp)/gi);
                if (matches) {
                    const uniqueUrls = [...new Set(matches.filter(url => !url.includes('loader') && !url.includes('placeholder')))];
                    images = uniqueUrls.map(url => ({ url, alt: '' }));
                }
            }

            // Breadcrumbs for category info
            const breadcrumbs = await page.$$eval('.breadcrumbs a, .breadcrumb a', els =>
                els.map(el => ({
                    name: el.textContent.trim(),
                    url: el.href,
                }))
            ).catch(() => []);

            // SEO meta tags
            const seo = await page.evaluate(() => ({
                title: document.querySelector('meta[name="title"]')?.content || document.querySelector('title')?.textContent || '',
                description: document.querySelector('meta[name="description"]')?.content || '',
                keywords: document.querySelector('meta[name="keywords"]')?.content || '',
                og_title: document.querySelector('meta[property="og:title"]')?.content || '',
                og_description: document.querySelector('meta[property="og:description"]')?.content || '',
            })).catch(() => ({}));

            // Stock/availability
            const availability = await page.$eval('.stock.available span, .availability', el => el.textContent.trim()).catch(() => 'Unknown');

            // Delivery info (Probo specific) - extract from the delivery box
            const deliveryInfo = await page.$eval('.flex.w-100.mt-16 .border-gy-10', el => {
                const title = el.querySelector('h4')?.textContent.trim() || '';
                const subtitle = el.querySelector('p')?.textContent.trim() || '';
                return { title, subtitle, html: el.innerHTML };
            }).catch(() => null);

            // Product benefits (Probo specific)
            const productBenefits = await page.$$eval('#product-benefits .shortdescription li', els =>
                els.map(el => el.textContent.trim()).filter(Boolean)
            ).catch(() => []);

            // Extract product ID from page
            const productId = await page.$eval('[data-product-id], input[name="product"]', el =>
                el.dataset.productId || el.value
            ).catch(() => null);

            // Use the API code we extracted from raw HTML at the start of this method
            // (extractedApiCode was set above before Magento JS could remove the scripts)

            // Full HTML content of main content area
            const htmlContent = await page.$eval('#maincontent', el => el.innerHTML).catch(() => null);

            // Also get the form HTML specifically
            const formHtml = await page.$eval('.product-add-form form, #product_addtocart_form', el => el.outerHTML).catch(() => null);

            // Specificaties section HTML (Probo specific) - will be parsed later during import
            // Wait for specifications to fully load (they may be rendered dynamically by Knockout.js)
            try {
                // First check if the section exists
                const specSection = await page.$('#specificaties');
                if (specSection) {
                    // Wait for tables inside specifications to render
                    await page.waitForSelector('#specificaties table', { timeout: 5000 }).catch(() => {
                        console.error('No table found in #specificaties, continuing...');
                    });

                    // Wait for the Knockout.js comparison table to fully render
                    // The table has data-bind="scope: 'probo_product_attributes'" and contains dynamic rows
                    await page.waitForSelector('#specificaties .compare-product-attributes tbody tr', { timeout: 8000 }).catch(() => {
                        console.error('No comparison table rows found, continuing...');
                    });

                    // Wait for all attribute values to be populated (Knockout.js renders them)
                    // Check that at least some attribute values have content
                    let attempts = 0;
                    const maxAttempts = 10;
                    while (attempts < maxAttempts) {
                        const hasContent = await page.evaluate(() => {
                            const valueSpans = document.querySelectorAll('#specificaties [fdy-see="product-attribute-value"]');
                            if (valueSpans.length === 0) return false;
                            // Check if at least 80% of values have content
                            let filledCount = 0;
                            valueSpans.forEach(span => {
                                if (span.innerHTML.trim().length > 0) filledCount++;
                            });
                            return filledCount >= valueSpans.length * 0.8;
                        });

                        if (hasContent) {
                            console.error(`Specifications content loaded after ${attempts + 1} attempts`);
                            break;
                        }
                        attempts++;
                        await this.sleep(500);
                    }

                    // Additional wait for any remaining dynamic content
                    await this.sleep(1000);
                }
            } catch (e) {
                console.error('Error waiting for specifications:', e.message);
            }
            const specificatiesHtml = await page.$eval('#specificaties', el => el.innerHTML).catch(() => null);

            // FAQ section (Probo specific) - extract questions and answers
            const faq = await page.$$eval('#veelgestelde_vragen .faq-item', els =>
                els.map(el => ({
                    question: el.querySelector('[itemprop="name"]')?.textContent.trim() || '',
                    answer: el.querySelector('[itemprop="text"]')?.textContent.trim() || '',
                })).filter(item => item.question && item.answer)
            ).catch(() => []);

            // Pinterest inspiration board URL (Probo specific)
            const pinterestUrl = await page.evaluate(() => {
                // Try multiple selectors for Pinterest embed
                const selectors = [
                    'a[data-pin-do="embedBoard"]',
                    '[data-type="pin"] a[href*="pinterest.com"]',
                    'a[href*="pinterest.com/printspiratie"]',
                    '.pinterest-embed a',
                ];
                for (const selector of selectors) {
                    const el = document.querySelector(selector);
                    if (el && el.href) {
                        return el.href;
                    }
                }
                return null;
            }).catch(() => null);

            if (pinterestUrl) {
                console.error(`Found Pinterest URL: ${pinterestUrl}`);
            }

            // Option alerts (Probo specific) - messages like size constraints, contact info, etc.
            const optionAlerts = await page.$$eval('.msg--alert', els =>
                els.map(el => el.textContent.trim()).filter(Boolean)
            ).catch(() => []);

            // Downloads (Probo specific) - product documents like mock ups, certificates, manuals
            const downloads = await page.$$eval('#product-documents a[data-track="download"]', els =>
                els.map(el => ({
                    title: el.querySelector('span')?.textContent.trim() || el.getAttribute('data-track-title') || '',
                    url: el.href || '',
                })).filter(item => item.title && item.url)
            ).catch(() => []);

            // Tier pricing above (Probo specific) - "Vanafprijs" pricing displayed at top of product page
            // Wait for Knockout.js to render the tier pricing
            await this.sleep(2000);
            const tierPricingAbove = await page.$eval('#tierprice-above', el => {
                // Get the material name from "Vanafprijs voor: [material]"
                const materialEl = el.querySelector('.truncate, [data-bind*="Vanafprijs"]');
                let materialName = materialEl?.textContent.trim() || '';
                // Clean up the "Vanafprijs voor: " prefix
                materialName = materialName.replace('Vanafprijs voor: ', '').trim();

                // Get the unit from the HTML (e.g., "m²")
                const unitEl = el.querySelector('[data-bind*="getTierUnitCode"]');
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = unitEl?.innerHTML || '';
                const unit = tempDiv.textContent.replace(/\s|&nbsp;/g, '').trim() || 'm²';

                // Extract tier prices from the tier blocks - they are direct children with flex-column class
                const tierBlocks = el.querySelectorAll('.flex.flex-row[data-bind*="foreach"] > .flex.flex-column');
                const tiers = [];

                tierBlocks.forEach(block => {
                    // Get the quantity/tier value
                    const tierValueEl = block.querySelector('[data-bind*="tierDisplay"]');
                    const tierValue = tierValueEl?.textContent.trim() || '';

                    // Get the price - it's split into integer and decimal parts
                    // Integer part has the period at the end (e.g., "13.")
                    // Decimal part is padded (e.g., "95")
                    const priceIntEl = block.querySelector('[data-bind*="split(\'.\')[0]"]');
                    const priceDecEl = block.querySelector('[data-bind*="padEnd"]');

                    if (tierValue && priceIntEl) {
                        const priceInt = priceIntEl.textContent.replace('.', '').trim();
                        const priceDec = priceDecEl?.textContent.trim() || '00';
                        const price = parseFloat(`${priceInt}.${priceDec}`);

                        tiers.push({
                            quantity: tierValue.replace(',', '.'),
                            price: price,
                            unit: unit,
                        });
                    }
                });

                return {
                    material: materialName,
                    unit: unit,
                    tiers: tiers,
                };
            }).catch(() => null);

            // Tier pricing below (Probo specific) - volume-based pricing tiers at bottom of product page
            const tierPricing = await page.$eval('#tierprice-below', el => {
                // Get the material name
                const materialName = el.querySelector('.truncate')?.textContent.replace('Vanafprijs voor: ', '').trim() || '';

                // Get the unit from the HTML (e.g., "m²")
                const unitEl = el.querySelector('[data-bind*="getTierUnitCode"]');
                const unit = unitEl?.textContent.replace(/\s|&nbsp;/g, '').trim() || 'm²';

                // Extract tier prices from the tier blocks
                const tierBlocks = el.querySelectorAll('[data-bind*="foreach"] > div');
                const tiers = [];

                tierBlocks.forEach(block => {
                    // Get the quantity/tier value
                    const tierValueEl = block.querySelector('[data-bind="tierDisplay:tier"]');
                    const tierValue = tierValueEl?.textContent.trim() || '';

                    // Get the price parts (integer and decimal)
                    const priceIntEl = block.querySelector('[data-bind*="split(\'.\')[0]"]');
                    const priceDecEl = block.querySelector('[data-bind*="split(\'.\')[1]"]');

                    if (tierValue && priceIntEl) {
                        const priceInt = priceIntEl.textContent.replace('.', '').trim();
                        const priceDec = priceDecEl?.textContent.trim() || '00';
                        const price = parseFloat(`${priceInt}.${priceDec}`);

                        tiers.push({
                            quantity: tierValue.replace(',', '.'),
                            price: price,
                            unit: unit,
                        });
                    }
                });

                return {
                    material: materialName,
                    unit: unit,
                    tiers: tiers,
                };
            }).catch(() => null);

            // Uitgelicht / Featured articles (Probo specific) - related help articles
            const featuredArticles = await page.$$eval('#uitgelicht .flex.flex-row.nested-img-hover-zoom', els =>
                els.map(el => {
                    // Get the title link (has hover:underline class)
                    const linkEl = el.querySelector('a.hover\\:underline, a[class*="hover:underline"]');
                    const imageEl = el.querySelector('img');
                    // Get the category link (has text-gy-60 class)
                    const categoryEl = el.querySelector('a[class*="text-gy-60"]');

                    return {
                        title: linkEl?.textContent.trim() || '',
                        url: linkEl?.href || '',
                        image: imageEl?.src || '',
                        category: categoryEl?.textContent.trim() || '',
                        category_url: categoryEl?.href || '',
                    };
                }).filter(item => item.title && item.url)
            ).catch(() => []);

            // Product notifications (Probo specific) - promotional/info banners at top of product
            const notifications = await page.$$eval('#product-notifications [data-testid="product-notification"]', els =>
                els.map(el => {
                    const messageEl = el.querySelector('[data-bind*="notification.message"]');
                    const message = messageEl?.textContent.trim() || el.textContent.trim();
                    // Extract type from background color class (e.g., bg-or-10 -> orange, bg-gr-10 -> green)
                    const bgClass = el.className.match(/bg-(\w+)-\d+/)?.[1] || '';
                    const typeMap = { 'or': 'warning', 'gr': 'success', 'bl': 'info', 'rd': 'error' };
                    const type = typeMap[bgClass] || 'info';
                    return { message, type };
                }).filter(n => n.message)
            ).catch(() => []);

            // Pricelist table (Probo specific) - dynamic pricing table rendered by Knockout.js
            // Wait for the pricelist table to be fully rendered
            let pricelistTable = null;
            try {
                console.error('Waiting for pricelist table to render...');
                await this.sleep(3000); // Wait for JS to render the table

                // Check if pricelisttable exists
                const pricelistExists = await page.$('.pricelisttable');
                if (pricelistExists) {
                    // Wait for rows to be populated
                    await page.waitForSelector('.pricelisttable .row .cell.price', { timeout: 10000 }).catch(() => {
                        console.error('Pricelist table prices not found, continuing...');
                    });

                    // Additional wait for all data to populate
                    await this.sleep(2000);

                    pricelistTable = await page.evaluate(() => {
                        const table = document.querySelector('.pricelisttable');
                        if (!table) return null;

                        const result = {
                            tiers: [],
                            unit: '',
                            materials: []
                        };

                        // Get the header row with tier quantities
                        const headerRow = table.querySelector('.row:first-child');
                        if (headerRow) {
                            const tierCells = headerRow.querySelectorAll('.cell.price');
                            tierCells.forEach(cell => {
                                const tierValue = cell.querySelector('[data-bind*="tierDisplay"]')?.textContent.trim() || '';
                                const unitHtml = cell.querySelector('[data-bind*="getTierUnitCode"]')?.innerHTML || '';
                                if (tierValue) {
                                    result.tiers.push(tierValue.replace(',', '.'));
                                }
                                if (!result.unit && unitHtml) {
                                    // Clean up unit - extract text content
                                    const tempDiv = document.createElement('div');
                                    tempDiv.innerHTML = unitHtml;
                                    result.unit = tempDiv.textContent.trim();
                                }
                            });
                        }

                        // Get each material row
                        const materialRows = table.querySelectorAll('.row.border-t-1');
                        materialRows.forEach(row => {
                            const materialName = row.querySelector('.cell.name')?.textContent.trim() || '';
                            if (!materialName) return;

                            const prices = [];
                            const priceCells = row.querySelectorAll('.cell.price');

                            priceCells.forEach(cell => {
                                const priceEl = cell.querySelector('[data-bind*="priceFormat"]');
                                const discountEl = cell.querySelector('.text-rd-100');

                                let priceText = priceEl?.textContent.trim() || '-';
                                let discount = null;

                                if (discountEl) {
                                    const discountMatch = discountEl.textContent.match(/-(\d+)%/);
                                    if (discountMatch) {
                                        discount = parseInt(discountMatch[1]);
                                    }
                                }

                                // Parse price from European format (€ 33,76 -> 33.76)
                                let priceValue = null;
                                if (priceText && priceText !== '-') {
                                    const cleanPrice = priceText.replace(/[€\s]/g, '').replace('.', '').replace(',', '.');
                                    priceValue = parseFloat(cleanPrice);
                                    if (isNaN(priceValue)) priceValue = null;
                                }

                                prices.push({
                                    price: priceValue,
                                    price_formatted: priceText,
                                    discount: discount
                                });
                            });

                            result.materials.push({
                                name: materialName,
                                prices: prices
                            });
                        });

                        return result;
                    });

                    if (pricelistTable && pricelistTable.materials.length > 0) {
                        console.error(`Extracted pricelist with ${pricelistTable.materials.length} materials and ${pricelistTable.tiers.length} tiers`);
                    } else {
                        console.error('Pricelist table found but no data extracted');
                        pricelistTable = null;
                    }
                } else {
                    console.error('No pricelist table found on page');
                }
            } catch (e) {
                console.error('Error extracting pricelist table:', e.message);
            }

            // Generate external ID
            const externalId = productId || this.generateExternalId(url);

            return {
                external_id: externalId,
                api_code: extractedApiCode,  // Probo API code (e.g., 'doormat-with-border')
                supplier: 'probo',
                name,
                menu_passport_label: menuPassportLabel, // Subtitle under product name (e.g., "In verschillende modellen en formaten")
                menu_label: proboMenuLabel, // Product label (e.g., "Nieuw", "Sale")
                menu_delivery_time: proboMenuDeliveryTime, // Delivery time in hours (e.g., 120)
                hide_pricelist: hidePricelist, // Whether to hide pricelist (boolean)
                has_sample: proboHasSample, // Whether product has sample available (boolean)
                url,
                sku,
                description,
                short_description: shortDescription,
                price: price?.value,
                currency: 'EUR',
                images, // Array of {url, alt} objects
                seo,
                breadcrumbs,
                availability,
                delivery_info: deliveryInfo,
                product_benefits: productBenefits,
                pros_and_cons: prosAndCons, // { pros: [], cons: [] }
                html_content: this.sanitizeForJson(htmlContent),
                form_html: this.sanitizeForJson(formHtml),
                specificaties_html: this.sanitizeForJson(specificatiesHtml),
                faq,
                pinterest_url: pinterestUrl,
                option_alerts: optionAlerts,
                downloads,
                tier_pricing_above: tierPricingAbove, // "Vanafprijs" tier pricing at top of product page
                tier_pricing: tierPricing, // Tier pricing at bottom of product page
                pricelist_table: pricelistTable, // Full pricing table with all materials and tier quantities
                featured_articles: featuredArticles, // Uitgelicht - related help articles
                notifications,
                meta: {
                    scraped_at: new Date().toISOString(),
                    source: 'probo.nl',
                    product_id: productId,
                    api_code: extractedApiCode,  // Also in meta for backwards compatibility
                },
            };
        } catch (error) {
            console.error(`Error extracting Probo product:`, error.message);
            return null;
        }
    }

    async extractProboOptions(page) {
        const options = [];

        try {
            // Probo uses configurable products with dropdowns
            const optionContainers = await page.$$('.swatch-attribute, .product-options-wrapper .field');

            for (const container of optionContainers) {
                const label = await container.$eval('.swatch-attribute-label, label', el => el.textContent.trim()).catch(() => null);

                if (!label) continue;

                // Get option values
                const values = await container.$$eval('.swatch-option, select option, input[type="radio"]', els =>
                    els.map(el => ({
                        value: el.value || el.dataset.optionId || el.textContent?.trim(),
                        label: el.title || el.textContent?.trim() || el.getAttribute('aria-label'),
                        image: el.dataset.thumbImage || el.style?.backgroundImage?.match(/url\(['"]?(.+?)['"]?\)/)?.[1],
                        price_adjustment: el.dataset.priceAmount || null,
                    })).filter(v => v.value)
                ).catch(() => []);

                if (values.length > 0) {
                    options.push({
                        name: label,
                        type: 'select',
                        values,
                        required: true,
                    });
                }
            }

            // Check for quantity-based pricing (Probo specific)
            const tierPricing = await page.$$eval('.prices-tier li, .tier-price', els =>
                els.map(el => {
                    const text = el.textContent;
                    const qtyMatch = text.match(/(\d+)\s*(?:stuks?|pcs?|pieces?)/i);
                    const priceMatch = text.match(/€\s*([\d,.]+)/);
                    return {
                        quantity: qtyMatch ? parseInt(qtyMatch[1]) : null,
                        price: priceMatch ? parseFloat(priceMatch[1].replace(',', '.')) : null,
                    };
                }).filter(tp => tp.quantity && tp.price)
            ).catch(() => []);

            if (tierPricing.length > 0) {
                options.push({
                    name: 'Quantity Pricing',
                    type: 'tier_pricing',
                    values: tierPricing,
                });
            }
        } catch (error) {
            console.error('Error extracting options:', error.message);
        }

        return options;
    }

    async scrapeCategory(url) {
        const page = await this.browser.newPage();
        const products = [];

        try {
            console.error(`Scraping Probo category: ${url}`);
            await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 90000 });
            try {
                await page.waitForLoadState('networkidle', { timeout: 30000 });
            } catch (e) {
                console.error('Network idle timeout on category page, continuing...');
            }
            await this.sleep(this.delay);

            await this.acceptCookies(page);

            // Get category info
            const categoryName = await page.$eval('.page-title span, h1', el => el.textContent.trim()).catch(() => 'Unknown');
            const categoryDescription = await page.$eval('.category-description', el => el.textContent.trim()).catch(() => null);

            // Find all product links on the page
            let hasMore = true;
            let currentPage = 1;

            while (hasMore) {
                const productLinks = await page.$$eval('.product-item a.product-item-link, .products-grid .product-item-info a', els =>
                    [...new Set(els.map(el => el.href).filter(href => href && !href.includes('#')))]
                ).catch(() => []);

                console.error(`Found ${productLinks.length} products on page ${currentPage}`);

                for (const productUrl of productLinks) {
                    if (!this.visitedUrls.has(productUrl)) {
                        this.visitedUrls.add(productUrl);
                        products.push({
                            url: productUrl,
                            category: categoryName,
                        });
                    }
                }

                // Check for next page
                const nextPage = await page.$('.pages-item-next a, a.next').catch(() => null);
                if (nextPage) {
                    await nextPage.click();
                    await page.waitForLoadState('networkidle');
                    await this.sleep(this.delay);
                    currentPage++;
                } else {
                    hasMore = false;
                }

                // Safety limit
                if (currentPage > 50) {
                    console.error('Reached maximum page limit');
                    break;
                }
            }

            return {
                category: {
                    name: categoryName,
                    url,
                    description: categoryDescription,
                },
                products,
            };
        } finally {
            await page.close();
        }
    }

    async fullScrape(startUrl) {
        console.error('Starting full Probo scrape...');

        // First authenticate
        const authResult = await this.authenticate();
        if (!authResult.success) {
            return {
                success: false,
                error: authResult.message || 'Authentication failed',
            };
        }

        // Verify authentication persists by checking cookies
        const contextCookies = await this.browser.getCookies();
        console.error(`Auth cookies in context: ${contextCookies.length}`);

        // Detect page type (product vs category)
        const pageType = await this.detectPageType(startUrl);
        console.error(`Detected page type: ${pageType}`);

        if (pageType === 'product') {
            // Scrape single product
            const product = await this.scrapeProductPage(startUrl);
            return {
                success: true,
                category: {
                    name: product?.breadcrumbs?.[0]?.name || 'Direct Product',
                    url: startUrl,
                },
                products: product ? [product] : [],
                stats: {
                    total_products: product ? 1 : 0,
                    page_type: 'product',
                    scraped_at: new Date().toISOString(),
                },
            };
        }

        // Scrape the starting category
        const categoryResult = await this.scrapeCategory(startUrl);

        // Scrape each product
        const products = [];
        for (const productInfo of categoryResult.products) {
            const product = await this.scrapeProductPage(productInfo.url);
            if (product) {
                product.category = productInfo.category;
                products.push(product);
            }

            // Progress indicator
            if (products.length % 10 === 0) {
                console.error(`Scraped ${products.length}/${categoryResult.products.length} products`);
            }
        }

        return {
            success: true,
            category: categoryResult.category,
            products,
            stats: {
                total_products: products.length,
                page_type: 'category',
                scraped_at: new Date().toISOString(),
            },
        };
    }

    async detectPageType(url) {
        const page = await this.browser.newPage();

        try {
            console.error(`Detecting page type for: ${url}`);
            await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 90000 });
            try {
                await page.waitForLoadState('networkidle', { timeout: 30000 });
            } catch (e) {
                console.error('Network idle timeout during page type detection, continuing...');
            }
            await this.sleep(2000);

            // Accept cookies if present
            await this.acceptCookies(page);

            // Check for product-specific elements
            const productIndicators = [
                '.product-info-main',
                '.product-info-price',
                '.product-add-form',
                'button#product-addtocart-button',
                '[data-product-id]',
                '.product-options-wrapper',
                '.swatch-opt',
            ];

            for (const selector of productIndicators) {
                const element = await page.$(selector);
                if (element) {
                    console.error(`Found product indicator: ${selector}`);
                    return 'product';
                }
            }

            // Check for category/listing indicators
            const categoryIndicators = [
                '.products-grid',
                '.product-items',
                '.product-list',
                '.category-products',
                '.products.wrapper',
            ];

            for (const selector of categoryIndicators) {
                const element = await page.$(selector);
                if (element) {
                    console.error(`Found category indicator: ${selector}`);
                    return 'category';
                }
            }

            // Default to product if we can't determine
            console.error('Could not determine page type, defaulting to product');
            return 'product';
        } finally {
            await page.close();
        }
    }

    parsePrice(priceText) {
        if (!priceText) return null;

        // Remove currency symbols and whitespace, handle European format
        const cleaned = priceText.replace(/[€\s]/g, '').trim();

        if (!cleaned) return null;

        // Handle European format (1.234,56)
        let value;
        if (cleaned.includes(',')) {
            // European decimal format
            value = parseFloat(cleaned.replace(/\./g, '').replace(',', '.'));
        } else {
            value = parseFloat(cleaned);
        }

        return isNaN(value) ? null : { value, original: priceText };
    }

    generateExternalId(url) {
        try {
            const urlObj = new URL(url);
            const path = urlObj.pathname.replace(/^\/|\/$/g, '');
            return `probo-${path.replace(/\//g, '-')}`;
        } catch {
            return `probo-${Date.now()}`;
        }
    }

    /**
     * Sanitize string content to remove control characters that break JSON.
     */
    sanitizeForJson(str) {
        if (!str) return str;
        // Remove control characters (except newline, carriage return, tab)
        return str.replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g, '');
    }

    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}
