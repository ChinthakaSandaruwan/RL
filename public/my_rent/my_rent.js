// My Rent JavaScript

document.addEventListener('DOMContentLoaded', function () {
    console.log('My Rent JS Loaded');

    // Check if Bootstrap is loaded
    if (typeof bootstrap !== 'undefined') {
        // Initialize tooltips if any
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(tooltip => {
            new bootstrap.Tooltip(tooltip);
        });
        console.log('Bootstrap initialized in my_rent.js');
    } else {
        console.error('Bootstrap 5 not found!');
    }

    // Smooth scroll animation for rental cards
    const rentalCards = document.querySelectorAll('.rental-card');
    rentalCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
});
