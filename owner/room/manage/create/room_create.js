document.addEventListener('DOMContentLoaded', function () {

    // 1. Image Preview & Primary Selection
    const roomImgInput = document.getElementById('roomImages');
    const previewContainer = document.getElementById('imagePreviewContainer');
    const primaryIndexInput = document.getElementById('primaryImageIndex');

    if (roomImgInput) {
        roomImgInput.addEventListener('change', function (event) {
            previewContainer.innerHTML = ''; // Clear prev
            previewContainer.style.display = 'flex';
            const files = Array.from(event.target.files);

            if (files.length > 0) {
                files.forEach((file, index) => {
                    if (!file.type.startsWith('image/')) return;

                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const colProxy = document.createElement('div');
                        colProxy.className = 'col-auto position-relative';

                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'img-preview shadow-sm';
                        img.dataset.index = index;

                        // Select Default Primary (0)
                        if (index === 0) img.classList.add('selected-primary');

                        img.addEventListener('click', function () {
                            // Clear others
                            document.querySelectorAll('.img-preview').forEach(el => el.classList.remove('selected-primary'));
                            // Select this
                            this.classList.add('selected-primary');
                            primaryIndexInput.value = this.dataset.index;
                        });

                        colProxy.appendChild(img);
                        previewContainer.appendChild(colProxy);
                    };
                    reader.readAsDataURL(file);
                });
                // Reset primary to 0 on new selection
                primaryIndexInput.value = 0;
            } else {
                previewContainer.style.display = 'none';
            }
        });
    }

    // 2. Form Validation (Bootstrap)
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

});
