<?php

use App\Http\Controllers\Admin\ProductExcelController;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

/*
|--------------------------------------------------------------------------
| SHOP
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| CUSTOMER AUTH
|--------------------------------------------------------------------------
*/

Route::livewire('/sign-in', 'pages::auth.customer.login')
    ->middleware('guest:customer')
    ->name('customer.login');

Route::livewire('/sign-up', 'pages::auth.customer.register')
    ->middleware('guest:customer')
    ->name('customer.register');

Route::livewire('/account', 'pages::shop.account.index')
    ->middleware('auth:customer')
    ->name('account.index');

Route::post('/customer/logout', function (Request $request) {
    Auth::guard('customer')->logout();

    $request->session()->regenerateToken();

    return redirect()->route('home');
})->name('customer.logout');


/*
|--------------------------------------------------------------------------
| CUSTOMER GOOGLE LOGIN
|--------------------------------------------------------------------------
*/

Route::get('/auth/google/redirect', function () {
    return Socialite::driver('google')->redirect();
})->middleware('guest:customer')->name('customer.google.redirect');

Route::get('/auth/google/callback', function () {
    $googleUser = Socialite::driver('google')->user();

    $user = User::updateOrCreate(
        [
            'email' => $googleUser->getEmail(),
        ],
        [
            'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Customer',
            'google_id' => $googleUser->getId(),
            'provider' => 'google',
            'avatar' => $googleUser->getAvatar(),
            'password' => Hash::make(str()->random(32)),
            'role' => 'customer',
        ]
    );

    if ($user->role === 'admin') {
        return redirect()
            ->route('customer.login')
            ->withErrors([
                'email' => 'Akun admin tidak digunakan untuk login customer.',
            ]);
    }

    Auth::guard('customer')->login($user, true);

    request()->session()->regenerate();

    return redirect()->route('home');
})->middleware('guest:customer')->name('customer.google.callback');


/*
|--------------------------------------------------------------------------
| CART & WISHLIST
|--------------------------------------------------------------------------
*/

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
        return redirect()
            ->route('cart.index')
            ->with('success', 'Produk masuk ke keranjang.');
    }

    return back()->with('success', 'Produk berhasil ditambahkan ke keranjang.');
})->name('cart.add');


/*
|--------------------------------------------------------------------------
| CHECKOUT / PAYMENT
|--------------------------------------------------------------------------
*/

Route::livewire('/checkout', 'pages::shop.checkout.index')
    ->middleware('auth:customer')
    ->name('checkout.index');

Route::get('/checkout/success/{order}', function (Order $order) {
    abort_if($order->user_id !== Auth::guard('customer')->id(), 403);

    return view('pages.shop.checkout.success', compact('order'));
})->middleware('auth:customer')->name('checkout.success');


/*
|--------------------------------------------------------------------------
| ADMIN AUTH
|--------------------------------------------------------------------------
*/

Route::livewire(config('compify.admin_login_path'), 'pages::auth.admin.login')
    ->middleware(['guest:admin', 'throttle:5,1'])
    ->name('login');


/*
|--------------------------------------------------------------------------
| ADMIN PANEL
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:admin', 'admin'])
    ->prefix(config('compify.admin_panel_path'))
    ->name('admin.')
    ->group(function () {
        Route::livewire('/', 'pages::admin.dashboard.index')->name('dashboard');

        Route::livewire('/analytics', 'pages::admin.analytics.index')->name('analytics.index');
        Route::livewire('/customers', 'pages::admin.customers.index')->name('customers.index');
        Route::livewire('/reviews', 'pages::admin.reviews.index')->name('reviews.index');

        Route::post('/logout', function (Request $request) {
            Auth::guard('admin')->logout();

            $request->session()->regenerateToken();

            return redirect()->route('login');
        })->name('logout');

        Route::prefix('catalog')->name('catalog.')->group(function () {
            Route::livewire('/products', 'pages::admin.catalog.products.index')->name('products');
            Route::livewire('/categories', 'pages::admin.catalog.categories.index')->name('categories');
            Route::livewire('/brands', 'pages::admin.catalog.brands.index')->name('brands');

            Route::get('/products/export', [ProductExcelController::class, 'export'])
                ->name('products.export');

            Route::post('/products/import', [ProductExcelController::class, 'import'])
                ->name('products.import');
        });

        Route::prefix('content')->name('content.')->group(function () {
            Route::livewire('/banners', 'pages::admin.content.banners.index')->name('banners');

            Route::livewire('/home-category-products', 'pages::admin.content.home-sections.category-products')
                ->name('home-category-products');

            Route::livewire('/home-full-banners', 'pages::admin.content.home-sections.full-banners')
                ->name('home-full-banners');

            Route::livewire('/home-split-banners', 'pages::admin.content.home-sections.split-banners')
                ->name('home-split-banners');

            Route::livewire('/home-galleries', 'pages::admin.content.home-sections.galleries')
                ->name('home-galleries');

            Route::livewire('/pages', 'pages::admin.content.pages.index')->name('pages');
        });

        Route::prefix('sales')->name('sales.')->group(function () {
            Route::livewire('/orders', 'pages::admin.sales.orders.index')->name('orders');
        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::livewire('/shop', 'pages::admin.settings.shop.index')->name('shop');

            Route::livewire('/payment-methods', 'pages::admin.settings.payment-methods.index')
                ->name('payment-methods');
        });
    });