document.addEventListener('DOMContentLoaded', () => {
    const heroCarousel = document.querySelector('#heroCarousel');
    if (heroCarousel) {
        const carousel = new bootstrap.Carousel(heroCarousel, {
            interval: 5000,
            pause: 'hover',
            ride: 'carousel'
        });
    }
});
