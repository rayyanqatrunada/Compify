<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Admin Compify' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="admin-body-v2">
    @php
        $admin = auth('admin')->user();

        $isProductOpen =
            request()->routeIs('admin.catalog.*') ||
            request()->routeIs('admin.content.banners') ||
            request()->routeIs('admin.content.home-category-products') ||
            request()->routeIs('admin.content.home-full-banners') ||
            request()->routeIs('admin.content.home-split-banners') ||
            request()->routeIs('admin.content.home-galleries');

        $isConfigureOpen =
            request()->routeIs('admin.settings.*') ||
            request()->routeIs('admin.content.pages');
    @endphp

    <div class="admin-shell-v2" data-admin-theme="light">
        <aside class="admin-sidebar-v2">
            <a href="{{ route('admin.dashboard') }}" class="admin-logo-v2" wire:navigate>
                <img src="{{ asset('assets/brand/compify-logo.svg') }}" alt="Compify">
            </a>

            <nav class="admin-menu-v2">
                <a href="{{ route('admin.dashboard') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.dashboard')])>
                    <span>▦</span>
                    Dashboard
                </a>

                <a href="{{ route('admin.analytics.index') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.analytics.*')])>
                    <span>▥</span>
                    Analytic
                </a>

                <details {{ $isProductOpen ? 'open' : '' }}>
                    <summary>
                        <span>▤</span>
                        Product
                    </summary>

                    <div class="admin-submenu-v2">
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

                        <a href="{{ route('admin.content.banners') }}" wire:navigate
                           @class(['active' => request()->routeIs('admin.content.banners')])>
                            Hero Banners
                        </a>

                        <a href="{{ route('admin.content.home-category-products') }}" wire:navigate
                           @class(['active' => request()->routeIs('admin.content.home-category-products')])>
                            Category Products
                        </a>

                        <a href="{{ route('admin.content.home-full-banners') }}" wire:navigate
                           @class(['active' => request()->routeIs('admin.content.home-full-banners')])>
                            Full Banners
                        </a>

                        <a href="{{ route('admin.content.home-split-banners') }}" wire:navigate
                           @class(['active' => request()->routeIs('admin.content.home-split-banners')])>
                            Split Banners
                        </a>

                        <a href="{{ route('admin.content.home-galleries') }}" wire:navigate
                           @class(['active' => request()->routeIs('admin.content.home-galleries')])>
                            Gallery 3 Images
                        </a>
                    </div>
                </details>

                <a href="{{ route('admin.sales.orders') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.sales.orders')])>
                    <span>▧</span>
                    Orders
                </a>

                <a href="{{ route('admin.customers.index') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.customers.*')])>
                    <span>☻</span>
                    Customer
                </a>

                <a href="{{ route('admin.reviews.index') }}" wire:navigate
                   @class(['active' => request()->routeIs('admin.reviews.*')])>
                    <span>✎</span>
                    Reviews
                </a>

                <details {{ $isConfigureOpen ? 'open' : '' }}>
                    <summary>
                        <span>⚙</span>
                        Configure
                    </summary>

                    <div class="admin-submenu-v2">
                        <a href="{{ route('admin.settings.shop') }}" wire:navigate
                           @class(['active' => request()->routeIs('admin.settings.shop')])>
                            Shop Settings
                        </a>

                        <a href="{{ route('admin.settings.payment-methods') }}" wire:navigate
                           @class(['active' => request()->routeIs('admin.settings.payment-methods')])>
                            Payment Methods
                        </a>
                        
                        <a href="{{ route('admin.content.pages') }}" wire:navigate
                           @class(['active' => request()->routeIs('admin.content.pages')])>
                            Static Pages
                        </a>
                    </div>
                </details>
            </nav>

            <form method="POST" action="{{ route('admin.logout') }}" class="admin-logout-v2">
                @csrf
                <button type="submit">
                    <span>⇥</span>
                    Logout
                </button>
            </form>
        </aside>

        <section class="admin-main-v2">
            <header class="admin-topbar-v2">
                <div>
                    <p>Admin Panel</p>
                    <h1>{{ $title ?? 'Dashboard' }}</h1>
                </div>

                <div class="admin-topbar-actions-v2">
                    <button type="button" class="admin-icon-btn-v2" title="Notification">🔔</button>
                    <button type="button" class="admin-icon-btn-v2" title="Message">💬</button>

                    <button type="button" class="admin-icon-btn-v2" id="adminThemeToggle" title="Dark Mode">
                        🌙
                    </button>

                    <div class="admin-avatar-v2">
                        <span>{{ strtoupper(substr($admin?->name ?? 'A', 0, 1)) }}</span>
                    </div>
                </div>
            </header>

            <main class="admin-content-v2">
                {{ $slot }}
            </main>
        </section>
    </div>

    <script>
        const adminShell = document.querySelector('.admin-shell-v2');
        const adminThemeToggle = document.getElementById('adminThemeToggle');

        const savedAdminTheme = localStorage.getItem('compify_admin_theme') || 'light';
        adminShell?.setAttribute('data-admin-theme', savedAdminTheme);

        if (adminThemeToggle) {
            adminThemeToggle.textContent = savedAdminTheme === 'dark' ? '☀️' : '🌙';

            adminThemeToggle.addEventListener('click', () => {
                const current = adminShell.getAttribute('data-admin-theme') || 'light';
                const next = current === 'dark' ? 'light' : 'dark';

                adminShell.setAttribute('data-admin-theme', next);
                localStorage.setItem('compify_admin_theme', next);
                adminThemeToggle.textContent = next === 'dark' ? '☀️' : '🌙';
            });
        }
    </script>

    @livewireScripts
</body>
</html>