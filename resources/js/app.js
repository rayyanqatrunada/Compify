// animasi nav sticky

//func tombol up scroll

document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('scrollTopBtn');

    if (!button) return;

    window.addEventListener('scroll', () => {
        if (window.scrollY > 500) {
            button.classList.add('show');
        } else {
            button.classList.remove('show');
        }
    });

    button.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});

// csrf wishlist button 
document.addEventListener('submit', async function (event) {
    const form = event.target.closest('.js-wishlist-form');

    if (!form) {
        return;
    }

    event.preventDefault();

    const button = form.querySelector('.js-wishlist-button');
    const icon = form.querySelector('.js-wishlist-icon');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!button || button.disabled) {
        return;
    }

    button.disabled = true;
    button.classList.add('is-loading');

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new FormData(form),
        });

        if (!response.ok) {
            throw new Error('Wishlist request failed');
        }

        const data = await response.json();

        button.classList.toggle('active', Boolean(data.wishlisted));

        if (icon) {
            icon.textContent = data.wishlisted ? '♥' : '♡';
        }

        document.querySelectorAll('[data-wishlist-count]').forEach((counter) => {
            counter.textContent = data.count ?? 0;
        });
    } catch (error) {
        console.error(error);
        alert('Wishlist gagal diperbarui. Silakan coba lagi.');
    } finally {
        button.disabled = false;
        button.classList.remove('is-loading');
    }
});

// product card combo
document.addEventListener('click', function (event) {
    const card = event.target.closest('.js-clickable-card');

    if (!card) {
        return;
    }

    // Kalau yang diklik memang link/button/form, jangan ganggu.
    if (event.target.closest('a, button, input, select, textarea, form')) {
        return;
    }

    const href = card.dataset.href;

    if (!href) {
        return;
    }

    if (window.Livewire && typeof window.Livewire.navigate === 'function') {
        window.Livewire.navigate(href);
        return;
    }

    window.location.href = href;
});