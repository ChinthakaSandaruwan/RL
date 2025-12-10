// JavaScript for backup page interactions
document.addEventListener('DOMContentLoaded', function () {
    // Optional: Add loading state to button on submit
    const form = document.querySelector('form');
    const btn = document.querySelector('button[type="submit"]');

    if (form && btn) {
        form.addEventListener('submit', function () {
            // Use setTimeout to allow the form submission to start before changing UI
            // preventing the 'disabled' state from excluding the button from POST data
            // although we now have a hidden input as backup.
            setTimeout(() => {
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Generating...';
                btn.classList.add('disabled'); // Use class instead of property to keep it intuitive but safe
                btn.style.pointerEvents = 'none';

                // Re-enable after a short delay (simulating download start)
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.classList.remove('disabled');
                    btn.style.pointerEvents = 'auto';
                }, 3000);
            }, 50);
        });
    }
});
