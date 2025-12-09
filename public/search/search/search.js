document.addEventListener('DOMContentLoaded', function () {
    const provinceSelect = document.getElementById('search_province');
    const districtSelect = document.getElementById('search_district');
    const citySelect = document.getElementById('search_city');
    const provinceImage = document.getElementById('province_image');
    const provinceNameDisplay = document.getElementById('province_name_display');

    // Initial Image Setup
    if (typeof initialImage !== 'undefined' && initialImage) {
        // If initialImage is just filename, prepend path
        const isUrl = initialImage.indexOf('/') !== -1;
        const src = isUrl ? initialImage : (provinceImagesPath + initialImage);

        // Only set if we really have a value, otherwise keep default "central" or whatever is hardcoded
        // Actually PHP provides filename like "central.jpg"
        if (initialImage !== 'default_map.jpg') {
            provinceImage.src = provinceImagesPath + initialImage;
        } else {
            // Keep default or set to a specific default map image
            // For now we default to central in HTML if nothing selected
            // If "All Provinces" selected, maybe switch to generic?
            // Let's stick to Central or a random one for "All"
            provinceImage.src = provinceImagesPath + 'central.jpg';
            provinceNameDisplay.textContent = "Discover Sri Lanka";
        }
    }

    // Helper to populate select
    function populateSelect(select, items, valueKey, textKey, selectedValue) {
        select.innerHTML = select.options[0].outerHTML; // Keep first option
        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueKey]; // 'id'
            option.textContent = item[textKey]; // 'name_en'
            if (selectedValue && item[valueKey] == selectedValue) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    }

    function updateDistricts(provinceId) {
        districtSelect.innerHTML = '<option value="">All Districts</option>';
        citySelect.innerHTML = '<option value="">All Cities</option>';

        if (!provinceId) {
            districtSelect.disabled = true;
            citySelect.disabled = true;
            return;
        }

        const filteredDistricts = searchDistricts.filter(d => d.province_id == provinceId);
        populateSelect(districtSelect, filteredDistricts, 'id', 'name_en', currentDistrict);
        districtSelect.disabled = false;

        if (currentDistrict && filteredDistricts.some(d => d.id == currentDistrict)) {
            updateCities(currentDistrict);
        }
    }

    function updateCities(districtId) {
        citySelect.innerHTML = '<option value="">All Cities</option>';

        if (!districtId) {
            citySelect.disabled = true;
            return;
        }

        const filteredCities = searchCities.filter(c => c.district_id == districtId);
        populateSelect(citySelect, filteredCities, 'id', 'name_en', currentCity);
        citySelect.disabled = false;
    }

    function updateProvinceImage(provinceId) {
        const selectedOption = provinceSelect.options[provinceSelect.selectedIndex];
        const imageName = selectedOption.getAttribute('data-image');
        const provinceName = selectedOption.text;

        if (imageName && imageName !== 'default_map.jpg') {
            // Check if image exists? We assume it does based on strict naming
            provinceImage.style.opacity = 0;
            setTimeout(() => {
                provinceImage.src = provinceImagesPath + imageName;
                provinceNameDisplay.textContent = provinceName;
                provinceImage.onload = () => {
                    provinceImage.style.opacity = 1;
                };
                // Fallback transparency fix if cached
                if (provinceImage.complete) provinceImage.style.opacity = 1;
            }, 200);
        } else {
            // Default to central or generic
            provinceImage.style.opacity = 0;
            setTimeout(() => {
                provinceImage.src = provinceImagesPath + 'central.jpg';
                provinceNameDisplay.textContent = "Discover Sri Lanka";
                provinceImage.style.opacity = 1;
            }, 200);
        }
    }

    // Event Listeners
    provinceSelect.addEventListener('change', function () {
        updateDistricts(this.value);
        updateProvinceImage(this.value);
    });

    districtSelect.addEventListener('change', function () {
        updateCities(this.value);
    });

    // Initial Load Logic
    if (provinceSelect.value) {
        updateDistricts(provinceSelect.value);
        // Image is partly handled by PHP generation but JS listener ensures consistency
        // Text name needs update
        const selectedOption = provinceSelect.options[provinceSelect.selectedIndex];
        provinceNameDisplay.textContent = selectedOption.text;
    }
});
