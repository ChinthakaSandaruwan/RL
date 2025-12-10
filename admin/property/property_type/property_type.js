function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Add Property Type';
    document.getElementById('formAction').value = 'add';
    document.getElementById('typeId').value = '';
    document.getElementById('typeName').value = '';

    var myModal = new bootstrap.Modal(document.getElementById('typeModal'));
    myModal.show();
}

function openEditModal(id, name) {
    document.getElementById('modalTitle').innerText = 'Edit Property Type';
    document.getElementById('formAction').value = 'update';
    document.getElementById('typeId').value = id;
    document.getElementById('typeName').value = name;

    var myModal = new bootstrap.Modal(document.getElementById('typeModal'));
    myModal.show();
}

function confirmDelete(id, usageCount) {
    if (usageCount > 0) {
        Swal.fire({
            icon: 'error',
            title: 'Cannot Delete',
            text: `This property type is currently assigned to ${usageCount} properties. Update those properties first.`,
            confirmButtonColor: '#588157'
        });
        return;
    }

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
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').submit();
        }
    })
}
