<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$products = \Lunar\Models\Product::all();
echo "Total products: " . $products->count() . PHP_EOL . PHP_EOL;

$missingTranslations = [];
foreach ($products as $product) {
    $issues = [];

    // Check name translations
    $nlName = $product->translateAttribute('name', 'nl');
    $enName = $product->translateAttribute('name', 'en');
    $deName = $product->translateAttribute('name', 'de');
    $esName = $product->translateAttribute('name', 'es');

    if (empty($enName) || $enName === $nlName) $issues[] = 'EN name missing/same';
    if (empty($deName) || $deName === $nlName || $deName === $enName) $issues[] = 'DE name missing/same';
    if (empty($esName) || $esName === $nlName) $issues[] = 'ES name missing/same';

    // Check description
    $nlDesc = $product->translateAttribute('description', 'nl');
    $enDesc = $product->translateAttribute('description', 'en');
    $deDesc = $product->translateAttribute('description', 'de');
    $esDesc = $product->translateAttribute('description', 'es');

    if (empty($enDesc) || $enDesc === $nlDesc) $issues[] = 'EN desc missing/same';
    if (empty($deDesc) || $deDesc === $nlDesc) $issues[] = 'DE desc missing/same';
    if (empty($esDesc) || $esDesc === $nlDesc) $issues[] = 'ES desc missing/same';

    if (!empty($issues)) {
        $missingTranslations[] = [
            'id' => $product->id,
            'name' => $nlName,
            'issues' => $issues
        ];
    }
}

echo "Products with missing translations: " . count($missingTranslations) . PHP_EOL . PHP_EOL;
foreach (array_slice($missingTranslations, 0, 5) as $item) {
    echo "Product {$item['id']} ({$item['name']}): " . implode(', ', $item['issues']) . PHP_EOL;
}
if (count($missingTranslations) > 5) {
    echo "... and " . (count($missingTranslations) - 5) . " more" . PHP_EOL;
}
