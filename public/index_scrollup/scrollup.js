document.addEventListener('DOMContentLoaded', function () {
    const scrollUpBtn = document.getElementById('scrollUp');

    if (!scrollUpBtn) return;

    // Show/Hide button based on scroll position
    window.addEventListener('scroll', function () {
        if (window.scrollY > 300) {
            scrollUpBtn.classList.add('show');
        } else {
            scrollUpBtn.classList.remove('show');
        }
    });

    // Smooth scroll to top when clicked
    scrollUpBtn.addEventListener('click', function (e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});
