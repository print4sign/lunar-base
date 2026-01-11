<?php

namespace Lunar\Scraper\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeClient
{
    protected string $baseUrl = 'https://api.anthropic.com/v1/';

    /**
     * Send a simple completion request to Claude.
     * Returns the text response.
     */
    public function complete(string $prompt, int $maxTokens = 4096): string
    {
        $apiKey = config('lunar.scraper.claude_api_key');

        if (empty($apiKey)) {
            Log::error('Claude API key not configured');

            return '';
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(120)->post($this->baseUrl.'messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => $maxTokens,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            if (! $response->successful()) {
                Log::error('Claude API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return '';
            }

            $text = $response->json('content.0.text') ?? '';

            // Strip markdown code blocks if Claude wrapped the response
            return $this->stripMarkdownCodeBlocks($text);
        } catch (\Exception $e) {
            Log::error('Claude API exception', [
                'message' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Rewrite HTML content to be copyright-free.
     * Returns raw HTML string that can be placed directly in a Blade template.
     *
     * @param  array  $imageUrls  Optional array of local image URLs to use in the rewritten HTML
     */
    public function rewriteHtml(string $htmlContent, string $productName, array $imageUrls = []): string
    {
        $apiKey = config('lunar.scraper.claude_api_key');

        if (empty($apiKey)) {
            Log::error('Claude API key not configured');

            return '';
        }

        // Clean HTML to reduce token count
        $htmlContent = $this->cleanHtmlForProcessing($htmlContent);

        Log::info('ClaudeClient: HTML size after cleanup', [
            'chars' => strlen($htmlContent),
            'estimated_tokens' => intval(strlen($htmlContent) / 4),
        ]);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(180)->post($this->baseUrl.'messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 16384,
                'system' => $this->buildSystemPrompt(),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $this->buildUserPrompt($htmlContent, $productName, $imageUrls),
                    ],
                ],
            ]);

            if (! $response->successful()) {
                Log::error('Claude API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return '';
            }

            $html = $response->json('content.0.text') ?? '';

            // Strip markdown code blocks if Claude wrapped the response
            $html = $this->stripMarkdownCodeBlocks($html);

            // Strip DOCTYPE and wrapper tags if present
            return $this->stripWrapperTags($html);
        } catch (\Exception $e) {
            Log::error('Claude API exception', [
                'message' => $e->getMessage(),
                'product' => $productName,
            ]);

            return '';
        }
    }

    /**
     * Build the system prompt with detailed instructions.
     * This is separate from the user message to optimize token usage.
     */
    protected function buildSystemPrompt(): string
    {
        return <<<SYSTEM
You are an expert HTML content rewriter specializing in e-commerce product pages. Transform supplier product HTML into original, copyright-free content using Flowbite e-commerce components with Tailwind CSS.

## OUTPUT REQUIREMENTS
- Output ONLY raw HTML - no markdown blocks, no explanations
- DO NOT include <!DOCTYPE>, <html>, <head>, <body> tags
- Start with a <section> or <div> tag

## PAGE STRUCTURE - USE THIS EXACT LAYOUT

### Main Product Section
```html
<section class="py-8 bg-white md:py-16 xl:py-24 dark:bg-gray-900 antialiased">
  <div class="max-w-screen-xl mx-auto">
    <div class="lg:flex justify-between">
      <!-- Left: Image Gallery + Accordion -->
      <div class="px-4">
        <!-- Image Gallery with Tabs -->
        <div class="max-w-md lg:max-w-none mx-auto flex flex-col lg:flex-row justify-center mb-4">
          <ul class="grid grid-cols-4 lg:block gap-4 order-2 lg:order-1 lg:space-y-4 mt-8 lg:mt-0" id="product-tab" data-tabs-toggle="#product-tab-content" role="tablist">
            <!-- Thumbnail buttons -->
            <li role="presentation">
              <button class="h-20 w-20 overflow-hidden border-2 rounded-lg p-2 cursor-pointer mx-auto" type="button" role="tab">
                <img class="object-contain w-full h-full" src="IMAGE_URL" alt="Thumbnail" />
              </button>
            </li>
          </ul>
          <div id="product-tab-content" class="order-1 lg:order-2">
            <!-- Main images -->
            <div class="px-4 rounded-lg bg-white dark:bg-gray-900" role="tabpanel">
              <img class="w-full mx-auto" src="IMAGE_URL" alt="Product" />
            </div>
          </div>
        </div>

        <!-- Accordion: Details, Specs, Warranty -->
        <div id="accordion-flush" data-accordion="collapse">
          <h2><button class="flex items-center justify-between w-full py-5 font-medium text-gray-500 border-b border-gray-200 dark:border-gray-700" data-accordion-target="#accordion-body-1">
            <span>Product Details</span>
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
          </button></h2>
          <div id="accordion-body-1" class="hidden"><div class="py-5 border-b border-gray-200 dark:border-gray-700">
            <p class="text-gray-500 dark:text-gray-400">Product description content...</p>
          </div></div>
          <!-- Repeat for Specifications, Warranty -->
        </div>
      </div>

      <!-- Right: Product Info Card -->
      <div class="w-full mt-6 lg:max-w-lg lg:mt-0 shrink-0 px-4">
        <div class="p-4 border border-gray-200 rounded-lg sm:p-6 lg:p-8 bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
          <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Product Name</h1>

          <!-- Rating -->
          <div class="mt-4 flex items-center gap-2">
            <div class="flex items-center gap-1">
              <svg class="w-4 h-4 text-yellow-300" fill="currentColor" viewBox="0 0 24 24"><path d="M13.849 4.22c-.684-1.626-3.014-1.626-3.698 0L8.397 8.387l-4.552.361c-1.775.14-2.495 2.331-1.142 3.477l3.468 2.937-1.06 4.392c-.413 1.713 1.472 3.067 2.992 2.149L12 19.35l3.897 2.354c1.52.918 3.405-.436 2.992-2.15l-1.06-4.39 3.468-2.938c1.353-1.146.633-3.336-1.142-3.477l-4.552-.36-1.754-4.17Z"/></svg>
              <!-- Repeat 5x for stars -->
            </div>
            <a href="#" class="text-sm font-medium text-gray-900 underline dark:text-white">Reviews</a>
          </div>

          <!-- Price -->
          <div class="gap-4 mt-4 flex items-center justify-between">
            <p class="text-2xl font-extrabold text-gray-900 sm:text-3xl dark:text-white">Price on request</p>
          </div>

          <!-- Action Buttons -->
          <div class="gap-4 mt-4 sm:flex lg:flex-col">
            <a href="#" class="flex items-center w-full justify-center py-2.5 px-5 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600">
              <svg class="w-5 h-5 -ms-2 me-2" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.01 6.001C6.5 1 1 8 5.782 13.001L12.011 20l6.23-7C23 8 17.5 1 12.01 6.002Z"/></svg>
              Add to favorites
            </a>
            <a href="#" class="text-white w-full mt-4 sm:mt-0 bg-primary-700 hover:bg-primary-800 font-medium rounded-lg text-sm px-5 py-2.5 flex items-center justify-center">
              <svg class="w-5 h-5 -ms-2 me-2" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h1.5L8 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm.75-3H7.5M11 7H6.312M17 4v6m-3-3h6"/></svg>
              Add to cart
            </a>
          </div>

          <!-- Features List -->
          <div class="pt-8 mt-8 border-t border-gray-200 dark:border-gray-700">
            <p class="text-base font-medium text-gray-900 dark:text-white">Features</p>
            <ul class="mt-2 space-y-2">
              <li class="flex items-center gap-2 text-sm text-gray-500"><svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Feature text</li>
            </ul>
          </div>

          <!-- Shipping Options -->
          <div class="pt-8 mt-8 border-t border-gray-200 dark:border-gray-700">
            <p class="text-base font-medium text-gray-900 dark:text-white">Delivery</p>
            <div class="flex flex-col gap-4 mt-2">
              <div class="flex"><input type="radio" class="w-4 h-4 text-primary-600" checked /><div class="ms-2"><label class="font-medium text-gray-900 dark:text-white">Standard - Free</label><p class="text-xs text-gray-500">5-7 days</p></div></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
```

### Description Section (after main section)
```html
<section class="bg-white py-8 dark:bg-gray-900 md:py-16">
  <div class="mx-auto max-w-screen-xl px-4">
    <h2 class="mb-6 text-xl font-semibold text-gray-900 dark:text-white">Product description</h2>
    <div class="prose prose-lg dark:prose-invert max-w-none text-gray-500">
      <!-- Rewritten product description paragraphs -->
    </div>

    <h2 class="mb-6 mt-12 text-xl font-semibold text-gray-900 dark:text-white">Technical details</h2>
    <table class="w-full text-left text-gray-500 dark:text-gray-400">
      <tbody>
        <tr class="bg-gray-50 dark:bg-gray-800"><th class="p-4 font-medium text-gray-900 dark:text-white">Spec Name</th><td class="p-4">Value</td></tr>
        <tr><th class="p-4 font-medium text-gray-900 dark:text-white">Spec Name</th><td class="p-4">Value</td></tr>
      </tbody>
    </table>
  </div>
</section>
```

## CONTENT RULES
REMOVE: Login prompts, supplier branding, navigation, footers, cookie banners, chat widgets, newsletter forms
PRESERVE & REWRITE: Descriptions, specifications (keep values accurate), features, materials, dimensions
IMAGES: Replace ALL src URLs with provided local URLs, use proper alt text

## STYLING
- Use Tailwind CSS utilities only, no inline styles
- Include dark: variants for dark mode support
- Use primary-600/700 for brand colors
- Responsive: sm:, md:, lg:, xl: breakpoints
SYSTEM;
    }

    /**
     * Build the user prompt with the HTML content and context.
     */
    protected function buildUserPrompt(string $htmlContent, string $productName, array $imageUrls = []): string
    {
        $imageSection = '';
        if (! empty($imageUrls)) {
            $imageList = implode("\n", array_map(fn ($url, $i) => ($i + 1).". {$url}", $imageUrls, array_keys($imageUrls)));
            $imageSection = <<<IMAGES

## Available Local Image URLs (use these instead of original URLs):
{$imageList}

IMAGES;
        }

        return <<<PROMPT
Rewrite the following product page HTML for: **{$productName}**
{$imageSection}
## Original HTML Content:
{$htmlContent}

Output the rewritten HTML now:
PROMPT;
    }

    /**
     * Clean HTML content to reduce token count before sending to API.
     */
    protected function cleanHtmlForProcessing(string $html): string
    {
        // Remove script tags and their content
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);

        // Remove style tags and their content
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);

        // Remove HTML comments
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        // Remove noscript tags
        $html = preg_replace('/<noscript\b[^>]*>.*?<\/noscript>/is', '', $html);

        // Remove SVG content (often very large)
        $html = preg_replace('/<svg\b[^>]*>.*?<\/svg>/is', '<svg></svg>', $html);

        // Remove data-* attributes (can be very long)
        $html = preg_replace('/\s+data-[a-z0-9-]+="[^"]*"/i', '', $html);
        $html = preg_replace("/\s+data-[a-z0-9-]+='[^']*'/i", '', $html);

        // Remove inline styles (we'll use Tailwind anyway)
        $html = preg_replace('/\s+style="[^"]*"/i', '', $html);
        $html = preg_replace("/\s+style='[^']*'/i", '', $html);

        // Remove onclick and other event handlers
        $html = preg_replace('/\s+on[a-z]+="[^"]*"/i', '', $html);
        $html = preg_replace("/\s+on[a-z]+='[^']*'/i", '', $html);

        // Remove hidden elements
        $html = preg_replace('/<[^>]+\s+hidden[^>]*>.*?<\/[^>]+>/is', '', $html);

        // Remove elements with display:none
        $html = preg_replace('/<[^>]+style="[^"]*display:\s*none[^"]*"[^>]*>.*?<\/[^>]+>/is', '', $html);

        // Remove common non-content elements
        $elementsToRemove = ['nav', 'footer', 'header', 'aside', 'iframe', 'form'];
        foreach ($elementsToRemove as $element) {
            $html = preg_replace('/<' . $element . '\b[^>]*>.*?<\/' . $element . '>/is', '', $html);
        }

        // Remove empty elements
        $html = preg_replace('/<(\w+)[^>]*>\s*<\/\1>/i', '', $html);

        // Collapse multiple whitespace into single space
        $html = preg_replace('/\s+/', ' ', $html);

        // Remove whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html);

        return trim($html);
    }

    /**
     * Generate JavaScript to make scraped HTML interactive.
     * The HTML cannot be changed - only JavaScript is generated.
     */
    public function generateJavaScript(string $htmlContent, string $productName): string
    {
        $apiKey = config('lunar.scraper.claude_api_key');

        if (empty($apiKey)) {
            Log::error('Claude API key not configured');

            return '';
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(120)->post($this->baseUrl.'messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 8192,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $this->buildJavaScriptPrompt($htmlContent, $productName),
                    ],
                ],
            ]);

            if (! $response->successful()) {
                Log::error('Claude API request failed for JavaScript generation', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return '';
            }

            $js = $response->json('content.0.text') ?? '';

            // Strip markdown code blocks if Claude wrapped the response
            return $this->stripMarkdownCodeBlocks($js);
        } catch (\Exception $e) {
            Log::error('Claude API exception during JavaScript generation', [
                'message' => $e->getMessage(),
                'product' => $productName,
            ]);

            return '';
        }
    }

    /**
     * Build prompt for JavaScript generation.
     */
    protected function buildJavaScriptPrompt(string $htmlContent, string $productName): string
    {
        return <<<PROMPT
You are generating JavaScript to make a scraped product page HTML interactive and functional.

Product: {$productName}

The HTML content below was scraped from a supplier website. The HTML CANNOT be changed.
Your task is to write JavaScript that:
1. Makes any image galleries/carousels functional (clicking thumbnails changes main image)
2. Makes tab navigation work (switching between tabs/sections)
3. Makes accordion/collapsible sections work
4. Handles any quantity inputs or form interactions
5. Makes any "add to cart" or similar buttons show feedback
6. Fixes any broken click handlers or event listeners
7. Makes dropdown menus and select options functional

HTML content:
```html
{$htmlContent}
```

CRITICAL INSTRUCTIONS:
1. Return ONLY JavaScript code - no HTML, no markdown code blocks, no explanations
2. The JavaScript should be vanilla JS (no jQuery required, but can use it if present)
3. Use document.addEventListener('DOMContentLoaded', ...) to ensure DOM is ready
4. Use querySelector/querySelectorAll to find elements
5. Be defensive - check if elements exist before adding event listeners
6. Look for common patterns like:
   - Image galleries: thumbnail clicks should update main image
   - Tabs: click handlers to show/hide tab content
   - Accordions: toggle visibility of content sections
   - Form inputs: validation and feedback
7. The script should work with the exact HTML structure provided
8. Do NOT try to fetch data from external APIs
9. Focus on UI interactivity, not business logic

Start your output directly with JavaScript code (no markdown fencing).
PROMPT;
    }

    /**
     * Rewrite product description content to be original and copyright-free.
     * Returns clean HTML suitable for storing as a product attribute.
     */
    public function rewriteDescription(string $description, string $productName, ?string $brandName = null): string
    {
        $apiKey = config('lunar.scraper.claude_api_key');

        if (empty($apiKey)) {
            Log::error('Claude API key not configured');

            return $description;
        }

        $brandName = $brandName ?? config('app.name', 'Print4Sign');

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(60)->post($this->baseUrl.'messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 4096,
                'system' => $this->buildDescriptionSystemPrompt($brandName),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $this->buildDescriptionUserPrompt($description, $productName),
                    ],
                ],
            ]);

            if (! $response->successful()) {
                Log::error('Claude API request failed for description rewriting', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $description;
            }

            $html = $response->json('content.0.text') ?? '';

            // Strip markdown code blocks if Claude wrapped the response
            $html = $this->stripMarkdownCodeBlocks($html);

            // Strip DOCTYPE and wrapper tags if present
            return $this->stripWrapperTags($html);
        } catch (\Exception $e) {
            Log::error('Claude API exception during description rewriting', [
                'message' => $e->getMessage(),
                'product' => $productName,
            ]);

            return $description;
        }
    }

    /**
     * Build the system prompt for description rewriting.
     */
    protected function buildDescriptionSystemPrompt(string $brandName): string
    {
        return <<<SYSTEM
You are an expert e-commerce copywriter. Rewrite product descriptions to be original, engaging, and copyright-free while preserving all factual information.

## OUTPUT REQUIREMENTS
- Output ONLY raw HTML - no markdown blocks, no explanations
- Use semantic HTML: <p>, <ul>, <li>, <h3>, <strong>, <em>
- DO NOT include any wrapper tags like <div>, <section>, <article>
- Keep the content concise but informative

## CONTENT RULES
- Completely rewrite the text to be original - do not copy phrases verbatim
- Replace any supplier/brand mentions with "{$brandName}"
- Preserve all technical specifications and measurements exactly
- Maintain the professional, informative tone
- Focus on benefits and features for the customer
- Use active voice and engaging language
- Keep formatting clean and readable

## STRUCTURE
- Start with a compelling opening paragraph about the product
- Use bullet points for key features when appropriate
- Include any important specifications or dimensions
- End with usage suggestions or applications if relevant
SYSTEM;
    }

    /**
     * Build the user prompt for description rewriting.
     */
    protected function buildDescriptionUserPrompt(string $description, string $productName): string
    {
        return <<<PROMPT
Rewrite the following product description for: **{$productName}**

## Original Description:
{$description}

Output the rewritten HTML now:
PROMPT;
    }

    /**
     * Translate content from one language to another.
     * Preserves HTML structure and only translates text content.
     */
    public function translateContent(string $content, string $fromLanguage = 'nl', string $toLanguage = 'en'): string
    {
        $apiKey = config('lunar.scraper.claude_api_key');

        if (empty($apiKey)) {
            Log::error('Claude API key not configured');

            return $content;
        }

        // Don't translate empty content
        if (empty(trim(strip_tags($content)))) {
            return $content;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(60)->post($this->baseUrl.'messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 4096,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $this->buildTranslationPrompt($content, $fromLanguage, $toLanguage),
                    ],
                ],
            ]);

            if (! $response->successful()) {
                Log::error('Claude API request failed for translation', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $content;
            }

            $translated = $response->json('content.0.text') ?? '';

            // Strip markdown code blocks if Claude wrapped the response
            $translated = $this->stripMarkdownCodeBlocks($translated);

            return $this->stripWrapperTags($translated);
        } catch (\Exception $e) {
            Log::error('Claude API exception during translation', [
                'message' => $e->getMessage(),
                'from' => $fromLanguage,
                'to' => $toLanguage,
            ]);

            return $content;
        }
    }

    /**
     * Build the prompt for translation.
     */
    protected function buildTranslationPrompt(string $content, string $fromLanguage, string $toLanguage): string
    {
        $fromName = $this->getLanguageName($fromLanguage);
        $toName = $this->getLanguageName($toLanguage);

        return <<<PROMPT
Translate the following content from {$fromName} to {$toName}.

IMPORTANT RULES:
- Output ONLY the translated content - no explanations, no markdown blocks
- Preserve ALL HTML tags and structure exactly as they are
- Only translate the text content between tags
- Keep technical terms, measurements, and brand names unchanged
- Maintain the same tone and style
- If content is already in {$toName}, return it unchanged

Content to translate:
{$content}

Output the translated content now:
PROMPT;
    }

    /**
     * Get the full language name for a language code.
     */
    protected function getLanguageName(string $code): string
    {
        return match ($code) {
            'nl' => 'Dutch',
            'en' => 'English',
            'de' => 'German',
            'fr' => 'French',
            'es' => 'Spanish',
            'it' => 'Italian',
            default => $code,
        };
    }

    /**
     * Strip markdown code blocks from Claude's response.
     */
    protected function stripMarkdownCodeBlocks(string $html): string
    {
        // Remove ```html at the start and ``` at the end
        $html = preg_replace('/^```(?:html)?\s*\n?/i', '', $html);
        $html = preg_replace('/\n?```\s*$/i', '', $html);

        return trim($html);
    }

    /**
     * Format specifications HTML for use in a product page.
     * Converts supplier-specific classes to Tailwind 4, JavaScript to Alpine.js,
     * and replaces supplier brand name with app name.
     *
     * @param  string  $html  The raw HTML to format
     * @param  string  $supplierName  The supplier name to replace
     */
    public function formatSpecificationsHtml(string $html, string $supplierName = 'Probo'): string
    {
        $apiKey = config('lunar.scraper.claude_api_key');

        if (empty($apiKey)) {
            Log::error('Claude API key not configured');

            return $html;
        }

        // Don't process empty content
        if (empty(trim(strip_tags($html)))) {
            return $html;
        }

        // Clean HTML to reduce token count before sending to API
        $originalLength = strlen($html);
        $cleanedHtml = $this->cleanSpecificationsHtml($html);
        $cleanedLength = strlen($cleanedHtml);

        Log::info('Specifications HTML cleaned for Claude', [
            'original_length' => $originalLength,
            'cleaned_length' => $cleanedLength,
            'reduction_percent' => round((1 - $cleanedLength / $originalLength) * 100, 1),
        ]);

        $appName = config('app.name', 'Print4Sign');

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(180)->post($this->baseUrl.'messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 32000,
                'system' => $this->buildSpecificationsSystemPrompt($supplierName, $appName),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $this->buildSpecificationsUserPrompt($cleanedHtml),
                    ],
                ],
            ]);

            if (! $response->successful()) {
                Log::error('Claude API request failed for specifications formatting', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $html;
            }

            // Check if the response was truncated due to max_tokens
            $stopReason = $response->json('stop_reason');
            if ($stopReason === 'max_tokens') {
                Log::warning('Claude specifications formatting was truncated due to max_tokens', [
                    'input_length' => strlen($cleanedHtml),
                ]);
            }

            $formattedHtml = $response->json('content.0.text') ?? '';

            // Strip markdown code blocks if Claude wrapped the response
            $formattedHtml = $this->stripMarkdownCodeBlocks($formattedHtml);

            return $this->stripWrapperTags($formattedHtml);
        } catch (\Exception $e) {
            Log::error('Claude API exception during specifications formatting', [
                'message' => $e->getMessage(),
            ]);

            return $html;
        }
    }

    /**
     * Build the system prompt for specifications formatting.
     */
    protected function buildSpecificationsSystemPrompt(string $supplierName, string $appName): string
    {
        return <<<SYSTEM
You are an expert frontend developer. Transform supplier product specifications HTML to use modern Tailwind CSS 4 and Alpine.js.

## OUTPUT REQUIREMENTS
- Output ONLY raw HTML - no markdown blocks, no explanations
- Keep the same semantic structure and content
- Preserve all technical specifications, measurements, and data exactly

## ABSOLUTELY CRITICAL - ROW PRESERVATION
- You MUST preserve 100% of ALL rows from EVERY table in the original HTML
- Count the input rows and ensure your output has the SAME number of rows
- Do NOT truncate, summarize, skip, or omit ANY rows whatsoever
- Every single specification row, comparison table row, and data row in the input MUST appear in the output
- If the input has 30 rows of specifications, your output MUST have 30 rows
- If you find yourself running low on output tokens, you MUST still complete ALL rows - do NOT stop early

## TRANSFORMATION RULES

### CSS Classes
- Convert any custom/supplier CSS classes to standard Tailwind CSS 4 utilities
- Use semantic Tailwind classes: text-gray-900, bg-white, border-gray-200, etc.
- Include dark mode variants: dark:text-white, dark:bg-gray-900, etc.
- Use responsive prefixes where appropriate: sm:, md:, lg:
- For lists: use list-disc, list-inside, space-y-2
- For headings: use text-lg font-semibold text-gray-900 dark:text-white

### Table Formatting (IMPORTANT)
- For key-value specification tables (label + value pairs), use FIXED column widths:
  - First column (label): w-1/3 for the label/key
  - Second column (value): w-2/3 for the value
- Use this exact table structure for specification tables:
```html
<table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
  <tbody>
    <tr class="border-b border-gray-200 dark:border-gray-700">
      <th scope="row" class="w-1/3 px-4 py-3 font-medium text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">Label</th>
      <td class="w-2/3 px-4 py-3">Value</td>
    </tr>
  </tbody>
</table>
```
- Apply alternating row backgrounds: odd rows get bg-gray-50 dark:bg-gray-800 on the th cell
- Use consistent padding: px-4 py-3 on both th and td cells
- Add border-b border-gray-200 dark:border-gray-700 to each row
- For comparison tables with multiple columns, distribute widths evenly

### JavaScript to Alpine.js
- Convert any inline JavaScript or onclick handlers to Alpine.js
- Use x-data for component state
- Use x-show, x-on:click, x-bind for interactivity
- Use x-transition for animations
- Example: onclick="toggle()" becomes x-on:click="open = !open"
- Example: style="display:none" with JS toggle becomes x-show="open"

### Collapsible/Accordion Sections
- If there are collapsible sections, use this Alpine pattern:
```html
<div x-data="{ open: false }">
  <button x-on:click="open = !open" class="flex items-center justify-between w-full p-4 text-left">
    <span>Section Title</span>
    <svg x-bind:class="{ 'rotate-180': open }" class="w-5 h-5 transition-transform">...</svg>
  </button>
  <div x-show="open" x-transition class="p-4">
    Content here
  </div>
</div>
```

### Brand Name Replacement
- Replace all occurrences of "{$supplierName}" (case-insensitive) with "{$appName}"
- Also replace variations like "{$supplierName}.nl", "{$supplierName}.com", "www.{$supplierName}"

### Content Preservation
- Keep ALL technical specifications exactly as they are
- Keep measurements, dimensions, weights unchanged
- Keep material names and technical terms unchanged
- Preserve table structure and data

### Clean Up
- Remove any tracking scripts or analytics code
- Remove any external resource links (fonts, stylesheets)
- Remove any supplier-specific branding images or logos
- Keep product-related images with proper alt text
SYSTEM;
    }

    /**
     * Build the user prompt for specifications formatting.
     */
    protected function buildSpecificationsUserPrompt(string $html): string
    {
        return <<<PROMPT
Transform the following specifications HTML according to the rules above.

## Original HTML:
{$html}

Output the formatted HTML now:
PROMPT;
    }

    /**
     * Clean specifications HTML to reduce token count before sending to Claude.
     * Removes unnecessary attributes, comments, and whitespace while preserving content.
     */
    protected function cleanSpecificationsHtml(string $html): string
    {
        // Remove HTML comments
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        // Remove data-bind attributes (Knockout.js)
        $html = preg_replace('/\s+data-bind="[^"]*"/i', '', $html);
        $html = preg_replace("/\s+data-bind='[^']*'/i", '', $html);

        // Remove fdy-see attributes (supplier-specific)
        $html = preg_replace('/\s+fdy-see="[^"]*"/i', '', $html);
        $html = preg_replace("/\s+fdy-see='[^']*'/i", '', $html);

        // Remove inline styles (we'll use Tailwind anyway)
        $html = preg_replace('/\s+style="[^"]*"/i', '', $html);
        $html = preg_replace("/\s+style='[^']*'/i", '', $html);

        // Remove data-* attributes
        $html = preg_replace('/\s+data-[a-z0-9-]+="[^"]*"/i', '', $html);
        $html = preg_replace("/\s+data-[a-z0-9-]+='[^']*'/i", '', $html);

        // Remove onclick and other event handlers
        $html = preg_replace('/\s+on[a-z]+="[^"]*"/i', '', $html);
        $html = preg_replace("/\s+on[a-z]+='[^']*'/i", '', $html);

        // Remove ko (Knockout.js) attributes
        $html = preg_replace('/\s+ko[a-z]*="[^"]*"/i', '', $html);

        // Collapse multiple whitespace into single space
        $html = preg_replace('/\s+/', ' ', $html);

        // Remove whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html);

        // Remove whitespace around text content (but preserve content)
        $html = preg_replace('/>\s+([^<\s])/s', '>$1', $html);
        $html = preg_replace('/([^>\s])\s+</s', '$1<', $html);

        return trim($html);
    }

    /**
     * Strip DOCTYPE and wrapper HTML tags from the response.
     */
    protected function stripWrapperTags(string $html): string
    {
        // Remove DOCTYPE declaration
        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);

        // Remove <html> and </html> tags
        $html = preg_replace('/<html[^>]*>/i', '', $html);
        $html = preg_replace('/<\/html>/i', '', $html);

        // Remove entire <head>...</head> section
        $html = preg_replace('/<head>.*?<\/head>/is', '', $html);

        // Remove <body> and </body> tags (but keep content)
        $html = preg_replace('/<body[^>]*>/i', '', $html);
        $html = preg_replace('/<\/body>/i', '', $html);

        return trim($html);
    }

}
