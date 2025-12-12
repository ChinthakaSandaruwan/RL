document.addEventListener('DOMContentLoaded', () => {
    if (window.serverMessages) {
        if (window.serverMessages.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: window.serverMessages.success,
                confirmButtonColor: '#3a5a40',
                background: '#fff',
                color: '#344e41',
                confirmButtonText: 'Login Now'
            }).then((result) => {
                if (result.isConfirmed) {
                    let url = '../login/index.php';
                    if (window.serverMessages.mobile) {
                        url += '?mobile=' + encodeURIComponent(window.serverMessages.mobile);
                    }
                    window.location.href = url;
                }
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
});
