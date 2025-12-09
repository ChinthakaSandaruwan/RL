// Wishlist Toggle Function
function toggleWishlist(type, itemId, button) {
    const icon = button.querySelector('i');
    const isFilled = icon.classList.contains('bi-heart-fill');
    const action = isFilled ? 'remove' : 'add';

    // Optimistic UI update
    if (isFilled) {
        icon.classList.remove('bi-heart-fill');
        icon.classList.add('bi-heart');
        button.classList.remove('active');
    } else {
        icon.classList.remove('bi-heart');
        icon.classList.add('bi-heart-fill');
        button.classList.add('active');
    }

    // AJAX call
    fetch('/RL/public/wishlist/toggle_wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `type=${type}&item_id=${itemId}&action=${action}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update navbar count
                updateWishlistCount(data.in_wishlist ? 1 : -1);
            } else {
                // Revert on error
                if (isFilled) {
                    icon.classList.add('bi-heart-fill');
                    icon.classList.remove('bi-heart');
                    button.classList.add('active');
                } else {
                    icon.classList.add('bi-heart');
                    icon.classList.remove('bi-heart-fill');
                    button.classList.remove('active');
                }
                alert(data.message || 'Failed to update wishlist');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Revert on error
            if (isFilled) {
                icon.classList.add('bi-heart-fill');
                icon.classList.remove('bi-heart');
                button.classList.add('active');
            } else {
                icon.classList.add('bi-heart');
                icon.classList.remove('bi-heart-fill');
                button.classList.remove('active');
            }
        });
}

// Update Wishlist Count in Navbar
function updateWishlistCount(change) {
    const countBadge = document.getElementById('wishlistCount');
    if (!countBadge) return;

    let currentCount = parseInt(countBadge.textContent) || 0;
    let newCount = Math.max(0, currentCount + change);

    if (newCount > 0) {
        countBadge.textContent = newCount > 99 ? '99+' : newCount;
        countBadge.style.display = '';
    } else {
        countBadge.style.display = 'none';
    }
}
