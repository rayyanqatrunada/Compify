<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.shop')]
#[Title('About Us - Compify')]
class extends Component {
};
?>

<section class="section shop-soft-section">
    <div class="static-page">
        <p class="section-kicker">About Us</p>
        <h1>Compify</h1>

        <p>
            Compify adalah toko perlengkapan komputer yang menyediakan berbagai kebutuhan
            untuk build PC, upgrade komputer, dan setup kerja maupun gaming.
        </p>

        <p>
            Produk yang tersedia meliputi motherboard, processor, VGA/GPU, RAM, SSD,
            power supply, casing, cooling, monitor, keyboard, mouse, headset, dan aksesori komputer lainnya.
        </p>

        <p>
            Website ini dibuat agar pelanggan bisa melihat produk secara rapi, mencari berdasarkan kategori
            dan merk, menyimpan wishlist, serta melihat informasi produk yang dikelola langsung dari admin site.
        </p>
    </div>
</section>