document.addEventListener('DOMContentLoaded', function () {
    // Search
    const searchInput = document.getElementById('customerSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const val = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const name = row.querySelector('.customer-name').textContent.toLowerCase();
                const email = row.querySelector('.customer-email').textContent.toLowerCase();
                const mobile = row.querySelector('.customer-mobile').textContent.toLowerCase();

                if (name.includes(val) || email.includes(val) || mobile.includes(val)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});

function deleteCustomer(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently delete the customer account.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteInputId').value = id;
            document.getElementById('deleteForm').submit();
        }
    });
}
