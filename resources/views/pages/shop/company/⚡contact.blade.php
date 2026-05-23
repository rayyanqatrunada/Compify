<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.shop')]
#[Title('Contact - Compify')]
class extends Component {
};
?>

<section class="section shop-soft-section">
    <div class="static-page">
        <p class="section-kicker">Contact</p>
        <h1>Hubungi Kami</h1>

        <p>
            Punya pertanyaan tentang produk, stok, garansi, atau rekomendasi komponen?
            Kamu bisa menghubungi tim Compify melalui kontak berikut.
        </p>

        <div class="static-info-grid">
            <div>
                <h3>Email</h3>
                <p>support@compify.test</p>
            </div>

            <div>
                <h3>WhatsApp</h3>
                <p>08xx-xxxx-xxxx</p>
            </div>

            <div>
                <h3>Alamat</h3>
                <p>Indonesia</p>
            </div>
        </div>
    </div>
</section>