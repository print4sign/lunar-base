<?php

namespace Lunar\Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\FieldTypes\ListField;
use Lunar\FieldTypes\Number;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\Toggle;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Attribute;
use Lunar\Models\AttributeGroup;
use Lunar\Models\ProductType;

class ProboProductTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Create or get the ProductType
        $productType = ProductType::firstOrCreate(
            ['name' => 'Probo Supplier Product'],
            ['name' => 'Probo Supplier Product']
        );

        // Create Attribute Groups
        $basicInfoGroup = AttributeGroup::firstOrCreate(
            ['attributable_type' => 'product', 'handle' => 'basic-info'],
            ['name' => ['en' => 'Basic Information', 'nl' => 'Basisinformatie'], 'position' => 1]
        );

        $specsGroup = AttributeGroup::firstOrCreate(
            ['attributable_type' => 'product', 'handle' => 'specifications'],
            ['name' => ['en' => 'Technical Specifications', 'nl' => 'Technische Specificaties'], 'position' => 2]
        );

        $featuresGroup = AttributeGroup::firstOrCreate(
            ['attributable_type' => 'product', 'handle' => 'features'],
            ['name' => ['en' => 'Features', 'nl' => 'Kenmerken'], 'position' => 3]
        );

        // Define attributes
        $attributes = [
            // Basic Info Group
            [
                'group' => $basicInfoGroup,
                'handle' => 'name',
                'name' => ['en' => 'Name', 'nl' => 'Naam'],
                'type' => TranslatedText::class,
                'required' => true,
                'position' => 1,
            ],
            [
                'group' => $basicInfoGroup,
                'handle' => 'short-description',
                'name' => ['en' => 'Short Description', 'nl' => 'Korte Omschrijving'],
                'type' => TranslatedText::class,
                'required' => false,
                'position' => 2,
            ],
            [
                'group' => $basicInfoGroup,
                'handle' => 'description',
                'name' => ['en' => 'Description', 'nl' => 'Omschrijving'],
                'type' => TranslatedText::class,
                'required' => false,
                'position' => 3,
                'configuration' => ['richtext' => true],
            ],

            // Specifications Group
            [
                'group' => $specsGroup,
                'handle' => 'max-print-width-cm',
                'name' => ['en' => 'Max Print Width (cm)', 'nl' => 'Max Printbreedte (cm)'],
                'type' => Number::class,
                'position' => 1,
                'filterable' => true,
            ],
            [
                'group' => $specsGroup,
                'handle' => 'max-print-height-cm',
                'name' => ['en' => 'Max Print Height (cm)', 'nl' => 'Max Printhoogte (cm)'],
                'type' => Number::class,
                'position' => 2,
                'filterable' => true,
            ],
            [
                'group' => $specsGroup,
                'handle' => 'weight-g-m2',
                'name' => ['en' => 'Weight (g/m²)', 'nl' => 'Gewicht (g/m²)'],
                'type' => Number::class,
                'position' => 3,
            ],
            [
                'group' => $specsGroup,
                'handle' => 'base-material',
                'name' => ['en' => 'Base Material', 'nl' => 'Basismateriaal'],
                'type' => Text::class,
                'position' => 4,
                'filterable' => true,
            ],
            [
                'group' => $specsGroup,
                'handle' => 'print-technology',
                'name' => ['en' => 'Print Technology', 'nl' => 'Printtechnologie'],
                'type' => Text::class,
                'position' => 5,
                'filterable' => true,
            ],
            [
                'group' => $specsGroup,
                'handle' => 'lead-time-days',
                'name' => ['en' => 'Lead Time (days)', 'nl' => 'Levertijd (dagen)'],
                'type' => Number::class,
                'position' => 6,
            ],
            [
                'group' => $specsGroup,
                'handle' => 'indoor-outdoor-use',
                'name' => ['en' => 'Indoor/Outdoor Use', 'nl' => 'Binnen/Buiten Gebruik'],
                'type' => Text::class,
                'position' => 7,
                'filterable' => true,
            ],
            [
                'group' => $specsGroup,
                'handle' => 'lifespan',
                'name' => ['en' => 'Lifespan', 'nl' => 'Levensduur'],
                'type' => Text::class,
                'position' => 8,
                'filterable' => true,
            ],

            // Features Group (toggles)
            [
                'group' => $featuresGroup,
                'handle' => 'pvc-free',
                'name' => ['en' => 'PVC-Free', 'nl' => 'PVC-Vrij'],
                'type' => Toggle::class,
                'position' => 1,
                'filterable' => true,
            ],
            [
                'group' => $featuresGroup,
                'handle' => 'fire-certificate',
                'name' => ['en' => 'Fire Certificate', 'nl' => 'Brandcertificaat'],
                'type' => Toggle::class,
                'position' => 2,
                'filterable' => true,
            ],
            [
                'group' => $featuresGroup,
                'handle' => 'wind-permeable',
                'name' => ['en' => 'Wind Permeable', 'nl' => 'Winddoorlatend'],
                'type' => Toggle::class,
                'position' => 3,
                'filterable' => true,
            ],
            [
                'group' => $featuresGroup,
                'handle' => 'washable',
                'name' => ['en' => 'Washable', 'nl' => 'Wasbaar'],
                'type' => Toggle::class,
                'position' => 4,
                'filterable' => true,
            ],
            [
                'group' => $featuresGroup,
                'handle' => 'recyclable',
                'name' => ['en' => 'Recyclable', 'nl' => 'Recyclebaar'],
                'type' => Toggle::class,
                'position' => 5,
                'filterable' => true,
            ],
            [
                'group' => $featuresGroup,
                'handle' => 'advantages',
                'name' => ['en' => 'Advantages', 'nl' => 'Voordelen'],
                'type' => ListField::class,
                'position' => 6,
            ],
            [
                'group' => $featuresGroup,
                'handle' => 'considerations',
                'name' => ['en' => 'Considerations', 'nl' => 'Aandachtspunten'],
                'type' => ListField::class,
                'position' => 7,
            ],
        ];

        // Create attributes and attach to product type
        foreach ($attributes as $attrData) {
            $attribute = Attribute::firstOrCreate(
                [
                    'attribute_type' => 'product',
                    'handle' => $attrData['handle'],
                ],
                [
                    'attribute_group_id' => $attrData['group']->id,
                    'name' => $attrData['name'],
                    'type' => $attrData['type'],
                    'required' => $attrData['required'] ?? false,
                    'position' => $attrData['position'],
                    'configuration' => $attrData['configuration'] ?? [],
                    'searchable' => true,
                    'filterable' => $attrData['filterable'] ?? false,
                    'section' => 'main',
                    'system' => false,
                ]
            );

            // Attach to product type if not already
            if (! $productType->mappedAttributes()->where('lunar_attributes.id', $attribute->id)->exists()) {
                $productType->mappedAttributes()->attach($attribute);
            }
        }

        $this->command->info('Probo Product Type seeded with '.count($attributes).' attributes.');
    }
}
