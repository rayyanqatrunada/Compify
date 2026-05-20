@extends('layouts.app')

@section('title', $product->name . ' - Compify')

@section('content')
    <section class="section-shell">
        <div class="grid gap-10 lg:grid-cols-[1.05fr_.95fr]">
            <div class="space-y-4">
                <div class="overflow-hidden rounded-lg border border-white/10 bg-slate-950">
                    <img src="{{ $product->thumbnail }}" alt="{{ $product->name }}" class="aspect-[4/3] w-full object-cover">
                </div>
                <div class="grid grid-cols-3 gap-3">
                    @foreach (array_slice($product->gallery ?? [$product->thumbnail], 0, 3) as $image)
                        <img src="{{ $image }}" alt="{{ $product->name }} gallery" class="aspect-[4/3] rounded-lg border border-white/10 object-cover">
                    @endforeach
                </div>
            </div>

            <div class="lg:pt-6">
                <a href="{{ route('products.index', ['category' => $product->category?->slug]) }}" class="section-kicker">{{ $product->category?->name }}</a>
                <h1 class="mt-4 text-4xl font-black leading-tight text-white sm:text-5xl">{{ $product->name }}</h1>
                <p class="mt-4 text-base leading-7 text-slate-400">{{ $product->short_description }}</p>

                <div class="mt-7 flex flex-wrap items-end gap-4">
                    <div>
                        <p class="text-3xl font-black text-white">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                        @if ($product->compare_price)
                            <p class="text-sm text-slate-500 line-through">Rp {{ number_format($product->compare_price, 0, ',', '.') }}</p>
                        @endif
                    </div>
                    @if ($product->discount_percentage)
                        <span class="rounded-md bg-sky-300 px-3 py-1.5 text-sm font-bold text-slate-950">Hemat {{ $product->discount_percentage }}%</span>
                    @endif
                </div>

                <div class="mt-8 grid gap-3 sm:grid-cols-3">
                    <div class="metric-panel">
                        <span>{{ $product->stock }}</span>
                        <p>Stok tersedia</p>
                    </div>
                    <div class="metric-panel">
                        <span>{{ number_format($product->rating, 1) }}</span>
                        <p>Rating produk</p>
                    </div>
                    <div class="metric-panel">
                        <span>{{ $product->sold_count }}</span>
                        <p>Terjual dummy</p>
                    </div>
                </div>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <button type="button" class="neon-button justify-center">Add to cart</button>
                    <a href="{{ route('products.index') }}" class="soft-button justify-center">Back to catalog</a>
                </div>

                <div class="mt-10 space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-white">Deskripsi</h2>
                        <p class="mt-3 leading-7 text-slate-400">{{ $product->description }}</p>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-white">Spesifikasi</h2>
                        <dl class="mt-3 grid gap-3">
                            @foreach (($product->specs ?? []) as $key => $value)
                                <div class="flex items-center justify-between rounded-lg border border-white/10 bg-white/[.03] px-4 py-3 text-sm">
                                    <dt class="text-slate-400">{{ $key }}</dt>
                                    <dd class="font-medium text-white">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-shell pt-0">
        <div class="section-heading">
            <div>
                <p class="section-kicker">Related products</p>
                <h2>Masih satu kategori.</h2>
            </div>
        </div>
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($relatedProducts as $related)
                <x-product-card :product="$related" />
            @empty
                <div class="empty-state sm:col-span-2 lg:col-span-4">Produk terkait belum tersedia.</div>
            @endforelse
        </div>
    </section>
@endsection
