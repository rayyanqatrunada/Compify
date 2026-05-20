<div class="space-y-8">
    <div class="glass-panel p-4">
        <div class="grid gap-4 lg:grid-cols-[1.5fr_.9fr_.8fr_.8fr_auto]">
            <label class="block">
                <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Search</span>
                <input
                    type="search"
                    wire:model.live.debounce.350ms="search"
                    placeholder="Keyboard, mouse, monitor..."
                    class="form-input"
                >
            </label>

            <label class="block">
                <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Category</span>
                <select wire:model.live="category" class="form-input">
                    <option value="">All categories</option>
                    @foreach ($categories as $item)
                        <option value="{{ $item->slug }}">{{ $item->name }} ({{ $item->products_count }})</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Sort</span>
                <select wire:model.live="sort" class="form-input">
                    <option value="featured">Featured</option>
                    <option value="newest">Newest</option>
                    <option value="popular">Popular</option>
                    <option value="price-low">Lowest price</option>
                    <option value="price-high">Highest price</option>
                </select>
            </label>

            <label class="block">
                <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Stock</span>
                <select wire:model.live="stock" class="form-input">
                    <option value="">All stock</option>
                    <option value="ready">Ready</option>
                    <option value="low">Low stock</option>
                </select>
            </label>

            <div class="flex items-end">
                <button type="button" wire:click="resetFilters" class="soft-button w-full justify-center">Reset</button>
            </div>
        </div>
    </div>

    <div wire:loading.delay class="glass-panel flex items-center justify-between p-4 text-sm text-sky-100">
        <span>Memuat katalog...</span>
        <span class="h-2 w-24 overflow-hidden rounded-full bg-white/10">
            <span class="block h-full w-1/2 animate-pulse rounded-full bg-sky-300"></span>
        </span>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3" wire:loading.class="opacity-60">
        @forelse ($products as $product)
            <x-product-card :product="$product" />
        @empty
            <div class="empty-state sm:col-span-2 lg:col-span-3">
                Tidak ada produk yang cocok. Coba kata kunci atau kategori lain.
            </div>
        @endforelse
    </div>

    <div>
        {{ $products->links() }}
    </div>
</div>
