# AI Product Matcher - Setup & Usage

## 📋 Overview

The AI Product Matcher uses Claude AI (Anthropic API) to automatically match your product variants with supplier products based on:

- Product type similarity (40%)
- Specifications match (30%)
- Attributes compatibility (20%)
- Semantic similarity (10%)

## 🚀 Setup

### Step 1: Get Anthropic API Key

1. Go to [https://console.anthropic.com/](https://console.anthropic.com/)
2. Sign up or log in
3. Navigate to API Keys
4. Create a new API key
5. Copy the key (starts with `sk-ant-api03-...`)

### Step 2: Configure Environment

Add to your `.env` file:

```bash
# Anthropic AI Configuration
ANTHROPIC_API_KEY=sk-ant-api03-your-key-here
ANTHROPIC_MODEL=claude-3-5-sonnet-20241022  # Optional, this is the default
```

**Important**: Add `.env` to your `.gitignore` to keep your API key secure!

### Step 3: Update Services Configuration

The configuration is already added to [`sandbox/config/services.php`](sandbox/config/services.php):

```php
'anthropic' => [
    'api_key' => env('ANTHROPIC_API_KEY'),
    'model' => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'),
],
```

### Step 4: Clear Configuration Cache

```bash
php artisan config:clear
php artisan cache:clear
```

### Step 5: Verify Setup

Check if AI matching is available:

```bash
php artisan tinker
```

```php
$service = app(\Lunar\Services\AIProductMatcherService::class);
$service->isConfigured(); // Should return true
```

## 🎯 Usage in Admin Panel

### Quick Start

1. **Navigate to Product**: Admin → Products → [Select Product] → Fulfillment tab
2. **Click "Match with AI"**: Blue button with sparkles icon (✨)
3. **Wait for Results**: AI analyzes and returns top 10 matches
4. **Select Match**: Click on a match card to link it to your variant

### Detailed Workflow

#### 1. Prerequisites

- Product variant must **NOT** have a supplier link yet
- Anthropic API key must be configured
- Supplier products must exist in the catalog

#### 2. Opening AI Matcher

**Location**:
- Simplified Product: `Admin → Products → [Product] → Fulfillment`
- Multi-Variant Product: `Admin → Product Variants → [Variant] → Fulfillment`

**Action**:
- "Match with AI" button (blue, sparkles icon)
- Only visible if `ANTHROPIC_API_KEY` is set
- Only shown for variants without supplier link

#### 3. AI Analysis Process

**What happens**:
1. Modal opens automatically
2. Variant data extracted:
   - Product name and description
   - SKU
   - Variant attributes (size, color, material, etc.)
   - Dimensions (width, height, length, weight)
3. All supplier products fetched from database
4. Claude API analyzes and scores each match
5. Results displayed in modal

**Duration**: 10-30 seconds depending on catalog size

#### 4. Reviewing Results

**Each match shows**:

- **Product Name**: Supplier product name
- **Supplier Name**: Which supplier offers this
- **External ID**: Supplier's product ID
- **Score Badge**: 0-100 score with color coding
  - 90-100: Green "Excellent Match" ⭐ Recommended
  - 75-89: Blue "Good Match"
  - 60-74: Orange "Moderate Match"
  - <60: Red "Poor Match"
- **Reasoning**: AI explanation why this is a good match
- **Matched Fields**: Visual badges for what matches (✓ Product type, ✓ Dimensions, ✓ Material, etc.)
- **Concerns**: Any caveats or differences (if applicable)

#### 5. Filtering Results (Optional)

**Supplier Filter**:
- Multi-select dropdown at top of modal
- Select specific suppliers to search only their products
- Leave empty to search all enabled suppliers
- Click "Refresh Matches" after changing filter

#### 6. Selecting a Match

**How to select**:
- Click anywhere on the match card, or
- Click the "Select" button

**What happens**:
- Variant's `supplier_product_id` is updated
- Success notification shown
- Modal closes
- Page refreshes to show new supplier link

#### 7. Next Steps After Matching

After selecting a match, you can:

- **Configure the product** (if supplier supports it)
- **Enable dynamic pricing** for real-time supplier prices
- **Set margin** percentage for dynamic products
- **Refresh price** for pre-configured products

## 🧪 Testing

### Test Scenario 1: Match a Flyer Product

```bash
# Create test variant
php artisan tinker
```

```php
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;

$product = Product::create([
    'name' => 'A5 Flyer Glossy 150gsm',
    'status' => 'published',
]);

$variant = ProductVariant::create([
    'product_id' => $product->id,
    'sku' => 'FLYER-A5-GLOSS-150',
    'width_value' => 148,
    'width_unit' => 'mm',
    'height_value' => 210,
    'height_unit' => 'mm',
]);
```

Now go to Admin → Products → "A5 Flyer..." → Fulfillment → "Match with AI"

Expected result: High-scoring matches for A5 flyer products from suppliers

### Test Scenario 2: Programmatic Matching

```bash
php artisan tinker
```

```php
use Lunar\Services\AIProductMatcherService;
use Lunar\Models\ProductVariant;

$service = app(AIProductMatcherService::class);
$variant = ProductVariant::first();

// Find matches
$matches = $service->findMatches($variant, limit: 10);

foreach ($matches as $match) {
    echo sprintf(
        "%s - Score: %d - %s - %s\n",
        $match->supplierProduct->external_name,
        $match->score,
        $match->getScoreLabel(),
        $match->reasoning
    );
}

// Find single best match
$best = $service->findBestMatch($variant);
if ($best) {
    echo "Best match: {$best->supplierProduct->external_name} ({$best->score}%)\n";
}
```

### Test Scenario 3: Filter by Supplier

In the admin UI:
1. Open AI Matcher modal
2. Select "Probo" in supplier filter dropdown
3. Click "Refresh Matches"
4. Only Probo products should be shown

## 🔧 Troubleshooting

### "Match with AI" button not showing

**Cause**: API key not configured or variant already has supplier link

**Fix**:
1. Check `.env` has `ANTHROPIC_API_KEY`
2. Run `php artisan config:clear`
3. Verify variant has NO `supplier_product_id`

### "Matching Failed" error

**Cause**: API call failed or network issue

**Check**:
1. Verify API key is valid at console.anthropic.com
2. Check Laravel logs: `tail -f storage/logs/laravel.log`
3. Verify network can reach api.anthropic.com
4. Check API rate limits

### "No matching supplier products found"

**Cause**: Catalog is empty or no good matches

**Fix**:
1. Verify supplier products exist: `php artisan tinker` → `Lunar\Models\SupplierProduct::count()`
2. Check if suppliers are enabled: `Lunar\Models\Supplier::enabled()->count()`
3. Try different supplier filter
4. Check if variant attributes are filled in

### Modal opens but stays on loading spinner

**Cause**: Timeout or API not responding

**Check**:
1. Browser console for JavaScript errors
2. Network tab for failed requests
3. Laravel logs for PHP errors
4. Increase timeout if large catalog (edit AIProductMatcherService.php)

### Matches have very low scores

**Cause**: Variant data is incomplete or supplier products poorly described

**Fix**:
1. Fill in more variant details (dimensions, attributes, description)
2. Improve supplier product `external_data` with better descriptions
3. Use more specific product names
4. Add specifications to `external_data['specifications']`

## 💰 API Costs

**Claude API Pricing** (as of 2024):
- Model: `claude-3-5-sonnet-20241022`
- Input: ~$3 per million tokens
- Output: ~$15 per million tokens

**Estimated Cost per Match**:
- Small catalog (100 products): ~$0.01
- Medium catalog (500 products): ~$0.02-0.03
- Large catalog (1000+ products): ~$0.05-0.10

**Cost Optimization**:
- Cache results per variant
- Only refresh on demand
- Filter by supplier to reduce catalog size
- Consider batch matching during off-peak hours

## 📚 Architecture

### Components

1. **AIProductMatcherService** ([`packages/core/src/Services/AIProductMatcherService.php`](packages/core/src/Services/AIProductMatcherService.php))
   - Core matching logic
   - Claude API integration
   - Scoring algorithm

2. **ProductMatch DTO** ([`packages/core/src/DataTransferObjects/ProductMatch.php`](packages/core/src/DataTransferObjects/ProductMatch.php))
   - Match result structure
   - Score helpers
   - Color/label getters

3. **AIProductMatcher Livewire** ([`packages/admin/src/Livewire/Components/AIProductMatcher.php`](packages/admin/src/Livewire/Components/AIProductMatcher.php))
   - UI component
   - Event handling
   - State management

4. **Blade View** ([`packages/admin/resources/views/livewire/components/ai-product-matcher.blade.php`](packages/admin/resources/views/livewire/components/ai-product-matcher.blade.php))
   - Modal UI
   - Match cards
   - Loading/error states

### Data Flow

```
User clicks "Match with AI"
    ↓
Dispatch 'open-ai-matcher-modal' event
    ↓
AIProductMatcher Livewire component receives event
    ↓
Call AIProductMatcherService->findMatches()
    ↓
Extract variant context (name, dimensions, attributes)
    ↓
Fetch supplier products from database
    ↓
Build catalog array for AI
    ↓
Call Claude API with matching prompt
    ↓
Parse JSON response
    ↓
Enrich with SupplierProduct models
    ↓
Create ProductMatch DTOs
    ↓
Display in modal
    ↓
User selects match
    ↓
Update variant.supplier_product_id
    ↓
Close modal & refresh page
```

## 🔐 Security

**API Key Protection**:
- Store in `.env` (never commit)
- Use `config('services.anthropic.api_key')` in code
- Validate key existence before showing UI
- Log errors without exposing key

**Rate Limiting**:
- Consider adding rate limit per user
- Monitor API usage in Anthropic console
- Set up alerts for high usage

**Input Sanitization**:
- Variant data is sanitized before API call
- JSON responses are validated
- Failed parses are logged and handled

## 📖 Further Reading

- **Full Guide**: [`PRODUCT_FULFILLMENT_GUIDE.md`](PRODUCT_FULFILLMENT_GUIDE.md) - Complete product fulfillment configuration
- **Testing Guide**: [`MULTI_SUPPLIER_TESTING_GUIDE.md`](MULTI_SUPPLIER_TESTING_GUIDE.md) - Testing supplier order flow
- **Implementation Plan**: [`docs/plans/2026-01-12-multi-supplier-fulfillment-system.md`](docs/plans/2026-01-12-multi-supplier-fulfillment-system.md)

## ✅ Quick Checklist

Before using AI Product Matcher:

- [ ] Anthropic API key added to `.env`
- [ ] Configuration cache cleared
- [ ] At least 1 enabled supplier exists
- [ ] Supplier products exist in catalog
- [ ] Product variant created without supplier link
- [ ] Variant has dimensions/attributes filled in
- [ ] Admin panel accessible

Ready to match!
