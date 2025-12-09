function changeMainImage(src) {
    const mainImg = document.getElementById('mainImage');
    // Simple fade effect
    mainImg.style.opacity = '0.7';
    setTimeout(() => {
        mainImg.src = src;
        mainImg.style.opacity = '1';
    }, 150);
}
