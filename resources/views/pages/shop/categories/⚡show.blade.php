<?php

use App\Models\Category;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.shop')]
class extends Component {
    use WithPagination;

    public Category $category;

    #[Computed]
    public function products()
    {
        $categoryIds = $this->category->children()
            ->pluck('id')
            ->push($this->category->id);

        return Product::with(['category', 'brand'])
            ->active()
            ->whereIn('category_id', $categoryIds)
            ->latest()
            ->paginate(12);
    }

    public function title(): string
    {
        return $this->category->name . ' - Compify';
    }
};
?>

<section class="section">
    <div class="section-title">
        <h2>{{ $category->name }}</h2>
    </div>

    @if($category->description)
        <p style="text-align: center; margin-bottom: 34px;">{{ $category->description }}</p>
    @endif

    <div class="product-grid">
        @forelse($this->products as $product)
            <x-product-card :product="$product" />
        @empty
            <p>Belum ada produk di kategori ini.</p>
        @endforelse
    </div>

    <div style="margin-top: 34px;">
        {{ $this->products->links() }}
    </div>
</section>