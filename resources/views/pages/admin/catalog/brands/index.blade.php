<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.admin')]
#[Title('Brands - Admin Compify')]
class extends Component {
};
?>

<div>
    <div class="admin-page-head">
        <div>
            <p>Catalog</p>
            <h2>Brands</h2>
        </div>
    </div>

    <div class="admin-panel">
        <h3>Brand management</h3>
        <p>Halaman ini nanti untuk mengatur merk produk, logo merk, dan status aktif/nonaktif.</p>
    </div>
</div>