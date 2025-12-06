document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({
        icon: 'success',
        title: 'Logged Out',
        text: 'You have been successfully logged out.',
        timer: 1500,
        showConfirmButton: false
    }).then(() => {
        window.location.href = window.homeUrl;
    });
});
