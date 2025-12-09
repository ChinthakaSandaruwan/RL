function removeFromWishlist(type, itemId, button) {
    if (!confirm('Remove this item from your wishlist?')) return;

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
                setTimeout(() => {
                    card.remove();
                    // Refresh if no items left
                    const remaining = document.querySelectorAll('.wishlist-card').length;
                    if (remaining === 0) location.reload();
                }, 300);
            } else {
                alert(data.message || 'Failed to remove item');
                card.querySelector('.wishlist-card').classList.remove('removing');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
            card.querySelector('.wishlist-card').classList.remove('removing');
        });
}
