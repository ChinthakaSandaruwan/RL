document.addEventListener('DOMContentLoaded', () => {
    // Show Server Messages
    if (window.serverMessages) {
        if (window.serverMessages.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: window.serverMessages.success,
                confirmButtonColor: '#588157'
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
});
