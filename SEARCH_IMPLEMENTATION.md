# Search Implementation Guide

## Current Implementation

The application uses **Lunar Search** package with automatic fallback to database search. This provides the best of both worlds: powerful search capabilities with reliability.

### Search Locations

1. **Header Search Form** ([header.blade.php](sandbox/resources/views/components/storefront/header.blade.php))
   - Main search form in the header
   - Includes category dropdown filter
   - Mobile-responsive design
   - Submits to `/search` route

2. **Live Search Component** ([LiveSearch.php](sandbox/app/Livewire/Components/LiveSearch.php))
   - Real-time search suggestions
   - Shows top 6 results as you type
   - Minimum 2 characters required
   - Searches in product attributes and SKU

3. **Search Results Page** ([SearchPage.php](sandbox/app/Livewire/Pages/SearchPage.php))
   - Full search results with pagination
   - Shows 12 products per page
   - Displays total result count
   - Empty state for no results

### Search Logic

The application uses Lunar Search with intelligent fallback:

**Primary Method: Lunar Search**
```php
$searchResults = Search::on(Product::class)
    ->query($this->query)
    ->get();
```

**Fallback Method: Database Search**
If Lunar Search is unavailable or fails, the system automatically falls back to database queries:
- Product `attribute_data` (includes name, description, etc.)
- Product variant SKUs
- Only published products with prices
- Results ordered by relevance

```php
// Fallback search in product attributes and SKUs
$q->where('attribute_data', 'like', $searchTerm)
  ->orWhereHas('variants', function($variantQuery) use ($searchTerm) {
      $variantQuery->where('sku', 'like', $searchTerm);
  });
```

### Routes

```php
// Search results page
Route::get('/search', SearchPage::class)->name('search.view');
```

## Lunar Search Setup

### Installation Status

✅ **Installed** - The Lunar Search package is already installed and configured.

### Scout Driver Configuration

Lunar Search supports multiple Scout drivers. Choose one based on your needs:

#### 1. Meilisearch (Recommended for Production)

```bash
# Install Scout driver
composer require laravel/scout
composer require meilisearch/meilisearch-php

# Install and run Meilisearch server
# https://www.meilisearch.com/docs/learn/getting_started/installation
```

Update `.env`:
```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=your-master-key
```

Index your products:
```bash
php artisan scout:import "Lunar\Models\Product"
```

#### 2. Database Driver (Development)

For local development without external services:

```env
SCOUT_DRIVER=database
```

Then index products:
```bash
php artisan scout:import "Lunar\Models\Product"
```

#### 3. Algolia (Production Alternative)

```bash
composer require algolia/algoliasearch-client-php
```

Update `.env`:
```env
SCOUT_DRIVER=algolia
ALGOLIA_APP_ID=your-app-id
ALGOLIA_SECRET=your-secret-key
```

### Facet Configuration

Configure search facets in `config/lunar/search.php`:

```php
'facets' => [
    \Lunar\Models\Product::class => [
        'brand' => ['label' => 'Brand'],
        'category' => ['label' => 'Category'],
        'price' => ['label' => 'Price Range'],
    ]
]
```

### Advanced Search with Facets

Once you've configured a Scout driver and indexed products, you can add faceted search:

```php
use Lunar\Search\Facades\Search;

// Basic search
$results = Search::on(Product::class)
    ->query($this->query)
    ->get();

// With facets
$results = Search::on(Product::class)
    ->query($this->query)
    ->facet('brand', ['Nike', 'Adidas'])
    ->facet('category', ['Electronics'])
    ->get();
```

### Current Benefits

✅ **Intelligent Fallback**: Automatic fallback to database search if Scout unavailable
✅ **Production Ready**: Works out of the box without external services
✅ **Scalable**: Easy to add Meilisearch or Algolia when needed

### Future Benefits (with Scout driver)

- **Faster Search**: Lightning-fast results with Meilisearch/Algolia
- **Faceted Filtering**: Filter by attributes, categories, brands
- **Typo Tolerance**: Better handling of misspellings
- **Relevance Tuning**: Configure search result ranking
- **Better Performance**: Scales to millions of products

## Testing Search

1. Navigate to the homepage
2. Use the search bar in the header
3. Try searching for:
   - Product names
   - Product descriptions
   - SKU numbers
   - Partial matches

## Notes

- Minimum 2 characters required to trigger search
- Search is case-insensitive
- Results are paginated (12 per page)
- Only published products with prices are searchable
