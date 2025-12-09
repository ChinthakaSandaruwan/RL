// Customer Management JavaScript

// Populate Edit Modal
function editCustomer(user) {
    document.getElementById('edit_user_id').value = user.user_id;
    document.getElementById('edit_name').value = user.name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_mobile').value = user.mobile_number;
    document.getElementById('edit_status_id').value = user.status_id;
    
    // Open Modal
    new bootstrap.Modal(document.getElementById('editCustomerModal')).show();
}

// Delete Confirmation
function deleteCustomer(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will delete the customer account. This cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete_user_id').value = id;
            document.getElementById('deleteForm').submit();
        }
    });
}
