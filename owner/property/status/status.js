document.addEventListener('DOMContentLoaded', function () {
    const actions = document.querySelectorAll('.status-action');
    actions.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const id = this.dataset.id;
            const status = this.dataset.status;

            // Map status ID to name for better confirmation msg
            // 1: Available, 2: Rented, 3: Unavailable
            const statusMap = {
                '1': 'Available',
                '2': 'Rented',
                '3': 'Unavailable'
            };
            const sName = statusMap[status] || 'Active';

            Swal.fire({
                title: 'Update Status?',
                text: `Change listing status to "${sName}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4F7942', // Fern
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, update it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    updateStatus(id, status);
                }
            });
        });
    });

    function updateStatus(id, newStatus) {
        const fd = new FormData();
        fd.append('action', 'update_status');
        fd.append('id', id);
        fd.append('status_id', newStatus);

        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        fd.append('csrf_token', token);

        fetch('status.php', {
            method: 'POST',
            body: fd
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Updated!',
                        text: 'Status has been updated successfully.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    })
                        .then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Update failed', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Something went wrong. Check console.', 'error');
            });
    }
});
