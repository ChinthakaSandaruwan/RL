document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('ownerSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const val = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const name = row.querySelector('.owner-name').textContent.toLowerCase();
                const email = row.querySelector('.owner-email').textContent.toLowerCase();
                const mobile = row.querySelector('.owner-mobile').textContent.toLowerCase();
                if (name.includes(val) || email.includes(val) || mobile.includes(val)) row.style.display = '';
                else row.style.display = 'none';
            });
        });
    }
});

function deleteOwner(id) {
    Swal.fire({
        title: 'Delete Owner?',
        text: "This action cannot be undone. Ensure the owner has no active listings or handle them first.",
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
