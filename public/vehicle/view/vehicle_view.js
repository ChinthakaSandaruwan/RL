
document.addEventListener('DOMContentLoaded', function () {
    const carouselElement = document.getElementById('vehicleCarousel');
    if (!carouselElement) return;

    const myCarousel = new bootstrap.Carousel(carouselElement);
    const thumbs = document.querySelectorAll('.vehicle-thumb');

    // Function to update active thumbnail
    const updateActiveThumb = (index) => {
        thumbs.forEach((thumb, i) => {
            if (i === index) {
                thumb.classList.add('active-thumb');
                thumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            } else {
                thumb.classList.remove('active-thumb');
            }
        });
    };

    // Listen for slide events to update thumbnail
    carouselElement.addEventListener('slide.bs.carousel', function (event) {
        updateActiveThumb(event.to);
    });

    // Thumbnail click handler
    window.showSlide = function (index) {
        myCarousel.to(index);
        updateActiveThumb(index);
    };
});
