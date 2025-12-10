document.getElementById('backupForm').addEventListener('submit', function () {
    // Show loading state
    const btn = document.getElementById('backupBtn');
    const btnText = document.getElementById('btnText');
    const btnLoading = document.getElementById('btnLoading');

    btn.disabled = true;
    btnText.style.display = 'none';
    btnLoading.style.display = 'inline-block';

    // Since it's a file download, we need to re-enable the button after a timeout 
    // or use a cookie-based approach to detect when the download starts.
    // For simplicity, we'll just re-enable it after 5 seconds, assuming if it failed it would be quick.
    // Ideally, the server execution halts the page interaction until the response (download) starts.

    // Actually, form submission will reload the page/trigger download. 
    // If it triggers a download, the page stays. We can set a timeout to reset the button state.
    setTimeout(function () {
        btn.disabled = false;
        btnText.style.display = 'inline-block';
        btnLoading.style.display = 'none';
        // Note: If the backup takes longer than 10 seconds, the user might click again. 
        // But the browser will handle pending requests.
    }, 8000);
});
