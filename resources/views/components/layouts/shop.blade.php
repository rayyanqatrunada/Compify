<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Compify' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="shop-body">
    @php
        $menuCategories = \App\Models\Category::active()
            ->whereNull('parent_id')
            ->with(['children' => fn ($query) => $query->active()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        $navBrands = \App\Models\Brand::active()
            ->orderBy('name')
            ->limit(16)
            ->get();

        $wishlistCount = count(session('wishlist', []));
    @endphp

    <header class="shop-header">
        <div class="top-header">
            <a href="{{ route('home') }}" class="brand-logo" wire:navigate>
                <img src="{{ asset('assets/brand/compify-logo.svg') }}" alt="Compify Logo">
            </a>

            {{-- <div class="header-right">
                <div class="language-box">
                    <span class="flag-dot"></span>
                    <span>ID</span>
                    <span>⌄</span>
                </div> --}}

                <form action="{{ route('products.index') }}" method="GET" class="search-box">
                    <input type="text" name="search" placeholder="Cari">
                    <button type="submit" aria-label="Cari">⌕</button>
                </form>

                <a href="{{ route('products.index') }}" class="header-link" wire:navigate>
                    <span>▢</span>
                    <span>Keranjang Belanja</span>
                    <b>0</b>
                </a>

                <a href="{{ route('wishlist.index') }}" class="header-link" wire:navigate>
                    <span>♡</span>
                    <span>My Wish List</span>
                    <b>{{ $wishlistCount }}</b>
                </a>

                @auth
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('admin.dashboard') }}" class="header-link" wire:navigate>Admin</a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="plain-button">Keluar</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="header-link" wire:navigate>Masuk</a>
                @endauth
            </div>
        </div>

        <nav class="main-nav">
            <div class="main-nav-inner">
                <a href="{{ route('home') }}" class="nav-link" wire:navigate>Beranda</a>

                <div class="nav-dropdown">
                    <button type="button" class="nav-link nav-dropdown-trigger">
                        Kategori
                    </button>

                    <div class="mega-menu">
                        <div class="mega-menu-inner">
                            @foreach($menuCategories as $parent)
                                <div class="mega-column">
                                    <a href="{{ route('categories.show', $parent) }}" class="mega-title" wire:navigate>
                                        {{ $parent->name }}
                                    </a>

                                    @forelse($parent->children as $child)
                                        <a href="{{ route('categories.show', $child) }}" class="mega-link" wire:navigate>
                                            {{ $child->name }}
                                        </a>
                                    @empty
                                        <a href="{{ route('categories.show', $parent) }}" class="mega-link" wire:navigate>
                                            Lihat Produk
                                        </a>
                                    @endforelse
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="nav-dropdown">
                    <button type="button" class="nav-link nav-dropdown-trigger">
                        Merk
                    </button>

                    <div class="mega-menu mega-menu-small">
                        <div class="mega-menu-inner brand-menu-inner">
                            @forelse($navBrands as $brand)
                                <a href="{{ route('products.index', ['brand' => $brand->slug]) }}" class="brand-dropdown-item" wire:navigate>
                                    <span>{{ strtoupper(substr($brand->name, 0, 2)) }}</span>
                                    <strong>{{ $brand->name }}</strong>
                                </a>
                            @empty
                                <p>Belum ada merk.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <a href="{{ route('products.index') }}" class="nav-link" wire:navigate>Produk</a>
                <a href="{{ route('products.index', ['sort' => 'latest']) }}" class="nav-link" wire:navigate>Produk Baru</a>
                <a href="{{ route('products.index') }}" class="nav-link" wire:navigate>Promo</a>
                <a href="#about-compify" class="nav-link">About Us</a>
            </div>
        </nav>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="shop-footer modern-footer">
        <div class="footer-main">
            <div class="footer-about">
                <a href="{{ route('home') }}" class="footer-logo" wire:navigate>
                    <img src="{{ asset('assets/brand/compify-logo.svg') }}" alt="Compify Logo">
                </a>

                <p>
                    Compify adalah toko perlengkapan komputer untuk kebutuhan build PC,
                    upgrade komponen, dan peripheral. Semua produk yang tampil dikelola
                    langsung melalui admin site.
                </p>

                <div class="footer-socials">
                    <a href="#" aria-label="Facebook">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M14 8.5V6.7c0-.8.2-1.2 1.3-1.2H17V2.4c-.9-.1-1.8-.2-2.7-.2-2.7 0-4.6 1.7-4.6 4.7v1.6H6.8V12h2.9v9.8H14V12h2.8l.5-3.5H14Z"/>
                        </svg>
                    </a>

                    <a href="#" aria-label="Instagram">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M7.5 2.5h9A5 5 0 0 1 21.5 7.5v9a5 5 0 0 1-5 5h-9A5 5 0 0 1 2.5 16.5v-9a5 5 0 0 1 5-5Zm0 2A3 3 0 0 0 4.5 7.5v9a3 3 0 0 0 3 3h9a3 3 0 0 0 3-3v-9a3 3 0 0 0-3-3h-9ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm5.2-2.5a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4Z"/>
                        </svg>
                    </a>

                    <a href="#" aria-label="TikTok">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M14.7 2.5c.4 3 2.1 4.8 4.8 5v3.4a8 8 0 0 1-4.7-1.5v6.2c0 3.4-2.4 5.9-5.8 5.9a5.7 5.7 0 0 1-5.8-5.7c0-3.5 2.8-6.1 6.4-5.7v3.5c-1.6-.5-3 .5-3 2.1 0 1.3 1 2.3 2.3 2.3 1.4 0 2.3-.9 2.3-2.6V2.5h3.5Z"/>
                        </svg>
                    </a>

                    <a href="#" aria-label="YouTube">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M21.4 7.2a3 3 0 0 0-2.1-2.1C17.4 4.6 12 4.6 12 4.6s-5.4 0-7.3.5a3 3 0 0 0-2.1 2.1A31 31 0 0 0 2.1 12c0 1.7.1 3.4.5 4.8a3 3 0 0 0 2.1 2.1c1.9.5 7.3.5 7.3.5s5.4 0 7.3-.5a3 3 0 0 0 2.1-2.1c.4-1.4.5-3.1.5-4.8 0-1.7-.1-3.4-.5-4.8ZM10 15.5v-7l6 3.5-6 3.5Z"/>
                        </svg>
                    </a>

                    <a href="#" aria-label="Email">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3.5 5h17A1.5 1.5 0 0 1 22 6.5v11A1.5 1.5 0 0 1 20.5 19h-17A1.5 1.5 0 0 1 2 17.5v-11A1.5 1.5 0 0 1 3.5 5Zm.9 2 7.6 5.1L19.6 7H4.4Zm15.6 9.8V9.1l-7.2 4.8a1.5 1.5 0 0 1-1.6 0L4 9.1v7.7h16Z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <div>
                <h3>Products</h3>
                <a href="{{ route('products.index') }}" wire:navigate>Motherboard</a>
                <a href="{{ route('products.index') }}" wire:navigate>Processor</a>
                <a href="{{ route('products.index') }}" wire:navigate>VGA / GPU</a>
                <a href="{{ route('products.index') }}" wire:navigate>Power Supply</a>
                <a href="{{ route('products.index') }}" wire:navigate>Peripheral</a>
            </div>

            <div>
                <h3>Support</h3>
                <a href="{{ route('contact') }}" wire:navigate>Contact Us</a>
                <a href="{{ route('shipping') }}" wire:navigate>Kebijakan Pengiriman</a>
                <a href="{{ route('returns') }}" wire:navigate>Pengembalian Produk</a>
            </div>

            <div>
                <h3>Company</h3>
                <a href="{{ route('about') }}" wire:navigate>About Us</a>
                <a href="{{ route('privacy') }}" wire:navigate>Privacy Policy</a>
                <a href="{{ route('terms') }}" wire:navigate>Terms & Conditions</a>
            </div>

            <div>
                <h3>Newsletter</h3>
                <p>Dapatkan info produk baru, promo, dan rekomendasi build PC.</p>

                <div class="newsletter-box">
                    <input type="email" placeholder="Masukkan email Anda">
                    <button>Submit</button>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>Copyright © COMPIFY. All rights reserved.</p>
            <p>Powered by Compify Store System</p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>