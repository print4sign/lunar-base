<?php

use App\Livewire\Pages\CheckoutPage;
use App\Livewire\Pages\CheckoutSuccessPage;
use App\Livewire\Pages\CollectionPage;
use App\Livewire\Pages\CollectionsIndex;
use App\Livewire\Pages\Home;
use App\Livewire\Pages\ProductPage;
use App\Livewire\Pages\ProductsIndex;
use App\Livewire\Pages\SearchPage;
use Illuminate\Support\Facades\Route;

// Home
Route::get('/', Home::class)->name('home');

// Products
Route::get('/products', ProductsIndex::class)->name('products.index');
Route::get('/products/{slug}', ProductPage::class)->name('product.view');

// Collections
Route::get('/collections', CollectionsIndex::class)->name('collections.index');
Route::get('/collections/{slug}', CollectionPage::class)->name('collection.view');

// Search
Route::get('/search', SearchPage::class)->name('search.view');

// Checkout
Route::get('/checkout', CheckoutPage::class)->name('checkout.view');
Route::get('/checkout/success', CheckoutSuccessPage::class)->name('checkout-success.view');
