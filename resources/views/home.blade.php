@extends('layouts.app')

@section('title', 'Compify - Modern Computer Setup Store')

@section('content')
    @php
        $heroImage = $banners->first()?->image ?? 'https://images.unsplash.com/photo-1618477388954-7852f32655ec?auto=format&fit=crop&w=1600&q=80';
    @endphp

    <section class="relative min-h-[86vh] overflow-hidden">
        <img src="{{ $heroImage }}" alt="Compify desk setup" class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(5,7,11,.95),rgba(5,7,11,.66),rgba(5,7,11,.25))]"></div>
        <div class="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-[#05070b] to-transparent"></div>

        <div class="relative mx-auto flex min-h-[86vh] max-w-7xl items-center px-4 py-24 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="section-kicker">Computer gear and desk setup</p>
                <h1 class="mt-5 max-w-4xl text-5xl font-black leading-[1.02] text-white sm:text-7xl lg:text-8xl">Compify</h1>
                <p class="mt-6 max-w-2xl text-base leading-7 text-slate-200 sm:text-lg">Perlengkapan komputer modern untuk keyboard enthusiast, gamer, creator, dan siswa PPLG yang ingin membangun demo e-commerce profesional.</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('products.index') }}" class="neon-button justify-center">Explore products</a>
                    <a href="#featured" class="soft-button justify-center">Featured gear</a>
                </div>
                <div class="mt-10 grid max-w-2xl grid-cols-3 gap-3">
                    <div class="hero-stat">
                        <span>20+</span>
                        <small>Products</small>
                    </div>
                    <div class="hero-stat">
                        <span>5</span>
                        <small>Categories</small>
                    </div>
                    <div class="hero-stat">
                        <span>4.8</span>
                        <small>Avg rating</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="featured" class="section-shell">
        <div class="section-heading">
            <div>
                <p class="section-kicker">Featured products</p>
                <h2>Gear pilihan untuk setup yang lebih clean.</h2>
            </div>
            <a href="{{ route('products.index') }}" class="soft-button">View all</a>
        </div>

        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($featuredProducts as $product)
                <x-product-card :product="$product" />
            @empty
                <div class="empty-state sm:col-span-2 lg:col-span-3">Produk unggulan belum tersedia.</div>
            @endforelse
        </div>
    </section>

    <section id="categories" class="section-shell">
        <div class="section-heading">
            <div>
                <p class="section-kicker">Categories</p>
                <h2>Cari berdasarkan workflow dan setup.</h2>
            </div>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($categories as $category)
                <a href="{{ route('products.index', ['category' => $category->slug]) }}" class="category-tile">
                    <span class="category-swatch" style="background: {{ $category->accent_color }}"></span>
                    <span class="text-base font-semibold text-white">{{ $category->name }}</span>
                    <span class="text-sm text-slate-400">{{ $category->products_count }} produk</span>
                </a>
            @endforeach
        </div>
    </section>

    <section id="promo" class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-5 lg:grid-cols-2">
            @foreach ($banners as $banner)
                <div class="promo-band">
                    <img src="{{ $banner->image }}" alt="{{ $banner->title }}" class="absolute inset-0 h-full w-full object-cover">
                    <div class="absolute inset-0 bg-black/62"></div>
                    <div class="relative z-10 max-w-lg">
                        <p class="section-kicker">{{ $banner->badge }}</p>
                        <h2 class="mt-4 text-3xl font-bold text-white">{{ $banner->title }}</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-300">{{ $banner->subtitle }}</p>
                        <a href="{{ $banner->cta_url }}" class="mt-6 inline-flex neon-button">{{ $banner->cta_label }}</a>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="section-shell">
        <div class="grid gap-10 lg:grid-cols-[.9fr_1.1fr] lg:items-center">
            <div>
                <p class="section-kicker">About Compify</p>
                <h2 class="mt-4 text-3xl font-bold text-white sm:text-4xl">Brand dummy yang dibuat seperti toko tech sungguhan.</h2>
                <p class="mt-5 leading-7 text-slate-400">Compify memadukan katalog produk, dashboard admin, autentikasi, dan data dummy agar siswa bisa mempelajari alur Laravel modern dari database sampai UI.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="metric-panel">
                    <span>Clean MVC</span>
                    <p>Controller, model, view, factory, seeder, dan resource admin dipisah jelas.</p>
                </div>
                <div class="metric-panel">
                    <span>Livewire</span>
                    <p>Filter katalog berjalan interaktif dengan loading dan pagination.</p>
                </div>
                <div class="metric-panel">
                    <span>Filament</span>
                    <p>Admin panel siap CRUD untuk produk, kategori, order, user, banner, testimonial.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section-shell">
        <div class="section-heading">
            <div>
                <p class="section-kicker">Testimonials</p>
                <h2>Dipakai untuk demo, portfolio, dan latihan presentasi.</h2>
            </div>
        </div>

        <div class="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($testimonials as $testimonial)
                <figure class="testimonial-card">
                    <div class="flex items-center gap-3">
                        <img src="{{ $testimonial->avatar }}" alt="{{ $testimonial->name }}" class="size-11 rounded-lg object-cover">
                        <div>
                            <figcaption class="font-semibold text-white">{{ $testimonial->name }}</figcaption>
                            <p class="text-xs text-slate-400">{{ $testimonial->role }} / {{ $testimonial->company }}</p>
                        </div>
                    </div>
                    <blockquote class="mt-5 text-sm leading-6 text-slate-300">"{{ $testimonial->quote }}"</blockquote>
                </figure>
            @endforeach
        </div>
    </section>

    <section id="faq" class="section-shell">
        <div class="mx-auto max-w-3xl">
            <p class="section-kicker text-center">FAQ</p>
            <h2 class="mt-4 text-center text-3xl font-bold text-white">Pertanyaan yang sering muncul.</h2>
            <div class="mt-8 space-y-3">
                @foreach ([
                    ['Apakah ini toko sungguhan?', 'Ini proyek dummy untuk portfolio dan tugas akhir, tetapi struktur datanya dibuat seperti e-commerce nyata.'],
                    ['Apakah admin panel sudah terhubung database?', 'Ya. Produk, kategori, order, user, banner, dan testimonial dikelola melalui Filament.'],
                    ['Apakah user bisa login dan register?', 'Ya. Ada autentikasi Laravel dengan role admin dan user.'],
                    ['Apakah bisa dipakai dengan MySQL?', 'Bisa. Konfigurasi database ada di file .env dan migration sudah dibuat kompatibel untuk MySQL.'],
                ] as $item)
                    <div x-data="{ open: false }" class="faq-item">
                        <button type="button" x-on:click="open = ! open" class="flex w-full items-center justify-between gap-4 text-left">
                            <span class="font-semibold text-white">{{ $item[0] }}</span>
                            <span class="text-sky-200" x-text="open ? '-' : '+'"></span>
                        </button>
                        <p x-cloak x-show="open" x-transition class="mt-3 text-sm leading-6 text-slate-400">{{ $item[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-shell pb-20">
        <div class="newsletter-panel">
            <div>
                <p class="section-kicker">Newsletter</p>
                <h2 class="mt-3 text-3xl font-bold text-white">Dapatkan update gear dan promo setup.</h2>
            </div>
            <livewire:newsletter-form />
        </div>
    </section>
@endsection
