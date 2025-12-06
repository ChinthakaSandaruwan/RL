// Image preview and validation
document.getElementById('propertyImages').addEventListener('change', function (e) {
    const files = e.target.files;
    const previewContainer = document.getElementById('imagePreviewContainer');
    const primaryImageInput = document.getElementById('primaryImageIndex');

    // Clear previous previews
    previewContainer.innerHTML = '';

    // Validate count
    if (files.length < 3) {
        alert('Please select at least 3 images');
        e.target.value = '';
        previewContainer.style.display = 'none';
        return;
    }

    if (files.length > 15) {
        alert('Maximum 15 images allowed');
        e.target.value = '';
        previewContainer.style.display = 'none';
        return;
    }

    // Show preview container
    previewContainer.style.display = 'flex';

    // Create previews
    Array.from(files).forEach((file, index) => {
        // Validate file type
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            alert(`Invalid file type: ${file.name}`);
            e.target.value = '';
            previewContainer.innerHTML = '';
            previewContainer.style.display = 'none';
            return;
        }

        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert(`File too large: ${file.name} (Max 5MB)`);
            e.target.value = '';
            previewContainer.innerHTML = '';
            previewContainer.style.display = 'none';
            return;
        }

        const reader = new FileReader();
        reader.onload = function (event) {
            const col = document.createElement('div');
            col.className = 'col-md-3 col-6';

            const card = document.createElement('div');
            card.className = 'image-preview-card';
            if (index === 0) card.classList.add('primary');
            card.dataset.index = index;

            const img = document.createElement('img');
            img.src = event.target.result;
            img.alt = file.name;

            const badge = document.createElement('div');
            badge.className = 'primary-badge';
            badge.textContent = 'PRIMARY';

            const setPrimaryBtn = document.createElement('button');
            setPrimaryBtn.type = 'button';
            setPrimaryBtn.className = 'btn btn-sm btn-primary set-primary-btn';
            setPrimaryBtn.textContent = 'Set as Primary';
            setPrimaryBtn.onclick = function () {
                // Remove primary class from all
                document.querySelectorAll('.image-preview-card').forEach(c => c.classList.remove('primary'));
                // Add to this one
                card.classList.add('primary');
                // Update hidden input
                primaryImageInput.value = index;
            };

            card.appendChild(img);
            card.appendChild(badge);
            card.appendChild(setPrimaryBtn);
            col.appendChild(card);
            previewContainer.appendChild(col);
        };
        reader.readAsDataURL(file);
    });
});
