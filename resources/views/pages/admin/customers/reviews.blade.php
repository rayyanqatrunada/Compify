<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.admin')]
#[Title('Reviews')]
class extends Component {};
?>

<div class="admin-page-v2">
    <div class="admin-section-title-v2">
        <h2>Reviews</h2>
        <p>Halaman review produk. Fitur ini bisa dihubungkan setelah tabel reviews dibuat.</p>
    </div>

    <div class="admin-panel-v2">
        <div class="admin-empty-v2">
            Belum ada data review.
        </div>
    </div>
</div>