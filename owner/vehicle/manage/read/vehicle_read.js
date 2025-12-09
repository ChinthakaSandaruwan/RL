// Vehicle Read - Image Gallery Handler

document.addEventListener('DOMContentLoaded', function () {

    // Gallery Thumbnail Click Handler
    const thumbnails = document.querySelectorAll('.gallery-thumb');
    const mainImage = document.getElementById('mainImage');

    if (mainImage && thumbnails.length > 0) {
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function () {
                // Fade effect
                mainImage.style.opacity = '0.7';

                setTimeout(() => {
                    mainImage.src = this.src;
                    mainImage.style.opacity = '1';
                }, 150);
            });
        });
    }
});
