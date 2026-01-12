<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\Collection;
use Lunar\Models\Product;

/**
 * Maps products to collections based on Probo.nl category structure.
 *
 * This seeder defines which products belong to which collections,
 * based on scraping the Probo.nl category pages.
 *
 * Run after: CollectionsSeeder, and after products have been imported.
 */
class ProductCollectionMapperSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Mapping products to collections...');

        $mappings = $this->getProductCollectionMappings();

        $totalMapped = 0;
        $notFound = [];

        foreach ($mappings as $collectionHandle => $productHandles) {
            $collection = Collection::whereHas('urls', function ($query) use ($collectionHandle) {
                $query->where('slug', $collectionHandle);
            })->first();

            if (! $collection) {
                $this->command->warn("  Collection not found: {$collectionHandle}");
                continue;
            }

            $productIds = [];
            foreach ($productHandles as $productHandle) {
                $product = $this->findProductByHandle($productHandle);

                if ($product) {
                    $productIds[] = $product->id;
                } else {
                    $notFound[$productHandle] = $collectionHandle;
                }
            }

            if (count($productIds) > 0) {
                // Sync products to collection (without detaching existing)
                $collection->products()->syncWithoutDetaching($productIds);
                $this->command->info("  Mapped ".count($productIds)." products to: {$collectionHandle}");
                $totalMapped += count($productIds);

                // Also map products to parent collection(s)
                $this->mapToParentCollections($collection, $productIds);
            }
        }

        $this->command->info("Total products mapped: {$totalMapped}");

        if (count($notFound) > 0) {
            $this->command->warn('Products not found (may need to be imported first):');
            foreach (array_slice($notFound, 0, 20) as $handle => $collection) {
                $this->command->warn("  - {$handle} (for {$collection})");
            }
            if (count($notFound) > 20) {
                $this->command->warn('  ... and '.(count($notFound) - 20).' more');
            }
        }

        $this->command->info('Product to collection mapping completed!');
    }

    /**
     * Map products to all parent collections (recursive).
     *
     * @param  array<int>  $productIds
     */
    protected function mapToParentCollections(Collection $collection, array $productIds): void
    {
        $parent = $collection->parent;

        while ($parent) {
            $parent->products()->syncWithoutDetaching($productIds);
            $parentSlug = $parent->urls->first()?->slug ?? $parent->id;
            $this->command->info("    -> Also mapped to parent: {$parentSlug}");

            $parent = $parent->parent;
        }
    }

    /**
     * Find a product by handle, using flexible matching strategies.
     *
     * Matching order:
     * 1. Exact slug match
     * 2. Slug with numeric suffix (e.g., 'beachflag' matches 'beachflag-2')
     * 3. Handle as prefix in slug (e.g., 'lichtbak' matches 'lichtbak-2')
     */
    protected function findProductByHandle(string $handle): ?Product
    {
        // Use direct URL table query for better performance and reliability
        $url = \Lunar\Models\Url::where('element_type', 'product')
            ->where(function ($query) use ($handle) {
                // Exact match
                $query->where('slug', $handle)
                    // Or with numeric suffix (e.g., handle-2, handle-3)
                    ->orWhere(function ($q) use ($handle) {
                        $q->where('slug', 'like', $handle.'-%')
                          ->whereRaw("slug GLOB ?", [$handle.'-[0-9]*']);
                    });
            })
            ->first();

        if ($url) {
            // Include both published and draft products for collection mapping
            return Product::whereIn('status', ['published', 'draft'])
                ->where('id', $url->element_id)
                ->first();
        }

        // Fallback: try SKU match on variants
        return Product::whereIn('status', ['published', 'draft'])
            ->whereHas('variants', function ($q) use ($handle) {
                $q->where('sku', $handle);
            })
            ->first();
    }

    /**
     * Get the product-to-collection mappings.
     *
     * Structure: collection_handle => [product_handles]
     *
     * Based on actual products in the database.
     * Handles are matched flexibly (exact, with suffix, or prefix match).
     */
    protected function getProductCollectionMappings(): array
    {
        return [
            // =================================================================
            // BUITENRECLAME (Outdoor Advertising)
            // =================================================================
            'reclameborden' => [
                'akylite-plex',
                'aluminium-bord',
                'dibond',
                'dibond-aluminium-paneel',
                'dibond-butler-finish',
                'easyprint',
                'forex',
                'lantaarnpaalbord',
                'plexiglas',
                'bouwbord',
            ],

            'stoepborden' => [
                'klapbord',
                'klapbord-a1-formaat',
            ],

            'gevelreclame' => [
                'aluminium-bord',
                'freesletters',
                'gevelbord',
                'muursticker',
            ],

            'lichtreclame' => [
                'led-frame',
                'led-neon-flex',
                'led-verlichting-voor-beurswanden',
                'lichtbak',
                'lichtbakplaat',
                'lichtbakposter',
            ],

            'spandoeken' => [
                'blind-spandoekframe',
                'bouwhekdoek',
                'bouwhekdoek-eigen-formaat',
                'dranghekdoek',
                'vinyl-spandoek',
            ],

            'spandoekframes' => [
                'blind-spandoekframe',
                'flag-pet',
            ],

            'voertuigreclame' => [
                'autosticker',
                'carwrap',
                'dode-hoek-sticker',
            ],

            'bouw-vastgoed' => [
                'bouwbord',
                'bouwhekdoek',
                'bouwhekdoek-eigen-formaat',
                'makelaarsbordsticker',
            ],

            'terras-tuin' => [
                'buitenkleed',
                'buitenkussen',
            ],

            // =================================================================
            // VLAGGEN (Flags)
            // =================================================================
            'gevelvlaggen' => [
                'banier',
                'banierhouderset',
                'baniervlag',
                'bootvlag',
                'gevelvlag',
                'golfvlag',
                'haakse-baniersteun',
                'kioskvlag',
                'landenvlag',
                'mastvlag',
            ],

            'beachflags' => [
                'beachflag',
                'beachflag-drop',
                'beachflag-mini',
                'beachpole-draagtas',
                'muurbevestiging-beachflag',
                'zwarte-kruisvoet',
            ],

            'overige-vlaggen' => [
                'bootvlag',
                'landenvlag',
                'golfvlag',
            ],

            // =================================================================
            // BINNENRECLAME (Indoor Advertising)
            // =================================================================
            'wandreclame' => [
                'acousticpro-bureauklem-wit',
                'acousticpro-bureauklem-zwart',
                'acousticpro-bureauscherm',
                'acousticpro-hexagons',
                'acousticpro-panelen',
                'acousticpro-roomdivider',
                'hexagon-afstandhouder',
                'hexagons',
                'magnetische-bevestiging-fotowand',
                'muurcirkel',
                'muurcirkel-standaard',
                'muursticker',
                'peesdoeken-voor-aluvision-en-bematrix-frames',
            ],

            'wandbekleding' => [
                'behangrollen',
                'airtex',
                'basicwall',
                'multitexpro',
            ],

            'presentatie' => [
                'gebogen-pop-up-wand',
                'l-banner',
                'roll-up-banner-standaard',
                'led-frame',
            ],

            'horeca' => [
                'deurmat',
                'lichtbakplaat',
                'acousticpro-panelen',
            ],

            'retail' => [
                'deurmat',
                'acousticpro-panelen',
                'banier',
                'detectiepoorthoes',
                'de-inhaker-beursstand',
            ],

            // =================================================================
            // BEURS & EVENEMENT (Trade Shows & Events)
            // =================================================================
            'beurswanden' => [
                'gebogen-pop-up-wand',
                'beursframe',
                'beurswanden',
                'de-inhaker-beursstand',
                'de-inhaker-onderdelen',
                'de-inhaker-samplepakket',
                'inhaker-750-mm',
                'inhaker-975-mm',
                'inhaker-inslagdoppen',
                'inhaker-kanaalplaat-haken-met-plakstips',
                'inhaker-paal-1500-mm',
                'inhaker-paal-1950-mm',
                'inhaker-paal-2475-mm',
                'inhaker-paal-3000-mm',
                'inhaker-universeel-koppelstuk-met-snor',
                'inhaker-voetplaat-250-x-250-x-20-mm',
                'inhaker-voetplaat-400-x-400-x-6-mm',
                'inhaker-voetplaat-450-x-150-x-3-mm',
                'inhaker-wandhaak-met-lock',
                'led-verlichting-voor-beurswanden',
            ],

            'verlichte-beurswanden' => [
                'led-frame',
                'led-verlichting-voor-beurswanden',
            ],

            'beursdoeken' => [
                'peesdoeken-voor-aluvision-en-bematrix-frames',
                'beursbanner',
            ],

            'roll-up-banners' => [
                'roll-up-banner-standaard',
                'l-banner',
            ],

            'festivaldoeken' => [
                'backdrop',
                'blanco-doek',
                'skytube',
            ],

            'aankleding' => [
                'draagtas',
                'blind-ophangsysteem-variabel-formaat',
                'blind-ophangsysteem-vaste-formaten',
            ],

            // =================================================================
            // INTERIEUR (Interior)
            // =================================================================
            'wanddecoratie' => [
                'canvas',
                'canvas-met-baklijst',
                'canvas-print',
                'foto-op-canvas',
                'foto-op-paneel',
                'menuez-foto-op-paneel',
                'muurcirkel',
                'muurcirkel-standaard',
                'behangcirkel',
            ],

            'behang' => [
                'behangcirkel',
                'behangrollen',
                'behangset-6-delig',
                'behangsnijder',
                'behangsnijder-mesje',
            ],

            'vloerdecoratie' => [
                'deurmat',
                'vloergrafiek-folie',
            ],

            'fotoproducten' => [
                'canvas',
                'canvas-met-baklijst',
                'canvas-print',
                'foto-op-canvas',
                'foto-op-paneel',
                'fotoblok',
                'kaarthouder-met-magneetjes',
                'magneet',
                'magneet-klein',
                'magneetbord',
                'magneetfolie-05-mm',
                'magneetfolie-085-mm',
                'magneetsticker',
                'magnetische-bevestiging-fotowand',
                'menuez-foto-op-paneel',
            ],

            'huis-horeca' => [
                'dekbedovertrek-en-kussensloop',
                'sherpa-fleece-deken',
            ],

            'kantoor' => [
                'acousticpro-roomdivider',
                'acousticpro-bureauscherm',
                'acousticpro-bureauklem-wit',
                'acousticpro-bureauklem-zwart',
                'freesletters',
                'magneetbord',
                'klembord',
                'muismat',
            ],

            // =================================================================
            // STICKERS & DRUKWERK (Stickers & Printing)
            // =================================================================
            'drukwerk' => [
                'ansichtkaarten',
                'briefpapier',
                'brochures',
                'enveloppen',
                'flyers',
                'folders',
                'folders-kruisvouw',
                'kalenders',
                'menukaarten',
                'premium-visitekaartjes',
                'visitekaartjes-doos',
                'kerstkaarten',
            ],

            'posters' => [
                'grootformaat-poster-print',
                'grote-poster',
                'lichtbakposter',
            ],

            'grote-stickers' => [
                'asfaltsticker',
                'autosticker',
                'dode-hoek-sticker',
                'etiketten',
                'etiketten-gestanst',
                'etiketten-stans-vast-formaat',
                'keuringsstickers',
                'magneetsticker',
                'makelaarsbordsticker',
                'muursticker',
                'sticker-pakket',
                'stickers',
            ],

            'kleine-stickers' => [
                'etiketten',
                'etiketten-gestanst',
                'etiketten-stans-vast-formaat',
                'keuringsstickers',
                'stickers',
            ],

            'stickers-op-rol' => [
                'etiketten',
                'etiketten-gestanst',
                'label-dispenser-box',
            ],

            // =================================================================
            // MATERIALEN (Materials)
            // =================================================================
            'plaat' => [
                'akylite-plex',
                'dibond',
                'dibond-aluminium-paneel',
                'dibond-butler-finish',
                'dibond-xl',
                'displaykarton',
                'displaykarton-menuez-v2',
                'easyprint',
                'evacast',
                'forex',
                'honingraatkarton',
                'multiplex-12-mm',
                'multiplex-9-mm',
                'plexiglas',
                'polyprop-kanaalplaat',
                'schuimplaat',
                'blanco-paneel',
                'flexibel-pvc',
            ],

            'doek' => [
                'blackback',
                'blackback-soft',
                'blackback-xl',
                'blokkerend-doek',
                'canvas',
                'dekostof',
                'flag',
                'flag-longlife',
                'flag-pet',
                'kendal',
                'lumitex',
                'blanco-doek',
                'mezo',
            ],

            'wandbekleding-materialen' => [
                'airtex',
                'basicwall',
                'multitexpro',
                'erfurt-variovlies',
            ],

            'textiel' => [
                'mezo',
                'flex-propes-fr-gecodeerd-tomorrowland',
                'sherpa-fleece-deken',
            ],

            'banner-materialen' => [
                'backlitdoek',
                'banner-510',
                'banner-610',
                'budget-banner',
                'meshdoek',
            ],

            'folie' => [
                '3m-8150-clear-view',
                '3m-ij20-10r-wit-grijze-lijm',
                '3m-ij20-10t-wit-transparante-lijm',
                '3m-ij20-transparant',
                '3m-ij20-wit',
                '3m-ij280-wit-print-wrap-film',
                '3m-ij40-transparant',
                '3m-ij40-wit',
                '3m-knifeless-tape',
                'avery-mpi-1104-ea',
                'avery-mpi-1105-ea',
                'backlit-polyester',
                'dot-folie',
                'gegoten-vinyl-folie',
                'gekkotex',
                'jetprint',
                'lintec',
                'magneetfolie-05-mm',
                'magneetfolie-085-mm',
                'raamfolie-rol',
                'vloergrafiek-folie',
                'zelfklevende-vinyl-rol',
            ],

            // =================================================================
            // ACCESSOIRES (Accessories)
            // =================================================================
            'spandoek-accessoires' => [
                'hoekstuk-doorlopende-staander',
                'blind-spandoekframe',
                'aluminium-buis',
                'haakse-baniersteun',
                'damwandplaat',
                'leuningdrader',
                'kniestuk',
                'kort-t-stuk',
                'koppelmof',
                'kruisstuk-90-graden',
                'draaikoppeling',
                'lang-t-stuk',
            ],

            'interieur-accessoires' => [
                'baklijst',
                'muurcirkel-standaard',
                'canvas-met-baklijst',
            ],

            'behang-accessoires' => [
                'behangset-6-delig',
                'behangsnijder',
                'behangsnijder-mesje',
                'afplaktape-38cm',
                'multitexpro-lijm-5l',
                'multitexpro-navulmesjes',
                'multitexpro-snijtool',
                'buitenhoekprofiel',
                'buitenhoekprofiel-wit',
                'buitenhoekprofiel-zwart',
            ],

            'drukwerk-accessoires' => [
                'brochurehouder',
                'kaarthouder-met-magneetjes',
                'enveloppen',
                'klapbord',
                'klapbord-a1-formaat',
            ],

            'paneel-accessoires' => [
                'de-inhaker-onderdelen',
                'baklijst',
                'dubbelzijdig-tape',
                'muurcirkel-standaard',
                'klittenband',
                'magnetische-bevestiging-fotowand',
                'hexagon-afstandhouder',
                'lijm-permacol-hp-l328-50-ml',
                'hoogwaardige-vloeibare-secondelijm',
            ],

            'textielframe-accessoires' => [
                'akoestisch-vilt-10-mm',
                'baseplate-side',
                'baseplate-heavy',
                'druknagels-64mm-set-van-20st',
                'frame-connector-zwart-90',
                'frame-connector-zwart-180',
                'baseplate-wheel-wit',
                'baseplate-wheel-zwart',
                'frame-connector-wit-90',
                'frame-connector-wit-180',
                'e-lock-tool',
                'akoestisch-materiaal-herstel',
            ],

            'sticker-folie-accessoires' => [
                'heteluchtpistool',
                'label-dispenser-box',
                'lijmverwijderaar',
                '3m-knifeless-tape',
                'bellenprikker',
                'geckopatch-m',
                'geckopatch-s',
                'magneet-klein',
                'monkeystrips',
                'magtaperuler-power-100cm',
                'afbreekmesjes-50-stuks',
                'applicatievloeistof',
                'rakels',
            ],

            'vlag-accessoires' => [
                'landenvlag',
                'flagfix',
                'banierhouderset',
                'gevelstokhouder',
                'contragewicht',
                'gevelstokhouder-zwart',
                'gevelstok',
                'haakse-baniersteun',
                'gevelstok-zwart',
                'handystick-met-deelbare-stok',
                'drieling-gevelstokhouder',
                'grondpen-met-rotator',
                'grondplug-met-rotator',
                'deelbare-gevelstok',
            ],

            'beachflag-accessoires' => [
                'zwarte-kruisvoet',
                'grondpen-met-rotator',
                'grondplug-met-rotator',
                'beachpole-draagtas',
                'muurbevestiging-beachflag',
            ],

            'beurswand-accessoires' => [
                'led-verlichting-voor-beurswanden',
                'locksets',
            ],

            'algemene-accessoires' => [
                'zuignap-moer',
                'plafondhaken',
                'ophangplaten',
                'klittenband',
            ],

            // =================================================================
            // DUURZAMER (Sustainable)
            // =================================================================
            'duurzamere-platen' => [
                'gerecycled-forex',
                'sign-again',
            ],

            'duurzamere-doeken' => [
                'blackback-renew',
                'flag-ciclo',
            ],

            'duurzamer-behang' => [
                'multitexpro',
            ],

            'duurzamere-folies' => [
                '3m-pl50-envision',
                'quapro-permanent-pvc-vrij',
            ],

            'duurzamer-textiel' => [
                'flag-ciclo',
                'flag-pet',
            ],
        ];
    }
}
