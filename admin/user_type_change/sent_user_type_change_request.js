document.addEventListener('DOMContentLoaded', () => {
    // Show Server Messages
    if (window.serverMessages) {
        if (window.serverMessages.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: window.serverMessages.success,
                confirmButtonColor: '#588157'
            }).then(() => {
                location.reload();
            });
        }

        if (window.serverMessages.errors && window.serverMessages.errors.length > 0) {
            const errorHtml = window.serverMessages.errors.join('<br>');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: errorHtml,
                confirmButtonColor: '#588157'
            });
        }
    }

    // Approve Button Click
    document.querySelectorAll('.approve-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            Swal.fire({
                title: 'Approve Request?',
                text: "This will upgrade the user to Owner status.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#588157',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, approve!'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.closest('form').submit();
                }
            });
        });
    });

    // Reject Button Click
    document.querySelectorAll('.reject-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            Swal.fire({
                title: 'Reject Request?',
                text: "This action cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, reject'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.closest('form').submit();
                }
            });
        });
    });
});
