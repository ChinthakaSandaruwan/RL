document.addEventListener('DOMContentLoaded', () => {
    // Show Server Messages
    if (window.serverMessages) {
        if (window.serverMessages.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: window.serverMessages.success,
                confirmButtonColor: '#3a5a40',
                background: '#fff',
                color: '#344e41'
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

    // Profile Image Preview
    const profileImageInput = document.getElementById('profileImageInput');
    const profileImagePreview = document.getElementById('profileImagePreview');
    const removeImageBtn = document.getElementById('removeImageBtn');
    const deleteImageForm = document.getElementById('deleteImageForm');

    if (profileImageInput && profileImagePreview) {
        profileImageInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                // Validation (Size < 2MB, Type)
                const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    Swal.fire('Error', 'Invalid file type. Only JPG, PNG, and WEBP are allowed.', 'error');
                    this.value = ''; // Clear input
                    return;
                }
                if (file.size > 2 * 1024 * 1024) {
                    Swal.fire('Error', 'File size exceeds 2MB.', 'error');
                    this.value = ''; // Clear input
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    profileImagePreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // Remove Image Confirmation
    if (removeImageBtn && deleteImageForm) {
        removeImageBtn.addEventListener('click', function () {
            Swal.fire({
                title: 'Remove Profile Image?',
                text: "You are about to remove your profile photo. This cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, remove it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteImageForm.submit();
                }
            });
        });
    }

    // Delete Account Confirmation
    const deleteBtn = document.getElementById('deleteAccountBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const form = document.getElementById('deleteForm');

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this! Your account will be permanently deleted.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef476f',
                cancelButtonColor: '#a3b18a',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    }
});
