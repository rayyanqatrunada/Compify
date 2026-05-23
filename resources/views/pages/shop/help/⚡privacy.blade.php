<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.shop')]
#[Title('Privacy Policy - Compify')]
class extends Component {
};
?>

<section class="section shop-soft-section">
    <div class="static-page">
        <p class="section-kicker">Privacy Policy</p>
        <h1>Kebijakan Privasi</h1>

        <p>
            Compify menjaga data pengguna seperti nama, email, alamat, dan informasi pesanan
            agar hanya digunakan untuk keperluan transaksi dan pelayanan.
        </p>

        <p>
            Data pengguna tidak akan dibagikan ke pihak lain tanpa alasan yang jelas dan diperlukan.
        </p>
    </div>
</section>