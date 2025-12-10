document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-action').forEach(btn => {
        btn.addEventListener('click', function () {
            const rentId = this.dataset.id;
            const action = this.dataset.action; // 'approve' or 'reject'

            const title = action === 'approve' ? 'Approve Request?' : 'Reject Request?';
            const icon = action === 'approve' ? 'question' : 'warning';
            const confirmBtnColor = action === 'approve' ? '#4F7942' : '#d33';

            Swal.fire({
                title: title,
                text: "Confirm this action regarding the rental request.",
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: confirmBtnColor,
                confirmButtonText: 'Yes, ' + action + ' it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    processRequest(rentId, action);
                }
            });
        });
    });

    function processRequest(rentId, action) {
        const formData = new FormData();
        formData.append('request_action', action);
        formData.append('rent_id', rentId);

        fetch('rent_approval.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error!', 'Something went wrong.', 'error');
            });
    }
});
