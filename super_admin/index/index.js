// Super Admin Dashboard JavaScript

// Initialize tooltips if needed
document.addEventListener('DOMContentLoaded', function () {
    // Could add charts or real-time data updates here
    console.log('Super Admin Dashboard loaded');

    // Smooth scroll for action buttons
    const actionButtons = document.querySelectorAll('.btn-outline-danger, .btn-outline-primary, .btn-outline-success');

    actionButtons.forEach(button => {
        button.addEventListener('mouseenter', function () {
            this.style.transition = 'all 0.3s ease';
        });
    });
});
