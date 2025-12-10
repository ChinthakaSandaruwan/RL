document.addEventListener('DOMContentLoaded', function () {

    // --- SweetAlert Handling ---
    const successMsg = document.getElementById('swal-success').value;
    const errorMsg = document.getElementById('swal-error').value;

    if (successMsg) {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: successMsg,
            confirmButtonColor: '#588157',
            confirmButtonText: 'Go to Dashboard'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../manage.php';
            }
        });
    }

    if (errorMsg) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            html: errorMsg, // Using html to allow list formatting if passed
            confirmButtonColor: '#d33'
        });
    }

    // --- Location Logic ---
    // Ensure these variables are available (passed from PHP via window object or hidden inputs if needed)
    // For cleaner separation, we assume the data is available globally or we fetch it. 
    // However, to keep it simple as per previous inline script, we'll assume `districts` and `cities` are global 
    // OR we will parse them from hidden JSON inputs which is cleaner than global vars.

    // Check if globals exist (from inline script legacy) or try to parse from hidden inputs
    let districtData = typeof districts !== 'undefined' ? districts : [];
    let cityData = typeof cities !== 'undefined' ? cities : [];

    // If undefined, try to find hidden inputs (Recommended refactor step)
    const districtsInput = document.getElementById('districtsData');
    const citiesInput = document.getElementById('citiesData');
    if (districtsInput) districtData = JSON.parse(districtsInput.value);
    if (citiesInput) cityData = JSON.parse(citiesInput.value);

    const provSel = document.getElementById('province');
    const distSel = document.getElementById('district');
    const citySel = document.getElementById('city');

    // Pre-selection Logic (for when form validation fails)
    const preSelectedProvince = provSel.getAttribute('data-selected');
    const preSelectedDistrict = distSel.getAttribute('data-selected');
    const preSelectedCity = citySel.getAttribute('data-selected');

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
            // If we have a selected district, trigger city load
            if (selectedId) loadCities(selectedId, preSelectedCity);
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

    // Events
    provSel.addEventListener('change', function () {
        loadDistricts(this.value);
    });

    distSel.addEventListener('change', function () {
        loadCities(this.value);
    });

    // Initialize if data exists (e.g. repopulating form)
    if (preSelectedProvince) {
        provSel.value = preSelectedProvince;
        loadDistricts(preSelectedProvince, preSelectedDistrict);
    }

    // --- Image Preview Logic ---
    const imgInput = document.getElementById('imgInput');
    const previewArea = document.getElementById('previewArea');
    const primaryInput = document.getElementById('primaryIdx');

    if (imgInput) {
        imgInput.addEventListener('change', function (e) {
            previewArea.innerHTML = '';
            if (this.files) {
                Array.from(this.files).forEach((file, idx) => {
                    // Start Reader
                    const reader = new FileReader();
                    reader.onload = function (ev) {
                        const img = document.createElement('img');
                        img.src = ev.target.result;
                        img.className = 'img-preview' + (idx === 0 ? ' selected-main' : '');
                        img.onclick = () => {
                            document.querySelectorAll('.img-preview').forEach(el => el.classList.remove('selected-main'));
                            img.classList.add('selected-main');
                            primaryInput.value = idx;
                        };
                        previewArea.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                });
            }
        });
    }

});
