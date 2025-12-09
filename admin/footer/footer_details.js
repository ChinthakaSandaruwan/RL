document.addEventListener('DOMContentLoaded', function () {
    // Check for Flash Messages passed from PHP
    if (window.flashMessage) {
        if (window.flashMessage.success) {
            Swal.fire({
                icon: 'success',
                title: 'Saved!',
                text: window.flashMessage.success,
                timer: 2000,
                showConfirmButton: false,
                position: 'center'
            });
        }

        if (window.flashMessage.error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: window.flashMessage.error,
                confirmButtonColor: '#d33'
            });
        }
    }
});
