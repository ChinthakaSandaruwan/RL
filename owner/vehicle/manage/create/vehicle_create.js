// Image preview and validation
document.getElementById('vehicleImages').addEventListener('change', function (e) {
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

document.addEventListener('DOMContentLoaded', function () {
    const { districts, cities, models } = window.vehicleData || { districts: [], cities: [], models: [] };

    // Brand change handler
    const brandSelect = document.getElementById('brand');
    if (brandSelect) {
        brandSelect.addEventListener('change', function () {
            const brandId = parseInt(this.value);
            const modelSelect = document.getElementById('model');

            // Clear models
            modelSelect.innerHTML = '<option value="" selected>Select Model</option>';

            // Filter models
            const filteredModels = models.filter(m => m.brand_id == brandId);

            if (filteredModels.length > 0) {
                filteredModels.forEach(model => {
                    const option = document.createElement('option');
                    option.value = model.model_id;
                    option.textContent = model.model_name;
                    modelSelect.appendChild(option);
                });
                modelSelect.disabled = false;
            } else {
                modelSelect.disabled = true;
            }
        });
    }

    // Driver details toggle
    const driverCheck = document.getElementById('driverCheck');
    if (driverCheck) {
        driverCheck.addEventListener('change', function () {
            const costInput = document.getElementById('driverCost');
            costInput.disabled = !this.checked;
            if (!this.checked) costInput.value = '';
        });
    }

    // Province change handler
    const provinceSelect = document.getElementById('province');
    if (provinceSelect) {
        provinceSelect.addEventListener('change', function () {
            const provinceId = parseInt(this.value);
            const districtSelect = document.getElementById('district');
            const citySelect = document.getElementById('city');

            // Clear and disable district and city
            districtSelect.innerHTML = '<option value="" selected>Select District</option>';
            citySelect.innerHTML = '<option value="" selected>Select Province first</option>';
            citySelect.disabled = true;

            // Filter districts by province
            const filteredDistricts = districts.filter(d => d.province_id == provinceId);

            if (filteredDistricts.length > 0) {
                filteredDistricts.forEach(district => {
                    const option = document.createElement('option');
                    option.value = district.id;
                    option.textContent = district.name_en;
                    districtSelect.appendChild(option);
                });
                districtSelect.disabled = false;
            } else {
                districtSelect.disabled = true;
            }
        });
    }

    // District change handler
    const districtSelect = document.getElementById('district');
    if (districtSelect) {
        districtSelect.addEventListener('change', function () {
            const districtId = parseInt(this.value);
            const citySelect = document.getElementById('city');

            // Clear city
            citySelect.innerHTML = '<option value="" selected>Select City</option>';

            // Filter cities by district
            const filteredCities = cities.filter(c => c.district_id == districtId);

            if (filteredCities.length > 0) {
                filteredCities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.id;
                    option.textContent = city.name_en;
                    citySelect.appendChild(option);
                });
                citySelect.disabled = false;
            } else {
                citySelect.disabled = true;
            }
        });
    }

    // Pricing Toggle Handler
    const radioDaily = document.getElementById('priceOption1');
    const radioKm = document.getElementById('priceOption2');
    const containerDaily = document.getElementById('dailyPriceContainer');
    const containerKm = document.getElementById('kmPriceContainer');
    const inputDaily = document.getElementById('inputDailyPrice');
    const inputKm = document.getElementById('inputKmPrice');

    if (radioDaily && radioKm) {
        function updatePricingUI() {
            if (radioDaily.checked) {
                containerDaily.style.display = 'block';
                containerKm.style.display = 'none';
                inputDaily.required = true;
                inputKm.required = false;
                inputKm.value = ''; // Clear value
            } else {
                containerDaily.style.display = 'none';
                containerKm.style.display = 'block';
                inputDaily.required = false;
                inputKm.required = true;
                inputDaily.value = ''; // Clear value
            }
        }

        radioDaily.addEventListener('change', updatePricingUI);
        radioKm.addEventListener('change', updatePricingUI);

        // Initialize
        updatePricingUI();
    }
});
