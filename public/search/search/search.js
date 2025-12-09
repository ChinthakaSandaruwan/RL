document.addEventListener('DOMContentLoaded', function () {
    const provinceSelect = document.getElementById('search_province');
    const districtSelect = document.getElementById('search_district');
    const citySelect = document.getElementById('search_city');
    const provinceImage = document.getElementById('province_image');
    const provinceNameDisplay = document.getElementById('province_name_display');

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

        let targetSrc = "";
        let targetName = "";

        if (imageName && imageName !== 'default_map.jpg') {
            targetSrc = provinceImagesPath + imageName;
            targetName = provinceName;
        } else {
            // Default fallback
            targetSrc = provinceImagesPath + 'central.jpg';
            targetName = "Discover Sri Lanka";
        }

        // 1. Start transition (fade out)
        provinceImage.style.opacity = 0;

        // 2. Wait briefly for fade out, then swap source
        setTimeout(() => {
            provinceImage.src = targetSrc;
            provinceNameDisplay.textContent = targetName;

            // Define handlers for the new load
            provinceImage.onload = () => {
                provinceImage.style.opacity = 1;
            };

            provinceImage.onerror = () => {
                console.warn("Map image failed to load:", targetSrc);
                // Fallback to central if specific fails, ensuring we show SOMETHING
                if (targetSrc !== provinceImagesPath + 'central.jpg') {
                    provinceImage.src = provinceImagesPath + 'central.jpg';
                }
                provinceImage.style.opacity = 1;
            };

            // 3. Check if already cached/complete immediately
            if (provinceImage.complete) {
                provinceImage.style.opacity = 1;
            }
        }, 150);
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
    }

    // Always call this on init to ensure image state is correct (even if default)
    updateProvinceImage(provinceSelect.value);
});
