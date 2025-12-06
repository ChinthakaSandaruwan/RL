document.addEventListener('DOMContentLoaded', () => {
    // Active Link Highlighting
    const currentLocation = window.location.href;
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');

    navLinks.forEach(link => {
        if (link.href === currentLocation) {
            link.classList.add('active');
            link.setAttribute('aria-current', 'page');
        }
    });

    // Optional: Navbar background change on scroll (if we want a sticky/transparent header effect later)
    /*
    const navbar = document.querySelector('.custom-navbar');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
    */
});
