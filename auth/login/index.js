document.addEventListener('DOMContentLoaded', () => {
    // 1. Show alerts for server messages (Success/Error)
    if (window.serverMessages) {
        if (window.serverMessages.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: window.serverMessages.success,
                confirmButtonColor: '#3a5a40',
                background: '#fff',
                color: '#344e41'
            });
        }

        if (window.serverMessages.errors && window.serverMessages.errors.length > 0) {
            const errorHtml = window.serverMessages.errors.join('<br>');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: errorHtml,
                confirmButtonColor: '#3a5a40',
                background: '#fff',
                color: '#344e41'
            });
        }
    }

    // 2. Countdown Timer for Resend OTP
    const resendBtn = document.getElementById('resendBtn');
    const countdownTimer = document.getElementById('countdownTimer');

    if (resendBtn && countdownTimer) {
        // Check if there is a saved timestamp in localStorage
        const storedEndTime = localStorage.getItem('otp_resend_end_time');
        let endTime;

        if (storedEndTime && new Date(storedEndTime) > new Date()) {
            // Use stored time if it's still in the future
            endTime = new Date(storedEndTime);
        } else {
            // Otherwise set new 1 minute timer
            endTime = new Date(new Date().getTime() + 60000); // 1 minute from now
            localStorage.setItem('otp_resend_end_time', endTime);
        }

        function updateTimer() {
            const now = new Date();
            const timeRemaining = endTime - now;

            if (timeRemaining <= 0) {
                // Time is up
                clearInterval(interval);
                resendBtn.disabled = false;
                resendBtn.classList.remove('text-muted');
                resendBtn.classList.add('text-primary-theme'); // Use a brighter color if available
                resendBtn.style.cursor = 'pointer';
                countdownTimer.textContent = '';
                localStorage.removeItem('otp_resend_end_time'); // Clear storage
            } else {
                // Update countdown text
                const seconds = Math.floor((timeRemaining % (1000 * 60)) / 1000);
                countdownTimer.textContent = `(${seconds}s)`;
                resendBtn.disabled = true;
                resendBtn.style.cursor = 'not-allowed';
            }
        }

        // Run immediately and then every second
        updateTimer();
        const interval = setInterval(updateTimer, 1000);

        // Reset timer on button click (when form submits, page reloads, but just in case of AJAX later)
        resendBtn.addEventListener('click', () => {
            // Let the form submit normally, the PHP reload will restart logic
            // But we can preemptively clear/reset storage if needed. 
            // Actually, since the page reloads, the new 'storedEndTime' logic 
            // in the next load needs to know to START FRESH.
            // We should clear the old one just before submit or assume the reload handles it.
            // The simplest way for a Reload scenario is to remove the Item so the next page load re-sets it.
            localStorage.removeItem('otp_resend_end_time');
        });
    }
});
