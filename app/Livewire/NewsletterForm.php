<?php

namespace App\Livewire;

use Livewire\Component;

class NewsletterForm extends Component
{
    public string $email = '';

    public ?string $message = null;

    public function subscribe(): void
    {
        $this->validate([
            'email' => ['required', 'email', 'max:150'],
        ]);

        // Demo portfolio: email tidak disimpan, tetapi alur validasi dan feedback tetap nyata.
        $this->reset('email');
        $this->message = 'Terima kasih. Kamu masuk daftar update setup dan promo Compify.';
    }

    public function render()
    {
        return view('livewire.newsletter-form');
    }
}
