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
    @endphp

    <header class="shop-header">
        <div class="top-header">
            <a href="{{ route('home') }}" class="brand-logo" wire:navigate>
                <img src="{{ asset('assets/brand/compify-logo.svg') }}" alt="Compify Logo">
            </a>

            <div class="header-right">
                <div class="language-box">
                    <span class="flag-dot"></span>
                    <span>ID</span>
                    <span>⌄</span>
                </div>

                <form action="{{ route('products.index') }}" method="GET" class="search-box">
                    <input type="text" name="search" placeholder="Cari">
                    <button type="submit" aria-label="Cari">⌕</button>
                </form>

                <a href="{{ route('products.index') }}" class="header-link" wire:navigate>
                    <span>▢</span>
                    <span>Keranjang Belanja</span>
                    <b>0</b>
                </a>

                <a href="{{ route('products.index') }}" class="header-link" wire:navigate>
                    <span>♡</span>
                    <span>My Wish List</span>
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

                <a href="{{ route('products.index') }}" class="nav-link" wire:navigate>Produk</a>
                <a href="{{ route('products.index') }}" class="nav-link" wire:navigate>Merk</a>
                <a href="{{ route('products.index') }}" class="nav-link" wire:navigate>Promo</a>
                <a href="{{ route('products.index') }}" class="nav-link" wire:navigate>Database Komponen</a>
            </div>
        </nav>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="shop-footer">
        <div>
            <h3>Pembayaran & Pengiriman</h3>
            <div class="payment-grid">
                <span>VISA</span>
                <span>GOPAY</span>
                <span>OVO</span>
                <span>QRIS</span>
                <span>BCA</span>
                <span>BNI</span>
                <span>JNE</span>
                <span>SICEPAT</span>
            </div>
        </div>

        <div>
            <h3>Store</h3>
            <p>Tokopedia</p>
            <p>Shopee</p>
            <p>Tiktok Shop</p>
        </div>

        <div>
            <h3>Informasi</h3>
            <p>Kebijakan Privasi</p>
            <p>Kebijakan Pengiriman</p>
            <p>Pengembalian & Penukaran</p>
            <p>Hubungi Kami</p>
        </div>

        <div>
            <h3>Daftar Newsletter</h3>
            <p>Dapatkan informasi produk komputer terbaru dari Compify.</p>
            <div class="newsletter-box">
                <input type="email" placeholder="Masukkan alamat email Anda">
                <button>Submit</button>
            </div>
        </div>

        <div class="copyright">
            COMPIFY. All Rights Reserved.
        </div>
    </footer>

    @livewireScripts
</body>
</html>