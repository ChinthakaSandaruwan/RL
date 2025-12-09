// Room Rental Booking - Dynamic Pricing & Validation
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('bookingForm');
    const checkinInput = document.getElementById('checkin_date');
    const checkoutInput = document.getElementById('checkout_date');
    const guestsInput = document.getElementById('guests');
    const mealSelect = document.getElementById('meal_id');

    // Elements for pricing display
    const nightsCountSpan = document.getElementById('nightsCount');
    const nightsCount2Span = document.getElementById('nightsCount2');
    const roomTotalSpan = document.getElementById('roomTotal');
    const mealTotalSpan = document.getElementById('mealTotal');
    const grandTotalSpan = document.getElementById('grandTotal');
    const mealRow = document.getElementById('mealRow');
    const mealNameSpan = document.getElementById('mealName');

    // Calculate and update pricing
    function updatePricing() {
        const checkinDate = checkinInput.value;
        const checkoutDate = checkoutInput.value;
        const guests = parseInt(guestsInput.value) || 1;
        const selectedMeal = mealSelect ? mealSelect.options[mealSelect.selectedIndex] : null;
        const mealPrice = selectedMeal && selectedMeal.value ? parseFloat(selectedMeal.dataset.price) || 0 : 0;
        const mealName = selectedMeal && selectedMeal.value ? selectedMeal.text : '';

        if (!checkinDate || !checkoutDate) {
            resetPricing();
            return;
        }

        // Calculate number of nights
        const checkin = new Date(checkinDate);
        const checkout = new Date(checkoutDate);
        const timeDiff = checkout.getTime() - checkin.getTime();
        const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));

        if (nights <= 0) {
            resetPricing();
            return;
        }

        // Calculate totals
        const roomTotal = nights * roomData.pricePerDay;
        const mealTotal = mealPrice > 0 ? nights * guests * mealPrice : 0;
        const grandTotal = roomTotal + mealTotal;

        // Update display
        nightsCountSpan.textContent = nights;
        nightsCount2Span.textContent = nights;
        roomTotalSpan.textContent = 'LKR ' + formatNumber(roomTotal.toFixed(2));
        grandTotalSpan.textContent = 'LKR ' + formatNumber(grandTotal.toFixed(2));

        // Show/hide meal row
        if (mealTotal > 0) {
            mealRow.style.display = 'flex';
            mealTotalSpan.textContent = 'LKR ' + formatNumber(mealTotal.toFixed(2));
            mealNameSpan.textContent = mealName;
        } else {
            mealRow.style.display = 'none';
        }

        // Add animation
        animateValue(grandTotalSpan, grandTotal);
    }

    // Reset pricing display
    function resetPricing() {
        nightsCountSpan.textContent = '0';
        nightsCount2Span.textContent = '0';
        roomTotalSpan.textContent = 'LKR 0.00';
        grandTotalSpan.textContent = 'LKR 0.00';
        mealRow.style.display = 'none';
    }

    // Format number with thousand separators
    function formatNumber(number) {
        return parseFloat(number).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Animate number changes
    function animateValue(element, newValue) {
        element.style.transition = 'all 0.3s ease';
        element.style.transform = 'scale(1.05)';
        setTimeout(() => {
            element.style.transform = 'scale(1)';
        }, 300);
    }

    // Event listeners
    if (checkinInput) {
        checkinInput.addEventListener('change', function () {
            // Update minimum checkout date
            const checkinDate = new Date(this.value);
            const minCheckout = new Date(checkinDate);
            minCheckout.setDate(minCheckout.getDate() + 1);

            if (checkoutInput) {
                checkoutInput.min = minCheckout.toISOString().split('T')[0];

                // If checkout is before new minimum, reset it
                if (checkoutInput.value && new Date(checkoutInput.value) <= checkinDate) {
                    checkoutInput.value = '';
                }
            }

            updatePricing();
        });
    }

    if (checkoutInput) {
        checkoutInput.addEventListener('change', updatePricing);
    }

    if (guestsInput) {
        guestsInput.addEventListener('input', function () {
            // Enforce max guests
            const maxGuests = parseInt(this.max);
            if (parseInt(this.value) > maxGuests) {
                this.value = maxGuests;
            }
            if (parseInt(this.value) < 1) {
                this.value = 1;
            }
            updatePricing();
        });
    }

    if (mealSelect) {
        mealSelect.addEventListener('change', updatePricing);
    }

    // Form validation
    if (form) {
        form.addEventListener('submit', function (e) {
            const checkinDate = new Date(checkinInput.value);
            const checkoutDate = new Date(checkoutInput.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Validate dates
            if (checkinDate < today) {
                e.preventDefault();
                showError('Check-in date cannot be in the past.');
                checkinInput.focus();
                return false;
            }

            if (checkoutDate <= checkinDate) {
                e.preventDefault();
                showError('Check-out date must be after check-in date.');
                checkoutInput.focus();
                return false;
            }

            // Validate guests
            const guests = parseInt(guestsInput.value);
            if (guests < 1 || guests > roomData.maxGuests) {
                e.preventDefault();
                showError(`Number of guests must be between 1 and ${roomData.maxGuests}.`);
                guestsInput.focus();
                return false;
            }

            // Add loading state to button
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            }

            return true;
        });
    }

    // Show error message
    function showError(message) {
        // Remove existing error alerts
        const existingAlerts = document.querySelectorAll('.alert-danger');
        existingAlerts.forEach(alert => alert.remove());

        // Create new alert
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Insert before form
        form.parentNode.insertBefore(alertDiv, form);

        // Scroll to alert
        alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Real-time date validation with visual feedback
    function addDateValidation() {
        if (checkinInput) {
            checkinInput.addEventListener('blur', function () {
                const checkinDate = new Date(this.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (this.value && checkinDate < today) {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                } else if (this.value) {
                    this.classList.add('is-valid');
                    this.classList.remove('is-invalid');
                }
            });
        }

        if (checkoutInput) {
            checkoutInput.addEventListener('blur', function () {
                const checkinDate = new Date(checkinInput.value);
                const checkoutDate = new Date(this.value);

                if (this.value && checkoutDate <= checkinDate) {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                } else if (this.value) {
                    this.classList.add('is-valid');
                    this.classList.remove('is-invalid');
                }
            });
        }

        if (guestsInput) {
            guestsInput.addEventListener('blur', function () {
                const guests = parseInt(this.value);
                if (guests >= 1 && guests <= roomData.maxGuests) {
                    this.classList.add('is-valid');
                    this.classList.remove('is-invalid');
                } else {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                }
            });
        }
    }

    addDateValidation();

    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-info)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Add smooth scroll for better UX
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Initialize pricing on page load
    updatePricing();

    // Add entrance animation
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
