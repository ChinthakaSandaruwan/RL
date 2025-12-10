document.addEventListener('DOMContentLoaded', function () {

    // --- SweetAlert Handling ---
    const swalSuccess = document.getElementById('swal-success');
    const swalError = document.getElementById('swal-error');

    if (swalSuccess && swalSuccess.value) {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: swalSuccess.value,
            confirmButtonColor: '#588157',
            confirmButtonText: 'Back to Dashboard'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../../index/index.php';
            }
        });
    }

    if (swalError && swalError.value) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            html: swalError.value,
            confirmButtonColor: '#d33'
        });
    }

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





    // --- Location Logic ---
    let districtData = [];
    let cityData = [];

    const districtsInput = document.getElementById('districtsData');
    const citiesInput = document.getElementById('citiesData');
    if (districtsInput) districtData = JSON.parse(districtsInput.value);
    if (citiesInput) cityData = JSON.parse(citiesInput.value);

    const provSel = document.getElementById('province');
    const distSel = document.getElementById('district');
    const citySel = document.getElementById('city');

    function loadDistricts(pid, selectedId = null) {
        distSel.innerHTML = '<option value="">Select District</option>';
        citySel.innerHTML = '<option value="">Select District First</option>';
        citySel.disabled = true;

        if (pid) {
            const fil = districtData.filter(d => d.province_id == pid);
            fil.forEach(d => {
                let opt = new Option(d.name_en, d.id);
                if (d.id == selectedId) opt.selected = true;
                distSel.add(opt);
            });
            distSel.disabled = false;
        } else {
            distSel.disabled = true;
        }
    }

    function loadCities(did, selectedId = null) {
        citySel.innerHTML = '<option value="">Select City</option>';
        if (did) {
            const fil = cityData.filter(c => c.district_id == did);
            fil.forEach(c => {
                let opt = new Option(c.name_en, c.id);
                if (c.id == selectedId) opt.selected = true;
                citySel.add(opt);
            });
            citySel.disabled = false;
        } else {
            citySel.disabled = true;
        }
    }

    if (provSel) {
        provSel.addEventListener('change', function () {
            loadDistricts(this.value);
        });
    }

    if (distSel) {
        distSel.addEventListener('change', function () {
            loadCities(this.value);
        });
    }

    // Check for pre-selected values (re-populate on error)
    if (provSel && provSel.dataset.selected) {
        loadDistricts(provSel.dataset.selected, distSel ? distSel.dataset.selected : null);
        if (distSel && distSel.dataset.selected) {
            loadCities(distSel.dataset.selected, citySel ? citySel.dataset.selected : null);
        }
    }


    // --- Meal Options Logic ---
    const radioNone = document.getElementById('mealsNone');
    const radioAvailable = document.getElementById('mealsAvailable');
    const mealContainer = document.getElementById('mealSelectionContainer');

    function toggleMeals() {
        if (radioAvailable && radioAvailable.checked) {
            mealContainer.style.display = 'flex';
        } else if (mealContainer) {
            mealContainer.style.display = 'none';
        }
    }

    if (radioNone) radioNone.addEventListener('change', toggleMeals);
    if (radioAvailable) radioAvailable.addEventListener('change', toggleMeals);

    // Initialize state
    if (radioAvailable && radioAvailable.checked) toggleMeals();

    // Per Meal Logic
    const mealChecks = document.querySelectorAll('.meal-check');
    mealChecks.forEach(check => {
        check.addEventListener('change', function () {
            const id = this.value;
            const priceInput = document.getElementById('price_' + id);
            const freeCheck = document.getElementById('free_' + id);

            if (this.checked) {
                if (priceInput) priceInput.disabled = freeCheck && freeCheck.checked;
                if (freeCheck) freeCheck.disabled = false;
            } else {
                if (priceInput) {
                    priceInput.disabled = true;
                    priceInput.value = '';
                }
                if (freeCheck) {
                    freeCheck.disabled = true;
                    freeCheck.checked = false;
                }
            }
        });
        // Trigger change on load if checked
        if (check.checked) check.dispatchEvent(new Event('change'));
    });

    const freeChecks = document.querySelectorAll('.meal-free-check');
    freeChecks.forEach(check => {
        check.addEventListener('change', function () {
            const id = this.id.split('_')[1];
            const priceInput = document.getElementById('price_' + id);

            if (this.checked) {
                if (priceInput) {
                    priceInput.readOnly = true;
                    priceInput.disabled = false;
                    priceInput.value = '0';
                }
            } else {
                if (priceInput) {
                    priceInput.readOnly = false;
                    priceInput.value = '';
                }
            }
        });
        // Trigger on load
        if (check.checked) check.dispatchEvent(new Event('change'));
    });

});
