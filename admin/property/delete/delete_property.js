function confirmDelete(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this! The owner's package quota will be incremented.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').submit();
        }
    });
}
