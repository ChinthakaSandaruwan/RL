function removeFromWishlist(type, itemId, button) {
    Swal.fire({
        title: 'Remove from Wishlist?',
        text: "Are you sure you want to remove this item from your wishlist?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, remove it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const card = button.closest('.wishlist-card').parentElement;
            card.querySelector('.wishlist-card').classList.add('removing');

            fetch('toggle_wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `type=${type}&item_id=${itemId}&action=remove`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Removed!',
                            text: 'Item has been removed from your wishlist.',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        setTimeout(() => {
                            card.remove();
                            // Refresh if no items left
                            const remaining = document.querySelectorAll('.wishlist-card').length;
                            if (remaining === 0) location.reload();
                        }, 300);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to remove item'
                        });
                        card.querySelector('.wishlist-card').classList.remove('removing');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while removing the item'
                    });
                    card.querySelector('.wishlist-card').classList.remove('removing');
                });
        }
    });
}
