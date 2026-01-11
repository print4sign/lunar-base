# Root Product URLs Design

## Doel

Product en collectie URL's direct na de locale prefix plaatsen, zonder tussenliggend segment.

**Oud:** `/{locale}/producten/{slug}` → `/nl/producten/visitekaartjes`
**Nieuw:** `/{locale}/{slug}` → `/nl/visitekaartjes`

## URL Structuur

| Type | Nieuwe URL | Voorbeeld |
|------|------------|-----------|
| Product | `/{locale}/{slug}` | `/nl/visitekaartjes` |
| Collectie | `/{locale}/{slug}` | `/nl/drukwerk` |
| Product overzicht | `/{locale}/alle-producten` | `/nl/alle-producten` |
| Collectie overzicht | `/{locale}/collecties` | `/nl/collecties` |
| Statische pagina's | `/{locale}/{page}` | `/nl/contact` |

## Route Prioriteit

1. Statische routes (contact, checkout, cart, auth, etc.)
2. Expliciete overzichtspagina's (alle-producten, collecties)
3. Catch-all → UnifiedSlugResolver (database lookup)

## Technische Implementatie

### 1. UnifiedSlugResolver Component

**Nieuw bestand:** `sandbox/app/Livewire/Pages/UnifiedSlugResolver.php`

- Ontvangt `locale` en `slug`
- Query `lunar_urls` tabel om entity type te bepalen
- Lookup volgorde: product → collectie → 404
- Rendert juiste view (product-page of collection-page)

### 2. Route Wijzigingen

**Bestand:** `sandbox/routes/web.php`

- Verwijder routes met `productsSegment` en `collectionsSegment`
- Voeg expliciete overzichtspagina routes toe
- Voeg catch-all route toe voor UnifiedSlugResolver

### 3. URL Helper Aanpassingen

**Bestand:** `sandbox/app/Services/LocalizedRoute.php`

- `product()`: verwijder segment, return `/{locale}/{slug}`
- `collection()`: verwijder segment, return `/{locale}/{slug}`

**Bestand:** `sandbox/app/Livewire/Concerns/HasLocale.php`

- Update `productUrl()` en `collectionUrl()` methods

### 4. Configuratie

**Bestand:** `sandbox/config/localization.php`

Voeg vertaalde segments toe voor overzichtspagina's:
- nl: `alle-producten`
- en: `all-products`
- de: `alle-produkte`
- es: `todos-productos`

## Bestanden Overzicht

| Bestand | Actie |
|---------|-------|
| `sandbox/app/Livewire/Pages/UnifiedSlugResolver.php` | Nieuw |
| `sandbox/routes/web.php` | Aanpassen |
| `sandbox/app/Services/LocalizedRoute.php` | Aanpassen |
| `sandbox/app/Livewire/Concerns/HasLocale.php` | Aanpassen |
| `sandbox/config/localization.php` | Aanpassen |

## Notities

- Geen 301 redirects nodig (website staat nog niet live)
- Bestaande ProductPage en CollectionPage views worden hergebruikt
- Eén database query per paginaload voor slug resolution
