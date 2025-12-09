// Delete Package JavaScript

document.addEventListener('DOMContentLoaded', function () {
    const deleteForms = document.querySelectorAll('.delete-form');

    deleteForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const type = this.getAttribute('data-type');
            let message = '';

            if (type === 'soft') {
                message = 'Are you sure you want to deactivate this package?\n\nIt will be hidden from owners but data will be preserved.';
            } else if (type === 'hard') {
                message = 'WARNING: This will PERMANENTLY delete the package!\n\nThis action cannot be undone.\n\nAre you absolutely sure?';
            }

            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
});
