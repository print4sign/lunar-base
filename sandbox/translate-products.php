<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Lunar\Models\Product;
use Lunar\Scraper\Services\ClaudeClient;
use Lunar\FieldTypes\Text;

// Get products that need translation
$products = Product::all();
echo "Total products to process: " . $products->count() . PHP_EOL . PHP_EOL;

$claudeClient = new ClaudeClient();

$translated = 0;
$skipped = 0;
$errors = 0;

foreach ($products as $product) {
    $nlName = $product->translateAttribute('name', 'nl');
    $nlDesc = $product->translateAttribute('description', 'nl');

    if (empty($nlName)) {
        echo "Skipping product {$product->id} - no Dutch name" . PHP_EOL;
        $skipped++;
        continue;
    }

    $needsTranslation = false;
    $enName = $product->translateAttribute('name', 'en');
    $deName = $product->translateAttribute('name', 'de');
    $esName = $product->translateAttribute('name', 'es');

    $enDesc = $product->translateAttribute('description', 'en');
    $deDesc = $product->translateAttribute('description', 'de');
    $esDesc = $product->translateAttribute('description', 'es');

    if (empty($enName) || $enName === $nlName) {
        $needsTranslation = true;
    }
    if (empty($deName) || $deName === $nlName || $deName === $enName) {
        $needsTranslation = true;
    }
    if (empty($esName) || $esName === $nlName) {
        $needsTranslation = true;
    }

    if (!empty($nlDesc)) {
        if (empty($enDesc) || $enDesc === $nlDesc) {
            $needsTranslation = true;
        }
        if (empty($deDesc) || $deDesc === $nlDesc) {
            $needsTranslation = true;
        }
        if (empty($esDesc) || $esDesc === $nlDesc) {
            $needsTranslation = true;
        }
    }

    if (!$needsTranslation) {
        $skipped++;
        continue;
    }

    echo "Translating product {$product->id}: {$nlName}" . PHP_EOL;

    try {
        $attributeData = $product->attribute_data;

        // Translate to English
        if (empty($enName) || $enName === $nlName) {
            $toTranslate = ['name' => $nlName];
            if (!empty($nlDesc) && (empty($enDesc) || $enDesc === $nlDesc)) {
                $toTranslate['description'] = $nlDesc;
            }

            echo "  → Translating to English..." . PHP_EOL;
            $enTranslations = $claudeClient->translateBatch($toTranslate, 'nl', 'en');

            if (!empty($enTranslations['name'])) {
                // Update name translation
                $nameField = $attributeData->get('name');
                $nameTranslations = $nameField->getValue();
                $nameTranslations->put('en', new Text($enTranslations['name']));

                // Update description translation if provided
                if (isset($enTranslations['description'])) {
                    $descField = $attributeData->get('description');
                    if ($descField) {
                        $descTranslations = $descField->getValue();
                        $descTranslations->put('en', new Text($enTranslations['description']));
                    }
                }

                echo "    ✓ EN: {$enTranslations['name']}" . PHP_EOL;
            }

            // Rate limiting
            sleep(1);
        }

        // Translate to German
        if (empty($deName) || $deName === $nlName || $deName === $enName) {
            $toTranslate = ['name' => $nlName];
            if (!empty($nlDesc) && (empty($deDesc) || $deDesc === $nlDesc)) {
                $toTranslate['description'] = $nlDesc;
            }

            echo "  → Translating to German..." . PHP_EOL;
            $deTranslations = $claudeClient->translateBatch($toTranslate, 'nl', 'de');

            if (!empty($deTranslations['name'])) {
                // Update name translation
                $nameField = $attributeData->get('name');
                $nameTranslations = $nameField->getValue();
                $nameTranslations->put('de', new Text($deTranslations['name']));

                // Update description translation if provided
                if (isset($deTranslations['description'])) {
                    $descField = $attributeData->get('description');
                    if ($descField) {
                        $descTranslations = $descField->getValue();
                        $descTranslations->put('de', new Text($deTranslations['description']));
                    }
                }

                echo "    ✓ DE: {$deTranslations['name']}" . PHP_EOL;
            }

            // Rate limiting
            sleep(1);
        }

        // Translate to Spanish
        if (empty($esName) || $esName === $nlName) {
            $toTranslate = ['name' => $nlName];
            if (!empty($nlDesc) && (empty($esDesc) || $esDesc === $nlDesc)) {
                $toTranslate['description'] = $nlDesc;
            }

            echo "  → Translating to Spanish..." . PHP_EOL;
            $esTranslations = $claudeClient->translateBatch($toTranslate, 'nl', 'es');

            if (!empty($esTranslations['name'])) {
                // Update name translation
                $nameField = $attributeData->get('name');
                $nameTranslations = $nameField->getValue();
                $nameTranslations->put('es', new Text($esTranslations['name']));

                // Update description translation if provided
                if (isset($esTranslations['description'])) {
                    $descField = $attributeData->get('description');
                    if ($descField) {
                        $descTranslations = $descField->getValue();
                        $descTranslations->put('es', new Text($esTranslations['description']));
                    }
                }

                echo "    ✓ ES: {$esTranslations['name']}" . PHP_EOL;
            }

            // Rate limiting
            sleep(1);
        }

        // Save the product with updated translations
        $product->save();
        $translated++;
    } catch (\Exception $e) {
        echo "  ✗ Error: {$e->getMessage()}" . PHP_EOL;
        $errors++;
    }
}

echo PHP_EOL;
echo "Summary:" . PHP_EOL;
echo "  Translated: {$translated}" . PHP_EOL;
echo "  Skipped: {$skipped}" . PHP_EOL;
echo "  Errors: {$errors}" . PHP_EOL;
