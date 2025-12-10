document.addEventListener('DOMContentLoaded', function () {

    // Flatpickr Configuration
    const today = new Date();

    const pickupPicker = flatpickr("#pickup_date", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        minDate: "today",
        onChange: function (selectedDates, dateStr, instance) {
            dropoffPicker.set('minDate', dateStr);
            calculateTotal();
        }
    });

    const dropoffPicker = flatpickr("#dropoff_date", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        minDate: "today",
        onChange: function (selectedDates, dateStr, instance) {
            calculateTotal();
        }
    });

    // Driver Checkbox
    const driverCheck = document.getElementById('with_driver');
    if (driverCheck) {
        driverCheck.addEventListener('change', calculateTotal);
    }

    // Calculation Logic
    function calculateTotal() {
        const pickupStr = document.getElementById('pickup_date').value;
        const dropoffStr = document.getElementById('dropoff_date').value;

        // Elements to update
        const daysLabel = document.getElementById('summary_days');
        const rentLabel = document.getElementById('summary_rent');
        const driverRow = document.getElementById('driver_row');
        const driverLabel = document.getElementById('summary_driver');
        const totalLabel = document.getElementById('summary_total');

        if (!pickupStr || !dropoffStr) {
            return;
        }

        const pDate = new Date(pickupStr);
        const dDate = new Date(dropoffStr);

        if (dDate <= pDate) {
            // Invalid range, waiting for user to fix
            daysLabel.textContent = "Invalid Dates";
            totalLabel.textContent = "-";
            return;
        }

        // Calculate Days (24h blocks or Calendar Days? The PHP logic used ceiled days. Matching JS)
        const diffTime = Math.abs(dDate - pDate);
        let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        if (diffDays < 1) diffDays = 1;

        // Base Cost
        let rentCost = diffDays * PRICE_PER_DAY;
        let driverCost = 0;

        // Driver Cost
        if (driverCheck && driverCheck.checked) {
            driverCost = diffDays * DRIVER_COST;
            if (driverRow) driverRow.style.display = 'flex';
        } else {
            if (driverRow) driverRow.style.display = 'none';
        }

        const totalCost = rentCost + driverCost;

        // Update UI
        daysLabel.textContent = diffDays + (diffDays === 1 ? " Day" : " Days");
        rentLabel.textContent = "LKR " + formatMoney(rentCost);
        if (driverLabel) driverLabel.textContent = "LKR " + formatMoney(driverCost);
        totalLabel.textContent = "LKR " + formatMoney(totalCost);
    }

    function formatMoney(amount) {
        return amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

});
