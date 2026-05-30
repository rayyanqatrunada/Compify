<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.admin')]
#[Title('Pages - Admin Compify')]
class extends Component {
};
?>

<div>
    <div class="admin-page-head">
        <div>
            <p>Content</p>
            <h2>Pages</h2>
        </div>
    </div>

    <div class="admin-panel">
        <h3>Static pages</h3>
        <p>Halaman ini nanti untuk mengatur About Us, Contact, Privacy Policy, Terms, dan informasi bantuan.</p>
    </div>
</div>