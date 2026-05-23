<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\Product;

Route::livewire('/', 'pages::shop.home')->name('home');
Route::livewire('/products', 'pages::shop.products')->name('products.index');
Route::livewire('/category/{category:slug}', 'pages::shop.category')->name('categories.show');
Route::livewire('/product/{product:slug}', 'pages::shop.product-show')->name('products.show');

Route::livewire('/admin/login', 'pages::auth.login')
    ->middleware('guest')
    ->name('login');

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('home');
})->name('logout');

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');
        Route::livewire('/categories', 'pages::admin.categories')->name('categories');
        Route::livewire('/products', 'pages::admin.products')->name('products');
        Route::livewire('/banners', 'pages::admin.banners')->name('banners');
    });

Route::livewire('/wishlist', 'pages::shop.wishlist')->name('wishlist.index');

Route::post('/wishlist/{product}/toggle', function (Product $product) {
    $wishlist = session()->get('wishlist', []);

    if (in_array($product->id, $wishlist)) {
        $wishlist = array_values(array_diff($wishlist, [$product->id]));
    } else {
        $wishlist[] = $product->id;
    }

    session()->put('wishlist', $wishlist);

    return back();
})->name('wishlist.toggle');