export class AuthHandler {
    constructor(browser, config) {
        this.browser = browser;
        this.config = config;
    }

    async authenticate() {
        if (!this.config || this.config.type === 'none') {
            return { success: true, message: 'No authentication required' };
        }

        switch (this.config.type) {
            case 'form':
                return await this.formLogin();
            case 'basic':
                return await this.basicAuth();
            case 'cookie':
                return await this.cookieAuth();
            default:
                throw new Error(`Unknown authentication type: ${this.config.type}`);
        }
    }

    async formLogin() {
        const page = await this.browser.newPage();

        try {
            const { login_url, selectors, credentials } = this.config;

            if (!login_url || !selectors || !credentials) {
                throw new Error('Form login requires login_url, selectors, and credentials');
            }

            // Navigate to login page
            await page.goto(login_url, { waitUntil: 'networkidle' });

            // Fill username/email field
            const usernameSelector = selectors.username || 'input[name="email"], input[name="username"]';
            await page.waitForSelector(usernameSelector, { timeout: 10000 });
            await page.fill(usernameSelector, credentials.username);

            // Fill password field
            const passwordSelector = selectors.password || 'input[name="password"], input[type="password"]';
            await page.waitForSelector(passwordSelector, { timeout: 10000 });
            await page.fill(passwordSelector, credentials.password);

            // Dismiss any modal overlays that might be blocking
            try {
                // Wait a moment for any modals to appear
                await page.waitForTimeout(500);

                // Try to close cookie/modal overlays
                const overlaySelectors = [
                    '.modals-overlay',
                    '.modal-popup .action-close',
                    '.onetrust-close-btn-handler',
                    '#onetrust-accept-btn-handler',
                    '.cookie-notice .close',
                    '[data-action="accept-cookies"]',
                    '.cc-dismiss',
                ];

                for (const selector of overlaySelectors) {
                    const element = await page.$(selector);
                    if (element) {
                        const isVisible = await element.isVisible();
                        if (isVisible) {
                            // Try clicking close button or pressing Escape
                            try {
                                await element.click({ timeout: 2000 });
                            } catch (e) {
                                // Element might not be clickable, try Escape
                            }
                        }
                    }
                }

                // Press Escape to close any modal
                await page.keyboard.press('Escape');
                await page.waitForTimeout(300);
            } catch (e) {
                // Ignore overlay dismissal errors
            }

            // Click submit button with force option to bypass overlay issues
            const submitSelector = selectors.submit || 'button[type="submit"], input[type="submit"]';
            await page.click(submitSelector, { force: true });

            // Wait for navigation or success indicator
            if (selectors.success_indicator) {
                try {
                    await page.waitForSelector(selectors.success_indicator, { timeout: 15000 });
                } catch (e) {
                    // Check for error indicator
                    if (selectors.error_indicator) {
                        const errorElement = await page.$(selectors.error_indicator);
                        if (errorElement) {
                            const errorText = await errorElement.textContent();
                            throw new Error(`Login failed: ${errorText}`);
                        }
                    }
                    throw new Error('Login failed: Success indicator not found');
                }
            } else {
                await page.waitForLoadState('networkidle');
            }

            // Store cookies for session persistence
            const cookies = await page.context().cookies();
            await this.browser.setCookies(cookies);

            return {
                success: true,
                message: 'Form login successful',
                cookies: cookies.length,
            };
        } catch (error) {
            return {
                success: false,
                message: error.message,
            };
        } finally {
            await page.close();
        }
    }

    async basicAuth() {
        const { credentials } = this.config;

        if (!credentials || !credentials.username || !credentials.password) {
            throw new Error('Basic auth requires username and password credentials');
        }

        // Set HTTP credentials on the browser context
        await this.browser.context.setHTTPCredentials({
            username: credentials.username,
            password: credentials.password,
        });

        return {
            success: true,
            message: 'Basic auth credentials set',
        };
    }

    async cookieAuth() {
        const { cookies } = this.config;

        if (!cookies || !Array.isArray(cookies)) {
            throw new Error('Cookie auth requires an array of cookies');
        }

        await this.browser.setCookies(cookies);

        return {
            success: true,
            message: `${cookies.length} cookies set`,
            cookies: cookies.length,
        };
    }
}
