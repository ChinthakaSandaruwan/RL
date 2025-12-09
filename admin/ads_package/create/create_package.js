// Create Package JavaScript

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('packageForm');

    if (form) {
        form.addEventListener('submit', function (e) {
            const maxProps = parseInt(document.querySelector('[name="max_properties"]').value) || 0;
            const maxRooms = parseInt(document.querySelector('[name="max_rooms"]').value) || 0;
            const maxVehicles = parseInt(document.querySelector('[name="max_vehicles"]').value) || 0;

            if (maxProps === 0 && maxRooms === 0 && maxVehicles === 0) {
                e.preventDefault();
                alert('Please set at least one listing limit (Properties, Rooms, or Vehicles)');
                return false;
            }
        });
    }
});
