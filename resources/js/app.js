// animasi nav sticky

document.addEventListener('livewire:navigated', () => {
    const navbar = document.querySelector('.main-nav');
    const hero = document.querySelector('.hero-slider');

    if (!navbar || !hero) return;

    window.addEventListener('scroll', () => {
        const heroBottom = hero.offsetTop + hero.offsetHeight;

        navbar.classList.toggle(
            'is-sticky',
            window.scrollY >= heroBottom
        );
    });
});

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