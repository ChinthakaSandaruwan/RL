// Room Rental Booking - Dynamic Pricing & Validation
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('rentForm');
    const checkinInput = document.getElementById('checkin_date');
    const checkoutInput = document.getElementById('checkout_date');
    const guestsInput = document.getElementById('guests');

    // Elements for pricing display
    const summaryNights = document.getElementById('summary_nights');
    const summaryTotal = document.getElementById('summary_total');

    // Calculate and update pricing
    function updatePricing() {
        const checkinDate = checkinInput.value;
        const checkoutDate = checkoutInput.value;
        const guests = parseInt(guestsInput.value) || 1;

        // PRICE_PER_NIGHT is defined in the php file script tag
        const pricePerNight = typeof PRICE_PER_NIGHT !== 'undefined' ? PRICE_PER_NIGHT : 0;

        if (!checkinDate || !checkoutDate) {
            resetPricing();
            return;
        }

        // Calculate number of nights
        const checkin = new Date(checkinDate);
        const checkout = new Date(checkoutDate);

        // Set time to noon to avoid timezone/daylight saving issues
        checkin.setHours(12, 0, 0, 0);
        checkout.setHours(12, 0, 0, 0);

        const timeDiff = checkout.getTime() - checkin.getTime();
        let nights = Math.ceil(timeDiff / (1000 * 3600 * 24));

        if (nights <= 0) {
            resetPricing();
            return;
        }

        // Calculate total
        const grandTotal = nights * pricePerNight;

        // Update display
        if (summaryNights) summaryNights.textContent = nights + (nights === 1 ? ' Night' : ' Nights');
        if (summaryTotal) summaryTotal.textContent = 'LKR ' + formatNumber(grandTotal.toFixed(2));
    }

    // Reset pricing display
    function resetPricing() {
        if (summaryNights) summaryNights.textContent = '0 Nights';
        if (summaryTotal) summaryTotal.textContent = 'LKR 0.00';
    }

    // Format number with thousand separators
    function formatNumber(number) {
        return parseFloat(number).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Event listeners
    if (checkinInput) {
        checkinInput.addEventListener('change', function () {
            if (this.value) {
                // Update minimum checkout date to checkin + 1 day
                const checkin = new Date(this.value);
                const nextDay = new Date(checkin);
                nextDay.setDate(checkin.getDate() + 1);

                if (checkoutInput) {
                    checkoutInput.min = nextDay.toISOString().split('T')[0];

                    // If current checkout is invalid, clear it
                    if (checkoutInput.value && new Date(checkoutInput.value) <= checkin) {
                        checkoutInput.value = '';
                    }
                }
            }
            updatePricing();
        });
    }

    if (checkoutInput) {
        checkoutInput.addEventListener('change', updatePricing);
    }

    if (guestsInput) {
        guestsInput.addEventListener('input', updatePricing);
    }

    // Form submission validation
    if (form) {
        form.addEventListener('submit', function (e) {
            const checkinDate = new Date(checkinInput.value);
            const checkoutDate = new Date(checkoutInput.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Basic validation
            if (checkinInput.value && checkinDate < today) {
                e.preventDefault();
                alert('Check-in date cannot be in the past.');
                return false;
            }

            if (checkoutInput.value && checkoutDate <= checkinDate) {
                e.preventDefault();
                alert('Check-out date must be after check-in date.');
                return false;
            }

            // Allow form submission
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Book Now';
            }
        });
    }

    // Initialize pricing on page load if values exist
    updatePricing();
});
