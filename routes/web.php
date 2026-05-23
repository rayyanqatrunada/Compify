<?php

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::shop.home.index')->name('home');

Route::livewire('/products', 'pages::shop.products.index')->name('products.index');
Route::livewire('/product/{product:slug}', 'pages::shop.products.show')->name('products.show');

Route::livewire('/category/{category:slug}', 'pages::shop.categories.show')->name('categories.show');

Route::livewire('/wishlist', 'pages::shop.wishlist.index')->name('wishlist.index');

Route::livewire('/about-us', 'pages::shop.company.about')->name('about');
Route::livewire('/contact', 'pages::shop.company.contact')->name('contact');

Route::livewire('/shipping-policy', 'pages::shop.help.shipping')->name('shipping');
Route::livewire('/returns-policy', 'pages::shop.help.returns')->name('returns');
Route::livewire('/privacy-policy', 'pages::shop.help.privacy')->name('privacy');
Route::livewire('/terms-and-conditions', 'pages::shop.help.terms')->name('terms');

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