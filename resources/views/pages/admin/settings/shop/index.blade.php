<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.admin')]
#[Title('Shop Settings - Admin Compify')]
class extends Component {
};
?>

<div>
    <div class="admin-page-head">
        <div>
            <p>Settings</p>
            <h2>Shop Settings</h2>
        </div>
    </div>

    <div class="admin-panel">
        <h3>General settings</h3>
        <p>Halaman ini nanti untuk mengatur nama toko, logo, kontak, footer, dan sosial media.</p>
    </div>
</div>