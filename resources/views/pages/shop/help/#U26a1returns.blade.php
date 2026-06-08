<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.shop')]
#[Title('Pengembalian Produk - Compify')]
class extends Component {
};
?>

<section class="section shop-soft-section">
    <div class="static-page">
        <p class="section-kicker">Returns Policy</p>
        <h1>Pengembalian Produk</h1>

        <p>
            Pengembalian produk dapat dilakukan jika produk yang diterima tidak sesuai,
            rusak saat diterima, atau terdapat kesalahan pengiriman.
        </p>

        <p>
            Produk harus dikembalikan dalam kondisi lengkap, termasuk kemasan, aksesoris,
            dan bukti pembelian.
        </p>
    </div>
</section>