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

Route::livewire(config('compify.admin_login_path'), 'pages::auth.admin.login')
    ->middleware(['guest', 'throttle:5,1'])
    ->name('login');

Route::post('/logout', function (Request $request) {
    $wasAdmin = $request->user()?->role === 'admin';

    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return $wasAdmin
        ? redirect()->route('login')
        : redirect()->route('home');
})->name('logout');

Route::middleware(['auth', 'admin'])
    ->prefix(config('compify.admin_panel_path'))
    ->name('admin.')
    ->group(function () {
        Route::livewire('/', 'pages::admin.dashboard.index')->name('dashboard');

        Route::prefix('catalog')->name('catalog.')->group(function () {
            Route::livewire('/products', 'pages::admin.catalog.products.index')->name('products');
            Route::livewire('/categories', 'pages::admin.catalog.categories.index')->name('categories');
            Route::livewire('/brands', 'pages::admin.catalog.brands.index')->name('brands');
        });

        Route::prefix('content')->name('content.')->group(function () {
            Route::livewire('/banners', 'pages::admin.content.banners.index')->name('banners');
            Route::livewire('/home-sections', 'pages::admin.content.home-sections.index')->name('home-sections');
            Route::livewire('/pages', 'pages::admin.content.pages.index')->name('pages');
        });

        Route::prefix('sales')->name('sales.')->group(function () {
            Route::livewire('/orders', 'pages::admin.sales.orders.index')->name('orders');
        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::livewire('/shop', 'pages::admin.settings.shop.index')->name('shop');
        });
    });

Route::livewire('/sign-in', 'pages::auth.customer.login')
    ->middleware('guest')
    ->name('customer.login');

Route::livewire('/sign-up', 'pages::auth.customer.register')
    ->middleware('guest')
    ->name('customer.register');

Route::livewire('/account', 'pages::shop.account.index')
    ->middleware('auth')
    ->name('account.index');

Route::livewire('/cart', 'pages::shop.cart.index')
    ->name('cart.index');

Route::post('/cart/{product}/add', function (Request $request, Product $product) {
    abort_if(! $product->is_active, 404);

    $data = $request->validate([
        'quantity' => ['required', 'integer', 'min:1'],
        'redirect_to_cart' => ['nullable', 'boolean'],
    ]);

    $quantity = min((int) $data['quantity'], max(1, $product->stock));

    $cart = session()->get('cart', []);
    $currentQty = $cart[$product->id] ?? 0;

    $cart[$product->id] = min($currentQty + $quantity, max(1, $product->stock));

    session()->put('cart', $cart);

    if ($request->boolean('redirect_to_cart')) {
        return redirect()->route('cart.index')->with('success', 'Produk masuk ke keranjang.');
    }

    return back()->with('success', 'Produk berhasil ditambahkan ke keranjang.');
})->name('cart.add');