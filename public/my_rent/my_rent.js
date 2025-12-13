// My Rent - Simple page with no filtering

document.addEventListener('DOMContentLoaded', function () {
    console.log('My Rent Page Loaded');

    // Smooth scroll animation for rental cards
    const rentalCards = document.querySelectorAll('.rental-card');
    rentalCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
});
