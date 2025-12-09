document.addEventListener('DOMContentLoaded', function () {
    const provinceSelect = document.getElementById('search_province');
    const districtSelect = document.getElementById('search_district');
    const citySelect = document.getElementById('search_city');

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
        // Clear district and city
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

        // Trigger city update if district is preslected
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

    // Event Listeners
    provinceSelect.addEventListener('change', function () {
        updateDistricts(this.value);
    });

    districtSelect.addEventListener('change', function () {
        updateCities(this.value);
    });

    // Initial Load
    if (provinceSelect.value) {
        updateDistricts(provinceSelect.value);
    }
});
