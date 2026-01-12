# Lunar Scraper

A Playwright-based web scraper for importing products from suppliers into Lunar.

## Installation

The scraper requires Node.js and Playwright. Install the dependencies:

```bash
cd packages/scraper/scripts
npm install
npx playwright install chromium
```

## Configuration

Set your Probo credentials in `.env`:

```env
PROBO_USERNAME=your-email@example.com
PROBO_PASSWORD=your-password
```

Or pass them as command options with `--username` and `--password`.

## Commands

### Scrape All Products from Sitemap

Scrape all products from the Probo.nl sitemap. The command logs in once, then checks each URL to determine if it's a product page before scraping.

```bash
# Dry run - see which URLs would be scraped
php artisan lunar:scraper:probo-all --dry-run

# Limit to first 20 products
php artisan lunar:scraper:probo-all --limit=20

# Skip first 100, scrape next 50
php artisan lunar:scraper:probo-all --offset=100 --limit=50

# Scrape with visible browser for debugging
php artisan lunar:scraper:probo-all --limit=10 --no-headless

# Scrape all and import to Lunar
php artisan lunar:scraper:probo-all --import --supplier=probo

# Save results to JSON file
php artisan lunar:scraper:probo-all --output=probo-products.json

# Use custom sitemap path
php artisan lunar:scraper:probo-all --sitemap=/path/to/sitemap.xml
```

**Options:**

| Option | Description |
|--------|-------------|
| `--sitemap=PATH` | Path to sitemap.xml file |
| `--limit=N` | Limit number of URLs to process (0 = no limit) |
| `--offset=N` | Skip first N URLs |
| `--output=FILE` | Save JSON results to file |
| `--headless` | Run browser in headless mode (default) |
| `--no-headless` | Show browser window for debugging |
| `--import` | Import scraped products to Lunar |
| `--supplier=HANDLE` | Supplier handle (default: probo) |
| `--download-images` | Download original images from supplier |
| `--rewrite` | Rewrite content with Claude AI |
| `--dry-run` | Only show which URLs would be scraped |

### Scrape Single Product

```bash
# Scrape a single product page
php artisan lunar:scraper:probo --url=https://www.probo.nl/dibond --product

# Scrape and import
php artisan lunar:scraper:probo --url=https://www.probo.nl/dibond --product --import
```

### Scrape Multiple Products (Attribute Discovery)

Useful for discovering all attribute types across different products:

```bash
php artisan lunar:scraper:probo \
  --url=https://www.probo.nl/quapro-flag \
  --url=https://www.probo.nl/dibond \
  --url=https://www.probo.nl/mesh-spandoek \
  --multi
```

### Scrape Category

```bash
php artisan lunar:scraper:probo --url=https://www.probo.nl/materialen/textiel
```

## How It Works

1. **Authentication**: Logs into Probo.nl with your credentials
2. **Page Detection**: Checks if each URL is a product page or category page
3. **Multi-Language Scraping**: Scrapes each product in both NL and EN for translations
4. **Attribute Extraction**: Extracts product specifications and features
5. **Import**: Creates Lunar Product, ProductVariant, SupplierProduct, and Prices

## Data Flow

```
Sitemap URLs
    ↓
isProductPage() check
    ↓
scrapeProductPage() (NL + EN)
    ↓
mergeTranslations()
    ↓
ProboProductImporter
    ↓
Lunar Models:
  - SupplierProduct
  - Product (with TranslatedText attributes)
  - ProductVariant
  - Price
```

## Dynamic Attribute Creation

When importing, the scraper automatically:

1. Creates new Attributes for any discovered product specifications
2. Attaches them to the "Probo Supplier Product" ProductType
3. Groups them into "Technical Specifications" or "Features" based on type
4. Determines field types (Text, Number, Toggle) based on naming patterns

## Sitemap

The package includes a `sitemap.xml` file with all Probo product URLs. Products are identified by having `<image:image>` tags in the sitemap (categories don't have images).

To update the sitemap, download a fresh copy from Probo.nl.
