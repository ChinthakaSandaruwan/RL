// Owner Status Management JavaScript

function openStatusModal(owner) {
    // Populate modal with owner data
    document.getElementById('modal_user_id').value = owner.user_id;
    document.getElementById('modal_owner_name').textContent = owner.display_name;
    document.getElementById('modal_owner_email').textContent = owner.email;

    // Show current status with badge
    const statusClass = getStatusClass(owner.status_name);
    document.getElementById('modal_current_status').innerHTML =
        `<span class="badge bg-${statusClass}">${capitalizeFirst(owner.status_name)}</span>`;

    // Open modal
    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    modal.show();
}

function getStatusClass(statusName) {
    const statusMap = {
        'active': 'success',
        'inactive': 'warning',
        'banned': 'danger',
        'suspended': 'danger'
    };
    return statusMap[statusName] || 'secondary';
}

function capitalizeFirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Form validation
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('statusForm');

    if (form) {
        form.addEventListener('submit', function (e) {
            const statusSelect = form.querySelector('[name="status_id"]');

            if (!statusSelect.value) {
                e.preventDefault();
                alert('Please select a new status');
                return false;
            }

            // Optional: Confirm before submitting
            const confirmed = confirm('Are you sure you want to change this owner\'s status?');
            if (!confirmed) {
                e.preventDefault();
            }
        });
    }
});
