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

    // --- Location Logic ---
    let districtData = [];
    let cityData = [];
    let modelData = [];

    const districtsInput = document.getElementById('districtsData');
    const citiesInput = document.getElementById('citiesData');
    const modelsInput = document.getElementById('modelsData');

    if (districtsInput) districtData = JSON.parse(districtsInput.value);
    if (citiesInput) cityData = JSON.parse(citiesInput.value);
    if (modelsInput) modelData = JSON.parse(modelsInput.value);

    // Cascading Location
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

    if (provSel) provSel.addEventListener('change', function () { loadDistricts(this.value); });
    if (distSel) distSel.addEventListener('change', function () { loadCities(this.value); });

    // Pre-selection recovery
    if (provSel && provSel.dataset.selected) {
        loadDistricts(provSel.dataset.selected, distSel ? distSel.dataset.selected : null);
        if (distSel && distSel.dataset.selected) {
            loadCities(distSel.dataset.selected, citySel ? citySel.dataset.selected : null);
        }
    }

    // --- Vehicle Model Logic ---
    const brandSel = document.getElementById('brand');
    const modelSel = document.getElementById('model');

    function loadModels(bid, selectedId = null) {
        modelSel.innerHTML = '<option value="">Select Model</option>';
        if (bid) {
            const fil = modelData.filter(m => m.brand_id == bid);
            fil.forEach(m => {
                let opt = new Option(m.model_name, m.model_id);
                if (m.model_id == selectedId) opt.selected = true;
                modelSel.add(opt);
            });
            modelSel.disabled = false;
        } else {
            modelSel.disabled = true;
        }
    }

    if (brandSel) brandSel.addEventListener('change', function () { loadModels(this.value); });
    if (brandSel && brandSel.dataset.selected) {
        loadModels(brandSel.dataset.selected, modelSel ? modelSel.dataset.selected : null);
    }

    // --- Pricing Type Toggle ---
    const radioDaily = document.getElementById('priceOption1');
    const radioKm = document.getElementById('priceOption2');
    const containerDaily = document.getElementById('dailyPriceContainer');
    const containerKm = document.getElementById('kmPriceContainer');
    const inputDaily = document.getElementById('inputDailyPrice');
    const inputKm = document.getElementById('inputKmPrice');

    function updatePricingUI() {
        if (!radioDaily) return;
        if (radioDaily.checked) {
            if (containerDaily) containerDaily.style.display = 'block';
            if (containerKm) containerKm.style.display = 'none';
            if (inputDaily) inputDaily.required = true;
            if (inputKm) inputKm.required = false;
        } else {
            if (containerDaily) containerDaily.style.display = 'none';
            if (containerKm) containerKm.style.display = 'block';
            if (inputDaily) inputDaily.required = false;
            if (inputKm) inputKm.required = true;
        }
    }

    if (radioDaily && radioKm) {
        radioDaily.addEventListener('change', updatePricingUI);
        radioKm.addEventListener('change', updatePricingUI);
        updatePricingUI(); // Init
    }

    // --- Driver Option Toggle ---
    const driverCheck = document.getElementById('driverCheck');
    const driverCost = document.getElementById('driverCost');
    if (driverCheck) {
        driverCheck.addEventListener('change', function () {
            if (driverCost) {
                driverCost.disabled = !this.checked;
                if (!this.checked) driverCost.value = '';
            }
        });
        // Init
        if (driverCheck.checked && driverCost) driverCost.disabled = false;
    }

    // --- Image Preview Logic (Same as Owner) ---
    const imgInput = document.getElementById('vehicleImages');
    const previewContainer = document.getElementById('imagePreviewContainer');
    const primaryIndexInput = document.getElementById('primaryImageIndex');

    if (imgInput) {
        imgInput.addEventListener('change', function (e) {
            const files = e.target.files;
            previewContainer.innerHTML = '';

            if (files.length > 0) {
                previewContainer.style.display = 'flex';
                // Basic validations done in PHP, but simple JS check here too better UX
                if (files.length < 3) {
                    // Alert? Or just show
                }

                Array.from(files).forEach((file, index) => {
                    // Check type
                    if (!file.type.startsWith('image/')) return;

                    const reader = new FileReader();
                    reader.onload = function (event) {
                        const col = document.createElement('div');
                        col.className = 'col-md-3 col-6';

                        const card = document.createElement('div');
                        card.className = 'image-preview-card' + (index === 0 ? ' primary' : '');
                        card.dataset.index = index;

                        const img = document.createElement('img');
                        img.src = event.target.result;

                        const badge = document.createElement('div');
                        badge.className = 'primary-badge';
                        badge.textContent = 'PRIMARY';

                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'btn btn-sm btn-primary set-primary-btn';
                        btn.textContent = 'Set as Primary';
                        btn.onclick = function () {
                            document.querySelectorAll('.image-preview-card').forEach(c => c.classList.remove('primary'));
                            card.classList.add('primary');
                            primaryIndexInput.value = index;
                        };

                        card.appendChild(img);
                        card.appendChild(badge);
                        card.appendChild(btn);
                        col.appendChild(card);
                        previewContainer.appendChild(col);
                    };
                    reader.readAsDataURL(file);
                });
                primaryIndexInput.value = 0;
            } else {
                previewContainer.style.display = 'none';
            }
        });
    }

    // Bootstrap validation
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
