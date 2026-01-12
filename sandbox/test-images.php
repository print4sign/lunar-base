<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$productUrl = 'https://www.probo.nl/deco-fabric';

$response = \Illuminate\Support\Facades\Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
    'Accept' => 'text/html,application/xhtml+xml',
])->timeout(30)->get($productUrl);

echo "Status: ".$response->status()."\n";
echo "Body length: ".strlen($response->body())."\n";

$html = $response->body();

// Check for redirect or blocked
if (str_contains($html, 'redirect') || str_contains($html, 'blocked')) {
    echo "Might be redirect/blocked\n";
}

// Try to find media paths
preg_match_all('/media\/catalog\/product[^\s"\'<>]*\.(jpg|png|webp)/i', $html, $matches);
echo "Media paths found: ".count($matches[0])."\n";

if (!empty($matches[0])) {
    echo "First 3 paths:\n";
    foreach (array_slice(array_unique($matches[0]), 0, 3) as $path) {
        echo "  - $path\n";
    }
}

// Check what the page title is
if (preg_match('/<title>([^<]+)<\/title>/i', $html, $titleMatch)) {
    echo "Page title: ".$titleMatch[1]."\n";
}
