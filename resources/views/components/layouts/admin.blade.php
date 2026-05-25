<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Admin Compify' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="admin-body">
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <div class="admin-brand-mark">C</div>
            <div>
                <strong>COMPIFY</strong>
                <span>Admin Panel</span>
            </div>
        </div>

        <nav class="admin-nav">
            <div class="admin-nav-group">
                <p>Main</p>

                <a href="{{ route('admin.dashboard') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.dashboard')])>
                    Dashboard
                </a>
            </div>

            <div class="admin-nav-group">
                <p>Catalog</p>

                <a href="{{ route('admin.catalog.products') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.catalog.products')])>
                    Products
                </a>

                <a href="{{ route('admin.catalog.categories') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.catalog.categories')])>
                    Categories
                </a>

                <a href="{{ route('admin.catalog.brands') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.catalog.brands')])>
                    Brands
                </a>
            </div>

            <div class="admin-nav-group">
                <p>Content</p>

                <a href="{{ route('admin.content.banners') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.content.banners')])>
                    Home Banners
                </a>

                <a href="{{ route('admin.content.pages') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.content.pages')])>
                    Pages
                </a>
            </div>

                <a href="{{ route('admin.content.home-sections') }}" wire:navigate
                    @class(['active' => request()->routeIs('admin.content.home-sections')])>
                    Home Sections
                </a>

            <div class="admin-nav-group">
                <p>Sales</p>

                <a href="{{ route('admin.sales.orders') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.sales.orders')])>
                    Orders
                </a>
            </div>

            <div class="admin-nav-group">
                <p>Settings</p>

                <a href="{{ route('admin.settings.shop') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.settings.shop')])>
                    Shop Settings
                </a>
            </div>
        </nav>

        <div class="admin-sidebar-footer">
            <a href="{{ route('home') }}" wire:navigate>
                View Shop
            </a>

            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit">Logout</button>
            </form>
        </div>
    </aside>

    <main class="admin-shell">
        <header class="admin-topbar">
            <div>
                <p>Welcome back,</p>
                <h1>{{ auth('admin')->user()->name }}</h1>
            </div>

            <div class="admin-topbar-actions">
                <a href="{{ route('home') }}" wire:navigate>Open Store</a>
                <span>{{ now()->format('d M Y') }}</span>
            </div>
        </header>

        <section class="admin-content">
            {{ $slot }}
        </section>
    </main>

    @livewireScripts
</body>
</html>