<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.shop')]
#[Title('Kebijakan Pengiriman - Compify')]
class extends Component {
};
?>

<section class="section shop-soft-section">
    <div class="static-page">
        <p class="section-kicker">Shipping Policy</p>
        <h1>Kebijakan Pengiriman</h1>

        <p>
            Pesanan akan diproses setelah pembayaran dikonfirmasi. Waktu pengiriman dapat berbeda
            tergantung lokasi, jasa ekspedisi, dan ketersediaan produk.
        </p>

        <p>
            Produk akan dikemas dengan aman, terutama untuk komponen komputer seperti motherboard,
            power supply, storage, dan peripheral.
        </p>
    </div>
</section>