<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.admin')]
#[Title('Orders - Admin Compify')]
class extends Component {
};
?>

<div>
    <div class="admin-page-head">
        <div>
            <p>Sales</p>
            <h2>Orders</h2>
        </div>
    </div>

    <div class="admin-panel">
        <h3>Order management</h3>
        <p>Halaman ini nanti untuk melihat pesanan customer, status pembayaran, dan status pengiriman.</p>
    </div>
</div>