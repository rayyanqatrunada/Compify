<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.shop')]
#[Title('Terms and Conditions - Compify')]
class extends Component {
};
?>

<section class="section shop-soft-section">
    <div class="static-page">
        <p class="section-kicker">Terms & Conditions</p>
        <h1>Syarat dan Ketentuan</h1>

        <p>
            Dengan menggunakan website Compify, pengguna setuju untuk mengikuti aturan,
            kebijakan transaksi, dan ketentuan penggunaan yang berlaku.
        </p>

        <p>
            Harga, stok, dan informasi produk dapat berubah sewaktu-waktu sesuai pembaruan dari admin.
        </p>
    </div>
</section>