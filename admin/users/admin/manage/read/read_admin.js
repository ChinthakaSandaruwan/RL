// Read Admin JS
document.addEventListener('DOMContentLoaded', function () {
    // Search Functionality
    const searchInput = document.getElementById('adminSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const searchText = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const name = row.querySelector('.admin-name').textContent.toLowerCase();
                const email = row.querySelector('.admin-email').textContent.toLowerCase();

                if (name.includes(searchText) || email.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});

function deleteAdmin(adminId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create form to submit delete request
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = ''; // Submit to current page

            const inputAction = document.createElement('input');
            inputAction.type = 'hidden';
            inputAction.name = 'action';
            inputAction.value = 'delete';

            const inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'admin_id';
            inputId.value = adminId;

            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            const inputCsrf = document.createElement('input');
            inputCsrf.type = 'hidden';
            inputCsrf.name = 'csrf_token';
            inputCsrf.value = csrfToken;

            form.appendChild(inputAction);
            form.appendChild(inputId);
            form.appendChild(inputCsrf);
            document.body.appendChild(form);
            form.submit();
        }
    });
}
