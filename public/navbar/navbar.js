document.addEventListener('DOMContentLoaded', () => {
    // Active Link Highlighting
    const currentLocation = window.location.href;
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link, .navbar-nav .dropdown-item');

    navLinks.forEach(link => {
        if (link.href === currentLocation) {
            link.classList.add('active');
            link.setAttribute('aria-current', 'page');

            // If it's a dropdown item, also highlight the parent dropdown toggle
            if (link.classList.contains('dropdown-item')) {
                const parentDropdown = link.closest('.nav-item.dropdown');
                if (parentDropdown) {
                    const toggle = parentDropdown.querySelector('.dropdown-toggle');
                    if (toggle) {
                        toggle.classList.add('active');
                    }
                }
            }
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
