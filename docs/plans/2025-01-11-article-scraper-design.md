# Article Scraper & CMS Design

## Overview

Scrape klantenservice en blog artikelen van Probo.nl, sla ze op als concepten in Lunar, en bied AI-herschrijving aan zodat content copyright-proof is voordat deze gepubliceerd wordt.

## Scope

### In scope
- Article model met meertalige ondersteuning
- Scraper voor Probo.nl klantenservice (~60 artikelen) en blog (`/ontdek`)
- Filament admin resource voor artikelen
- AI-herschrijving via Claude (per artikel + batch command)
- Homepage integratie

### Out of scope
- Publieke artikel detailpagina's (later)
- Zoekfunctionaliteit in artikelen (later)
- Artikel revisies/versioning (later)

## Data Model

### Article

```php
Schema::create('lunar_articles', function (Blueprint $table) {
    $table->id();
    $table->json('title');           // TranslatedText {nl: "...", en: "..."}
    $table->string('slug')->unique();
    $table->json('excerpt')->nullable();        // TranslatedText
    $table->json('body');                       // TranslatedText
    $table->json('meta_description')->nullable(); // TranslatedText
    $table->string('featured_image')->nullable();
    $table->string('category');       // klantenservice, blog
    $table->string('subcategory')->nullable(); // bestellen, bezorgen, nieuws, etc.
    $table->json('tags')->nullable(); // ["marketing", "tips"]
    $table->string('source_url')->nullable();   // originele Probo URL
    $table->json('original_content')->nullable(); // backup van origineel
    $table->enum('status', ['draft', 'rewritten', 'published'])->default('draft');
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
});
```

### ArticleCategory (enum of model)

Klantenservice subcategorieën:
- bestellen
- algemeen
- bezorgen
- betalen

Blog subcategorieën:
- nieuws
- marketingtip
- services
- producten
- fun
- inspiratie
- verkoopondersteuning

## Components

### 1. Article Model (`packages/core/src/Models/Article.php`)

```php
class Article extends BaseModel
{
    use HasTranslations;

    protected $table = 'lunar_articles';

    protected $translatable = ['title', 'excerpt', 'body', 'meta_description'];

    protected $casts = [
        'tags' => 'array',
        'original_content' => 'array',
        'published_at' => 'datetime',
    ];

    public function scopePublished($query) { ... }
    public function scopeByCategory($query, $category) { ... }
}
```

### 2. Scraper Commands

**`lunar:scrape-articles`** - Hoofdcommand voor scrapen

```bash
# Scrape klantenservice artikelen
php artisan lunar:scrape-articles --source=probo-klantenservice

# Scrape blog artikelen
php artisan lunar:scrape-articles --source=probo-blog

# Beide
php artisan lunar:scrape-articles --source=probo-all

# Dry run
php artisan lunar:scrape-articles --source=probo-all --dry-run

# Limit
php artisan lunar:scrape-articles --source=probo-blog --limit=10
```

**`lunar:rewrite-articles`** - Batch AI herschrijving

```bash
# Herschrijf alle drafts
php artisan lunar:rewrite-articles

# Alleen specifieke categorie
php artisan lunar:rewrite-articles --category=klantenservice

# Limit
php artisan lunar:rewrite-articles --limit=5

# Dry run (toon wat zou worden herschreven)
php artisan lunar:rewrite-articles --dry-run
```

### 3. Scraper Services

**`ProboArticleScraper`** - Playwright-based scraper

```
ProboArticleScraper
├── scrapeKlantenservice()     # Alle FAQ artikelen
├── scrapeBlog()               # /ontdek artikelen met paginering
├── scrapeArticlePage($url)    # Individueel artikel
└── detectCategory($url)       # Bepaal categorie uit URL
```

Scraper flow:
1. Login op Probo.nl (hergebruik bestaande auth)
2. Navigeer naar klantenservice of blog index
3. Verzamel alle artikel URLs
4. Per artikel: scrape titel, body, afbeeldingen
5. Sla op met status=draft en original_content backup

### 4. AI Rewriter Service

**`ArticleRewriterService`**

```php
class ArticleRewriterService
{
    public function rewrite(Article $article, string $locale = 'nl'): string
    {
        $prompt = $this->buildPrompt($article, $locale);
        return $this->claude->complete($prompt);
    }

    private function buildPrompt(Article $article, string $locale): string
    {
        return <<<PROMPT
        Herschrijf het volgende artikel zodat het:
        - Uniek is en geen copyright schendt
        - Dezelfde informatie bevat
        - Professioneel en helder geschreven is
        - Geschikt is voor een print/sign webshop (Drukhoek)
        - In het {$locale} is

        Origineel artikel:
        {$article->getTranslation('body', $locale)}

        Geef alleen de herschreven tekst terug, geen uitleg.
        PROMPT;
    }
}
```

### 5. Filament Admin Resource

**`ArticleResource`** (`packages/admin/src/Filament/Resources/ArticleResource.php`)

Features:
- Tabel met titel, categorie, status, datum
- Filters op status en categorie
- Form met rich text editor voor body
- **"Herschrijf met AI" action** per artikel
- Bulk actions: publish, unpublish, delete
- Preview van origineel vs herschreven content

### 6. Homepage Integratie

Update `sandbox/app/Livewire/Pages/Home.php`:

```php
public function render()
{
    return view('livewire.pages.home', [
        'articles' => Article::published()
            ->orderBy('published_at', 'desc')
            ->take(4)
            ->get(),
        // ... existing props
    ]);
}
```

## File Structure

```
packages/core/
├── database/migrations/
│   └── 2025_01_11_100000_create_articles_table.php
├── src/Models/
│   ├── Article.php
│   └── Contracts/Article.php

packages/scraper/
├── src/
│   ├── Console/Commands/
│   │   ├── ScrapeArticlesCommand.php
│   │   └── RewriteArticlesCommand.php
│   ├── Services/
│   │   ├── ProboArticleScraper.php
│   │   └── ArticleRewriterService.php
│   └── scripts/
│       └── scrape-article.js    # Playwright script

packages/admin/
├── src/Filament/Resources/
│   ├── ArticleResource.php
│   └── ArticleResource/
│       └── Pages/
│           ├── ListArticles.php
│           ├── CreateArticle.php
│           └── EditArticle.php
```

## Implementation Steps

1. **Database & Model** - Migratie en Article model in core package
2. **Scraper infrastructure** - Playwright script voor artikel scraping
3. **ScrapeArticlesCommand** - Artisan command om te scrapen
4. **ArticleRewriterService** - Claude integratie voor herschrijven
5. **RewriteArticlesCommand** - Batch herschrijf command
6. **Filament ArticleResource** - Admin interface met AI knop
7. **Homepage update** - Artikelen uit database tonen

## AI Prompt Guidelines

Het herschrijf-prompt moet:
- Informatie behouden maar anders formuleren
- Probo-specifieke referenties vervangen door generieke of Drukhoek termen
- Tone of voice consistent houden (professioneel, behulpzaam)
- Geen nieuwe informatie toevoegen
- Formatting (koppen, lijsten) behouden
