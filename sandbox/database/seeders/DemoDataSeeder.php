<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Attribute;
use Lunar\Models\AttributeGroup;
use Lunar\Models\Channel;
use Lunar\Models\Collection;
use Lunar\Models\CollectionGroup;
use Lunar\Models\Country;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Language;
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use Lunar\Models\Supplier;
use Lunar\Admin\Models\Staff;
use Lunar\Models\TaxClass;
use Lunar\Models\TaxRate;
use Lunar\Models\TaxRateAmount;
use Lunar\Models\TaxZone;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Setting up Lunar demo data...');

        $this->seedLanguages();
        $this->seedCurrencies();
        $this->seedCountries();
        $this->seedTaxClasses();
        $this->seedTaxZones();
        $this->seedChannels();
        $this->seedCustomerGroups();
        $this->seedCollectionGroups();
        $this->seedAttributes();
        $this->seedProductTypes();
        $this->seedSuppliers();
        $this->seedStaff();

        $this->command->info('Demo data seeded successfully!');
        $this->command->newLine();
        $this->command->info('Admin login: admin@example.com / password');
    }

    protected function seedLanguages(): void
    {
        $this->command->info('Creating languages...');

        Language::create([
            'code' => 'en',
            'name' => 'English',
            'default' => true,
        ]);

        Language::create([
            'code' => 'nl',
            'name' => 'Dutch',
            'default' => false,
        ]);
    }

    protected function seedCurrencies(): void
    {
        $this->command->info('Creating currencies...');

        Currency::create([
            'code' => 'EUR',
            'name' => 'Euro',
            'exchange_rate' => 1,
            'decimal_places' => 2,
            'default' => true,
            'enabled' => true,
        ]);

        Currency::create([
            'code' => 'GBP',
            'name' => 'British Pound',
            'exchange_rate' => 0.85,
            'decimal_places' => 2,
            'default' => false,
            'enabled' => true,
        ]);
    }

    protected function seedCountries(): void
    {
        $this->command->info('Importing countries (this may take a moment)...');

        // Use Lunar's built-in country import command
        Artisan::call('lunar:import:address-data');

        $this->command->info('Countries imported.');
    }

    protected function seedTaxClasses(): void
    {
        $this->command->info('Creating tax classes...');

        TaxClass::create([
            'name' => 'Default',
            'default' => true,
        ]);

        TaxClass::create([
            'name' => 'Reduced Rate',
            'default' => false,
        ]);

        TaxClass::create([
            'name' => 'Zero Rate',
            'default' => false,
        ]);
    }

    protected function seedTaxZones(): void
    {
        $this->command->info('Creating tax zones and rates...');

        // Create EU tax zone
        $zone = TaxZone::create([
            'name' => 'EU',
            'zone_type' => 'country',
            'price_display' => 'tax_inclusive',
            'active' => true,
            'default' => true,
        ]);

        // Link EU countries to the zone
        $euCountries = Country::whereIn('iso2', ['NL', 'BE', 'DE', 'FR', 'AT', 'IT', 'ES', 'PT', 'LU', 'IE'])->get();
        $zone->countries()->createMany(
            $euCountries->map(fn ($country) => ['country_id' => $country->id])->toArray()
        );

        // Create tax rate for the zone
        $taxRate = TaxRate::create([
            'tax_zone_id' => $zone->id,
            'name' => 'VAT',
            'priority' => 1,
        ]);

        // Get all tax classes and create rate amounts for each
        $taxClasses = TaxClass::all();

        foreach ($taxClasses as $taxClass) {
            $percentage = match ($taxClass->name) {
                'Default' => 21.00,
                'Reduced Rate' => 9.00,
                'Zero Rate' => 0.00,
                default => 21.00,
            };

            TaxRateAmount::create([
                'tax_rate_id' => $taxRate->id,
                'tax_class_id' => $taxClass->id,
                'percentage' => $percentage,
            ]);
        }
    }

    protected function seedChannels(): void
    {
        $this->command->info('Creating channels...');

        Channel::create([
            'name' => 'Webshop',
            'handle' => 'webshop',
            'default' => true,
            'url' => 'https://example.com',
        ]);
    }

    protected function seedCustomerGroups(): void
    {
        $this->command->info('Creating customer groups...');

        CustomerGroup::create([
            'name' => 'Retail',
            'handle' => 'retail',
            'default' => true,
        ]);

        CustomerGroup::create([
            'name' => 'Trade',
            'handle' => 'trade',
            'default' => false,
        ]);
    }

    protected function seedCollectionGroups(): void
    {
        $this->command->info('Creating collection groups...');

        CollectionGroup::create([
            'name' => 'Main',
            'handle' => 'main',
        ]);
    }

    protected function seedAttributes(): void
    {
        $this->command->info('Creating attributes...');

        // Product attribute group
        $productGroup = AttributeGroup::create([
            'attributable_type' => Product::morphName(),
            'name' => collect(['en' => 'Details', 'nl' => 'Details']),
            'handle' => 'details',
            'position' => 1,
        ]);

        // Collection attribute group
        $collectionGroup = AttributeGroup::create([
            'attributable_type' => Collection::morphName(),
            'name' => collect(['en' => 'Details', 'nl' => 'Details']),
            'handle' => 'collection_details',
            'position' => 1,
        ]);

        // Product name attribute
        Attribute::create([
            'attribute_type' => Product::morphName(),
            'attribute_group_id' => $productGroup->id,
            'position' => 1,
            'name' => ['en' => 'Name', 'nl' => 'Naam'],
            'handle' => 'name',
            'section' => 'main',
            'type' => TranslatedText::class,
            'required' => true,
            'default_value' => null,
            'configuration' => ['richtext' => false],
            'system' => true,
        ]);

        // Product description attribute
        Attribute::create([
            'attribute_type' => Product::morphName(),
            'attribute_group_id' => $productGroup->id,
            'position' => 2,
            'name' => ['en' => 'Description', 'nl' => 'Beschrijving'],
            'handle' => 'description',
            'section' => 'main',
            'type' => TranslatedText::class,
            'required' => false,
            'default_value' => null,
            'configuration' => ['richtext' => true],
            'system' => false,
        ]);

        // Collection name attribute
        Attribute::create([
            'attribute_type' => Collection::morphName(),
            'attribute_group_id' => $collectionGroup->id,
            'position' => 1,
            'name' => ['en' => 'Name', 'nl' => 'Naam'],
            'handle' => 'name',
            'section' => 'main',
            'type' => TranslatedText::class,
            'required' => true,
            'default_value' => null,
            'configuration' => ['richtext' => false],
            'system' => true,
        ]);

        // Collection description attribute
        Attribute::create([
            'attribute_type' => Collection::morphName(),
            'attribute_group_id' => $collectionGroup->id,
            'position' => 2,
            'name' => ['en' => 'Description', 'nl' => 'Beschrijving'],
            'handle' => 'description',
            'section' => 'main',
            'type' => TranslatedText::class,
            'required' => false,
            'default_value' => null,
            'configuration' => ['richtext' => true],
            'system' => false,
        ]);
    }

    protected function seedProductTypes(): void
    {
        $this->command->info('Creating product types...');

        // Get product attributes created in seedAttributes()
        $productAttributes = Attribute::whereAttributeType(Product::morphName())->get();

        // Create Supplier Product type
        $supplierProductType = ProductType::create([
            'name' => 'Supplier Product',
        ]);
        $supplierProductType->mappedAttributes()->attach($productAttributes->pluck('id'));

        // Create Standard Product type
        $standardProductType = ProductType::create([
            'name' => 'Standard Product',
        ]);
        $standardProductType->mappedAttributes()->attach($productAttributes->pluck('id'));

        // Create Configurable Product type (for dimension-based products)
        $configurableProductType = ProductType::create([
            'name' => 'Configurable Product',
        ]);
        $configurableProductType->mappedAttributes()->attach($productAttributes->pluck('id'));
    }

    protected function seedSuppliers(): void
    {
        $this->command->info('Creating suppliers...');

        // Probo supplier
        Supplier::create([
            'name' => 'Probo',
            'handle' => 'probo',
            'driver' => 'probo',
            'enabled' => true,
            'priority' => 100,
            'credentials' => [
                'api_key' => env('PROBO_API_KEY', 'MGJjMDQyODkwNDUxNDJhYmFmYjgxOWQzYTliMTg2ZDM6RjZFZDkxOGM2QTE4NGU2MjlDN0VhMDEwMjdlMDg3Y0Q='),
            ],
            'capabilities' => [
                'catalog_sync',
                'pricing',
                'ordering',
                'file_upload',
                'tracking',
                'configurator',
            ],
        ]);

        // Example offline supplier
        Supplier::create([
            'name' => 'Local Warehouse',
            'handle' => 'local-warehouse',
            'driver' => 'offline',
            'enabled' => true,
            'priority' => 50,
            'credentials' => [],
            'capabilities' => [],
        ]);
    }

    protected function seedStaff(): void
    {
        $this->command->info('Creating admin staff...');

        Staff::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'admin' => true,
        ]);
    }
}
