document.addEventListener('DOMContentLoaded', function () {
    const provinceSelect = document.getElementById('filter_province');
    const districtSelect = document.getElementById('filter_district');
    const citySelect = document.getElementById('filter_city');

    const districts = locationData.districts;
    const cities = locationData.cities;

    // Helper to populate select
    function populateSelect(select, items, valueKey, textKey, selectedValue) {
        // Keep first option
        const firstOption = select.options[0];
        select.innerHTML = '';
        select.appendChild(firstOption);

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
        // Reset
        districtSelect.innerHTML = '<option value="">All Districts</option>';
        citySelect.innerHTML = '<option value="">All Cities</option>';
        citySelect.disabled = true;

        if (!provinceId) {
            districtSelect.disabled = true;
            return;
        }

        const filteredDistricts = districts.filter(d => d.province_id == provinceId);
        populateSelect(districtSelect, filteredDistricts, 'id', 'name_en', locationData.selected.district);
        districtSelect.disabled = false;

        // If district was pre-selected (from PHP load), trigger city update
        // But only if the pre-selected district actually belongs to the new province selection
        // In initial load it does. In manual change it might not.
        // We check if value matches any filtered district
        if (locationData.selected.district && filteredDistricts.some(d => d.id == locationData.selected.district)) {
            updateCities(locationData.selected.district);
        }
    }

    function updateCities(districtId) {
        citySelect.innerHTML = '<option value="">All Cities</option>';

        if (!districtId) {
            citySelect.disabled = true;
            return;
        }

        const filteredCities = cities.filter(c => c.district_id == districtId);
        populateSelect(citySelect, filteredCities, 'id', 'name_en', locationData.selected.city);
        citySelect.disabled = false;
    }

    // Event Listeners
    provinceSelect.addEventListener('change', function () {
        // When manually changing, clear selected state essentially
        locationData.selected.district = '';
        locationData.selected.city = '';
        updateDistricts(this.value);
    });

    districtSelect.addEventListener('change', function () {
        locationData.selected.city = '';
        updateCities(this.value);
    });

    // Initial Load
    if (provinceSelect.value) {
        updateDistricts(provinceSelect.value);
    }
});
