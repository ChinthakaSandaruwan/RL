// My Rent JavaScript

document.addEventListener('DOMContentLoaded', function () {
    // Initialize tooltips if any
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });

    // Smooth scroll for rental cards
    const rentalCards = document.querySelectorAll('.rental-card');
    rentalCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });

    // Remember active tab
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabButtons.forEach(button => {
        button.addEventListener('shown.bs.tab', function (e) {
            localStorage.setItem('activeRentalTab', e.target.id);
        });
    });

    // Restore active tab
    const activeTab = localStorage.getItem('activeRentalTab');
    if (activeTab) {
        const tabButton = document.getElementById(activeTab);
        if (tabButton) {
            const tab = new bootstrap.Tab(tabButton);
            tab.show();
        }
    }
});
