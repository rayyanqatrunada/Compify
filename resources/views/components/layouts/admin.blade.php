<!DOCTYPE html>
<html lang="id" data-admin-theme="light">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Admin Compify' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script>
        (function () {
            const savedTheme = localStorage.getItem('compify_admin_theme') || 'light';
            const theme = savedTheme === 'dark' ? 'dark' : 'light';

            document.documentElement.setAttribute('data-admin-theme', theme);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="admin-body-v2">
    @php
        $admin = auth('admin')->user();

        $adminAvatar = $admin?->avatar
            ? \Illuminate\Support\Facades\Storage::url($admin->avatar)
            : null;

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

        $isEventOpen = 
            request()->routeIs('admin.event.*');
    @endphp

    <div class="admin-shell-v2" id="adminShell" data-admin-theme="light">
        <aside class="admin-sidebar-v2">
            <a href="{{ route('admin.dashboard') }}" class="admin-logo-v2" wire:navigate>
                <img
                    id="adminBrandLogo"
                    src="{{ asset('assets/brand/compify-logo-dark.svg') }}"
                    data-light-logo="{{ asset('assets/brand/compify-logo-dark.svg') }}"
                    data-dark-logo="{{ asset('assets/brand/compify-logo-light.svg') }}"
                    alt="Compify"
                >
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
                        
                        <a href="{{ route('admin.content.home-category-products') }}" wire:navigate
                           @class(['active' => request()->routeIs('admin.content.home-category-products')])>
                            Category Products
                        </a>

                        <a href="{{ route('admin.content.banners') }}" wire:navigate
                           @class(['active' => request()->routeIs('admin.content.banners')])>
                            Hero Banners
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

                <details {{ $isEventOpen ? 'open' : '' }}>
                    <summary>
                        <span>✦</span>
                        Event
                    </summary>

                    <div class="admin-submenu-v2">
                        <a href="{{ route('admin.event.settings') }}" wire:navigate
                        @class(['active' => request()->routeIs('admin.event.settings')])>
                            Atur Event
                        </a>

                        <a href="{{ route('admin.event.hero-images') }}" wire:navigate
                        @class(['active' => request()->routeIs('admin.event.hero-images')])>
                            Image Hero
                        </a>

                        <a href="{{ route('admin.event.flash-sale') }}" wire:navigate
                        @class(['active' => request()->routeIs('admin.event.flash-sale')])>
                            Flash Sale
                        </a>

                        <a href="{{ route('admin.event.full-banners') }}" wire:navigate
                        @class(['active' => request()->routeIs('admin.event.full-banners')])>
                            Full Banner
                        </a>

                        <a href="{{ route('admin.event.combo-packages') }}" wire:navigate
                        @class(['active' => request()->routeIs('admin.event.combo-packages')])>
                            Paket Kombo
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

                        <a href="{{ route('admin.settings.shipping-methods') }}" wire:navigate
                            @class(['active' => request()->routeIs('admin.settings.shipping-methods')])>
                            Shipping Methods
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
                    <p>Compify Admin</p>
                    <h1>{{ $title ?? 'Admin Panel' }}</h1>
                </div>

                <div class="admin-topbar-actions-v2">
                    <button
                        type="button"
                        class="admin-theme-toggle"
                        id="adminThemeToggle"
                        data-light-icon="{{ asset('assets/admin/icons/moon.svg') }}"
                        data-dark-icon="{{ asset('assets/admin/icons/sun.svg') }}"
                        title="Ubah tema"
                    >
                        <img
                            id="adminThemeIcon"
                            src="{{ asset('assets/admin/icons/sun.svg') }}"
                            alt="Theme"
                        >
                    </button>

                    <a href="{{ route('admin.profile') }}" class="admin-profile-chip" wire:navigate>
                        <span class="admin-profile-avatar">
                            @if($adminAvatar)
                                <img src="{{ $adminAvatar }}" alt="{{ $admin?->name ?? 'Admin' }}">
                            @else
                                {{ strtoupper(substr($admin?->name ?? 'A', 0, 1)) }}
                            @endif
                        </span>

                        <span>
                            <strong>{{ $admin?->name ?? 'Admin' }}</strong>
                            <small>Administrator</small>
                        </span>
                    </a>
                </div>
            </header>

            <main class="admin-content-v2">
                {{ $slot }}
            </main>
        </section>
    </div>

    @livewireScripts

    <script>
        function applyAdminTheme(theme) {
            const finalTheme = theme === 'dark' ? 'dark' : 'light';

            document.documentElement.setAttribute('data-admin-theme', finalTheme);
            document.body?.setAttribute('data-admin-theme', finalTheme);

            document.querySelectorAll('.admin-shell-v2').forEach((shell) => {
                shell.setAttribute('data-admin-theme', finalTheme);
            });

            localStorage.setItem('compify_admin_theme', finalTheme);

            const icon = document.getElementById('adminThemeIcon');
            const button = document.getElementById('adminThemeToggle');

            if (icon && button) {
                icon.src = finalTheme === 'dark'
                    ? button.dataset.darkIcon
                    : button.dataset.lightIcon;

                icon.alt = finalTheme === 'dark' ? 'Dark Mode' : 'Light Mode';
            }

            const logo = document.getElementById('adminBrandLogo');

            if (logo) {
                logo.src = finalTheme === 'dark'
                    ? logo.dataset.darkLogo
                    : logo.dataset.lightLogo;
            }
        }

        function bootAdminThemeToggle() {
            const savedTheme = localStorage.getItem('compify_admin_theme') || 'light';

            applyAdminTheme(savedTheme);

            const button = document.getElementById('adminThemeToggle');

            if (!button) return;

            button.onclick = function () {
                const currentTheme = document.documentElement.getAttribute('data-admin-theme') || 'light';
                const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';

                applyAdminTheme(nextTheme);
            };
        }

        document.addEventListener('DOMContentLoaded', bootAdminThemeToggle);
        document.addEventListener('livewire:navigated', bootAdminThemeToggle);
    </script>
</body>
</html>