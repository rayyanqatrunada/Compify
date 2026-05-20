@extends('layouts.app')

@section('title', 'Products - Compify')

@section('content')
    <section class="border-b border-white/10 bg-[#070b12]">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <p class="section-kicker">Product listing</p>
            <div class="mt-4 grid gap-6 lg:grid-cols-[1fr_.7fr] lg:items-end">
                <div>
                    <h1 class="text-4xl font-black text-white sm:text-5xl">Katalog gear Compify.</h1>
                    <p class="mt-4 max-w-2xl text-slate-400">Filter produk berdasarkan kategori, stok, popularitas, dan harga. Semua data berasal dari database dan bisa dikelola dari Filament admin.</p>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-2">
                    @foreach ($categories->take(4) as $category)
                        <a href="{{ route('products.index', ['category' => $category->slug]) }}" class="mini-chip">{{ $category->name }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="section-shell">
        <livewire:product-catalog />
    </section>
@endsection
