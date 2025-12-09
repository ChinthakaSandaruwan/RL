
document.addEventListener('DOMContentLoaded', function () {
    // Edit Brand Modal
    const editBrandModal = document.getElementById('editBrandModal');
    if (editBrandModal) {
        editBrandModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');

            const modalTitle = editBrandModal.querySelector('.modal-title');
            const idInput = editBrandModal.querySelector('#edit_brand_id');
            const nameInput = editBrandModal.querySelector('#edit_brand_name');

            idInput.value = id;
            nameInput.value = name;
        });
    }

    // Edit Model Modal
    const editModelModal = document.getElementById('editModelModal');
    if (editModelModal) {
        editModelModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const brandId = button.getAttribute('data-brand-id');

            const idInput = editModelModal.querySelector('#edit_model_id');
            const nameInput = editModelModal.querySelector('#edit_model_name');
            const brandSelect = editModelModal.querySelector('#edit_brand_select');

            idInput.value = id;
            nameInput.value = name;
            brandSelect.value = brandId;
        });
    }

    // Auto-dismiss alerts
    setTimeout(function () {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});

function confirmDelete(type) {
    return confirm(`Are you sure you want to delete this ${type}? This action cannot be undone.`);
}
