# Article Scraper Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Scrape Probo.nl artikelen (klantenservice + blog) en bied AI-herschrijving aan voor copyright-proof content.

**Architecture:** Article model in Lunar core met HasTranslations trait. Scraper commands in scraper package. Filament admin resource voor beheer. Claude API voor herschrijving.

**Tech Stack:** Laravel, Filament v3, Playwright (Node.js), Anthropic Claude API

---

## Task 1: Database Migration

**Files:**
- Create: `packages/core/database/migrations/2025_01_11_100000_create_articles_table.php`

**Step 1: Create the migration file**

```php
<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'articles', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->string('slug')->unique();
            $table->json('excerpt')->nullable();
            $table->json('body');
            $table->json('meta_description')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('category'); // klantenservice, blog
            $table->string('subcategory')->nullable();
            $table->json('tags')->nullable();
            $table->string('source_url')->nullable();
            $table->json('original_content')->nullable();
            $table->string('status')->default('draft'); // draft, rewritten, published
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('category');
            $table->index('status');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'articles');
    }
};
```

**Step 2: Run migration in sandbox**

```bash
cd sandbox && php artisan migrate
```

Expected: Migration runs successfully, `lunar_articles` table created.

**Step 3: Commit**

```bash
git add packages/core/database/migrations/2025_01_11_100000_create_articles_table.php
git commit -m "feat(core): add articles table migration"
```

---

## Task 2: Article Model Contract

**Files:**
- Create: `packages/core/src/Models/Contracts/Article.php`

**Step 1: Create the contract interface**

```php
<?php

namespace Lunar\Models\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Article
{
    public function scopePublished(Builder $query): Builder;

    public function scopeByCategory(Builder $query, string $category): Builder;

    public function scopeDraft(Builder $query): Builder;

    public function isPublished(): bool;
}
```

**Step 2: Commit**

```bash
git add packages/core/src/Models/Contracts/Article.php
git commit -m "feat(core): add Article contract interface"
```

---

## Task 3: Article Model

**Files:**
- Create: `packages/core/src/Models/Article.php`
- Modify: `packages/core/src/LunarServiceProvider.php`

**Step 1: Create the Article model**

```php
<?php

namespace Lunar\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Lunar\Base\BaseModel;
use Lunar\Base\Traits\HasTranslations;
use Lunar\Models\Contracts\Article as ArticleContract;

/**
 * @property int $id
 * @property array $title
 * @property string $slug
 * @property ?array $excerpt
 * @property array $body
 * @property ?array $meta_description
 * @property ?string $featured_image
 * @property string $category
 * @property ?string $subcategory
 * @property ?array $tags
 * @property ?string $source_url
 * @property ?array $original_content
 * @property string $status
 * @property ?\Illuminate\Support\Carbon $published_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 */
class Article extends BaseModel implements ArticleContract
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'title' => 'array',
        'excerpt' => 'array',
        'body' => 'array',
        'meta_description' => 'array',
        'tags' => 'array',
        'original_content' => 'array',
        'published_at' => 'datetime',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function getTitle(?string $locale = null): ?string
    {
        return $this->translate('title', $locale);
    }

    public function getExcerpt(?string $locale = null): ?string
    {
        return $this->translate('excerpt', $locale);
    }

    public function getBody(?string $locale = null): ?string
    {
        return $this->translate('body', $locale);
    }

    public function getMetaDescription(?string $locale = null): ?string
    {
        return $this->translate('meta_description', $locale);
    }
}
```

**Step 2: Register model in LunarServiceProvider**

Open `packages/core/src/LunarServiceProvider.php` and add to the `$models` array:

```php
// Find the $models property and add:
\Lunar\Models\Contracts\Article::class => \Lunar\Models\Article::class,
```

**Step 3: Commit**

```bash
git add packages/core/src/Models/Article.php packages/core/src/LunarServiceProvider.php
git commit -m "feat(core): add Article model with translations"
```

---

## Task 4: Article Filament Resource

**Files:**
- Create: `packages/admin/src/Filament/Resources/ArticleResource.php`
- Create: `packages/admin/src/Filament/Resources/ArticleResource/Pages/ListArticles.php`
- Create: `packages/admin/src/Filament/Resources/ArticleResource/Pages/CreateArticle.php`
- Create: `packages/admin/src/Filament/Resources/ArticleResource/Pages/EditArticle.php`
- Create: `packages/admin/resources/lang/en/article.php`
- Create: `packages/admin/resources/lang/nl/article.php`
- Modify: `packages/admin/src/LunarPanelManager.php`

**Step 1: Create the ArticleResource**

```php
<?php

namespace Lunar\Admin\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Lunar\Admin\Filament\Resources\ArticleResource\Pages;
use Lunar\Admin\Support\Resources\BaseResource;
use Lunar\Models\Contracts\Article as ArticleContract;

class ArticleResource extends BaseResource
{
    protected static ?string $permission = 'content:manage-articles';

    protected static ?string $model = ArticleContract::class;

    protected static ?int $navigationSort = 100;

    public static function getLabel(): string
    {
        return __('lunarpanel::article.label');
    }

    public static function getPluralLabel(): string
    {
        return __('lunarpanel::article.plural_label');
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('lunarpanel::global.sections.content');
    }

    public static function getDefaultForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\TextInput::make('title.nl')
                                ->label(__('lunarpanel::article.form.title.label').' (NL)')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('title.en')
                                ->label(__('lunarpanel::article.form.title.label').' (EN)')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('slug')
                                ->label(__('lunarpanel::article.form.slug.label'))
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),
                            Forms\Components\Textarea::make('excerpt.nl')
                                ->label(__('lunarpanel::article.form.excerpt.label').' (NL)')
                                ->rows(3),
                            Forms\Components\Textarea::make('excerpt.en')
                                ->label(__('lunarpanel::article.form.excerpt.label').' (EN)')
                                ->rows(3),
                            Forms\Components\RichEditor::make('body.nl')
                                ->label(__('lunarpanel::article.form.body.label').' (NL)')
                                ->required()
                                ->columnSpanFull(),
                            Forms\Components\RichEditor::make('body.en')
                                ->label(__('lunarpanel::article.form.body.label').' (EN)')
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpan(['lg' => 2]),
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->label(__('lunarpanel::article.form.status.label'))
                                ->options([
                                    'draft' => __('lunarpanel::article.form.status.options.draft'),
                                    'rewritten' => __('lunarpanel::article.form.status.options.rewritten'),
                                    'published' => __('lunarpanel::article.form.status.options.published'),
                                ])
                                ->default('draft')
                                ->required(),
                            Forms\Components\Select::make('category')
                                ->label(__('lunarpanel::article.form.category.label'))
                                ->options([
                                    'klantenservice' => __('lunarpanel::article.form.category.options.klantenservice'),
                                    'blog' => __('lunarpanel::article.form.category.options.blog'),
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('subcategory')
                                ->label(__('lunarpanel::article.form.subcategory.label')),
                            Forms\Components\TagsInput::make('tags')
                                ->label(__('lunarpanel::article.form.tags.label')),
                            Forms\Components\DateTimePicker::make('published_at')
                                ->label(__('lunarpanel::article.form.published_at.label')),
                            Forms\Components\TextInput::make('source_url')
                                ->label(__('lunarpanel::article.form.source_url.label'))
                                ->url()
                                ->disabled(),
                            Forms\Components\Textarea::make('meta_description.nl')
                                ->label(__('lunarpanel::article.form.meta_description.label').' (NL)')
                                ->rows(2),
                            Forms\Components\Textarea::make('meta_description.en')
                                ->label(__('lunarpanel::article.form.meta_description.label').' (EN)')
                                ->rows(2),
                        ])
                        ->columnSpan(['lg' => 1]),
                ])
                ->columns(3),
        ]);
    }

    public static function getDefaultTable(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title.nl')
                    ->label(__('lunarpanel::article.table.title.label'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('category')
                    ->label(__('lunarpanel::article.table.category.label'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'klantenservice' => 'info',
                        'blog' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('lunarpanel::article.table.status.label'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'rewritten' => 'warning',
                        'published' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('published_at')
                    ->label(__('lunarpanel::article.table.published_at.label'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('lunarpanel::article.table.updated_at.label'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => __('lunarpanel::article.form.status.options.draft'),
                        'rewritten' => __('lunarpanel::article.form.status.options.rewritten'),
                        'published' => __('lunarpanel::article.form.status.options.published'),
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'klantenservice' => __('lunarpanel::article.form.category.options.klantenservice'),
                        'blog' => __('lunarpanel::article.form.category.options.blog'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
```

**Step 2: Create ListArticles page**

```php
<?php

namespace Lunar\Admin\Filament\Resources\ArticleResource\Pages;

use Filament\Actions;
use Lunar\Admin\Filament\Resources\ArticleResource;
use Lunar\Admin\Support\Pages\BaseListRecords;

class ListArticles extends BaseListRecords
{
    protected static string $resource = ArticleResource::class;

    protected function getDefaultHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
```

**Step 3: Create CreateArticle page**

```php
<?php

namespace Lunar\Admin\Filament\Resources\ArticleResource\Pages;

use Lunar\Admin\Filament\Resources\ArticleResource;
use Lunar\Admin\Support\Pages\BaseCreateRecord;

class CreateArticle extends BaseCreateRecord
{
    protected static string $resource = ArticleResource::class;
}
```

**Step 4: Create EditArticle page with AI rewrite action**

```php
<?php

namespace Lunar\Admin\Filament\Resources\ArticleResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Lunar\Admin\Filament\Resources\ArticleResource;
use Lunar\Admin\Support\Pages\BaseEditRecord;
use Lunar\Scraper\Services\ArticleRewriterService;

class EditArticle extends BaseEditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getDefaultHeaderActions(): array
    {
        return [
            Actions\Action::make('rewrite')
                ->label(__('lunarpanel::article.actions.rewrite.label'))
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('lunarpanel::article.actions.rewrite.modal_heading'))
                ->modalDescription(__('lunarpanel::article.actions.rewrite.modal_description'))
                ->action(function () {
                    $rewriter = app(ArticleRewriterService::class);

                    try {
                        $rewriter->rewrite($this->record);

                        $this->record->update(['status' => 'rewritten']);

                        Notification::make()
                            ->title(__('lunarpanel::article.actions.rewrite.success'))
                            ->success()
                            ->send();

                        $this->refreshFormData(['body', 'status']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('lunarpanel::article.actions.rewrite.error'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => $this->record->status === 'draft'),
            Actions\DeleteAction::make(),
        ];
    }
}
```

**Step 5: Create English translations**

File: `packages/admin/resources/lang/en/article.php`

```php
<?php

return [
    'label' => 'Article',
    'plural_label' => 'Articles',

    'form' => [
        'title' => [
            'label' => 'Title',
        ],
        'slug' => [
            'label' => 'Slug',
        ],
        'excerpt' => [
            'label' => 'Excerpt',
        ],
        'body' => [
            'label' => 'Body',
        ],
        'meta_description' => [
            'label' => 'Meta Description',
        ],
        'status' => [
            'label' => 'Status',
            'options' => [
                'draft' => 'Draft',
                'rewritten' => 'Rewritten',
                'published' => 'Published',
            ],
        ],
        'category' => [
            'label' => 'Category',
            'options' => [
                'klantenservice' => 'Customer Service',
                'blog' => 'Blog',
            ],
        ],
        'subcategory' => [
            'label' => 'Subcategory',
        ],
        'tags' => [
            'label' => 'Tags',
        ],
        'published_at' => [
            'label' => 'Published At',
        ],
        'source_url' => [
            'label' => 'Source URL',
        ],
    ],

    'table' => [
        'title' => [
            'label' => 'Title',
        ],
        'category' => [
            'label' => 'Category',
        ],
        'status' => [
            'label' => 'Status',
        ],
        'published_at' => [
            'label' => 'Published At',
        ],
        'updated_at' => [
            'label' => 'Updated At',
        ],
    ],

    'actions' => [
        'rewrite' => [
            'label' => 'Rewrite with AI',
            'modal_heading' => 'Rewrite Article with AI',
            'modal_description' => 'This will use Claude AI to rewrite the article content to make it unique and copyright-proof. The original content will be preserved.',
            'success' => 'Article has been rewritten successfully.',
            'error' => 'Failed to rewrite article.',
        ],
    ],
];
```

**Step 6: Create Dutch translations**

File: `packages/admin/resources/lang/nl/article.php`

```php
<?php

return [
    'label' => 'Artikel',
    'plural_label' => 'Artikelen',

    'form' => [
        'title' => [
            'label' => 'Titel',
        ],
        'slug' => [
            'label' => 'Slug',
        ],
        'excerpt' => [
            'label' => 'Samenvatting',
        ],
        'body' => [
            'label' => 'Inhoud',
        ],
        'meta_description' => [
            'label' => 'Meta Omschrijving',
        ],
        'status' => [
            'label' => 'Status',
            'options' => [
                'draft' => 'Concept',
                'rewritten' => 'Herschreven',
                'published' => 'Gepubliceerd',
            ],
        ],
        'category' => [
            'label' => 'Categorie',
            'options' => [
                'klantenservice' => 'Klantenservice',
                'blog' => 'Blog',
            ],
        ],
        'subcategory' => [
            'label' => 'Subcategorie',
        ],
        'tags' => [
            'label' => 'Tags',
        ],
        'published_at' => [
            'label' => 'Gepubliceerd Op',
        ],
        'source_url' => [
            'label' => 'Bron URL',
        ],
    ],

    'table' => [
        'title' => [
            'label' => 'Titel',
        ],
        'category' => [
            'label' => 'Categorie',
        ],
        'status' => [
            'label' => 'Status',
        ],
        'published_at' => [
            'label' => 'Gepubliceerd Op',
        ],
        'updated_at' => [
            'label' => 'Bijgewerkt Op',
        ],
    ],

    'actions' => [
        'rewrite' => [
            'label' => 'Herschrijf met AI',
            'modal_heading' => 'Artikel Herschrijven met AI',
            'modal_description' => 'Dit gebruikt Claude AI om de artikelinhoud te herschrijven zodat deze uniek en copyright-vrij is. De originele inhoud wordt bewaard.',
            'success' => 'Artikel is succesvol herschreven.',
            'error' => 'Fout bij herschrijven van artikel.',
        ],
    ],
];
```

**Step 7: Register resource in LunarPanelManager**

Open `packages/admin/src/LunarPanelManager.php` and add to the `$resources` array:

```php
// Find the resources array and add:
\Lunar\Admin\Filament\Resources\ArticleResource::class,
```

**Step 8: Add content section translation**

Check if `content` section exists in `packages/admin/resources/lang/en/global.php`. If not, add:

```php
'sections' => [
    // ... existing sections
    'content' => 'Content',
],
```

And in `packages/admin/resources/lang/nl/global.php`:

```php
'sections' => [
    // ... existing sections
    'content' => 'Content',
],
```

**Step 9: Commit**

```bash
git add packages/admin/src/Filament/Resources/ArticleResource.php \
    packages/admin/src/Filament/Resources/ArticleResource/Pages/ListArticles.php \
    packages/admin/src/Filament/Resources/ArticleResource/Pages/CreateArticle.php \
    packages/admin/src/Filament/Resources/ArticleResource/Pages/EditArticle.php \
    packages/admin/resources/lang/en/article.php \
    packages/admin/resources/lang/nl/article.php \
    packages/admin/src/LunarPanelManager.php \
    packages/admin/resources/lang/en/global.php \
    packages/admin/resources/lang/nl/global.php
git commit -m "feat(admin): add ArticleResource with AI rewrite action"
```

---

## Task 5: ArticleRewriterService

**Files:**
- Create: `packages/scraper/src/Services/ArticleRewriterService.php`

**Step 1: Create the service**

```php
<?php

namespace Lunar\Scraper\Services;

use Lunar\Models\Article;

class ArticleRewriterService
{
    public function __construct(
        protected ClaudeClient $claude
    ) {}

    public function rewrite(Article $article, array $locales = ['nl', 'en']): Article
    {
        // Backup original content if not already backed up
        if (empty($article->original_content)) {
            $article->original_content = [
                'title' => $article->title,
                'body' => $article->body,
                'excerpt' => $article->excerpt,
            ];
        }

        $rewrittenBody = [];
        $rewrittenExcerpt = [];

        foreach ($locales as $locale) {
            $originalBody = $article->body[$locale] ?? null;
            $originalExcerpt = $article->excerpt[$locale] ?? null;

            if ($originalBody) {
                $rewrittenBody[$locale] = $this->rewriteContent($originalBody, $locale);
            }

            if ($originalExcerpt) {
                $rewrittenExcerpt[$locale] = $this->rewriteContent($originalExcerpt, $locale, 'excerpt');
            }
        }

        $article->update([
            'body' => array_merge($article->body ?? [], $rewrittenBody),
            'excerpt' => array_merge($article->excerpt ?? [], $rewrittenExcerpt),
            'original_content' => $article->original_content,
        ]);

        return $article->fresh();
    }

    protected function rewriteContent(string $content, string $locale, string $type = 'body'): string
    {
        $languageName = match ($locale) {
            'nl' => 'Dutch',
            'en' => 'English',
            'de' => 'German',
            default => 'Dutch',
        };

        $lengthGuidance = $type === 'excerpt'
            ? 'Keep it concise, around 1-2 sentences.'
            : 'Maintain similar length and structure to the original.';

        $prompt = <<<PROMPT
Rewrite the following article content so that it:
- Is unique and does not infringe copyright
- Contains the same information and meaning
- Is written professionally and clearly
- Is suitable for a print/sign webshop called "Drukhoek"
- Replaces any Probo-specific references with generic terms or "Drukhoek"
- Maintains the same formatting (headings, lists, paragraphs)
- Is written in {$languageName}
- {$lengthGuidance}

IMPORTANT: Return ONLY the rewritten text, no explanations or commentary.

Original content:
{$content}
PROMPT;

        return $this->claude->complete($prompt);
    }

    public function rewriteBatch(iterable $articles, array $locales = ['nl', 'en']): int
    {
        $count = 0;

        foreach ($articles as $article) {
            $this->rewrite($article, $locales);
            $article->update(['status' => 'rewritten']);
            $count++;
        }

        return $count;
    }
}
```

**Step 2: Commit**

```bash
git add packages/scraper/src/Services/ArticleRewriterService.php
git commit -m "feat(scraper): add ArticleRewriterService for AI content rewriting"
```

---

## Task 6: ScrapeArticlesCommand

**Files:**
- Create: `packages/scraper/src/Console/Commands/ScrapeArticlesCommand.php`
- Create: `packages/scraper/scripts/scrape-article.js`
- Modify: `packages/scraper/src/ScraperServiceProvider.php`

**Step 1: Create the Playwright script**

File: `packages/scraper/scripts/scrape-article.js`

```javascript
const { chromium } = require('playwright');

const args = process.argv.slice(2);
const url = args.find(a => a.startsWith('--url='))?.split('=')[1];
const headless = !args.includes('--no-headless');
const username = args.find(a => a.startsWith('--username='))?.split('=')[1] || process.env.PROBO_USERNAME;
const password = args.find(a => a.startsWith('--password='))?.split('=')[1] || process.env.PROBO_PASSWORD;

async function scrapeArticle() {
    if (!url) {
        console.error(JSON.stringify({ error: 'No URL provided' }));
        process.exit(1);
    }

    const browser = await chromium.launch({ headless });
    const context = await browser.newContext();
    const page = await context.newPage();

    try {
        // Login if credentials provided
        if (username && password) {
            await page.goto('https://www.probo.nl/login');
            await page.fill('input[name="email"]', username);
            await page.fill('input[name="password"]', password);
            await page.click('button[type="submit"]');
            await page.waitForNavigation({ waitUntil: 'networkidle' });
        }

        // Navigate to article
        await page.goto(url, { waitUntil: 'networkidle' });

        // Determine article type and scrape accordingly
        const isKlantenservice = url.includes('/klantenservice/');
        const isBlog = url.includes('/ontdek/');

        let data;

        if (isKlantenservice) {
            data = await scrapeKlantenserviceArticle(page);
        } else if (isBlog) {
            data = await scrapeBlogArticle(page);
        } else {
            throw new Error('Unknown article type');
        }

        data.source_url = url;
        data.category = isKlantenservice ? 'klantenservice' : 'blog';

        console.log(JSON.stringify(data));
    } catch (error) {
        console.error(JSON.stringify({ error: error.message }));
        process.exit(1);
    } finally {
        await browser.close();
    }
}

async function scrapeKlantenserviceArticle(page) {
    const title = await page.textContent('h1').catch(() => null);
    const body = await page.innerHTML('article, .article-content, main .prose').catch(() => null);
    const subcategory = await page.textContent('.breadcrumb li:nth-child(2)').catch(() => null);

    return {
        title: { nl: title?.trim() },
        body: { nl: cleanHtml(body) },
        excerpt: { nl: extractExcerpt(body) },
        subcategory: subcategory?.trim().toLowerCase(),
    };
}

async function scrapeBlogArticle(page) {
    const title = await page.textContent('h1').catch(() => null);
    const body = await page.innerHTML('article, .article-content, main .prose').catch(() => null);
    const excerpt = await page.textContent('.article-excerpt, .intro, p:first-of-type').catch(() => null);
    const image = await page.getAttribute('article img, .article-image img', 'src').catch(() => null);
    const tags = await page.$$eval('.tags a, .article-tags a', els => els.map(e => e.textContent.trim())).catch(() => []);
    const subcategory = await page.textContent('.article-category, .category-badge').catch(() => null);

    return {
        title: { nl: title?.trim() },
        body: { nl: cleanHtml(body) },
        excerpt: { nl: excerpt?.trim() },
        featured_image: image,
        tags: tags,
        subcategory: subcategory?.trim().toLowerCase(),
    };
}

function cleanHtml(html) {
    if (!html) return null;
    // Remove script/style tags, normalize whitespace
    return html
        .replace(/<script[^>]*>[\s\S]*?<\/script>/gi, '')
        .replace(/<style[^>]*>[\s\S]*?<\/style>/gi, '')
        .replace(/\s+/g, ' ')
        .trim();
}

function extractExcerpt(html, maxLength = 200) {
    if (!html) return null;
    const text = html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength).replace(/\s+\S*$/, '') + '...';
}

scrapeArticle();
```

**Step 2: Create the Laravel command**

File: `packages/scraper/src/Console/Commands/ScrapeArticlesCommand.php`

```php
<?php

namespace Lunar\Scraper\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Lunar\Models\Article;
use Lunar\Scraper\Services\PlaywrightRunner;

class ScrapeArticlesCommand extends Command
{
    protected $signature = 'lunar:scrape-articles
        {--source=probo-all : Source to scrape (probo-klantenservice, probo-blog, probo-all)}
        {--url=* : Specific URLs to scrape}
        {--limit=0 : Limit number of articles (0 = no limit)}
        {--dry-run : Only show which URLs would be scraped}
        {--no-headless : Show browser window}';

    protected $description = 'Scrape articles from Probo.nl';

    protected array $klantenserviceUrls = [];
    protected array $blogUrls = [];

    public function handle(PlaywrightRunner $runner): int
    {
        $source = $this->option('source');
        $urls = $this->option('url');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        // If specific URLs provided, use those
        if (!empty($urls)) {
            $urlsToScrape = $urls;
        } else {
            $urlsToScrape = $this->discoverUrls($source);
        }

        if ($limit > 0) {
            $urlsToScrape = array_slice($urlsToScrape, 0, $limit);
        }

        $this->info(sprintf('Found %d articles to scrape', count($urlsToScrape)));

        if ($dryRun) {
            foreach ($urlsToScrape as $url) {
                $this->line("  - {$url}");
            }
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar(count($urlsToScrape));
        $bar->start();

        $imported = 0;
        $failed = 0;

        foreach ($urlsToScrape as $url) {
            try {
                $data = $this->scrapeArticle($runner, $url);
                $this->importArticle($data);
                $imported++;
            } catch (\Exception $e) {
                $this->error("\nFailed to scrape {$url}: {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Imported: {$imported}, Failed: {$failed}");

        return self::SUCCESS;
    }

    protected function discoverUrls(string $source): array
    {
        $urls = [];

        if (in_array($source, ['probo-klantenservice', 'probo-all'])) {
            $urls = array_merge($urls, $this->discoverKlantenserviceUrls());
        }

        if (in_array($source, ['probo-blog', 'probo-all'])) {
            $urls = array_merge($urls, $this->discoverBlogUrls());
        }

        return $urls;
    }

    protected function discoverKlantenserviceUrls(): array
    {
        // These are the main FAQ article URLs from Probo klantenservice
        // In a real implementation, you would scrape the index page to discover these
        return [
            'https://www.probo.nl/klantenservice/bestellen',
            'https://www.probo.nl/klantenservice/algemeen',
            'https://www.probo.nl/klantenservice/bezorgen-afhalen',
            'https://www.probo.nl/klantenservice/betalen',
        ];
    }

    protected function discoverBlogUrls(): array
    {
        // Blog URLs would be discovered by scraping /ontdek with pagination
        // For now, return empty - this needs the full discovery implementation
        return [];
    }

    protected function scrapeArticle(PlaywrightRunner $runner, string $url): array
    {
        $scriptPath = __DIR__.'/../../../scripts/scrape-article.js';

        $args = [
            "--url={$url}",
        ];

        if ($this->option('no-headless')) {
            $args[] = '--no-headless';
        }

        $result = $runner->run($scriptPath, $args);

        if (isset($result['error'])) {
            throw new \Exception($result['error']);
        }

        return $result;
    }

    protected function importArticle(array $data): Article
    {
        $slug = Str::slug($data['title']['nl'] ?? $data['title']['en'] ?? 'article-'.uniqid());

        // Check if article already exists
        $existing = Article::where('source_url', $data['source_url'])->first();

        if ($existing) {
            $this->line("\n  Updating existing article: {$slug}");
            $existing->update([
                'title' => $data['title'],
                'body' => $data['body'],
                'excerpt' => $data['excerpt'] ?? null,
                'featured_image' => $data['featured_image'] ?? null,
                'subcategory' => $data['subcategory'] ?? null,
                'tags' => $data['tags'] ?? null,
            ]);
            return $existing;
        }

        return Article::create([
            'title' => $data['title'],
            'slug' => $slug,
            'body' => $data['body'],
            'excerpt' => $data['excerpt'] ?? null,
            'meta_description' => null,
            'featured_image' => $data['featured_image'] ?? null,
            'category' => $data['category'],
            'subcategory' => $data['subcategory'] ?? null,
            'tags' => $data['tags'] ?? null,
            'source_url' => $data['source_url'],
            'original_content' => [
                'title' => $data['title'],
                'body' => $data['body'],
                'excerpt' => $data['excerpt'] ?? null,
            ],
            'status' => 'draft',
        ]);
    }
}
```

**Step 3: Register command in ScraperServiceProvider**

Add to the commands array in `packages/scraper/src/ScraperServiceProvider.php`:

```php
use Lunar\Scraper\Console\Commands\ScrapeArticlesCommand;

// In boot() method, add to commands array:
$this->commands([
    // ... existing commands
    ScrapeArticlesCommand::class,
]);
```

**Step 4: Commit**

```bash
git add packages/scraper/scripts/scrape-article.js \
    packages/scraper/src/Console/Commands/ScrapeArticlesCommand.php \
    packages/scraper/src/ScraperServiceProvider.php
git commit -m "feat(scraper): add ScrapeArticlesCommand for Probo articles"
```

---

## Task 7: RewriteArticlesCommand

**Files:**
- Create: `packages/scraper/src/Console/Commands/RewriteArticlesCommand.php`
- Modify: `packages/scraper/src/ScraperServiceProvider.php`

**Step 1: Create the command**

```php
<?php

namespace Lunar\Scraper\Console\Commands;

use Illuminate\Console\Command;
use Lunar\Models\Article;
use Lunar\Scraper\Services\ArticleRewriterService;

class RewriteArticlesCommand extends Command
{
    protected $signature = 'lunar:rewrite-articles
        {--category= : Only rewrite articles in this category}
        {--limit=0 : Limit number of articles (0 = no limit)}
        {--dry-run : Only show which articles would be rewritten}
        {--locales=nl,en : Comma-separated list of locales to rewrite}';

    protected $description = 'Rewrite draft articles using Claude AI';

    public function handle(ArticleRewriterService $rewriter): int
    {
        $category = $this->option('category');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');
        $locales = explode(',', $this->option('locales'));

        $query = Article::where('status', 'draft');

        if ($category) {
            $query->where('category', $category);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $articles = $query->get();

        $this->info(sprintf('Found %d draft articles to rewrite', $articles->count()));

        if ($dryRun) {
            foreach ($articles as $article) {
                $title = $article->title['nl'] ?? $article->title['en'] ?? 'Untitled';
                $this->line("  - [{$article->category}] {$title}");
            }
            return self::SUCCESS;
        }

        if ($articles->isEmpty()) {
            $this->info('No articles to rewrite.');
            return self::SUCCESS;
        }

        if (!$this->confirm("This will rewrite {$articles->count()} articles using Claude AI. Continue?")) {
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($articles->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($articles as $article) {
            try {
                $rewriter->rewrite($article, $locales);
                $article->update(['status' => 'rewritten']);
                $success++;
            } catch (\Exception $e) {
                $title = $article->title['nl'] ?? 'Untitled';
                $this->error("\nFailed to rewrite '{$title}': {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Success: {$success}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
```

**Step 2: Register command**

Add to `packages/scraper/src/ScraperServiceProvider.php`:

```php
use Lunar\Scraper\Console\Commands\RewriteArticlesCommand;

// In boot() commands array:
RewriteArticlesCommand::class,
```

**Step 3: Commit**

```bash
git add packages/scraper/src/Console/Commands/RewriteArticlesCommand.php \
    packages/scraper/src/ScraperServiceProvider.php
git commit -m "feat(scraper): add RewriteArticlesCommand for batch AI rewriting"
```

---

## Task 8: Homepage Integration

**Files:**
- Modify: `sandbox/app/Livewire/Pages/Home.php`
- Modify: `sandbox/resources/views/livewire/pages/home.blade.php`

**Step 1: Update Home Livewire component**

Add to `sandbox/app/Livewire/Pages/Home.php`:

```php
use Lunar\Models\Article;

// In the render() method, add articles to the view data:
public function render()
{
    return view('livewire.pages.home', [
        // ... existing data
        'articles' => Article::published()
            ->orderBy('published_at', 'desc')
            ->take(4)
            ->get(),
    ]);
}
```

**Step 2: Update the Blade view**

Replace the hardcoded articles section (lines ~127-196) in `sandbox/resources/views/livewire/pages/home.blade.php` with:

```blade
<!-- Klantenservice Artikelen Section -->
<section class="bg-white py-8 antialiased md:py-16">
    <div class="mx-auto max-w-screen-2xl px-4 2xl:px-0">
        <!-- Section Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Hulp & informatie</h2>
                <p class="mt-1 text-gray-500">Veelgestelde vragen en handige artikelen</p>
            </div>
            <a href="#" class="hidden text-primary-600 hover:text-primary-700 font-medium md:inline-flex items-center">
                Alle artikelen
                <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
            </a>
        </div>

        <!-- Articles Grid -->
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @forelse($articles as $article)
            <a href="#" class="group">
                <article class="h-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
                    @if($article->featured_image)
                    <div class="aspect-video w-full overflow-hidden bg-gray-100">
                        <img
                            src="{{ $article->featured_image }}"
                            alt="{{ $article->getTitle() }}"
                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                        >
                    </div>
                    @else
                    <div class="aspect-video w-full overflow-hidden bg-gray-100 flex items-center justify-center">
                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    @endif
                    <div class="p-4">
                        <h3 class="font-semibold text-gray-900 group-hover:text-primary-700">{{ $article->getTitle() }}</h3>
                        @if($article->getExcerpt())
                        <p class="mt-2 text-sm text-gray-500 line-clamp-2">{{ $article->getExcerpt() }}</p>
                        @endif
                    </div>
                </article>
            </a>
            @empty
            <!-- Fallback content when no articles -->
            <div class="col-span-full text-center py-8 text-gray-500">
                Nog geen artikelen beschikbaar.
            </div>
            @endforelse
        </div>

        <!-- Mobile: View all link -->
        <div class="mt-6 text-center md:hidden">
            <a href="#" class="text-primary-600 hover:text-primary-700 font-medium inline-flex items-center">
                Alle artikelen bekijken
                <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
            </a>
        </div>
    </div>
</section>
```

**Step 3: Commit**

```bash
git add sandbox/app/Livewire/Pages/Home.php \
    sandbox/resources/views/livewire/pages/home.blade.php
git commit -m "feat(sandbox): integrate articles from database on homepage"
```

---

## Task 9: Test & Verify

**Step 1: Run migrations**

```bash
cd sandbox && php artisan migrate
```

**Step 2: Verify admin panel**

1. Start dev server: `composer dev`
2. Go to admin panel
3. Check if "Articles" appears in sidebar under "Content"
4. Create a test article manually

**Step 3: Test scraper (dry run)**

```bash
php artisan lunar:scrape-articles --source=probo-klantenservice --dry-run --limit=5
```

**Step 4: Test rewriter (dry run)**

```bash
php artisan lunar:rewrite-articles --dry-run
```

**Step 5: Verify homepage**

1. Publish the test article
2. Refresh homepage
3. Verify article appears in the "Hulp & informatie" section

---

## Summary

| Task | Description | Files |
|------|-------------|-------|
| 1 | Database migration | 1 file |
| 2 | Article contract | 1 file |
| 3 | Article model | 2 files |
| 4 | Filament resource | 8 files |
| 5 | Rewriter service | 1 file |
| 6 | Scrape command | 3 files |
| 7 | Rewrite command | 2 files |
| 8 | Homepage integration | 2 files |
| 9 | Test & verify | - |

**Total: ~20 files**
