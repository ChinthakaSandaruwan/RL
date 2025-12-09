// Notification JavaScript (for future enhancements like real-time updates)

// Auto-refresh notifications every 60 seconds (optional)
// setInterval(() => {
//     location.reload();
// }, 60000);

// Smooth scroll to top
document.addEventListener('DOMContentLoaded', function () {
    const notificationItems = document.querySelectorAll('.notification-item');

    notificationItems.forEach(item => {
        item.addEventListener('click', function (e) {
            // If clicking on the mark-read button, don't do anything else
            if (e.target.closest('button')) return;

            // Could add navigation to related item here
            // e.g., if notification is about a property, navigate to property_view.php?id=X
        });
    });
});
