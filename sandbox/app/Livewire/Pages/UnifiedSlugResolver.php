<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\HasLocale;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url as UrlAttribute;
use Livewire\Component;
use Livewire\WithPagination;
use Lunar\Base\DataTransferObjects\PricingResponse;
use Lunar\Base\Enums\UnitCode;
use Lunar\Facades\Pricing;
use Lunar\Facades\Suppliers;
use Lunar\Models\Collection;
use Lunar\Models\Language;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;
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

    public ?ProductVariant $selectedVariant = null;

    /**
     * Get the product (alias for entity when it's a Product).
     */
    #[Computed]
    public function product(): ?Product
    {
        return $this->entityType === 'product' ? $this->entity : null;
    }

    /**
     * Get all product images for the gallery.
     */
    #[Computed]
    public function productImages(): SupportCollection
    {
        if ($this->entityType !== 'product') {
            return collect();
        }

        $product = $this->entity;
        $images = collect();

        if ($product->thumbnail) {
            $images->push([
                'url' => $product->thumbnail->getUrl('large'),
                'thumb' => $product->thumbnail->getUrl('small'),
                'alt' => $product->thumbnail->getCustomProperty('alt')
                    ?? $product->thumbnail->name
                    ?? $product->translateAttribute('name'),
            ]);
        }

        $mediaCollection = $product->getMedia('images');

        foreach ($mediaCollection as $media) {
            if ($product->thumbnail && $media->id === $product->thumbnail->id) {
                continue;
            }
            $images->push([
                'url' => $media->getUrl('large'),
                'thumb' => $media->getUrl('small'),
                'alt' => $media->getCustomProperty('alt')
                    ?? $media->name
                    ?? $product->translateAttribute('name'),
            ]);
        }

        if ($images->isEmpty()) {
            $images->push([
                'url' => null,
                'thumb' => null,
                'alt' => 'No image available',
            ]);
        }

        return $images;
    }

    /**
     * Get the supplier product ID for the selected variant.
     */
    #[Computed]
    public function supplierProductId(): ?int
    {
        return $this->selectedVariant?->supplier_product_id;
    }

    /**
     * Check if the selected variant should show a configurator.
     */
    #[Computed]
    public function showConfigurator(): bool
    {
        return $this->selectedVariant?->isDynamic() ?? false;
    }

    /**
     * Check if the selected variant is pre-configured.
     */
    #[Computed]
    public function isPreConfigured(): bool
    {
        $variant = $this->selectedVariant;

        return $variant
            && $variant->isSupplierBacked()
            && ! $variant->isDynamic();
    }

    /**
     * Get the unit code for the selected variant.
     */
    #[Computed]
    public function unitCode(): ?UnitCode
    {
        return $this->selectedVariant?->unit_code;
    }

    /**
     * Get the unit quantity for the selected variant.
     */
    #[Computed]
    public function unitQuantity(): int
    {
        return $this->selectedVariant?->unit_quantity ?? 1;
    }

    /**
     * Get the pricing response with price breaks for the selected variant.
     */
    #[Computed]
    public function pricing(): ?PricingResponse
    {
        if (! $this->selectedVariant) {
            return null;
        }

        try {
            return Pricing::for($this->selectedVariant)->qty($this->quantity)->get();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get price breaks (tier pricing) for the selected variant.
     */
    #[Computed]
    public function priceBreaks(): SupportCollection
    {
        $pricing = $this->pricing;

        if (! $pricing) {
            return collect();
        }

        $allPrices = collect([$pricing->base])
            ->merge($pricing->priceBreaks)
            ->unique('id')
            ->sortBy('min_quantity')
            ->values();

        return $allPrices;
    }

    /**
     * Check if the product has price breaks (tier pricing).
     */
    #[Computed]
    public function hasPriceBreaks(): bool
    {
        return $this->priceBreaks->count() > 1;
    }

    /**
     * Get the matched price based on current quantity.
     */
    #[Computed]
    public function matchedPrice(): ?\Lunar\Models\Price
    {
        return $this->pricing?->matched;
    }

    /**
     * Get product specifications from attributes.
     */
    #[Computed]
    public function specifications(): SupportCollection
    {
        if ($this->entityType !== 'product') {
            return collect();
        }

        $specs = collect();
        $attributeData = $this->entity->attribute_data;

        if (! $attributeData) {
            return $specs;
        }

        $mappedAttributes = $this->entity->productType?->mappedAttributes ?? collect();

        foreach ($mappedAttributes as $attribute) {
            if (in_array($attribute->handle, ['name', 'description', 'short_description'])) {
                continue;
            }

            $value = $this->entity->translateAttribute($attribute->handle);

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            if (is_string($value) && empty(trim(strip_tags($value)))) {
                continue;
            }

            $specs->push([
                'name' => $attribute->translate('name'),
                'handle' => $attribute->handle,
                'value' => $value,
            ]);
        }

        return $specs;
    }

    /**
     * Get product features.
     */
    #[Computed]
    public function features(): array
    {
        if ($this->entityType !== 'product') {
            return [];
        }

        $featuresAttr = $this->entity->translateAttribute('features');

        if ($featuresAttr && is_array($featuresAttr)) {
            return $featuresAttr;
        }

        return [
            'Free shipping on orders over €50',
            '30-day easy returns',
            'Secure checkout',
            '24/7 customer support',
        ];
    }

    /**
     * Check if the selected variant requires dimension inputs.
     * UnifiedSlugResolver does not support dimensions, so always returns false.
     */
    #[Computed]
    public function requiresDimensions(): bool
    {
        return false;
    }

    /**
     * Check if add to cart is allowed.
     */
    #[Computed]
    public function canAddToCart(): bool
    {
        return $this->selectedVariant !== null;
    }

    /**
     * Get the Livewire configurator component class for the selected variant.
     */
    public function getConfiguratorComponent(): ?string
    {
        $variant = $this->selectedVariant;

        if (! $variant?->isDynamic()) {
            return null;
        }

        $supplier = $variant->supplierProduct?->supplier;

        if (! $supplier) {
            return null;
        }

        return Suppliers::supplier($supplier)->getConfiguratorComponent();
    }

    /**
     * Set the active image index for the gallery.
     */
    public function setActiveImage(int $index): void
    {
        $this->activeImageIndex = $index;
    }

    /**
     * Increment quantity.
     */
    public function incrementQuantity(): void
    {
        $this->quantity++;
    }

    /**
     * Decrement quantity.
     */
    public function decrementQuantity(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    /**
     * Add to cart.
     */
    public function addToCart(): void
    {
        if (! $this->selectedVariant) {
            return;
        }

        $cart = \Lunar\Facades\CartSession::manager();
        $cart->add($this->selectedVariant, $this->quantity);

        $this->dispatch('cart-updated');
        $this->dispatch('toggle-cart');

        $this->added = true;
        $this->quantity = 1;

        $this->js('setTimeout(() => $wire.set("added", false), 2000)');
    }

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

        $element = $url->element;

        if (! $element) {
            abort(404);
        }

        $this->entity = $element;

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

            // Initialize selected variant and options from first variant
            $this->selectedVariant = $this->entity->variants->first();
            if ($this->selectedVariant) {
                foreach ($this->selectedVariant->values as $value) {
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
