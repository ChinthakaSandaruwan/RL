document.addEventListener('DOMContentLoaded', function () {
    const provinceSelect = document.getElementById('adv_province');
    const districtSelect = document.getElementById('adv_district');
    const citySelect = document.getElementById('adv_city');

    // Check if locationData exists (might be from main search.js if loaded on same page)
    // If NOT, we need to ensure we have data. 
    // We can assume searchDistricts and searchCities are global if this is included in search.php
    // If standalone, it might break. We should use safe checks.

    const districtsData = (typeof searchDistricts !== 'undefined') ? searchDistricts : [];
    const citiesData = (typeof searchCities !== 'undefined') ? searchCities : [];

    // Helper: Populate Select
    function populateAdvSelect(select, items, valueKey, textKey) {
        select.innerHTML = select.options[0].outerHTML; // Keep default
        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueKey];
            option.textContent = item[textKey];
            select.appendChild(option);
        });
    }

    // Tab Switching Logic
    const categoryLinks = document.querySelectorAll('.adv-category-trigger');
    const inputCategory = document.getElementById('advCategoryInput');
    const sections = document.querySelectorAll('.adv-section');

    categoryLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            // 1. Update Active State
            categoryLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            // 2. Show Target Section
            const targetId = this.getAttribute('data-target');
            sections.forEach(sec => sec.classList.add('d-none'));
            document.getElementById(targetId).classList.remove('d-none');

            // 3. Update Hidden Input
            const catName = targetId.replace('adv-', ''); // property, room, vehicle
            inputCategory.value = catName;
        });
    });

    // Location Cascading
    if (provinceSelect) {
        provinceSelect.addEventListener('change', function () {
            districtSelect.innerHTML = '<option value="">All Districts</option>';
            citySelect.innerHTML = '<option value="">All Cities</option>';
            citySelect.disabled = true;

            const pid = this.value;
            if (!pid) {
                districtSelect.disabled = true;
                return;
            }

            const filtered = districtsData.filter(d => d.province_id == pid);
            populateAdvSelect(districtSelect, filtered, 'id', 'name_en');
            districtSelect.disabled = false;
        });
    }

    if (districtSelect) {
        districtSelect.addEventListener('change', function () {
            citySelect.innerHTML = '<option value="">All Cities</option>';

            const did = this.value;
            if (!did) {
                citySelect.disabled = true;
                return;
            }

            const filtered = citiesData.filter(c => c.district_id == did);
            populateAdvSelect(citySelect, filtered, 'id', 'name_en');
            citySelect.disabled = false;
        });
    }

    // Reset Button
    const resetBtn = document.getElementById('advResetBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            document.getElementById('advSearchForm').reset();
            // Reset cascading dropdowns manually
            districtSelect.innerHTML = '<option value="">All Districts</option>';
            districtSelect.disabled = true;
            citySelect.innerHTML = '<option value="">All Cities</option>';
            citySelect.disabled = true;

            // Reset to first category
            categoryLinks[0].click();
        });
    }
});
