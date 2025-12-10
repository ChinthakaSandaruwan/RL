document.addEventListener('DOMContentLoaded', () => {

    // View Proof Logic
    const proofModal = document.getElementById('proofModal');
    const modalImage = document.getElementById('modalProofImage');

    if (proofModal) {
        const modalPdf = document.getElementById('modalProofPdf');

        proofModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            const fileUrl = button.getAttribute('data-image');

            if (fileUrl.toLowerCase().endsWith('.pdf')) {
                modalImage.style.display = 'none';
                modalPdf.style.display = 'block';
                modalPdf.src = fileUrl;
            } else {
                modalPdf.style.display = 'none';
                modalImage.style.display = 'block';
                modalImage.src = fileUrl;
            }
        });

        // Clear src on close to stop loading/flickering
        proofModal.addEventListener('hidden.bs.modal', () => {
            modalImage.src = '';
            modalPdf.src = '';
        });
    }

    // Approve Confirmation
    document.querySelectorAll('.btn-action-approve').forEach(btn => {
        btn.addEventListener('click', function () {
            Swal.fire({
                title: 'Approve Request?',
                html: "This will:<br>• Activate the user's package immediately<br>• Generate and send an invoice via email",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Approve & Send Invoice',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.closest('form').submit();
                }
            });
        });
    });

    // Reject Confirmation
    document.querySelectorAll('.btn-action-reject').forEach(btn => {
        btn.addEventListener('click', function () {
            Swal.fire({
                title: 'Reject Request?',
                text: "The user will be notified of the rejection.",
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Reject',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.closest('form').submit();
                }
            });
        });
    });
});
