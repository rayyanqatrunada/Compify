<?php

use App\Models\HomeSection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.admin')]
#[Title('Home Category Products - Admin Compify')]
class extends Component {
    #[Computed]
    public function totalCategoryProductSections(): int
    {
        return HomeSection::where('section_type', 'category_products')->count();
    }
};
?>

<div class="admin-page-v2">
    <div class="admin-section-title-v2">
        <h2>Home Category Products</h2>
        <p>Halaman dummy sementara untuk route <code>admin.content.home-category-products</code>.</p>
    </div>

    <div class="admin-panel-v2">
        <h3>Status sementara</h3>
        <p>
            Route ini sudah aman dibuka. Form pengelolaan khusus kategori produk belum dibuat pada tahap stabilisasi,
            jadi halaman ini sengaja dibuat dummy agar navigasi admin tidak error.
        </p>

        <p><strong>Total section kategori produk:</strong> {{ $this->totalCategoryProductSections }}</p>
    </div>
</div>
