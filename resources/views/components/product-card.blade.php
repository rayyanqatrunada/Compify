@props(['product'])

<article class="product-card group">
    <a href="{{ route('products.show', $product) }}" class="block overflow-hidden rounded-lg bg-slate-950">
        <div class="relative aspect-[4/3] overflow-hidden">
            <img
                src="{{ $product->thumbnail }}"
                alt="{{ $product->name }}"
                class="h-full w-full object-cover transition duration-700 group-hover:scale-105"
                loading="lazy"
            >
            <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-black/80 to-transparent"></div>
            @if ($product->discount_percentage)
                <span class="absolute left-3 top-3 rounded-md bg-sky-300 px-2.5 py-1 text-xs font-bold text-slate-950">-{{ $product->discount_percentage }}%</span>
            @endif
        </div>
    </a>

    <div class="space-y-3 p-4">
        <div class="flex items-center justify-between gap-3 text-xs uppercase tracking-[0.2em] text-sky-200/80">
            <span>{{ $product->category?->name }}</span>
            <span>{{ number_format($product->rating, 1) }}</span>
        </div>
        <div>
            <a href="{{ route('products.show', $product) }}" class="line-clamp-1 text-base font-semibold text-white">{{ $product->name }}</a>
            <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-400">{{ $product->short_description }}</p>
        </div>
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-lg font-bold text-white">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                @if ($product->compare_price)
                    <p class="text-xs text-slate-500 line-through">Rp {{ number_format($product->compare_price, 0, ',', '.') }}</p>
                @endif
            </div>
            <span class="rounded-md border border-white/10 px-2.5 py-1 text-xs text-slate-300">{{ $product->stock }} stok</span>
        </div>
    </div>
</article>
