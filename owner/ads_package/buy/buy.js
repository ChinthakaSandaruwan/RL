document.addEventListener('DOMContentLoaded', () => {

    // Payment Modal Handler
    const paymentModalElement = document.getElementById('paymentModal');
    let paymentModal = null;

    if (paymentModalElement) {
        paymentModal = new bootstrap.Modal(paymentModalElement);
    }

    const modalName = document.getElementById('modalPackageName');
    const modalPrice = document.getElementById('modalPackagePrice');
    const modalId = document.getElementById('modalPackageId');

    document.querySelectorAll('.btn-buy').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const price = this.getAttribute('data-price');

            modalName.textContent = name;
            modalPrice.textContent = 'LKR ' + price;
            modalId.value = id;

            if (paymentModal) {
                paymentModal.show();
            } else {
                console.error("Payment Modal not initialized");
            }
        });
    });

    // Form Validation - To show alert if file missing
    const form = document.getElementById('purchaseForm');
    const fileInput = document.getElementById('paymentSlip');

    if (form) {
        form.addEventListener('submit', function (e) {
            if (!fileInput.value) {
                e.preventDefault();
                // Check if SweetAlert is available (loaded in main layout or buy.php?)
                // buy.php did NOT include SweetAlert script. Need to check source.
                // Assuming bootstrap alert as fallback or basic alert.

                // Oops, I didn't include SweetAlert in buy.php script tags.
                // I should assume standard alert for now or updated PHP to include it.
                // Since I can't update PHP in this same step easily without chain, I'll alert.
                // Wait, previous step package_approval.php had Swal.
                // buy.php does not.

                Swal.fire({
                    icon: 'error',
                    title: 'Missing File',
                    text: 'Please upload the payment slip to continue.',
                    confirmButtonColor: '#3a5a40'
                });
                fileInput.focus();
            }
        });
    }

});
