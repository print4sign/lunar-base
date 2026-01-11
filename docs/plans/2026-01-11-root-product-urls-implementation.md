# Root Product URLs Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Product en collectie URL's direct na de locale prefix plaatsen (`/{locale}/{slug}`) zonder segment.

**Architecture:** Nieuwe UnifiedSlugResolver component die slug opzoekt in `lunar_urls` tabel en de juiste view rendert (product of collectie). Statische routes krijgen prioriteit, catch-all route onderaan.

**Tech Stack:** Laravel, Livewire, Lunar PHP

---

### Task 1: Maak UnifiedSlugResolver Component

**Files:**
- Create: `sandbox/app/Livewire/Pages/UnifiedSlugResolver.php`

**Step 1: Maak de UnifiedSlugResolver component**

```php
<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\HasLocale;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url as UrlAttribute;
use Livewire\Component;
use Livewire\WithPagination;
use Lunar\Models\Collection;
use Lunar\Models\Language;
use Lunar\Models\Product;
use Lunar\Models\Url;

class UnifiedSlugResolver extends Component
{
    use HasLocale;
    use WithPagination;

    #[Locked]
    public string $slug;

    #[Locked]
    public string $entityType;

    #[Locked]
    public Product|Collection $entity;

    // For collection sorting
    #[UrlAttribute]
    public string $sort = 'newest';

    // For product page
    public int $quantity = 1;
    public array $selectedOptions = [];
    public bool $added = false;
    public int $activeImageIndex = 0;

    public function mount(string $locale, string $slug): void
    {
        $this->initializeLocale($locale);
        $this->slug = $slug;

        $this->resolveSlug();
    }

    protected function resolveSlug(): void
    {
        $language = Language::where('code', $this->locale)->first();

        // Query lunar_urls table
        $url = Url::query()
            ->where('slug', $this->slug)
            ->when($language, fn ($q) => $q->where('language_id', $language->id))
            ->first();

        if (! $url) {
            abort(404);
        }

        $this->entity = $url->element;

        if (! $this->entity) {
            abort(404);
        }

        $this->entityType = match ($url->element_type) {
            Product::modelClass(), Product::class, 'product' => 'product',
            Collection::modelClass(), Collection::class, 'collection' => 'collection',
            default => abort(404),
        };

        // Load relationships based on entity type
        if ($this->entityType === 'product') {
            $this->entity->load([
                'variants.prices.currency',
                'variants.values.option',
                'variants.supplierProduct.supplier',
                'productOptions.values',
                'productType.mappedAttributes',
                'media',
                'thumbnail',
            ]);

            // Initialize selected options from first variant
            $firstVariant = $this->entity->variants->first();
            if ($firstVariant) {
                foreach ($firstVariant->values as $value) {
                    $this->selectedOptions[$value->option->id] = $value->id;
                }
            }
        } elseif ($this->entityType === 'collection') {
            $this->entity->load(['thumbnail', 'children.defaultUrl']);
        }
    }

    public function render()
    {
        if ($this->entityType === 'product') {
            return $this->renderProduct();
        }

        return $this->renderCollection();
    }

    protected function renderProduct()
    {
        // Delegate to ProductPage component for rendering
        // We re-use the product-page view directly
        $product = $this->entity;
        $selectedVariant = $product->variants->first();
        $price = $selectedVariant?->prices->first();

        $name = $product->translateAttribute('name');
        $description = $product->translateAttribute('short_description')
            ?? $product->translateAttribute('description');

        $metaDescription = $description
            ? \Illuminate\Support\Str::limit(strip_tags($description), 160)
            : null;

        $ogImage = $product->thumbnail?->getUrl('large');

        $productTypeSlug = \Illuminate\Support\Str::slug($product->productType?->name ?? 'default');
        $view = view()->exists("livewire.pages.product-page.{$productTypeSlug}")
            ? "livewire.pages.product-page.{$productTypeSlug}"
            : 'livewire.pages.product-page';

        return view($view, [
            'product' => $product,
            'selectedVariant' => $selectedVariant,
            'price' => $price,
        ])->layout('layouts.storefront', [
            'title' => $name,
            'metaDescription' => $metaDescription,
            'ogTitle' => $name,
            'ogType' => 'product',
            'ogImage' => $ogImage,
            'canonicalUrl' => url()->current(),
        ]);
    }

    protected function renderCollection()
    {
        $query = $this->entity->products()
            ->with(['defaultUrl', 'variants.prices.currency', 'thumbnail'])
            ->whereHas('variants.prices');

        $query = match ($this->sort) {
            'name_asc' => $query->orderBy('attribute_data->name->value'),
            'name_desc' => $query->orderByDesc('attribute_data->name->value'),
            default => $query->latest(),
        };

        $products = $query->paginate(12);

        return view('livewire.pages.collection-page', [
            'collection' => $this->entity,
            'products' => $products,
        ])->layout('layouts.storefront', [
            'title' => $this->entity->translateAttribute('name'),
        ]);
    }

    public function updatingSort(): void
    {
        $this->resetPage();
    }
}
```

**Step 2: Commit**

```bash
git add sandbox/app/Livewire/Pages/UnifiedSlugResolver.php
git commit -m "feat: add UnifiedSlugResolver component for root URLs"
```

---

### Task 2: Update routes in web.php

**Files:**
- Modify: `sandbox/routes/web.php`

**Step 1: Add import for UnifiedSlugResolver**

Add at top of file with other imports:

```php
use App\Livewire\Pages\UnifiedSlugResolver;
```

**Step 2: Remove old product and collection routes**

Remove these lines (39-55):

```php
// Products
Route::get('/{productsSegment}', ProductsIndex::class)
    ->name('products.index')
    ->where('productsSegment', 'producten|products|produkte|productos');

Route::get('/{productsSegment}/{slug}', ProductPage::class)
    ->name('product.view')
    ->where('productsSegment', 'producten|products|produkte|productos');

// Collections
Route::get('/{collectionsSegment}', CollectionsIndex::class)
    ->name('collections.index')
    ->where('collectionsSegment', 'collecties|collections|kollektionen|colecciones');

Route::get('/{collectionsSegment}/{slug}', CollectionPage::class)
    ->name('collection.view')
    ->where('collectionsSegment', 'collecties|collections|kollektionen|colecciones');
```

**Step 3: Add new explicit product/collection index routes and catch-all**

Add after the Home route (line 37), before Search:

```php
// Product listing (explicit translated URLs)
Route::get('/{allProductsSegment}', ProductsIndex::class)
    ->name('products.index')
    ->where('allProductsSegment', 'alle-producten|all-products|alle-produkte|todos-productos');

// Collections listing (explicit translated URLs)
Route::get('/{collectionsSegment}', CollectionsIndex::class)
    ->name('collections.index')
    ->where('collectionsSegment', 'collecties|collections|kollektionen|colecciones');
```

**Step 4: Add catch-all route at the END of the localized group**

After all other routes in the first localized group (after articles), add:

```php
// Catch-all for products and collections (must be LAST)
Route::get('/{slug}', UnifiedSlugResolver::class)
    ->name('slug.resolve')
    ->where('slug', '[a-z0-9-]+');
```

**Step 5: Commit**

```bash
git add sandbox/routes/web.php
git commit -m "feat: update routes for root product/collection URLs"
```

---

### Task 3: Update LocalizedRoute service

**Files:**
- Modify: `sandbox/app/Services/LocalizedRoute.php`

**Step 1: Update product() method**

Replace the return statement (line 126) to use the new route:

```php
public function product(Product $product, ?string $locale = null): string
{
    $locale = $locale ?? $this->currentLocale();

    // Try to get locale-specific URL
    $language = Language::where('code', $locale)->first();
    $url = null;

    if ($language) {
        $url = $product->urls()
            ->where('language_id', $language->id)
            ->first();
    }

    // Fallback to default URL
    if (! $url) {
        $url = $product->defaultUrl;
    }

    $slug = $url?->slug ?? $product->id;

    // Direct URL without segment
    return "/{$locale}/{$slug}";
}
```

**Step 2: Update collection() method**

Replace the return statement (line 153) to use the new route:

```php
public function collection(Collection $collection, ?string $locale = null): string
{
    $locale = $locale ?? $this->currentLocale();

    // Try to get locale-specific URL
    $language = Language::where('code', $locale)->first();
    $url = null;

    if ($language) {
        $url = $collection->urls()
            ->where('language_id', $language->id)
            ->first();
    }

    // Fallback to default URL
    if (! $url) {
        $url = $collection->defaultUrl;
    }

    $slug = $url?->slug ?? $collection->id;

    // Direct URL without segment
    return "/{$locale}/{$slug}";
}
```

**Step 3: Update switchTo() method for new route name**

Update the route name checks (lines 174, 184):

```php
// If on a product or collection page via slug resolver
if ($routeName === 'slug.resolve') {
    $slug = $route->parameter('slug');

    // Try product first
    $product = $this->findProductBySlug($slug);
    if ($product) {
        return $this->product($product, $newLocale);
    }

    // Then try collection
    $collection = $this->findCollectionBySlug($slug);
    if ($collection) {
        return $this->collection($collection, $newLocale);
    }
}

// Legacy route names (can be removed later)
if ($routeName === 'product.view') {
    // ... existing code
}

if ($routeName === 'collection.view') {
    // ... existing code
}
```

**Step 4: Update segmentMap to remove products/collections entries**

Remove or update these entries from the segmentMap array:

```php
// Remove these:
'products.index' => ['key' => 'products', 'param' => 'productsSegment'],
'product.view' => ['key' => 'products', 'param' => 'productsSegment'],
'collection.view' => ['key' => 'collections', 'param' => 'collectionsSegment'],

// Update collections.index:
'collections.index' => ['key' => 'collections', 'param' => 'collectionsSegment'],

// Add products.index with new key:
'products.index' => ['key' => 'all-products', 'param' => 'allProductsSegment'],
```

**Step 5: Commit**

```bash
git add sandbox/app/Services/LocalizedRoute.php
git commit -m "refactor: update LocalizedRoute for root URLs"
```

---

### Task 4: Update localization config

**Files:**
- Modify: `sandbox/config/localization.php`

**Step 1: Add all-products segment translations**

Add to each locale's route_segments array:

```php
'nl' => [
    // ... existing
    'all-products' => 'alle-producten',
],
'en' => [
    // ... existing
    'all-products' => 'all-products',
],
'de' => [
    // ... existing
    'all-products' => 'alle-produkte',
],
'es' => [
    // ... existing
    'all-products' => 'todos-productos',
],
```

**Step 2: Commit**

```bash
git add sandbox/config/localization.php
git commit -m "config: add all-products route segment translations"
```

---

### Task 5: Test the implementation

**Step 1: Clear route cache**

```bash
cd sandbox && php artisan route:clear && php artisan cache:clear
```

**Step 2: Test routes manually**

Start the dev server and test:
- `http://localhost/nl` - Home page
- `http://localhost/nl/alle-producten` - Products index
- `http://localhost/nl/collecties` - Collections index
- `http://localhost/nl/{product-slug}` - Product page (should work)
- `http://localhost/nl/{collection-slug}` - Collection page (should work)
- `http://localhost/nl/contact` - Contact page (should still work)

**Step 3: Commit final**

```bash
git add -A
git commit -m "feat: implement root URLs for products and collections

- Products and collections now use /{locale}/{slug} format
- Unified slug resolver determines entity type from database
- Static routes (contact, checkout, etc.) have priority
- Product/collection index pages use explicit translated URLs"
```

---

## Summary

| Task | Description |
|------|-------------|
| 1 | Create UnifiedSlugResolver component |
| 2 | Update web.php routes |
| 3 | Update LocalizedRoute service |
| 4 | Update localization config |
| 5 | Test and verify |

## Notes

- ProductPage and CollectionPage components blijven bestaan voor backwards compatibility
- UnifiedSlugResolver delegeert naar de bestaande views
- Geen 301 redirects nodig (website nog niet live)
