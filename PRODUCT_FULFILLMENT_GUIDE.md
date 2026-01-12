# Product Fulfillment Configuration Guide

## 📋 Overzicht

Deze guide legt uit hoe je supplier fulfillment configureert voor producten en variants in het Lunar Admin Panel. Alle backend functionaliteit die we geïmplementeerd hebben is toegankelijk via de Filament admin interface.

## 🎯 Waar vind je Product Fulfillment Settings?

### Voor Producten met 1 Variant (Simplified Product)

**Navigatie**: Admin → Catalog → Products → [Select Product] → **Fulfillment Tab**

**Locatie**: `http://your-site.test/admin/products/[product-id]/fulfillment`

**Bestand**: [`packages/admin/src/Filament/Resources/ProductResource/Pages/ManageProductFulfillment.php`](packages/admin/src/Filament/Resources/ProductResource/Pages/ManageProductFulfillment.php)

Dit tabblad verschijnt **alleen** voor producten met **1 variant** (simplified products). Voor multi-variant producten moet je naar de individuele variant pagina's.

### Voor Producten met Meerdere Variants

**Navigatie**: Admin → Catalog → Product Variants → [Select Variant] → **Fulfillment Tab**

**Locatie**: `http://your-site.test/admin/product-variants/[variant-id]/fulfillment`

**Bestand**: [`packages/admin/src/Filament/Resources/ProductVariantResource/Pages/ManageVariantFulfillment.php`](packages/admin/src/Filament/Resources/ProductVariantResource/Pages/ManageVariantFulfillment.php)

Elke variant heeft zijn eigen fulfillment configuratie.

## 🔧 Fulfillment Configuratie: Stap voor Stap

### Stap 1: Link een Supplier Product

**Wanneer**: Je hebt een product/variant zonder supplier link.

**Hoe**:

1. Open het product/variant in admin
2. Ga naar "Fulfillment" tab
3. Klik op **"Link Supplier"** actie (blauw, link icon)
4. Selecteer de **Supplier** uit de dropdown
   - Keuze uit alle enabled suppliers (Probo, HelloPrint, print.com, Internal, etc.)
5. Selecteer het **Supplier Product**
   - Dropdown toont alle supplier products van de geselecteerde supplier
   - Toont `external_name` of `external_id`
   - Dropdown is searchable
6. Klik "Submit"

**Wat gebeurt er**:

- Variant wordt gekoppeld aan het supplier product via `supplier_product_id`
- Er verschijnt automatisch een **Probo Configurator Modal** (als supplier Probo ondersteunt)
- Je wordt gevraagd om het product te configureren (afmetingen, materiaal, etc.)

**Code Snippet** (`ManageProductFulfillment.php:149-198`):
```php
Action::make('link_supplier')
    ->label(__('lunarpanel::productvariant.fulfillment.actions.link_supplier'))
    ->icon('heroicon-o-link')
    ->color('primary')
    ->form([
        Forms\Components\Select::make('supplier_id')
            ->label(__('lunarpanel::productvariant.fulfillment.supplier'))
            ->options(Supplier::enabled()->orderBy('name')->pluck('name', 'id'))
            ->required()
            ->live()
            ->afterStateUpdated(fn (Forms\Set $set) => $set('supplier_product_id', null)),
        Forms\Components\Select::make('supplier_product_id')
            ->label(__('lunarpanel::productvariant.fulfillment.supplier_product'))
            ->options(function (Forms\Get $get) {
                $supplierId = $get('supplier_id');
                if (!$supplierId) return [];

                return SupplierProduct::where('supplier_id', $supplierId)
                    ->orderBy('external_name')
                    ->get()
                    ->mapWithKeys(fn ($product) => [
                        $product->id => $product->external_name ?: $product->external_id,
                    ]);
            })
            ->searchable()
            ->required()
            ->visible(fn (Forms\Get $get) => filled($get('supplier_id'))),
    ])
```

### Stap 2A: Dynamic Pricing (Real-time van Supplier)

**Wanneer**: Je wilt real-time prijzen ophalen van de supplier API elke keer dat een klant het product in de cart legt.

**Voordelen**:
- ✅ Altijd actuele prijzen
- ✅ Automatische prijsaanpassingen
- ✅ Geen handmatige price updates nodig
- ✅ Perfect voor configureerbare producten (keuzes beïnvloeden prijs)

**Nadelen**:
- ⚠️ Afhankelijk van supplier API performance
- ⚠️ Geen vooraf vastgestelde prijs zichtbaar
- ⚠️ Margin moet worden ingesteld

**Hoe**:

1. Zorg dat variant een supplier product link heeft
2. Klik op **"Enable Dynamic"** actie (groene bolt icon)
3. Bevestig in de modal:
   - Title: "Enable Dynamic Pricing"
   - Description: "This will enable real-time pricing from the supplier. The product will fetch current prices on each cart calculation."
4. Klik "Confirm"

**Wat gebeurt er**:

- `is_dynamic` veld wordt `true`
- Bestaande `configuration` wordt **gewist** (want niet meer nodig)
- Bestaande supplier prices worden **verwijderd**
- Bij cart calculation wordt supplier API aangeroepen voor real-time prijs
- Dynamic pricing banner verschijnt bovenaan (gele achtergrond)

**Margin Instellen** (verplicht voor dynamic products):

1. Klik op **"Set Margin"** actie (procent badge icon)
2. Vul margin percentage in (bijvoorbeeld: `30` voor 30%)
3. Klik "Submit"

**Hoe margin werkt**:

- Supplier API geeft `cost_price` terug (bijvoorbeeld €10)
- Systeem berekent `sell_price` met margin: `sell_price = cost_price / (1 - margin/100)`
- Voorbeeld: €10 cost met 30% margin = €10 / 0.7 = **€14.29** sell price
- Profit = €14.29 - €10 = **€4.29** (30% van sell price)

**Code Snippet** (`ManageProductFulfillment.php:95-113`):
```php
Action::make('toggle_dynamic')
    ->label($isDynamic
        ? __('lunarpanel::productvariant.fulfillment.actions.disable_dynamic')
        : __('lunarpanel::productvariant.fulfillment.actions.enable_dynamic')
    )
    ->icon($isDynamic ? 'heroicon-o-bolt-slash' : 'heroicon-o-bolt')
    ->color($isDynamic ? 'warning' : 'success')
    ->requiresConfirmation()
    ->action(function () {
        $this->toggleDynamic();
    })
```

**Pricing Pipeline**:

Voor dynamic products wordt automatisch de juiste pricing pipeline aangeroepen:

- **HelloPrint**: [`GetHelloPrintPrice`](packages/core/src/Drivers/Suppliers/HelloPrint/Pipelines/GetHelloPrintPrice.php)
- **print.com**: [`GetPrintComPrice`](packages/core/src/Drivers/Suppliers/PrintCom/Pipelines/GetPrintComPrice.php)
- **Probo**: [`GetProboPrice`](packages/core/src/Drivers/Suppliers/Probo/Pipelines/GetProboPrice.php)

Deze pipelines worden geregistreerd in `LunarServiceProvider`:

```php
// packages/core/src/LunarServiceProvider.php
foreach (Supplier::enabled()->get() as $supplier) {
    $driver = Suppliers::supplier($supplier);
    $pipelines = $driver->getCartLinePipelines();

    foreach ($pipelines as $pipeline) {
        app(CartLineCalculator::class)->addPipeline($pipeline);
    }
}
```

### Stap 2B: Pre-configured Pricing (Vaste Prijs met Configuratie)

**Wanneer**: Je wilt een vaste prijs instellen voor een specifieke supplier product configuratie.

**Voordelen**:
- ✅ Vaste, voorspelbare prijzen
- ✅ Geen API calls tijdens checkout (sneller)
- ✅ Prijs zichtbaar voor admin/klant
- ✅ Volledige controle over pricing

**Nadelen**:
- ⚠️ Moet handmatig geüpdatet worden als supplier prijzen wijzigen
- ⚠️ Niet geschikt voor producten met veel configuratie opties

**Hoe**:

1. Zorg dat variant een supplier product link heeft
2. Zorg dat **dynamic pricing DISABLED** is (disable eerst als enabled)
3. Klik op **"Configure"** actie (tandwiel icon)

**Wat gebeurt er**:

- Er opent een **Probo Configurator Modal** (als supplier = Probo)
- Je configureert het product (afmetingen, papiersoort, afwerking, etc.)
- Bij "Save" wordt supplier API aangeroepen voor een quote
- `configuration` array wordt opgeslagen op de variant
- Supplier quote data wordt opgeslagen in `prices` table:
  - `cost_price` - inkoop prijs van supplier
  - `price` - verkoop prijs (sell price)
  - `supplier_id` - link naar supplier

**Code Snippet** (`ManageProductFulfillment.php:117-129`):
```php
Action::make('configure')
    ->label(__('lunarpanel::productvariant.fulfillment.actions.configure'))
    ->icon('heroicon-o-cog-6-tooth')
    ->color('primary')
    ->action(function () {
        $variant = $this->getVariant();
        $this->dispatch(
            'configure-variant-with-probo',
            variantId: $variant->id,
            supplierProductId: $variant->supplier_product_id,
            productId: $variant->product_id
        );
    })
```

**Refresh Price**:

Als de supplier prijzen zijn gewijzigd, kun je de prijs manueel vernieuwen:

1. Klik op **"Refresh Price"** actie (arrow-path icon)
2. Systeem haalt nieuwe quote op van supplier API
3. `cost_price` en `price` worden geüpdatet in database

**Code Snippet** (`ManageProductFulfillment.php:313-367`):
```php
protected function refreshSupplierPrice(): void
{
    $variant = $this->getVariant();

    $priceResponse = Suppliers::supplier($supplier)
        ->getPrice($variant->supplierProduct->external_id, $variant->configuration);

    $currency = Currency::getDefault();

    $variant->prices()->updateOrCreate(
        [
            'currency_id' => $currency->id,
            'customer_group_id' => null,
            'min_quantity' => 1,
        ],
        [
            'price' => (int) ($priceResponse->sellPrice * $currency->factor),
            'cost_price' => (int) ($priceResponse->costPrice * $currency->factor),
            'supplier_id' => $supplier->id,
        ]
    );
}
```

### Stap 3: Unlink Supplier

**Wanneer**: Je wilt de supplier koppeling verwijderen.

**Hoe**:

1. Klik op **"Unlink"** actie (rood, X-circle icon)
2. Bevestig in modal
3. Klik "Confirm"

**Wat gebeurt er**:

- `supplier_product_id` wordt `null`
- `configuration` wordt gewist
- `is_dynamic` wordt `false`
- Alle supplier prices worden verwijderd
- Variant is nu weer "unlinked"

**Code Snippet** (`ManageProductFulfillment.php:369-389`):
```php
protected function unlinkSupplier(): void
{
    $variant = $this->getVariant();

    $variant->update([
        'supplier_product_id' => null,
        'configuration' => null,
        'is_dynamic' => false,
    ]);

    $variant->prices()
        ->whereNotNull('supplier_id')
        ->delete();
}
```

## 📊 Fulfillment Infolist: Wat zie je?

### Dynamic Product Banner

**Zichtbaar**: Alleen als `is_dynamic = true`

**Inhoud**:
- ⚡ Bolt icon met "Dynamic Product" titel
- Info text: "This product uses real-time pricing from the supplier..."
- Margin percentage badge (groen als ingesteld, grijs als niet)

**Code** (`ManageProductFulfillment.php:212-229`):
```php
Infolists\Components\Section::make(__('lunarpanel::productvariant.fulfillment.dynamic.title'))
    ->schema([
        Infolists\Components\TextEntry::make('dynamic_info')
            ->label('')
            ->state(__('lunarpanel::productvariant.fulfillment.dynamic.info'))
            ->icon('heroicon-o-bolt')
            ->iconColor('warning'),
        Infolists\Components\TextEntry::make('margin')
            ->label(__('lunarpanel::productvariant.fulfillment.margin'))
            ->state(fn ($record) => $record->margin !== null
                ? number_format($record->margin, 2).'%'
                : __('lunarpanel::productvariant.fulfillment.no_margin_set')
            )
            ->icon('heroicon-o-percent-badge')
            ->iconColor(fn ($record) => $record->margin !== null ? 'success' : 'gray'),
    ])
    ->visible(fn ($record) => $record->isDynamic())
    ->extraAttributes(['class' => 'bg-warning-50 dark:bg-warning-950'])
```

### Supplier Information Section

**Zichtbaar**: Als variant een supplier link heeft

**Inhoud**:
- **Supplier**: Naam van supplier (clickable link naar SupplierResource)
- **Supplier Product**: External name van supplier product
- **External ID**: Supplier product ID (copyable)
- **Driver**: Badge met driver naam (probo, helloprint, printcom, etc.)

**Code** (`ManageProductFulfillment.php:231-249`):
```php
Infolists\Components\Section::make(__('lunarpanel::productvariant.fulfillment.supplier'))
    ->schema([
        Infolists\Components\TextEntry::make('supplierProduct.supplier.name')
            ->label(__('lunarpanel::productvariant.fulfillment.supplier'))
            ->url(fn ($record) => $record->supplierProduct?->supplier
                ? SupplierResource::getUrl('edit', ['record' => $record->supplierProduct->supplier])
                : null
            ),
        Infolists\Components\TextEntry::make('supplierProduct.external_name')
            ->label(__('lunarpanel::productvariant.fulfillment.supplier_product')),
        Infolists\Components\TextEntry::make('supplierProduct.external_id')
            ->label(__('lunarpanel::productvariant.fulfillment.external_id'))
            ->copyable(),
        Infolists\Components\TextEntry::make('supplierProduct.supplier.driver')
            ->label(__('lunarpanel::productvariant.fulfillment.driver'))
            ->badge(),
    ])
    ->columns(2)
    ->visible(fn ($record) => $record->supplier_product_id !== null)
```

### Pricing Section

**Zichtbaar**: Als variant een supplier link heeft

**Inhoud**:
- **Cost Price**: Inkoop prijs (formatted met currency)
- **Sell Price**: Verkoop prijs (formatted met currency)
- **Margin**: Berekende marge percentage
  - Groen als > 20%
  - Oranje als < 20%
  - Berekening: `((sell - cost) / sell) * 100`

**Code** (`ManageProductFulfillment.php:251-290`):
```php
Infolists\Components\Section::make(__('lunarpanel::productvariant.fulfillment.pricing'))
    ->schema([
        Infolists\Components\TextEntry::make('cost_price')
            ->label(__('lunarpanel::productvariant.fulfillment.cost_price'))
            ->state(function ($record) {
                $price = $record->prices()->whereNotNull('supplier_id')->first()
                    ?? $record->prices()->first();
                return $price?->cost_price?->formatted ?? '—';
            }),
        Infolists\Components\TextEntry::make('sell_price')
            ->label(__('lunarpanel::productvariant.fulfillment.sell_price'))
            ->state(function ($record) {
                $price = $record->prices()->whereNotNull('supplier_id')->first()
                    ?? $record->prices()->first();
                return $price?->price?->formatted ?? '—';
            }),
        Infolists\Components\TextEntry::make('margin')
            ->label(__('lunarpanel::productvariant.fulfillment.margin'))
            ->state(function ($record) {
                $price = $record->prices()->whereNotNull('supplier_id')->first()
                    ?? $record->prices()->first();

                if (!$price || !$price->cost_price || !$price->price) {
                    return '—';
                }

                $costValue = $price->cost_price->value;
                $sellValue = $price->price->value;

                if ($sellValue <= 0) {
                    return '—';
                }

                $margin = (($sellValue - $costValue) / $sellValue) * 100;
                return number_format($margin, 1) . '%';
            })
            ->color(fn ($state) => $state !== '—' && floatval($state) > 20 ? 'success' : 'warning'),
    ])
    ->columns(3)
    ->visible(fn ($record) => $record->supplier_product_id !== null)
```

### Configuration Section

**Zichtbaar**: Als variant een `configuration` array heeft (alleen voor pre-configured products)

**Inhoud**:
- Key-Value display van volledige configuration
- Voorbeeld voor Probo:
  ```json
  {
    "width": "210",
    "height": "297",
    "material": "silk-coated-paper-135gsm",
    "finishing": "none",
    "quantity": "500"
  }
  ```

**Code** (`ManageProductFulfillment.php:292-299`):
```php
Infolists\Components\Section::make(__('lunarpanel::productvariant.fulfillment.configuration'))
    ->schema([
        Infolists\Components\KeyValueEntry::make('configuration')
            ->label('')
            ->columnSpanFull(),
    ])
    ->visible(fn ($record) => $record->supplier_product_id !== null && !empty($record->configuration))
    ->collapsible()
```

### No Supplier Section

**Zichtbaar**: Als variant nog **geen** supplier link heeft

**Inhoud**:
- ℹ️ Info icon
- Message: "This variant is not linked to a supplier product"
- Hint om "Link Supplier" actie te gebruiken

## 🤖 AI Automatic Product Matching

**Status**: ✅ **GEÏMPLEMENTEERD** en klaar voor gebruik!

De AI matcher gebruikt Claude API om automatisch supplier products te matchen met je product variants op basis van:

- **Product Type Similarity** (40%): Kern product type matching (flyers, business cards, etc.)
- **Specifications Match** (30%): Afmetingen, materialen, finishing opties
- **Attributes Compatibility** (20%): Variant attributen vs supplier product opties
- **Semantic Similarity** (10%): Naam en beschrijving analyse

### Stap-voor-stap: AI Matching Gebruiken

**Vereiste**: Anthropic API key in `.env`:
```bash
ANTHROPIC_API_KEY=sk-ant-api03-...
ANTHROPIC_MODEL=claude-3-5-sonnet-20241022  # Optional, default is set
```

**Hoe werkt het**:

1. **Ga naar product variant** zonder supplier link
   - Admin → Products → [Product] → Fulfillment tab
   - Of Admin → Product Variants → [Variant] → Fulfillment tab

2. **Klik "Match with AI"** actie (sparkles icon, blauw)
   - Actie is alleen zichtbaar als Anthropic API key is geconfigureerd
   - Actie is alleen beschikbaar voor variants **zonder** supplier link

3. **AI Matcher Modal opent automatisch**
   - Modal title: "AI Product Matching"
   - Beschrijving: "Our AI will analyze this product variant..."
   - Matching start **automatisch** bij openen

4. **Analyseren** (10-30 seconden)
   - Loading spinner: "Analyzing product and searching catalog..."
   - AI haalt alle supplier products op
   - Claude analyseert variant context
   - Claude vergelijkt met catalogus en scoort matches

5. **Match Results** bekijken
   - Lijst met top 10 matches, gesorteerd op score (hoogste eerst)
   - Elke match toont:
     - **Product naam** en **Supplier naam**
     - **Score badge** (0-100) met kleur:
       - 90-100: Groen (Excellent Match) ⭐ Recommended
       - 75-89: Blauw (Good Match)
       - 60-74: Oranje (Moderate Match)
       - <60: Rood (Poor Match)
     - **Reasoning**: Waarom deze match goed is
     - **Matched Fields** badges: Product type ✓, Dimensions ✓, Material ✓, etc.
     - **Concerns** (indien van toepassing): Aandachtspunten of verschillen

6. **Filter op Suppliers** (optioneel)
   - Multi-select dropdown boven de results
   - Selecteer 1 of meer suppliers om alleen hun producten te matchen
   - Leeg laten = alle suppliers doorzoeken

7. **Match selecteren**
   - Klik op de hele match card of "Select" button
   - Variant wordt automatisch gekoppeld aan het gekozen supplier product
   - Success notification: "Supplier product linked successfully!"
   - Modal sluit en page refresh

8. **Refresh Matches** (indien nodig)
   - "Refresh Matches" button in footer
   - Gebruik na filter wijziging
   - Vraagt opnieuw aan Claude API

**Locaties**:

- **Service**: [`packages/core/src/Services/AIProductMatcherService.php`](packages/core/src/Services/AIProductMatcherService.php:1)
- **Livewire Component**: [`packages/admin/src/Livewire/Components/AIProductMatcher.php`](packages/admin/src/Livewire/Components/AIProductMatcher.php:1)
- **Blade View**: [`packages/admin/resources/views/livewire/components/ai-product-matcher.blade.php`](packages/admin/resources/views/livewire/components/ai-product-matcher.blade.php:1)
- **DTO**: [`packages/core/src/DataTransferObjects/ProductMatch.php`](packages/core/src/DataTransferObjects/ProductMatch.php:1)

**Code Snippet** (Filament Action in `ManageProductFulfillment.php:150-160`):
```php
// AI Matching action - only visible if AI is configured
$aiMatcher = app(AIProductMatcherService::class);
if ($aiMatcher->isConfigured()) {
    $actions[] = Action::make('ai_match')
        ->label(__('lunarpanel::productvariant.fulfillment.actions.ai_match'))
        ->icon('heroicon-o-sparkles')
        ->color('info')
        ->action(function () {
            $this->dispatch('open-ai-matcher-modal', variantId: $this->getVariant()->id);
        });
}
```

**Hoe AI Matching Werkt (Technisch)**:

1. **Variant Context Extraction**:
   ```php
   [
       'name' => 'Flyer A5',
       'description' => 'High quality flyer...',
       'sku' => 'FLYER-A5-GLOSS',
       'variant_attributes' => [
           ['attribute' => 'Paper Type', 'value' => 'Glossy'],
           ['attribute' => 'Weight', 'value' => '150gsm'],
       ],
       'dimensions' => [
           'width' => 148, 'width_unit' => 'mm',
           'height' => 210, 'height_unit' => 'mm',
       ],
   ]
   ```

2. **Catalogus Ophalen**:
   - Query: Alle `supplier_products` met `enabled` suppliers
   - Filter: Optioneel op geselecteerde supplier IDs
   - Data: external_name, external_id, description, specifications, attributes

3. **Claude API Call**:
   - Model: `claude-3-5-sonnet-20241022`
   - Prompt: Structured matching criteria met weighted scoring
   - Response: JSON array met scored matches
   ```json
   [
     {
       "supplier_product_id": 123,
       "score": 95,
       "reasoning": "Exact match on dimensions (148x210mm A5), paper type (glossy 150gsm), and product category (flyers).",
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

4. **Results Enrichment**:
   - Match IDs naar volledige `SupplierProduct` models
   - Create `ProductMatch` DTOs met helpers (score color, label, etc.)
   - Sort by score descending

**API Costs**:

- Gemiddelde request: ~500-1500 tokens input, ~500-1000 tokens output
- Cost per matching: ~$0.01-0.05 (afhankelijk van catalogus grootte)
- Recommend: Cache results per variant, refresh only on demand

**Error Handling**:

- **API key missing**: Action wordt niet getoond
- **API call fails**: Error banner in modal met foutmelding
- **No matches found**: Info message: "No matching supplier products found..."
- **Network timeout**: 30 second timeout, error message na timeout

## 📦 Collection Supplier Products

**Concept**: Een supplier product die meerdere items bevat (bijvoorbeeld een sample pack, bundel, of collectie).

**Gebruik Cases**:

1. **Sample Packs**: Probo sample pack met 10 verschillende papiersoorten
2. **Bundles**: HelloPrint bundel van flyers + business cards
3. **Kits**: Complete branding kit (logo op meerdere materialen)

**Hoe het zou werken**:

1. **SupplierProduct model** krijgt `is_collection` veld
2. **CollectionItems** tabel voor items in de collectie:
   ```php
   collection_items
   - supplier_product_id (parent collection)
   - child_supplier_product_id (item in collection)
   - quantity (hoeveel van dit item)
   - sort_order
   ```
3. Bij order placement worden alle collection items als aparte supplier orders aangemaakt
4. Admin kan collection samenstelling beheren in SupplierProductResource

**Implementatie TODO**:

- [ ] Add `is_collection` field to supplier_products table
- [ ] Create `collection_items` pivot table
- [ ] Add Collection section to SupplierProductResource
- [ ] Handle collection items in RuntimeSupplierSelector
- [ ] Split collection into multiple SupplierOrders at checkout

**UI Locatie**:

Zou toegevoegd worden aan:
- `SupplierProductResource` - Edit collection items
- `ManageProductFulfillment` - Badge/indicator als linked product is collection
- Order flow - Show collection items breakdown

## 🧪 Testing de Fulfillment Configuratie

### Test Scenario 1: Link Supplier + Configure

1. Ga naar Admin → Products
2. Open een product met 1 variant
3. Klik "Fulfillment" tab
4. Klik "Link Supplier"
5. Selecteer "Probo" als supplier
6. Selecteer een Probo product
7. Configurator modal opent automatisch
8. Configureer product (width: 210, height: 297, etc.)
9. Klik "Save" in configurator
10. Check dat "Configuration" sectie nu gevuld is
11. Check dat "Pricing" sectie cost/sell prices toont

### Test Scenario 2: Enable Dynamic Pricing

1. Start met variant met supplier link (zie scenario 1)
2. Klik "Enable Dynamic" actie
3. Bevestig modal
4. Check dat "Dynamic Product" banner verschijnt (geel)
5. Check dat "Configuration" sectie verdwenen is
6. Klik "Set Margin" actie
7. Vul `30` in voor 30% margin
8. Check dat margin in banner getoond wordt
9. Test door product in cart te leggen op storefront
10. Check dat prijs real-time opgehaald wordt via API

### Test Scenario 3: Refresh Pre-configured Price

1. Start met variant met supplier link + configuration (scenario 1)
2. Zorg dat dynamic pricing DISABLED is
3. Check huidige cost/sell prices in "Pricing" sectie
4. Klik "Refresh Price" actie
5. Wacht op success notification
6. Check dat prices geüpdatet zijn
7. Check activity log voor price update event

### Test Scenario 4: Unlink Supplier

1. Start met variant met supplier link
2. Klik "Unlink" actie
3. Bevestig modal
4. Check dat "No Supplier" sectie verschijnt
5. Check dat alle supplier info verdwenen is
6. Check dat prices met supplier_id verwijderd zijn

## 🔍 Debugging & Troubleshooting

### Problem: "Link Supplier" actie doet niets

**Check**:
1. Is JavaScript geladen? Check browser console
2. Is Livewire actief? Check network tab voor Livewire requests
3. Zijn er suppliers enabled? Check `lunar_suppliers` tabel

**Fix**:
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Problem: Configurator modal opent niet

**Check**:
1. Is `ProboConfigurator` Livewire component geregistreerd?
2. Is JavaScript listener actief voor `configure-variant-with-probo` event?
3. Check browser console voor errors

**Locatie**: [`packages/admin/resources/views/livewire/components/probo-configurator.blade.php`](packages/admin/resources/views/livewire/components/probo-configurator.blade.php)

### Problem: Dynamic pricing geeft geen prijzen

**Check**:
1. Is pricing pipeline geregistreerd? Check `LunarServiceProvider::boot()`
2. Heeft supplier `pricing` capability? Check `lunar_suppliers.capabilities`
3. Zijn API credentials correct? Check `lunar_suppliers.credentials`
4. Check Laravel log voor API errors:
   ```bash
   tail -f storage/logs/laravel.log | grep -i supplier
   ```

### Problem: Margin calculation niet correct

**Check**:
1. Is margin percentage ingesteld? Check `lunar_product_variants.margin`
2. Is supplier cost_price beschikbaar? Check supplier API response
3. Check calculation in pricing pipeline:
   ```php
   $sellPrice = $costPrice / (1 - ($margin / 100));
   // Example: €10 / (1 - 0.30) = €10 / 0.70 = €14.29
   ```

## 📚 Related Documentation

- **Testing Guide**: [`MULTI_SUPPLIER_TESTING_GUIDE.md`](MULTI_SUPPLIER_TESTING_GUIDE.md) - Complete testing van order flow
- **Implementation Plan**: [`docs/plans/2026-01-12-multi-supplier-fulfillment-system.md`](docs/plans/2026-01-12-multi-supplier-fulfillment-system.md)
- **Supplier Drivers**: [`packages/core/src/Drivers/Suppliers/`](packages/core/src/Drivers/Suppliers/)
- **Pricing Pipelines**: [`packages/core/src/Pipelines/CartLine/`](packages/core/src/Pipelines/CartLine/)

## 📋 Quick Reference

| Feature | Dynamic Pricing | Pre-configured |
|---------|----------------|----------------|
| Real-time API calls | ✅ Every cart calculation | ❌ Only on configure/refresh |
| Fixed price | ❌ Always current | ✅ Stored in DB |
| Configuration required | ❌ No | ✅ Yes |
| Margin required | ✅ Yes | ❌ Optional |
| Best for | Configurable products | Simple products |
| Performance | Slower (API calls) | Faster (DB lookup) |
| Price updates | Automatic | Manual refresh |

## ✅ Checklist voor Production

- [ ] Configureer alle suppliers met API credentials
- [ ] Test dynamic pricing voor elk supplier type
- [ ] Test pre-configured pricing met refresh
- [ ] Configureer margins voor dynamic products
- [ ] Test Probo configurator integration
- [ ] Verify price calculations zijn correct
- [ ] Setup monitoring voor supplier API errors
- [ ] Document margin strategy voor team
- [ ] Train staff op fulfillment configuratie
- [ ] Test full order flow (product config → cart → checkout → supplier order)
