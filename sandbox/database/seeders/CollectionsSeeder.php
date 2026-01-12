<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Attribute;
use Lunar\Models\AttributeGroup;
use Lunar\Models\Channel;
use Lunar\Models\Collection;
use Lunar\Models\CollectionGroup;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Language;

class CollectionsSeeder extends Seeder
{
    /**
     * The collection group for the main navigation.
     */
    protected ?CollectionGroup $collectionGroup = null;

    /**
     * Default channel.
     */
    protected ?Channel $channel = null;

    /**
     * Customer groups.
     */
    protected ?\Illuminate\Database\Eloquent\Collection $customerGroups = null;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating collections based on Probo.nl categories...');

        // Get or create the collection group
        $this->collectionGroup = CollectionGroup::firstOrCreate(
            ['handle' => 'main'],
            ['name' => 'Main']
        );

        // Get the default channel and customer groups
        $this->channel = Channel::where('default', true)->first();
        $this->customerGroups = CustomerGroup::all();

        // Create collection attributes
        $this->createCollectionAttributes();

        // Define the category structure based on Probo.nl
        $categories = $this->getCategoryStructure();

        // Create collections for each category
        foreach ($categories as $category) {
            $this->createCollection($category);
        }

        $this->command->info('Collections created successfully!');
    }

    /**
     * Create additional collection attributes for Pinterest and below_fold content.
     */
    protected function createCollectionAttributes(): void
    {
        $this->command->info('Creating collection attributes...');

        // Get or create the collection attribute group
        $attributeGroup = AttributeGroup::firstOrCreate(
            [
                'attributable_type' => Collection::morphName(),
                'handle' => 'collection_details',
            ],
            [
                'name' => collect(['nl' => 'Collectie Details', 'en' => 'Collection Details']),
                'position' => 1,
            ]
        );

        // Pinterest URL attribute
        Attribute::firstOrCreate(
            [
                'attribute_type' => Collection::morphName(),
                'handle' => 'pinterest_url',
            ],
            [
                'attribute_group_id' => $attributeGroup->id,
                'position' => 3,
                'name' => collect(['nl' => 'Pinterest URL', 'en' => 'Pinterest URL']),
                'description' => collect(['nl' => 'URL naar Pinterest bord voor inspiratie', 'en' => 'URL to Pinterest board for inspiration']),
                'type' => Text::class,
                'required' => false,
                'default_value' => null,
                'searchable' => false,
                'filterable' => false,
                'validation_rules' => '',
                'configuration' => collect([]),
                'system' => false,
            ]
        );

        // Below fold content attribute (rich text HTML)
        Attribute::firstOrCreate(
            [
                'attribute_type' => Collection::morphName(),
                'handle' => 'below_fold_content',
            ],
            [
                'attribute_group_id' => $attributeGroup->id,
                'position' => 4,
                'name' => collect(['nl' => 'Uitgebreide beschrijving', 'en' => 'Extended Description']),
                'description' => collect(['nl' => 'Extra content onder de vouw (HTML)', 'en' => 'Additional content below the fold (HTML)']),
                'type' => TranslatedText::class,
                'required' => false,
                'default_value' => null,
                'searchable' => true,
                'filterable' => false,
                'validation_rules' => '',
                'configuration' => collect(['richtext' => true]),
                'system' => false,
            ]
        );

        $this->command->info('Collection attributes created!');
    }

    /**
     * Get the category structure based on Probo.nl navigation.
     *
     * This contains ONLY categories/collections, NOT individual products.
     * Products are managed separately and linked to these collections.
     *
     * Structure based on https://www.probo.nl main navigation.
     */
    protected function getCategoryStructure(): array
    {
        return [
            // =================================================================
            // BUITENRECLAME (Outdoor Advertising)
            // =================================================================
            [
                'name' => [
                    'nl' => 'Buitenreclame',
                    'en' => 'Outdoor Advertising',
                    'es' => 'Publicidad Exterior',
                    'fr' => 'Publicité Extérieure',
                ],
                'description' => [
                    'nl' => 'Bekijk ons brede assortiment buitenreclame en bestel eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst, een scherpe prijs en de snelste levering.',
                    'en' => 'Browse our wide range of outdoor advertising and order easily online. Benefit from high-quality printing from our Durst high-tech machines, competitive pricing, and fastest delivery.',
                    'es' => 'Explore nuestra amplia gama de publicidad exterior y pida fácilmente en línea. Benefíciese de la impresión de alta calidad de nuestras máquinas Durst de alta tecnología, precios competitivos y la entrega más rápida.',
                    'fr' => 'Parcourez notre large gamme de publicité extérieure et commandez facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst de haute technologie, des prix compétitifs et une livraison rapide.',
                ],
                'handle' => 'buitenreclame',
                'slugs' => ['nl' => 'buitenreclame', 'en' => 'outdoor-advertising', 'es' => 'publicidad-exterior', 'fr' => 'publicite-exterieure'],
                'source_url' => 'https://www.probo.nl/buitenreclame',
                'pinterest_url' => 'https://www.pinterest.com/printspiratie/gevelbord-wandschild-wall-sign/',
                'below_fold_content' => [
                    'nl' => '<p>Buitenreclame is een zeer voordelige en effectieve manier om je bedrijf of evenement onder de aandacht te brengen. Van reclameborden tot spandoeken, van lichtreclame tot voertuigreclame - wij bieden een compleet assortiment voor al je outdoor communicatie.</p><h3>Waarom buitenreclame?</h3><ul><li>24/7 zichtbaarheid voor je doelgroep</li><li>Lokale targeting</li><li>Duurzame materialen voor langdurig gebruik</li><li>Professionele afwerking</li></ul>',
                    'en' => '<p>Outdoor advertising is a very affordable and effective way to bring attention to your business or event. From advertising signs to banners, from illuminated signs to vehicle advertising - we offer a complete range for all your outdoor communication.</p><h3>Why outdoor advertising?</h3><ul><li>24/7 visibility for your target audience</li><li>Local targeting</li><li>Durable materials for long-term use</li><li>Professional finishing</li></ul>',
                    'es' => '<p>La publicidad exterior es una forma muy asequible y efectiva de dar a conocer su negocio o evento. Desde carteles publicitarios hasta pancartas, desde letreros luminosos hasta publicidad en vehículos - ofrecemos una gama completa para toda su comunicación exterior.</p><h3>¿Por qué publicidad exterior?</h3><ul><li>Visibilidad 24/7 para su público objetivo</li><li>Segmentación local</li><li>Materiales duraderos para uso a largo plazo</li><li>Acabado profesional</li></ul>',
                    'fr' => '<p>La publicité extérieure est un moyen très abordable et efficace d\'attirer l\'attention sur votre entreprise ou événement. Des panneaux publicitaires aux bannières, des enseignes lumineuses à la publicité sur véhicules - nous offrons une gamme complète pour toute votre communication extérieure.</p><h3>Pourquoi la publicité extérieure?</h3><ul><li>Visibilité 24h/24 pour votre public cible</li><li>Ciblage local</li><li>Matériaux durables pour une utilisation à long terme</li><li>Finition professionnelle</li></ul>',
                ],
                'children' => [
                    [
                        'name' => ['nl' => 'Reclameborden', 'en' => 'Advertising Signs'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment reclameborden en bestel eenvoudig online. Van bouwborden tot sportveldborden.',
                            'en' => 'Browse our wide range of advertising signs and order easily online. From construction signs to sports field boards.',
                        ],
                        'handle' => 'reclameborden',
                        'source_url' => 'https://www.probo.nl/buitenreclame/gevelreclame',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/reclamebord-werbeschild-advertising-board/',
                    ],
                    [
                        'name' => ['nl' => 'Stoepborden', 'en' => 'Pavement Signs'],
                        'description' => [
                            'nl' => 'Stoepborden zijn spatwaterdichte borden voor posters. Ideaal voor winkels en horeca.',
                            'en' => 'Pavement signs are splash-proof boards for posters. Ideal for shops and hospitality.',
                        ],
                        'handle' => 'stoepborden',
                        'source_url' => 'https://www.probo.nl/buitenreclame/buitenreclame-stoepborden',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/stoepbord-kundenstopper-pavement-sign/',
                    ],
                    [
                        'name' => ['nl' => 'Gevelreclame', 'en' => 'Facade Advertising'],
                        'description' => [
                            'nl' => 'Maak indruk met gevelreclame. Van gevelborden tot freesletters.',
                            'en' => 'Make an impression with facade advertising. From facade signs to milled letters.',
                        ],
                        'handle' => 'gevelreclame',
                        'source_url' => 'https://www.probo.nl/buitenreclame/gevelvlaggen',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/gevelbord-wandschild-wall-sign/',
                    ],
                    [
                        'name' => ['nl' => 'Lichtreclame', 'en' => 'Light Advertising'],
                        'description' => [
                            'nl' => 'Val op met lichtreclame. Lichtbakken en LED-frames voor maximale zichtbaarheid.',
                            'en' => 'Stand out with light advertising. Light boxes and LED frames for maximum visibility.',
                        ],
                        'handle' => 'lichtreclame',
                        'source_url' => 'https://www.probo.nl/buitenreclame/lichtreclame',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/lichtbak-leuchtkasten-lightbox/',
                    ],
                    [
                        'name' => ['nl' => 'Spandoeken', 'en' => 'Banners'],
                        'description' => [
                            'nl' => 'Grote spandoeken voor buitengebruik. Bouwhekdoeken, steigerdoeken en meer.',
                            'en' => 'Large banners for outdoor use. Construction fence banners, scaffolding banners and more.',
                        ],
                        'handle' => 'spandoeken',
                        'source_url' => 'https://www.probo.nl/buitenreclame/buitenreclame-spandoeken',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/spandoek-banner/',
                    ],
                    [
                        'name' => ['nl' => 'Spandoekframes', 'en' => 'Banner Frames'],
                        'description' => [
                            'nl' => 'Spandoekframes voor een professionele presentatie van je spandoeken.',
                            'en' => 'Banner frames for a professional presentation of your banners.',
                        ],
                        'handle' => 'spandoekframes',
                        'source_url' => 'https://www.probo.nl/buitenreclame/spandoekframes',
                    ],
                    [
                        'name' => ['nl' => 'Voertuigreclame', 'en' => 'Vehicle Advertising'],
                        'description' => [
                            'nl' => 'Mobiele reclame op voertuigen. Carwraps, autostickers en meer.',
                            'en' => 'Mobile advertising on vehicles. Car wraps, car stickers and more.',
                        ],
                        'handle' => 'voertuigreclame',
                        'source_url' => 'https://www.probo.nl/buitenreclame/voertuigen',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/voertuigreclame-fahrzeugwerbung-vehicle-advertising/',
                    ],
                    [
                        'name' => ['nl' => 'Bouw & vastgoed', 'en' => 'Construction & Real Estate'],
                        'description' => [
                            'nl' => 'Bouwborden, makelaarsborden en steigerdoeken voor de bouw en vastgoed sector.',
                            'en' => 'Construction signs, real estate signs and scaffolding banners for the construction and real estate sector.',
                        ],
                        'handle' => 'bouw-vastgoed',
                        'source_url' => 'https://www.probo.nl/buitenreclame/bouw-vastgoed',
                    ],
                    [
                        'name' => ['nl' => 'Terras & tuin', 'en' => 'Terrace & Garden'],
                        'description' => [
                            'nl' => 'Tuinposters, parasols en buitenkussens voor terras en tuin.',
                            'en' => 'Garden posters, parasols and outdoor cushions for terrace and garden.',
                        ],
                        'handle' => 'terras-tuin',
                        'source_url' => 'https://www.probo.nl/buitenreclame/terras-tuin',
                    ],
                ],
            ],

            // =================================================================
            // VLAGGEN (Flags)
            // =================================================================
            [
                'name' => [
                    'nl' => 'Vlaggen',
                    'en' => 'Flags',
                    'es' => 'Banderas',
                    'fr' => 'Drapeaux',
                ],
                'description' => [
                    'nl' => 'Bekijk ons brede assortiment vlaggen en bestel je vlag eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst.',
                    'en' => 'Browse our wide range of flags and order your flag easily online. Benefit from high-quality printing from our Durst high-tech machines.',
                    'es' => 'Explore nuestra amplia gama de banderas y pida su bandera fácilmente en línea. Benefíciese de la impresión de alta calidad de nuestras máquinas Durst de alta tecnología.',
                    'fr' => 'Parcourez notre large gamme de drapeaux et commandez votre drapeau facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst de haute technologie.',
                ],
                'handle' => 'vlaggen',
                'slugs' => ['nl' => 'vlaggen', 'en' => 'flags', 'es' => 'banderas', 'fr' => 'drapeaux'],
                'source_url' => 'https://www.probo.nl/vlaggen',
                'pinterest_url' => 'https://www.pinterest.com/printspiratie/vlaggen-fahnen-flag/',
                'below_fold_content' => [
                    'nl' => '<p>Vlaggen zijn een krachtig middel om aandacht te trekken. Of het nu gaat om mastvlaggen, gevelvlaggen of beachflags - onze vlaggen worden geprint op hoogwaardige materialen die bestand zijn tegen weer en wind.</p><h3>Voordelen van onze vlaggen</h3><ul><li>Dubbelzijdig bedrukt</li><li>UV-bestendige inkten</li><li>Diverse formaten en vormen</li><li>Inclusief accessoires beschikbaar</li></ul>',
                    'en' => '<p>Flags are a powerful way to attract attention. Whether it\'s mast flags, facade flags or beach flags - our flags are printed on high-quality materials that are resistant to weather and wind.</p><h3>Advantages of our flags</h3><ul><li>Double-sided printing</li><li>UV-resistant inks</li><li>Various sizes and shapes</li><li>Accessories available</li></ul>',
                    'es' => '<p>Las banderas son una forma poderosa de atraer la atención. Ya sean banderas de mástil, banderas de fachada o banderas de playa - nuestras banderas se imprimen en materiales de alta calidad resistentes al clima y al viento.</p><h3>Ventajas de nuestras banderas</h3><ul><li>Impresión a doble cara</li><li>Tintas resistentes a los rayos UV</li><li>Varios tamaños y formas</li><li>Accesorios disponibles</li></ul>',
                    'fr' => '<p>Les drapeaux sont un moyen puissant d\'attirer l\'attention. Qu\'il s\'agisse de drapeaux de mât, de drapeaux de façade ou de beach flags - nos drapeaux sont imprimés sur des matériaux de haute qualité résistants aux intempéries.</p><h3>Avantages de nos drapeaux</h3><ul><li>Impression recto-verso</li><li>Encres résistantes aux UV</li><li>Différentes tailles et formes</li><li>Accessoires disponibles</li></ul>',
                ],
                'children' => [
                    [
                        'name' => ['nl' => 'Gevelvlaggen', 'en' => 'Facade Flags'],
                        'description' => [
                            'nl' => 'Gevelvlaggen, mastvlaggen en baniervlaggen voor aan de gevel of vlaggenmast.',
                            'en' => 'Facade flags, mast flags and banner flags for your facade or flagpole.',
                        ],
                        'handle' => 'gevelvlaggen',
                        'source_url' => 'https://www.probo.nl/vlaggen/gevelvlaggen',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/gevelvlag-fassadenfahne-facade-flag/',
                    ],
                    [
                        'name' => ['nl' => 'Beachflags', 'en' => 'Beach Flags'],
                        'description' => [
                            'nl' => 'Beachflags in diverse vormen en maten. Ideaal voor beurzen, evenementen en winkels.',
                            'en' => 'Beach flags in various shapes and sizes. Ideal for trade shows, events and shops.',
                        ],
                        'handle' => 'beachflags',
                        'source_url' => 'https://www.probo.nl/vlaggen/beachflags',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/beachflag/',
                    ],
                    [
                        'name' => ['nl' => 'Overige vlaggen', 'en' => 'Other Flags'],
                        'description' => [
                            'nl' => 'Vlaggenlijnen, bootvlaggen, landenvlaggen en meer.',
                            'en' => 'Bunting, boat flags, country flags and more.',
                        ],
                        'handle' => 'overige-vlaggen',
                        'source_url' => 'https://www.probo.nl/vlaggen/overige-vlaggen',
                    ],
                ],
            ],

            // =================================================================
            // BINNENRECLAME (Indoor Advertising)
            // =================================================================
            [
                'name' => [
                    'nl' => 'Binnenreclame',
                    'en' => 'Indoor Advertising',
                    'es' => 'Publicidad Interior',
                    'fr' => 'Publicité Intérieure',
                ],
                'description' => [
                    'nl' => 'Bekijk ons brede assortiment binnenreclame en bestel eenvoudig online.',
                    'en' => 'Browse our wide range of indoor advertising and order easily online.',
                    'es' => 'Explore nuestra amplia gama de publicidad interior y pida fácilmente en línea.',
                    'fr' => 'Parcourez notre large gamme de publicité intérieure et commandez facilement en ligne.',
                ],
                'handle' => 'binnenreclame',
                'slugs' => ['nl' => 'binnenreclame', 'en' => 'indoor-advertising', 'es' => 'publicidad-interior', 'fr' => 'publicite-interieure'],
                'source_url' => 'https://www.probo.nl/binnenreclame',
                'pinterest_url' => 'https://www.pinterest.com/printspiratie/wandreclame-wandreklame-wall-advertising/',
                'below_fold_content' => [
                    'nl' => '<p>Binnenreclame biedt eindeloze mogelijkheden om je interieur te verfraaien met professionele prints. Van textielframes tot wandpanelen, van LED-verlichte displays tot akoestische oplossingen.</p><h3>Toepassingen</h3><ul><li>Retail en winkels</li><li>Kantoren en vergaderruimtes</li><li>Horeca en restaurants</li><li>Beurzen en evenementen</li></ul>',
                    'en' => '<p>Indoor advertising offers endless possibilities to enhance your interior with professional prints. From textile frames to wall panels, from LED-lit displays to acoustic solutions.</p><h3>Applications</h3><ul><li>Retail and shops</li><li>Offices and meeting rooms</li><li>Hospitality and restaurants</li><li>Trade fairs and events</li></ul>',
                    'es' => '<p>La publicidad interior ofrece infinitas posibilidades para embellecer su interior con impresiones profesionales. Desde marcos textiles hasta paneles de pared, desde displays con LED hasta soluciones acústicas.</p><h3>Aplicaciones</h3><ul><li>Retail y tiendas</li><li>Oficinas y salas de reuniones</li><li>Hostelería y restaurantes</li><li>Ferias y eventos</li></ul>',
                    'fr' => '<p>La publicité intérieure offre des possibilités infinies pour embellir votre intérieur avec des impressions professionnelles. Des cadres textiles aux panneaux muraux, des écrans LED aux solutions acoustiques.</p><h3>Applications</h3><ul><li>Commerce de détail et magasins</li><li>Bureaux et salles de réunion</li><li>Hôtellerie et restaurants</li><li>Salons et événements</li></ul>',
                ],
                'children' => [
                    [
                        'name' => ['nl' => 'Wandreclame', 'en' => 'Wall Advertising'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment wandreclame en bestel onder andere textielframes en peesdoeken eenvoudig online.',
                            'en' => 'Browse our wide range of wall advertising and order textile frames and fabric panels easily online.',
                        ],
                        'handle' => 'wandreclame',
                        'source_url' => 'https://www.probo.nl/binnenreclame/wandreclame',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/wandreclame-wandreklame-wall-advertising/',
                    ],
                    [
                        'name' => ['nl' => 'Wandbekleding', 'en' => 'Wall Covering'],
                        'description' => [
                            'nl' => 'Professionele wandbekleding voor kantoren, winkels en meer.',
                            'en' => 'Professional wall covering for offices, shops and more.',
                        ],
                        'handle' => 'wandbekleding',
                        'source_url' => 'https://www.probo.nl/binnenreclame/wandbekleding',
                    ],
                    [
                        'name' => ['nl' => 'Presentatie', 'en' => 'Presentation'],
                        'description' => [
                            'nl' => 'Roll-up banners, LED-frames en presentatiesystemen voor beurzen en evenementen.',
                            'en' => 'Roll-up banners, LED frames and presentation systems for trade shows and events.',
                        ],
                        'handle' => 'presentatie',
                        'source_url' => 'https://www.probo.nl/binnenreclame/presentatie',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/roll-up-banners-roll-up/',
                    ],
                    [
                        'name' => ['nl' => 'Horeca', 'en' => 'Hospitality'],
                        'description' => [
                            'nl' => 'Menukaarten, placemats, deurmatten en meer voor de horeca.',
                            'en' => 'Menu cards, placemats, doormats and more for the hospitality industry.',
                        ],
                        'handle' => 'horeca',
                        'source_url' => 'https://www.probo.nl/binnenreclame/horeca',
                    ],
                    [
                        'name' => ['nl' => 'Retail', 'en' => 'Retail'],
                        'description' => [
                            'nl' => 'POS-displays, schapstroken en winkelreclame voor retail.',
                            'en' => 'POS displays, shelf strips and retail advertising.',
                        ],
                        'handle' => 'retail',
                        'source_url' => 'https://www.probo.nl/binnenreclame/retail',
                    ],
                ],
            ],

            // =================================================================
            // BEURS & EVENEMENT (Trade Shows & Events)
            // =================================================================
            [
                'name' => [
                    'nl' => 'Beurs & evenement',
                    'en' => 'Trade Shows & Events',
                    'es' => 'Ferias y Eventos',
                    'fr' => 'Salons et Événements',
                ],
                'description' => [
                    'nl' => 'Bekijk ons brede assortiment beurs & evenement en bestel eenvoudig online.',
                    'en' => 'Browse our wide range of trade show and event materials and order easily online.',
                    'es' => 'Explore nuestra amplia gama de materiales para ferias y eventos y pida fácilmente en línea.',
                    'fr' => 'Parcourez notre large gamme de matériel pour salons et événements et commandez facilement en ligne.',
                ],
                'handle' => 'beurs-evenement',
                'slugs' => ['nl' => 'beurs-evenement', 'en' => 'trade-shows-events', 'es' => 'ferias-eventos', 'fr' => 'salons-evenements'],
                'source_url' => 'https://www.probo.nl/beurs-evenement',
                'pinterest_url' => 'https://www.pinterest.com/printspiratie/beurswanden-messewände-exhibition-walls/',
                'below_fold_content' => [
                    'nl' => '<p>Beurswanden zijn er in verschillende modellen en formaten. Ze geven een stand een professionele uitstraling en zijn te voorzien van mooie prints. Van pop-up wanden tot roll-up banners, van LED-displays tot complete beursoplossingen.</p><h3>Waarom kiezen voor onze beursproducten?</h3><ul><li>Snelle levering</li><li>Herbruikbaar en duurzaam</li><li>Eenvoudig op te zetten</li><li>Professionele uitstraling</li></ul>',
                    'en' => '<p>Exhibition walls come in various models and sizes. They give a booth a professional look and can be fitted with beautiful prints. From pop-up walls to roll-up banners, from LED displays to complete exhibition solutions.</p><h3>Why choose our trade show products?</h3><ul><li>Fast delivery</li><li>Reusable and durable</li><li>Easy to set up</li><li>Professional appearance</li></ul>',
                    'es' => '<p>Los stands de exposición vienen en varios modelos y tamaños. Dan al stand un aspecto profesional y pueden equiparse con hermosas impresiones. Desde paredes pop-up hasta roll-up banners, desde displays LED hasta soluciones completas para ferias.</p><h3>¿Por qué elegir nuestros productos para ferias?</h3><ul><li>Entrega rápida</li><li>Reutilizable y duradero</li><li>Fácil de montar</li><li>Aspecto profesional</li></ul>',
                    'fr' => '<p>Les murs d\'exposition sont disponibles en différents modèles et tailles. Ils donnent un aspect professionnel au stand et peuvent être équipés de belles impressions. Des murs pop-up aux roll-up banners, des écrans LED aux solutions d\'exposition complètes.</p><h3>Pourquoi choisir nos produits pour salons?</h3><ul><li>Livraison rapide</li><li>Réutilisable et durable</li><li>Facile à installer</li><li>Aspect professionnel</li></ul>',
                ],
                'children' => [
                    [
                        'name' => ['nl' => 'Beurswanden', 'en' => 'Exhibition Walls'],
                        'description' => [
                            'nl' => 'Een beurswand laten bedrukken voor jouw klant? Profiteer van een hoge kwaliteit print door high tech machines van Durst, een scherpe prijs en de snelste levering.',
                            'en' => 'Have an exhibition wall printed for your customer? Benefit from high-quality printing by Durst high-tech machines, competitive pricing and fastest delivery.',
                            'es' => '¿Imprimir un stand de exposición para tu cliente? Benefíciate de impresión de alta calidad por máquinas de alta tecnología Durst, precios competitivos y la entrega más rápida.',
                            'fr' => 'Faire imprimer un mur d\'exposition pour votre client? Bénéficiez d\'une impression de haute qualité par les machines Durst haute technologie, des prix compétitifs et la livraison la plus rapide.',
                        ],
                        'handle' => 'beurswanden',
                        'source_url' => 'https://www.probo.nl/beurs-evenement/beurswanden',
                    ],
                    [
                        'name' => ['nl' => 'Verlichte beurswanden', 'en' => 'Illuminated Exhibition Walls'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment verlichte beurswanden en bestel je beurswand eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst, een scherpe prijs en de snelste levering.',
                            'en' => 'Browse our wide range of illuminated exhibition walls and order your exhibition wall easily online. Benefit from high-quality printing from our Durst high-tech machines, competitive pricing and fastest delivery.',
                            'es' => 'Explore nuestra amplia gama de paredes de exposición iluminadas y pida su pared de exposición fácilmente en línea. Benefíciese de impresión de alta calidad de nuestras máquinas Durst de alta tecnología, precios competitivos y la entrega más rápida.',
                            'fr' => 'Parcourez notre large gamme de murs d\'exposition éclairés et commandez votre mur d\'exposition facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst haute technologie, de prix compétitifs et de la livraison la plus rapide.',
                        ],
                        'handle' => 'verlichte-beurswanden',
                        'source_url' => 'https://www.probo.nl/beurs-evenement/verlichte-beurswanden',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/led-frame-led-rahmen/',
                    ],
                    [
                        'name' => ['nl' => 'Beursvloeren', 'en' => 'Exhibition Floors'],
                        'description' => [
                            'nl' => 'Duurzame vloerstickers en bescherming tegen krassen en vlekken op vloeren. Ideaal voor beurzen en evenementen.',
                            'en' => 'Durable floor stickers and protection against scratches and stains on floors. Ideal for exhibitions and events.',
                            'es' => 'Pegatinas de suelo duraderas y protección contra arañazos y manchas en suelos. Ideal para exposiciones y eventos.',
                            'fr' => 'Autocollants de sol durables et protection contre les rayures et les taches sur les sols. Idéal pour les expositions et événements.',
                        ],
                        'handle' => 'beursvloeren',
                        'source_url' => 'https://www.probo.nl/beurs-evenement/beursvloeren',
                    ],
                    [
                        'name' => ['nl' => 'Beursdoeken', 'en' => 'Exhibition Fabrics'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment beursdoeken en bestel je doek eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst, een scherpe prijs en de snelste levering.',
                            'en' => 'Browse our wide range of exhibition fabrics and order your fabric easily online. Benefit from high-quality printing from our Durst high-tech machines, competitive pricing and fastest delivery.',
                            'es' => 'Explore nuestra amplia gama de telas de exposición y pida su tela fácilmente en línea. Benefíciese de impresión de alta calidad de nuestras máquinas Durst de alta tecnología, precios competitivos y la entrega más rápida.',
                            'fr' => 'Parcourez notre large gamme de tissus d\'exposition et commandez votre tissu facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst haute technologie, de prix compétitifs et de la livraison la plus rapide.',
                        ],
                        'handle' => 'beursdoeken',
                        'source_url' => 'https://www.probo.nl/beurs-evenement/beursdoeken',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/beursdoeken-ausstellungst%C3%BCcher-exhibition-banner/',
                    ],
                    [
                        'name' => ['nl' => 'Roll-up banners', 'en' => 'Roll-up Banners'],
                        'description' => [
                            'nl' => 'Een roll-up banner kent vele namen: rolbanner, rollup banner of roll banner. Hiermee promoot je snel, eenvoudig én voordelig een product, bedrijf of evenement.',
                            'en' => 'A roll-up banner has many names: roll banner, rollup banner or roll banner. With this you quickly, easily and affordably promote a product, company or event.',
                            'es' => 'Un banner enrollable tiene muchos nombres: banner enrollable, roll banner o rollup banner. Con esto promocionas rápida, fácil y económicamente un producto, empresa o evento.',
                            'fr' => 'Un roll-up banner a de nombreux noms: roll banner, rollup banner ou banner enroulable. Avec cela, vous promouvez rapidement, facilement et à moindre coût un produit, une entreprise ou un événement.',
                        ],
                        'handle' => 'roll-up-banners',
                        'source_url' => 'https://www.probo.nl/beurs-evenement/roll-up-banners',
                    ],
                    [
                        'name' => ['nl' => 'Festivaldoeken', 'en' => 'Festival Fabrics'],
                        'description' => [
                            'nl' => 'Bedrukte festivaldoeken voor evenementen en festivals. Hoogwaardige prints voor een professionele uitstraling.',
                            'en' => 'Printed festival fabrics for events and festivals. High-quality prints for a professional appearance.',
                            'es' => 'Telas de festival impresas para eventos y festivales. Impresiones de alta calidad para una apariencia profesional.',
                            'fr' => 'Tissus de festival imprimés pour événements et festivals. Impressions de haute qualité pour une apparence professionnelle.',
                        ],
                        'handle' => 'festivaldoeken',
                        'source_url' => 'https://www.probo.nl/beurs-evenement/festivaldoeken',
                    ],
                    [
                        'name' => ['nl' => 'Aankleding', 'en' => 'Decoration'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment aankledingsproducten. Profiteer van een hoge kwaliteit print, concurrerende prijzen en snelle levering voor printprofessionals.',
                            'en' => 'Browse our wide range of decoration products. Benefit from high-quality printing, competitive prices and fast delivery for print professionals.',
                            'es' => 'Explore nuestra amplia gama de productos de decoración. Benefíciese de impresión de alta calidad, precios competitivos y entrega rápida para profesionales de la impresión.',
                            'fr' => 'Parcourez notre large gamme de produits de décoration. Bénéficiez d\'une impression de haute qualité, de prix compétitifs et d\'une livraison rapide pour les professionnels de l\'impression.',
                        ],
                        'handle' => 'aankleding',
                        'source_url' => 'https://www.probo.nl/beurs-evenement/aankleding',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/beurs-aankleding-ausstellungsdekoration/',
                    ],
                ],
            ],

            // =================================================================
            // INTERIEUR (Interior)
            // =================================================================
            [
                'name' => [
                    'nl' => 'Interieur',
                    'en' => 'Interior',
                    'es' => 'Interior',
                    'fr' => 'Intérieur',
                ],
                'description' => [
                    'nl' => 'Interieur decoratie en wandbekleding.',
                    'en' => 'Interior decoration and wall coverings.',
                    'es' => 'Decoración de interiores y revestimientos de pared.',
                    'fr' => 'Décoration intérieure et revêtements muraux.',
                ],
                'handle' => 'interieur',
                'slugs' => ['nl' => 'interieur', 'en' => 'interior', 'es' => 'interior', 'fr' => 'interieur'],
                'source_url' => 'https://www.probo.nl/interieur-inrichting',
                'pinterest_url' => 'https://www.pinterest.com/printspiratie/wandbekleding-fototapete-wall-covering/',
                'below_fold_content' => [
                    'nl' => '<p>Jij biedt je klant wandbekledingen met verrassende functionaliteiten: krasvast, isolerend, egaliserend, duurzaam, geurloos en akoestisch. Van fotobehang tot canvas prints, van muurcirkels tot complete wandoplossingen.</p><h3>Onze interieurproducten</h3><ul><li>Behang en wandbekleding</li><li>Canvas en foto op paneel</li><li>Akoestische oplossingen</li><li>Decoratieve elementen</li></ul>',
                    'en' => '<p>You offer your customer wall coverings with surprising functionalities: scratch-resistant, insulating, leveling, sustainable, odorless and acoustic. From photo wallpaper to canvas prints, from wall circles to complete wall solutions.</p><h3>Our interior products</h3><ul><li>Wallpaper and wall coverings</li><li>Canvas and photo on panel</li><li>Acoustic solutions</li><li>Decorative elements</li></ul>',
                    'es' => '<p>Ofrece a tu cliente revestimientos de pared con funcionalidades sorprendentes: resistente a los arañazos, aislante, nivelador, sostenible, inodoro y acústico. Desde papel tapiz fotográfico hasta lienzos, desde círculos de pared hasta soluciones completas.</p><h3>Nuestros productos de interior</h3><ul><li>Papel tapiz y revestimientos de pared</li><li>Lienzo y foto en panel</li><li>Soluciones acústicas</li><li>Elementos decorativos</li></ul>',
                    'fr' => '<p>Vous offrez à votre client des revêtements muraux aux fonctionnalités surprenantes: résistant aux rayures, isolant, nivelant, durable, inodore et acoustique. Du papier peint photo aux toiles, des cercles muraux aux solutions murales complètes.</p><h3>Nos produits d\'intérieur</h3><ul><li>Papier peint et revêtements muraux</li><li>Toile et photo sur panneau</li><li>Solutions acoustiques</li><li>Éléments décoratifs</li></ul>',
                ],
                'children' => [
                    [
                        'name' => ['nl' => 'Wanddecoratie', 'en' => 'Wall Decoration'],
                        'description' => [
                            'nl' => 'Hoogwaardige wanddecoratie voor interieur en bedrijfsinrichting. Van canvas prints tot akoestische panelen.',
                            'en' => 'High-quality wall decoration for interior and business furnishing. From canvas prints to acoustic panels.',
                            'es' => 'Decoración de pared de alta calidad para interiores y equipamiento empresarial. Desde lienzos hasta paneles acústicos.',
                            'fr' => 'Décoration murale de haute qualité pour intérieur et aménagement professionnel. Des toiles aux panneaux acoustiques.',
                        ],
                        'handle' => 'wanddecoratie',
                        'source_url' => 'https://www.probo.nl/interieur-inrichting/wanddecoratie',
                    ],
                    [
                        'name' => ['nl' => 'Behang', 'en' => 'Wallpaper'],
                        'description' => [
                            'nl' => 'Krasvast behang in banen op rol. Verkrijgbaar in twee rolbreedtes (53cm en 70cm), met vuil- en waterafstotende eigenschappen, FSC gecertificeerd en brandvertragend.',
                            'en' => 'Scratch-resistant wallpaper in strips on roll. Available in two roll widths (53cm and 70cm), with dirt and water repellent properties, FSC certified and fire-retardant.',
                            'es' => 'Papel tapiz resistente a los arañazos en tiras enrolladas. Disponible en dos anchos de rollo (53cm y 70cm), con propiedades repelentes de suciedad y agua, certificación FSC y retardante de llama.',
                            'fr' => 'Papier peint résistant aux rayures en lés sur rouleau. Disponible en deux largeurs de rouleau (53cm et 70cm), avec propriétés anti-salissures et hydrofuges, certifié FSC et ignifuge.',
                        ],
                        'handle' => 'behang',
                        'source_url' => 'https://www.probo.nl/interieur-inrichting/behang',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/behang-fototapete-wallpaper/',
                    ],
                    [
                        'name' => ['nl' => 'Vloerdecoratie', 'en' => 'Floor Decoration'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment vloerdecoratie en bestel eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst, een scherpe prijs en de snelste levering.',
                            'en' => 'Browse our wide range of floor decoration and order easily online. Benefit from high-quality printing from our Durst high-tech machines, competitive pricing and fastest delivery.',
                            'es' => 'Explore nuestra amplia gama de decoración de suelo y pida fácilmente en línea. Benefíciese de impresión de alta calidad de nuestras máquinas Durst de alta tecnología, precios competitivos y la entrega más rápida.',
                            'fr' => 'Parcourez notre large gamme de décoration de sol et commandez facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst haute technologie, de prix compétitifs et de la livraison la plus rapide.',
                        ],
                        'handle' => 'vloerdecoratie',
                        'source_url' => 'https://www.probo.nl/interieur-inrichting/vloerdecoratie',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/vloerdecoratie-fußbodendekorationfloordecoration/',
                    ],
                    [
                        'name' => ['nl' => 'Fotoproducten', 'en' => 'Photo Products'],
                        'description' => [
                            'nl' => 'Canvas prints, foto op paneel en andere fotoproducten van hoge kwaliteit. Perfect voor interieur en decoratie.',
                            'en' => 'Canvas prints, photo on panel and other high-quality photo products. Perfect for interior and decoration.',
                            'es' => 'Lienzos, foto en panel y otros productos fotográficos de alta calidad. Perfecto para interiores y decoración.',
                            'fr' => 'Toiles, photo sur panneau et autres produits photo de haute qualité. Parfait pour intérieur et décoration.',
                        ],
                        'handle' => 'fotoproducten',
                        'source_url' => 'https://www.probo.nl/interieur-inrichting/fotoproducten',
                    ],
                    [
                        'name' => ['nl' => 'Huis & horeca', 'en' => 'Home & Hospitality'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment huis & horeca producten en bestel je frame eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst, een scherpe prijs en de snelste levering.',
                            'en' => 'Browse our wide range of home & hospitality products and order your frame easily online. Benefit from high-quality printing from our Durst high-tech machines, competitive pricing and fastest delivery.',
                            'es' => 'Explore nuestra amplia gama de productos para hogar y hostelería y pida su marco fácilmente en línea. Benefíciese de impresión de alta calidad de nuestras máquinas Durst de alta tecnología, precios competitivos y la entrega más rápida.',
                            'fr' => 'Parcourez notre large gamme de produits pour maison et hôtellerie et commandez votre cadre facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst haute technologie, de prix compétitifs et de la livraison la plus rapide.',
                        ],
                        'handle' => 'huis-horeca',
                        'source_url' => 'https://www.probo.nl/interieur-inrichting/huis-horeca',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/huis-horeca-heim-gastfreundschaft/',
                    ],
                    [
                        'name' => ['nl' => 'Kantoor', 'en' => 'Office'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment kantoor producten en bestel eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst, een scherpe prijs en de snelste levering.',
                            'en' => 'Browse our wide range of office products and order easily online. Benefit from high-quality printing from our Durst high-tech machines, competitive pricing and fastest delivery.',
                            'es' => 'Explore nuestra amplia gama de productos para oficina y pida fácilmente en línea. Benefíciese de impresión de alta calidad de nuestras máquinas Durst de alta tecnología, precios competitivos y la entrega más rápida.',
                            'fr' => 'Parcourez notre large gamme de produits de bureau et commandez facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst haute technologie, de prix compétitifs et de la livraison la plus rapide.',
                        ],
                        'handle' => 'kantoor',
                        'source_url' => 'https://www.probo.nl/interieur-inrichting/kantoor',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/kantoor-büro-office/',
                    ],
                ],
            ],

            // =================================================================
            // STICKERS & DRUKWERK (Stickers & Printing)
            // =================================================================
            [
                'name' => [
                    'nl' => 'Stickers & drukwerk',
                    'en' => 'Stickers & Printing',
                    'es' => 'Pegatinas e Imprenta',
                    'fr' => 'Autocollants et Impression',
                ],
                'description' => [
                    'nl' => 'Bestel stickers in de unieke stijl van je klant. Van vinyl stickers tot vloerstickers, van rollen tot vellen.',
                    'en' => 'Order stickers in your customer\'s unique style. From vinyl stickers to floor stickers, from rolls to sheets.',
                    'es' => 'Pida pegatinas en el estilo único de su cliente. Desde pegatinas de vinilo hasta pegatinas de suelo, desde rollos hasta hojas.',
                    'fr' => 'Commandez des autocollants dans le style unique de votre client. Des autocollants vinyle aux autocollants de sol, des rouleaux aux feuilles.',
                ],
                'handle' => 'stickers-drukwerk',
                'slugs' => ['nl' => 'stickers-drukwerk', 'en' => 'stickers-printing', 'es' => 'pegatinas-imprenta', 'fr' => 'autocollants-impression'],
                'source_url' => 'https://www.probo.nl/stickers-drukwerk',
                'pinterest_url' => 'https://www.pinterest.com/printspiratie/drukwerk-drucksachen-printed-matter/',
                'below_fold_content' => [
                    'nl' => '<p>Ons uitgebreide assortiment stickers en drukwerk biedt voor elke toepassing de juiste oplossing. Van promotionele stickers tot professioneel drukwerk zoals flyers, brochures en visitekaartjes.</p><h3>Ons assortiment</h3><ul><li>Vinyl stickers en vloerstickers</li><li>Stickers op rol en vel</li><li>Flyers, brochures en folders</li><li>Visitekaartjes en menukaarten</li></ul>',
                    'en' => '<p>Our extensive range of stickers and printed materials offers the right solution for every application. From promotional stickers to professional printed matter such as flyers, brochures and business cards.</p><h3>Our range</h3><ul><li>Vinyl stickers and floor stickers</li><li>Stickers on roll and sheet</li><li>Flyers, brochures and folders</li><li>Business cards and menu cards</li></ul>',
                    'es' => '<p>Nuestra amplia gama de pegatinas y material impreso ofrece la solución adecuada para cada aplicación. Desde pegatinas promocionales hasta material impreso profesional como flyers, folletos y tarjetas de visita.</p><h3>Nuestra gama</h3><ul><li>Pegatinas de vinilo y de suelo</li><li>Pegatinas en rollo y hoja</li><li>Flyers, folletos y catálogos</li><li>Tarjetas de visita y cartas de menú</li></ul>',
                    'fr' => '<p>Notre vaste gamme d\'autocollants et d\'imprimés offre la bonne solution pour chaque application. Des autocollants promotionnels aux imprimés professionnels tels que flyers, brochures et cartes de visite.</p><h3>Notre gamme</h3><ul><li>Autocollants vinyle et de sol</li><li>Autocollants en rouleau et en feuille</li><li>Flyers, brochures et dépliants</li><li>Cartes de visite et cartes de menu</li></ul>',
                ],
                'children' => [
                    [
                        'name' => ['nl' => 'Drukwerk', 'en' => 'Printing'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment drukwerk en bestel eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst, een scherpe prijs en de snelste levering.',
                            'en' => 'Browse our wide range of printed matter and order easily online. Benefit from high-quality printing from our Durst high-tech machines, competitive pricing and fastest delivery.',
                            'es' => 'Explore nuestra amplia gama de material impreso y pida fácilmente en línea. Benefíciese de impresión de alta calidad de nuestras máquinas Durst de alta tecnología, precios competitivos y la entrega más rápida.',
                            'fr' => 'Parcourez notre large gamme d\'imprimés et commandez facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst haute technologie, de prix compétitifs et de la livraison la plus rapide.',
                        ],
                        'handle' => 'drukwerk',
                        'source_url' => 'https://www.probo.nl/stickers-drukwerk/drukwerk',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/drukwerk-drucksachen-printed-matter/',
                    ],
                    [
                        'name' => ['nl' => 'Posters', 'en' => 'Posters'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment posters en bestel je poster eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst, een scherpe prijs en de snelste levering.',
                            'en' => 'Browse our wide range of posters and order your poster easily online. Benefit from high-quality printing from our Durst high-tech machines, competitive pricing and fastest delivery.',
                            'es' => 'Explore nuestra amplia gama de pósters y pida su póster fácilmente en línea. Benefíciese de impresión de alta calidad de nuestras máquinas Durst de alta tecnología, precios competitivos y la entrega más rápida.',
                            'fr' => 'Parcourez notre large gamme de posters et commandez votre poster facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst haute technologie, de prix compétitifs et de la livraison la plus rapide.',
                        ],
                        'handle' => 'posters',
                        'source_url' => 'https://www.probo.nl/stickers-drukwerk/stickers-drukwerk-posters',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/poster/',
                    ],
                    [
                        'name' => ['nl' => 'Grote stickers', 'en' => 'Large Stickers'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment grote stickers en bestel je sticker eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst, een scherpe prijs en de snelste levering.',
                            'en' => 'Browse our wide range of large stickers and order your sticker easily online. Benefit from high-quality printing from our Durst high-tech machines, competitive pricing and fastest delivery.',
                            'es' => 'Explore nuestra amplia gama de pegatinas grandes y pida su pegatina fácilmente en línea. Benefíciese de impresión de alta calidad de nuestras máquinas Durst de alta tecnología, precios competitivos y la entrega más rápida.',
                            'fr' => 'Parcourez notre large gamme de grands autocollants et commandez votre autocollant facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst haute technologie, de prix compétitifs et de la livraison la plus rapide.',
                        ],
                        'handle' => 'grote-stickers',
                        'source_url' => 'https://www.probo.nl/stickers-drukwerk/stickers',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/vinylsticker-vinylaufkleber-vinyl-sticker/',
                    ],
                    [
                        'name' => ['nl' => 'Kleine stickers', 'en' => 'Small Stickers'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment kleine stickers en bestel je stickers eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst, een scherpe prijs en de snelste levering.',
                            'en' => 'Browse our wide range of small stickers and order your stickers easily online. Benefit from high-quality printing from our Durst high-tech machines, competitive pricing and fastest delivery.',
                            'es' => 'Explore nuestra amplia gama de pegatinas pequeñas y pida sus pegatinas fácilmente en línea. Benefíciese de impresión de alta calidad de nuestras máquinas Durst de alta tecnología, precios competitivos y la entrega más rápida.',
                            'fr' => 'Parcourez notre large gamme de petits autocollants et commandez vos autocollants facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst haute technologie, de prix compétitifs et de la livraison la plus rapide.',
                        ],
                        'handle' => 'kleine-stickers',
                        'source_url' => 'https://www.probo.nl/stickers-drukwerk/kleine-stickers-tot-30cm',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/kleine-stickers-kleine-aufkleber-small-stickers/',
                    ],
                    [
                        'name' => ['nl' => 'Stickers op rol', 'en' => 'Roll Stickers'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment stickers op rol en bestel eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst, een scherpe prijs en de snelste levering.',
                            'en' => 'Browse our wide range of roll stickers and order easily online. Benefit from high-quality printing from our Durst high-tech machines, competitive pricing and fastest delivery.',
                            'es' => 'Explore nuestra amplia gama de pegatinas en rollo y pida fácilmente en línea. Benefíciese de impresión de alta calidad de nuestras máquinas Durst de alta tecnología, precios competitivos y la entrega más rápida.',
                            'fr' => 'Parcourez notre large gamme d\'autocollants en rouleau et commandez facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst haute technologie, de prix compétitifs et de la livraison la plus rapide.',
                        ],
                        'handle' => 'stickers-op-rol',
                        'source_url' => 'https://www.probo.nl/stickers-drukwerk/stickers-op-rol',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/stickers-op-rol-aufkleber-auf-rollen/',
                    ],
                    [
                        'name' => ['nl' => 'Stickervellen', 'en' => 'Sticker Sheets'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment stickervellen en bestel eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst, een scherpe prijs en de snelste levering.',
                            'en' => 'Browse our wide range of sticker sheets and order easily online. Benefit from high-quality printing from our Durst high-tech machines, competitive pricing and fastest delivery.',
                            'es' => 'Explore nuestra amplia gama de hojas de pegatinas y pida fácilmente en línea. Benefíciese de impresión de alta calidad de nuestras máquinas Durst de alta tecnología, precios competitivos y la entrega más rápida.',
                            'fr' => 'Parcourez notre large gamme de feuilles d\'autocollants et commandez facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst haute technologie, de prix compétitifs et de la livraison la plus rapide.',
                        ],
                        'handle' => 'stickervellen',
                        'source_url' => 'https://www.probo.nl/stickers-drukwerk/sticker-vellen',
                        'pinterest_url' => 'https://www.pinterest.com/printspiratie/stickervellen-aufkleber-b%C3%B6gen-stickers-on-sheet/',
                    ],
                ],
            ],

            // =================================================================
            // MATERIALEN (Materials)
            // =================================================================
            [
                'name' => [
                    'nl' => 'Materialen',
                    'en' => 'Materials',
                    'es' => 'Materiales',
                    'fr' => 'Matériaux',
                ],
                'description' => [
                    'nl' => 'Bekijk ons brede assortiment materialen en bestel eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst.',
                    'en' => 'Browse our wide range of materials and order easily online. Benefit from high-quality printing from our Durst high-tech machines.',
                    'es' => 'Explore nuestra amplia gama de materiales y pida fácilmente en línea. Benefíciese de la impresión de alta calidad de nuestras máquinas Durst de alta tecnología.',
                    'fr' => 'Parcourez notre large gamme de matériaux et commandez facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst de haute technologie.',
                ],
                'handle' => 'materialen',
                'slugs' => ['nl' => 'materialen', 'en' => 'materials', 'es' => 'materiales', 'fr' => 'materiaux'],
                'source_url' => 'https://www.probo.nl/materialen',
                'below_fold_content' => [
                    'nl' => '<p>Wij bieden een uitgebreid assortiment materialen voor professionals. Van plaatmaterialen tot folies, van doeken tot papier - allemaal van de hoogste kwaliteit.</p><h3>Materiaalcategorieën</h3><ul><li>Plaatmaterialen (Dibond, Forex, Plexiglas)</li><li>Doeken en textiel</li><li>Folies en films</li><li>Papier en vinyl</li></ul>',
                    'en' => '<p>We offer an extensive range of materials for professionals. From rigid panels to films, from fabrics to paper - all of the highest quality.</p><h3>Material categories</h3><ul><li>Rigid panels (Dibond, Forex, Plexiglas)</li><li>Fabrics and textiles</li><li>Films and foils</li><li>Paper and vinyl</li></ul>',
                    'es' => '<p>Ofrecemos una amplia gama de materiales para profesionales. Desde paneles rígidos hasta películas, desde telas hasta papel - todo de la más alta calidad.</p><h3>Categorías de materiales</h3><ul><li>Paneles rígidos (Dibond, Forex, Plexiglas)</li><li>Telas y textiles</li><li>Películas y láminas</li><li>Papel y vinilo</li></ul>',
                    'fr' => '<p>Nous offrons une vaste gamme de matériaux pour les professionnels. Des panneaux rigides aux films, des tissus au papier - le tout de la plus haute qualité.</p><h3>Catégories de matériaux</h3><ul><li>Panneaux rigides (Dibond, Forex, Plexiglas)</li><li>Tissus et textiles</li><li>Films et feuilles</li><li>Papier et vinyle</li></ul>',
                ],
                'children' => [
                    [
                        'name' => ['nl' => 'Plaat', 'en' => 'Rigid Panels'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment plaat materialen en bestel eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst, een scherpe prijs en de snelste levering.',
                            'en' => 'Browse our wide range of rigid panel materials and order easily online. Benefit from high-quality printing from our Durst high-tech machines, competitive pricing and fastest delivery.',
                            'es' => 'Explore nuestra amplia gama de materiales de paneles rígidos y pida fácilmente en línea. Benefíciese de impresión de alta calidad de nuestras máquinas Durst de alta tecnología, precios competitivos y la entrega más rápida.',
                            'fr' => 'Parcourez notre large gamme de matériaux de panneaux rigides et commandez facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst haute technologie, de prix compétitifs et de la livraison la plus rapide.',
                        ],
                        'handle' => 'plaat',
                        'source_url' => 'https://www.probo.nl/materialen/plaat',
                    ],
                    [
                        'name' => ['nl' => 'Doek', 'en' => 'Fabrics'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment doek materialen en bestel eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst, een scherpe prijs en de snelste levering.',
                            'en' => 'Browse our wide range of fabric materials and order easily online. Benefit from high-quality printing from our Durst high-tech machines, competitive pricing and fastest delivery.',
                            'es' => 'Explore nuestra amplia gama de materiales de tela y pida fácilmente en línea. Benefíciese de impresión de alta calidad de nuestras máquinas Durst de alta tecnología, precios competitivos y la entrega más rápida.',
                            'fr' => 'Parcourez notre large gamme de matériaux textiles et commandez facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst haute technologie, de prix compétitifs et de la livraison la plus rapide.',
                        ],
                        'handle' => 'doek',
                        'source_url' => 'https://www.probo.nl/materialen/doek',
                    ],
                    [
                        'name' => ['nl' => 'Wandbekleding materialen', 'en' => 'Wall Covering Materials'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment wandbekleding materialen en bestel eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst, een scherpe prijs en de snelste levering. Exclusief te bestellen voor jou als printprofessional!',
                            'en' => 'Browse our wide range of wall covering materials and order easily online. Benefit from high-quality printing from our Durst high-tech machines, competitive pricing and fastest delivery. Exclusively available for print professionals!',
                            'es' => 'Explore nuestra amplia gama de materiales de revestimiento de pared y pida fácilmente en línea. Benefíciese de impresión de alta calidad de nuestras máquinas Durst de alta tecnología, precios competitivos y la entrega más rápida. ¡Disponible exclusivamente para profesionales de la impresión!',
                            'fr' => 'Parcourez notre large gamme de matériaux de revêtement mural et commandez facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst haute technologie, de prix compétitifs et de la livraison la plus rapide. Disponible exclusivement pour les professionnels de l\'impression!',
                        ],
                        'handle' => 'wandbekleding-materialen',
                        'source_url' => 'https://www.probo.nl/materialen/wandbekleding',
                    ],
                    [
                        'name' => ['nl' => 'Textiel', 'en' => 'Textiles'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment textiel materialen en bestel eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst, een scherpe prijs en de snelste levering.',
                            'en' => 'Browse our wide range of textile materials and order easily online. Benefit from high-quality printing from our Durst high-tech machines, competitive pricing and fastest delivery.',
                            'es' => 'Explore nuestra amplia gama de materiales textiles y pida fácilmente en línea. Benefíciese de impresión de alta calidad de nuestras máquinas Durst de alta tecnología, precios competitivos y la entrega más rápida.',
                            'fr' => 'Parcourez notre large gamme de matériaux textiles et commandez facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst haute technologie, de prix compétitifs et de la livraison la plus rapide.',
                        ],
                        'handle' => 'textiel',
                        'source_url' => 'https://www.probo.nl/materialen/textiel',
                    ],
                    [
                        'name' => ['nl' => 'Banner materialen', 'en' => 'Banner Materials'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment banner materialen en bestel eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst, een scherpe prijs en de snelste levering.',
                            'en' => 'Browse our wide range of banner materials and order easily online. Benefit from high-quality printing from our Durst high-tech machines, competitive pricing and fastest delivery.',
                            'es' => 'Explore nuestra amplia gama de materiales para pancartas y pida fácilmente en línea. Benefíciese de impresión de alta calidad de nuestras máquinas Durst de alta tecnología, precios competitivos y la entrega más rápida.',
                            'fr' => 'Parcourez notre large gamme de matériaux pour bannières et commandez facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst haute technologie, de prix compétitifs et de la livraison la plus rapide.',
                        ],
                        'handle' => 'banner-materialen',
                        'source_url' => 'https://www.probo.nl/materialen/banner',
                    ],
                    [
                        'name' => ['nl' => 'Vloer materialen', 'en' => 'Floor Materials'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment vloer materialen en bestel eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst, een scherpe prijs en de snelste levering.',
                            'en' => 'Browse our wide range of floor materials and order easily online. Benefit from high-quality printing from our Durst high-tech machines, competitive pricing and fastest delivery.',
                            'es' => 'Explore nuestra amplia gama de materiales de suelo y pida fácilmente en línea. Benefíciese de impresión de alta calidad de nuestras máquinas Durst de alta tecnología, precios competitivos y la entrega más rápida.',
                            'fr' => 'Parcourez notre large gamme de matériaux de sol et commandez facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst haute technologie, de prix compétitifs et de la livraison la plus rapide.',
                        ],
                        'handle' => 'vloer-materialen',
                        'source_url' => 'https://www.probo.nl/materialen/vloer',
                    ],
                    [
                        'name' => ['nl' => 'Folie', 'en' => 'Film'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment folie materialen en bestel eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst, een scherpe prijs en de snelste levering.',
                            'en' => 'Browse our wide range of film materials and order easily online. Benefit from high-quality printing from our Durst high-tech machines, competitive pricing and fastest delivery.',
                            'es' => 'Explore nuestra amplia gama de materiales de película y pida fácilmente en línea. Benefíciese de impresión de alta calidad de nuestras máquinas Durst de alta tecnología, precios competitivos y la entrega más rápida.',
                            'fr' => 'Parcourez notre large gamme de matériaux de film et commandez facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst haute technologie, de prix compétitifs et de la livraison la plus rapide.',
                        ],
                        'handle' => 'folie',
                        'source_url' => 'https://www.probo.nl/materialen/folie',
                    ],
                    [
                        'name' => ['nl' => 'Papier', 'en' => 'Paper'],
                        'description' => [
                            'nl' => 'Bekijk ons brede assortiment papier materialen en bestel eenvoudig online. Profiteer van een hoge kwaliteit print van onze high tech machines van Durst, een scherpe prijs en de snelste levering.',
                            'en' => 'Browse our wide range of paper materials and order easily online. Benefit from high-quality printing from our Durst high-tech machines, competitive pricing and fastest delivery.',
                            'es' => 'Explore nuestra amplia gama de materiales de papel y pida fácilmente en línea. Benefíciese de impresión de alta calidad de nuestras máquinas Durst de alta tecnología, precios competitivos y la entrega más rápida.',
                            'fr' => 'Parcourez notre large gamme de matériaux papier et commandez facilement en ligne. Bénéficiez d\'une impression de haute qualité de nos machines Durst haute technologie, de prix compétitifs et de la livraison la plus rapide.',
                        ],
                        'handle' => 'papier',
                        'source_url' => 'https://www.probo.nl/materialen/papier',
                    ],
                ],
            ],

            // =================================================================
            // ACCESSOIRES (Accessories)
            // =================================================================
            [
                'name' => [
                    'nl' => 'Accessoires',
                    'en' => 'Accessories',
                    'es' => 'Accesorios',
                    'fr' => 'Accessoires',
                ],
                'description' => [
                    'nl' => 'Professionele accessoires voor spandoeken, behang, panelen, stickers, vlaggen en meer.',
                    'en' => 'Professional accessories for banners, wallpaper, panels, stickers, flags and more.',
                    'es' => 'Accesorios profesionales para pancartas, papel tapiz, paneles, pegatinas, banderas y más.',
                    'fr' => 'Accessoires professionnels pour bannières, papier peint, panneaux, autocollants, drapeaux et plus.',
                ],
                'handle' => 'accessoires',
                'slugs' => ['nl' => 'accessoires', 'en' => 'accessories', 'es' => 'accesorios', 'fr' => 'accessoires'],
                'source_url' => 'https://www.probo.nl/accessoires',
                'below_fold_content' => [
                    'nl' => '<p>Maak je print compleet met onze professionele accessoires. Van bevestigingsmaterialen tot applicatiegereedschap, van behanglijm tot LED-verlichting.</p><h3>Accessoire categorieën</h3><ul><li>Spandoek en banner accessoires</li><li>Vlag en beachflag accessoires</li><li>Behang en wandbekleding accessoires</li><li>Montage en applicatie gereedschap</li></ul>',
                    'en' => '<p>Complete your print with our professional accessories. From mounting materials to application tools, from wallpaper paste to LED lighting.</p><h3>Accessory categories</h3><ul><li>Banner accessories</li><li>Flag and beach flag accessories</li><li>Wallpaper and wall covering accessories</li><li>Mounting and application tools</li></ul>',
                    'es' => '<p>Complete su impresión con nuestros accesorios profesionales. Desde materiales de montaje hasta herramientas de aplicación, desde pegamento para papel tapiz hasta iluminación LED.</p><h3>Categorías de accesorios</h3><ul><li>Accesorios para pancartas</li><li>Accesorios para banderas y beach flags</li><li>Accesorios para papel tapiz y revestimientos</li><li>Herramientas de montaje y aplicación</li></ul>',
                    'fr' => '<p>Complétez votre impression avec nos accessoires professionnels. Des matériaux de montage aux outils d\'application, de la colle à papier peint à l\'éclairage LED.</p><h3>Catégories d\'accessoires</h3><ul><li>Accessoires pour bannières</li><li>Accessoires pour drapeaux et beach flags</li><li>Accessoires pour papier peint et revêtements</li><li>Outils de montage et d\'application</li></ul>',
                ],
                'children' => [
                    [
                        'name' => ['nl' => 'Spandoek accessoires', 'en' => 'Banner Accessories'],
                        'description' => [
                            'nl' => 'Ontdek ons assortiment spandoeken en bestel eenvoudig online. Kies uit diverse materialen en combineer met handige accessoires zoals spanners, buiskoppelingen en outdoor frames.',
                            'en' => 'Discover our range of banners and order easily online. Choose from various materials and combine with handy accessories such as tensioners, pipe connectors and outdoor frames.',
                            'es' => 'Descubra nuestra gama de pancartas y pida fácilmente en línea. Elija entre varios materiales y combine con accesorios prácticos como tensores, conectores de tubos y marcos para exteriores.',
                            'fr' => 'Découvrez notre gamme de bannières et commandez facilement en ligne. Choisissez parmi divers matériaux et combinez avec des accessoires pratiques tels que tendeurs, raccords de tuyaux et cadres extérieurs.',
                        ],
                        'handle' => 'spandoek-accessoires',
                        'source_url' => 'https://www.probo.nl/accessoires/spandoek',
                    ],
                    [
                        'name' => ['nl' => 'Interieur accessoires', 'en' => 'Interior Accessories'],
                        'description' => [
                            'nl' => 'Ontdek ons assortiment interieur accessoires en bestel eenvoudig online. Kies uit praktische ophangsystemen, stijlvolle baklijsten en stevige wandhaken.',
                            'en' => 'Discover our range of interior accessories and order easily online. Choose from practical hanging systems, stylish frames and sturdy wall hooks.',
                            'es' => 'Descubra nuestra gama de accesorios de interior y pida fácilmente en línea. Elija entre sistemas de colgado prácticos, marcos elegantes y ganchos de pared resistentes.',
                            'fr' => 'Découvrez notre gamme d\'accessoires d\'intérieur et commandez facilement en ligne. Choisissez parmi des systèmes d\'accrochage pratiques, des cadres élégants et des crochets muraux robustes.',
                        ],
                        'handle' => 'interieur-accessoires',
                        'source_url' => 'https://www.probo.nl/accessoires/interieur',
                    ],
                    [
                        'name' => ['nl' => 'Behang accessoires', 'en' => 'Wallpaper Accessories'],
                        'description' => [
                            'nl' => 'Ontdek ons assortiment aan handige behang accessoires en bestel eenvoudig online. Kies uit een ruime keuze, zoals behanglijm, behangsnijders en lijmkwasten.',
                            'en' => 'Discover our range of handy wallpaper accessories and order easily online. Choose from a wide selection, such as wallpaper paste, wallpaper cutters and paste brushes.',
                            'es' => 'Descubra nuestra gama de prácticos accesorios para papel tapiz y pida fácilmente en línea. Elija entre una amplia selección, como pegamento para papel tapiz, cortadores de papel tapiz y brochas de pegamento.',
                            'fr' => 'Découvrez notre gamme d\'accessoires pratiques pour papier peint et commandez facilement en ligne. Choisissez parmi une large sélection, comme la colle à papier peint, les coupe-papier peint et les brosses à colle.',
                        ],
                        'handle' => 'behang-accessoires',
                        'source_url' => 'https://www.probo.nl/accessoires/behangen',
                    ],
                    [
                        'name' => ['nl' => 'Drukwerk accessoires', 'en' => 'Printing Accessories'],
                        'description' => [
                            'nl' => 'Ontdek ons assortiment drukwerk accessoires en bestel eenvoudig online. Kies uit praktische posterklemmen, brochurehouders, kliklijsten en stoepborden.',
                            'en' => 'Discover our range of printing accessories and order easily online. Choose from practical poster clamps, brochure holders, click frames and sidewalk signs.',
                            'es' => 'Descubra nuestra gama de accesorios de impresión y pida fácilmente en línea. Elija entre prácticas pinzas para pósters, portafolletos, marcos clic y letreros de acera.',
                            'fr' => 'Découvrez notre gamme d\'accessoires d\'impression et commandez facilement en ligne. Choisissez parmi des pinces à posters pratiques, des porte-brochures, des cadres clic et des chevalets de trottoir.',
                        ],
                        'handle' => 'drukwerk-accessoires',
                        'source_url' => 'https://www.probo.nl/accessoires/drukwerk',
                    ],
                    [
                        'name' => ['nl' => 'Paneel accessoires', 'en' => 'Panel Accessories'],
                        'description' => [
                            'nl' => 'Ontdek ons assortiment aan handige paneel accessoires en bestel eenvoudig online.',
                            'en' => 'Discover our range of handy panel accessories and order easily online.',
                            'es' => 'Descubra nuestra gama de prácticos accesorios para paneles y pida fácilmente en línea.',
                            'fr' => 'Découvrez notre gamme d\'accessoires pratiques pour panneaux et commandez facilement en ligne.',
                        ],
                        'handle' => 'paneel-accessoires',
                        'source_url' => 'https://www.probo.nl/accessoires/paneel',
                    ],
                    [
                        'name' => ['nl' => 'Textielframe accessoires', 'en' => 'Textile Frame Accessories'],
                        'description' => [
                            'nl' => 'Ontdek ons assortiment textielframe accessoires en bestel eenvoudig online. Kies uit stevige frames, praktische frame connectors en betrouwbare druknagels.',
                            'en' => 'Discover our range of textile frame accessories and order easily online. Choose from sturdy frames, practical frame connectors and reliable push pins.',
                            'es' => 'Descubra nuestra gama de accesorios para marcos textiles y pida fácilmente en línea. Elija entre marcos resistentes, conectores de marco prácticos y chinchetas fiables.',
                            'fr' => 'Découvrez notre gamme d\'accessoires pour cadres textiles et commandez facilement en ligne. Choisissez parmi des cadres robustes, des connecteurs de cadre pratiques et des punaises fiables.',
                        ],
                        'handle' => 'textielframe-accessoires',
                        'source_url' => 'https://www.probo.nl/accessoires/textielframe',
                    ],
                    [
                        'name' => ['nl' => 'Sticker/folie accessoires', 'en' => 'Sticker/Film Accessories'],
                        'description' => [
                            'nl' => 'Ontdek ons assortiment accessoires voor stickers en folie en bestel eenvoudig online. Kies uit handige applicatietools zoals een heteluchtpistool, rakels en meer.',
                            'en' => 'Discover our range of accessories for stickers and film and order easily online. Choose from handy application tools such as a heat gun, squeegees and more.',
                            'es' => 'Descubra nuestra gama de accesorios para pegatinas y película y pida fácilmente en línea. Elija entre herramientas de aplicación prácticas como pistola de calor, espátulas y más.',
                            'fr' => 'Découvrez notre gamme d\'accessoires pour autocollants et film et commandez facilement en ligne. Choisissez parmi des outils d\'application pratiques tels qu\'un pistolet thermique, des raclettes et plus encore.',
                        ],
                        'handle' => 'sticker-folie-accessoires',
                        'source_url' => 'https://www.probo.nl/accessoires/sticker-folie',
                    ],
                    [
                        'name' => ['nl' => 'Vlag accessoires', 'en' => 'Flag Accessories'],
                        'description' => [
                            'nl' => 'Ontdek ons assortiment aan accessoires voor vlaggen en bestel eenvoudig online. Kies uit een ruime keuze, zoals gevelstokken, gevelhouders en banierhoudersets.',
                            'en' => 'Discover our range of flag accessories and order easily online. Choose from a wide selection, such as facade poles, facade holders and banner holder sets.',
                            'es' => 'Descubra nuestra gama de accesorios para banderas y pida fácilmente en línea. Elija entre una amplia selección, como postes de fachada, soportes de fachada y juegos de soportes para pancartas.',
                            'fr' => 'Découvrez notre gamme d\'accessoires pour drapeaux et commandez facilement en ligne. Choisissez parmi une large sélection, comme des mâts de façade, des supports de façade et des ensembles de supports pour bannières.',
                        ],
                        'handle' => 'vlag-accessoires',
                        'source_url' => 'https://www.probo.nl/accessoires/vlag',
                    ],
                    [
                        'name' => ['nl' => 'Beachflag accessoires', 'en' => 'Beach Flag Accessories'],
                        'description' => [
                            'nl' => 'Ontdek ons assortiment aan handige beachflag accessoires en bestel eenvoudig online.',
                            'en' => 'Discover our range of handy beach flag accessories and order easily online.',
                            'es' => 'Descubra nuestra gama de prácticos accesorios para beach flags y pida fácilmente en línea.',
                            'fr' => 'Découvrez notre gamme d\'accessoires pratiques pour beach flags et commandez facilement en ligne.',
                        ],
                        'handle' => 'beachflag-accessoires',
                        'source_url' => 'https://www.probo.nl/accessoires/beachflag',
                    ],
                    [
                        'name' => ['nl' => 'Beurswand accessoires', 'en' => 'Exhibition Wall Accessories'],
                        'description' => [
                            'nl' => 'Ontdek ons assortiment beurwand accessoires en bestel eenvoudig online. Kies uit krachtige LED-verlichting, handige inhaker onderdelen of een topblad.',
                            'en' => 'Discover our range of exhibition wall accessories and order easily online. Choose from powerful LED lighting, handy hook-in parts or a top board.',
                            'es' => 'Descubra nuestra gama de accesorios para paredes de exposición y pida fácilmente en línea. Elija entre iluminación LED potente, prácticas piezas de enganche o un tablero superior.',
                            'fr' => 'Découvrez notre gamme d\'accessoires pour murs d\'exposition et commandez facilement en ligne. Choisissez parmi un éclairage LED puissant, des pièces d\'accrochage pratiques ou un plateau supérieur.',
                        ],
                        'handle' => 'beurswand-accessoires',
                        'source_url' => 'https://www.probo.nl/accessoires/beurswand',
                    ],
                    [
                        'name' => ['nl' => 'Algemene accessoires', 'en' => 'General Accessories'],
                        'description' => [
                            'nl' => 'Ontdek ons assortiment accessoires en bestel eenvoudig online. Praktische tools zoals een toolbox, een stanleymes of een skytube blower.',
                            'en' => 'Discover our range of accessories and order easily online. Practical tools such as a toolbox, a Stanley knife or a skytube blower.',
                            'es' => 'Descubra nuestra gama de accesorios y pida fácilmente en línea. Herramientas prácticas como una caja de herramientas, un cúter Stanley o un inflador skytube.',
                            'fr' => 'Découvrez notre gamme d\'accessoires et commandez facilement en ligne. Outils pratiques tels qu\'une boîte à outils, un cutter Stanley ou un gonfleur skytube.',
                        ],
                        'handle' => 'algemene-accessoires',
                        'source_url' => 'https://www.probo.nl/accessoires/algemeen',
                    ],
                ],
            ],

            // =================================================================
            // DUURZAMER (Sustainable)
            // =================================================================
            [
                'name' => [
                    'nl' => 'Duurzamer',
                    'en' => 'Sustainable',
                    'es' => 'Sostenible',
                    'fr' => 'Durable',
                ],
                'description' => [
                    'nl' => 'Duurzamere producten en materialen voor een groenere toekomst.',
                    'en' => 'More sustainable products and materials for a greener future.',
                    'es' => 'Productos y materiales más sostenibles para un futuro más verde.',
                    'fr' => 'Produits et matériaux plus durables pour un avenir plus vert.',
                ],
                'handle' => 'duurzamer',
                'slugs' => ['nl' => 'duurzamer', 'en' => 'sustainable', 'es' => 'sostenible', 'fr' => 'durable'],
                'source_url' => 'https://www.probo.nl/duurzamer',
                'below_fold_content' => [
                    'nl' => '<p>Wij zetten ons in voor een duurzamere toekomst. Ontdek ons groeiende assortiment milieuvriendelijke alternatieven, van gerecyclede materialen tot PVC-vrije folies.</p><h3>Onze duurzame keuzes</h3><ul><li>Gerecyclede plaatmaterialen</li><li>PVC-vrije folies en films</li><li>Biobased materialen</li><li>Milieuvriendelijke textiel</li></ul>',
                    'en' => '<p>We are committed to a more sustainable future. Discover our growing range of eco-friendly alternatives, from recycled materials to PVC-free films.</p><h3>Our sustainable choices</h3><ul><li>Recycled rigid panels</li><li>PVC-free films and foils</li><li>Biobased materials</li><li>Eco-friendly textiles</li></ul>',
                    'es' => '<p>Estamos comprometidos con un futuro más sostenible. Descubra nuestra creciente gama de alternativas ecológicas, desde materiales reciclados hasta películas sin PVC.</p><h3>Nuestras opciones sostenibles</h3><ul><li>Paneles rígidos reciclados</li><li>Películas y láminas sin PVC</li><li>Materiales de base biológica</li><li>Textiles ecológicos</li></ul>',
                    'fr' => '<p>Nous nous engageons pour un avenir plus durable. Découvrez notre gamme croissante d\'alternatives écologiques, des matériaux recyclés aux films sans PVC.</p><h3>Nos choix durables</h3><ul><li>Panneaux rigides recyclés</li><li>Films et feuilles sans PVC</li><li>Matériaux biosourcés</li><li>Textiles écologiques</li></ul>',
                ],
                'children' => [
                    [
                        'name' => ['nl' => 'Duurzamere platen', 'en' => 'Sustainable Panels'],
                        'handle' => 'duurzamere-platen',
                        'source_url' => 'https://www.probo.nl/duurzamer/duurzamere-platen',
                    ],
                    [
                        'name' => ['nl' => 'Duurzamere doeken', 'en' => 'Sustainable Fabrics'],
                        'handle' => 'duurzamere-doeken',
                        'source_url' => 'https://www.probo.nl/duurzamer/duurzamere-doeken',
                    ],
                    [
                        'name' => ['nl' => 'Duurzamer behang', 'en' => 'Sustainable Wallpaper'],
                        'handle' => 'duurzamer-behang',
                        'source_url' => 'https://www.probo.nl/duurzamer/duurzamer-behang',
                    ],
                    [
                        'name' => ['nl' => 'Duurzamere folies', 'en' => 'Sustainable Films'],
                        'handle' => 'duurzamere-folies',
                        'source_url' => 'https://www.probo.nl/duurzamer/duurzamere-folies',
                    ],
                    [
                        'name' => ['nl' => 'Duurzamer textiel', 'en' => 'Sustainable Textiles'],
                        'handle' => 'duurzamer-textiel',
                        'source_url' => 'https://www.probo.nl/duurzamer/duurzamer-textiel',
                    ],
                ],
            ],
        ];
    }

    /**
     * Create a collection with its children.
     */
    protected function createCollection(array $data, ?Collection $parent = null): Collection
    {
        // Build attribute data
        $attributeData = [
            'name' => new TranslatedText($data['name']),
        ];

        if (isset($data['description'])) {
            $attributeData['description'] = new TranslatedText($data['description']);
        }

        // Add Pinterest URL if provided
        if (isset($data['pinterest_url'])) {
            $attributeData['pinterest_url'] = new Text($data['pinterest_url']);
        }

        // Add below_fold_content if provided
        if (isset($data['below_fold_content'])) {
            $attributeData['below_fold_content'] = new TranslatedText($data['below_fold_content']);
        }

        // Create the collection
        $collection = Collection::create([
            'collection_group_id' => $this->collectionGroup->id,
            'attribute_data' => $attributeData,
            'sort' => 'custom',
        ]);

        // Set parent if provided
        if ($parent) {
            $collection->appendToNode($parent)->save();
        }

        // Create URL for the collection
        if ($this->channel) {
            $languages = Language::all();
            foreach ($languages as $language) {
                // Priority: 1. translated slug from 'slugs' array, 2. 'handle', 3. generate from name
                $slug = $data['slugs'][$language->code]
                    ?? $data['handle']
                    ?? Str::slug($data['name'][$language->code] ?? $data['name']['nl']);

                $collection->urls()->create([
                    'language_id' => $language->id,
                    'slug' => $slug,
                    'default' => $language->default,
                ]);
            }
        }

        // Sync channels
        if ($this->channel) {
            $collection->channels()->syncWithPivotValues(
                [$this->channel->id],
                [
                    'starts_at' => now(),
                    'ends_at' => null,
                    'enabled' => true,
                ]
            );
        }

        // Sync customer groups
        if ($this->customerGroups && $this->customerGroups->count() > 0) {
            $collection->customerGroups()->syncWithPivotValues(
                $this->customerGroups->pluck('id'),
                [
                    'visible' => true,
                    'enabled' => true,
                    'starts_at' => now(),
                    'ends_at' => null,
                ]
            );
        }

        $this->command->info("  Created: {$data['name']['nl']}");

        // Create children recursively
        if (isset($data['children']) && is_array($data['children'])) {
            foreach ($data['children'] as $childData) {
                $this->createCollection($childData, $collection);
            }
        }

        return $collection;
    }
}
