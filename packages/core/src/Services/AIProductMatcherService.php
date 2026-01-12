<?php

namespace Lunar\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Lunar\Models\Contracts\ProductVariant as ProductVariantContract;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierProduct;

class AIProductMatcherService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key');
        $this->model = config('services.anthropic.model', 'claude-3-5-sonnet-20241022');
    }

    /**
     * Find matching supplier products for a given product variant
     *
     * @param ProductVariantContract $variant
     * @param int $limit Maximum number of matches to return
     * @param array $supplierIds Filter by specific suppliers (empty = all)
     * @return Collection<ProductMatch>
     */
    public function findMatches(
        ProductVariantContract $variant,
        int $limit = 10,
        array $supplierIds = []
    ): Collection {
        // Get all available supplier products
        $supplierProducts = $this->getAvailableSupplierProducts($supplierIds);

        if ($supplierProducts->isEmpty()) {
            return collect();
        }

        // Extract variant context
        $variantContext = $this->extractVariantContext($variant);

        // Build catalog for AI
        $catalog = $this->buildCatalog($supplierProducts);

        // Call Claude API for matching
        $matches = $this->performAIMatching($variantContext, $catalog, $limit);

        // Enrich with supplier product models
        return $this->enrichMatches($matches, $supplierProducts);
    }

    /**
     * Get all available supplier products (enabled suppliers only)
     */
    protected function getAvailableSupplierProducts(array $supplierIds = []): Collection
    {
        $query = SupplierProduct::query()
            ->with(['supplier'])
            ->whereHas('supplier', function ($q) use ($supplierIds) {
                $q->where('enabled', true);

                if (!empty($supplierIds)) {
                    $q->whereIn('id', $supplierIds);
                }
            });

        return $query->get();
    }

    /**
     * Extract relevant context from variant for matching
     */
    protected function extractVariantContext(ProductVariantContract $variant): array
    {
        $product = $variant->product;

        return [
            'name' => $product->translateAttribute('name') ?? $product->name,
            'description' => $product->translateAttribute('description') ?? $product->description,
            'sku' => $variant->sku,
            'variant_attributes' => $variant->values->map(fn ($value) => [
                'attribute' => $value->productOption->name->get(app()->getLocale()) ?? $value->productOption->name->first(),
                'value' => $value->name->get(app()->getLocale()) ?? $value->name->first(),
            ])->toArray(),
            'dimensions' => [
                'width' => $variant->width_value ?? null,
                'height' => $variant->height_value ?? null,
                'length' => $variant->length_value ?? null,
                'weight' => $variant->weight_value ?? null,
                'width_unit' => $variant->width_unit ?? null,
                'height_unit' => $variant->height_unit ?? null,
                'length_unit' => $variant->length_unit ?? null,
                'weight_unit' => $variant->weight_unit ?? null,
            ],
        ];
    }

    /**
     * Build catalog data structure for AI
     */
    protected function buildCatalog(Collection $supplierProducts): array
    {
        return $supplierProducts->map(function ($product) {
            return [
                'id' => $product->id,
                'supplier' => $product->supplier->name,
                'name' => $product->external_name,
                'external_id' => $product->external_id,
                'description' => $product->external_data['description'] ?? '',
                'specifications' => $product->external_data['specifications'] ?? [],
                'categories' => $product->external_data['categories'] ?? [],
                'attributes' => $product->external_data['attributes'] ?? [],
            ];
        })->toArray();
    }

    /**
     * Perform AI matching using Claude API
     */
    protected function performAIMatching(array $variantContext, array $catalog, int $limit): array
    {
        $prompt = $this->buildMatchingPrompt($variantContext, $catalog, $limit);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->timeout(30)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => 4096,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            if (!$response->successful()) {
                \Log::error('AI Product Matcher API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $data = $response->json();
            $content = $data['content'][0]['text'] ?? '';

            // Parse JSON response from Claude
            return $this->parseAIResponse($content);
        } catch (\Exception $e) {
            \Log::error('AI Product Matcher Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Build the matching prompt for Claude
     */
    protected function buildMatchingPrompt(array $variantContext, array $catalog, int $limit): string
    {
        $variantJson = json_encode($variantContext, JSON_PRETTY_PRINT);
        $catalogJson = json_encode($catalog, JSON_PRETTY_PRINT);

        return <<<PROMPT
You are a product matching expert for a print-on-demand e-commerce platform.

Your task is to match the following product variant with the most suitable supplier products from our catalog.

## Product Variant to Match:
```json
{$variantJson}
```

## Supplier Product Catalog:
```json
{$catalogJson}
```

## Matching Criteria:

1. **Product Type Similarity** (40%): Does the supplier product match the core product type?
   - E.g., business cards match with business cards, flyers with flyers, etc.

2. **Specifications Match** (30%): Do dimensions, materials, finishing options align?
   - Size/dimensions compatibility
   - Material type (paper, vinyl, fabric, etc.)
   - Finishing options (glossy, matte, laminated, etc.)

3. **Attributes Compatibility** (20%): Do variant attributes match supplier product options?
   - Color options
   - Quantity ranges
   - Quality/grade levels

4. **Semantic Similarity** (10%): Do product names and descriptions suggest a good match?
   - Naming conventions
   - Description keywords
   - Use case alignment

## Output Format:

Return ONLY a valid JSON array with your top {$limit} matches, ordered by match score (highest first).

Each match must have this structure:
```json
[
  {
    "supplier_product_id": 123,
    "score": 95,
    "reasoning": "Exact match on product type (business cards), dimensions (85x55mm), and material (300gsm silk-coated paper). All variant attributes are supported.",
    "matches": {
      "product_type": true,
      "dimensions": true,
      "material": true,
      "attributes": true
    },
    "concerns": []
  }
]
```

**Score Scale:**
- 90-100: Excellent match, highly recommended
- 75-89: Good match, suitable with minor differences
- 60-74: Moderate match, may require configuration adjustments
- Below 60: Poor match, not recommended

**Important:**
- Return ONLY the JSON array, no additional text or explanation
- Include all matched fields in the "matches" object
- List any concerns or caveats in the "concerns" array
- Be conservative with scores - only award 90+ for near-perfect matches
- Consider both explicit specifications AND implicit product characteristics

Analyze and return your matches:
PROMPT;
    }

    /**
     * Parse AI response into structured data
     */
    protected function parseAIResponse(string $content): array
    {
        // Extract JSON from response (Claude might add markdown formatting)
        $content = trim($content);

        // Remove markdown code blocks if present
        $content = preg_replace('/^```json\s*/m', '', $content);
        $content = preg_replace('/\s*```$/m', '', $content);

        try {
            $matches = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($matches)) {
                \Log::warning('AI response is not an array', ['content' => $content]);
                return [];
            }

            return $matches;
        } catch (\JsonException $e) {
            \Log::error('Failed to parse AI response as JSON', [
                'content' => $content,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Enrich AI matches with full supplier product models
     */
    protected function enrichMatches(array $matches, Collection $supplierProducts): Collection
    {
        return collect($matches)->map(function ($match) use ($supplierProducts) {
            $supplierProduct = $supplierProducts->firstWhere('id', $match['supplier_product_id']);

            if (!$supplierProduct) {
                return null;
            }

            return new \Lunar\DataTransferObjects\ProductMatch(
                supplierProduct: $supplierProduct,
                score: $match['score'],
                reasoning: $match['reasoning'],
                matches: $match['matches'] ?? [],
                concerns: $match['concerns'] ?? []
            );
        })->filter();
    }

    /**
     * Quick match - find single best match
     */
    public function findBestMatch(
        ProductVariantContract $variant,
        array $supplierIds = []
    ): ?\Lunar\DataTransferObjects\ProductMatch {
        $matches = $this->findMatches($variant, 1, $supplierIds);

        return $matches->first();
    }

    /**
     * Check if API is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
