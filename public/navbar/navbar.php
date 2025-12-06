<link rel="stylesheet" href="<?= app_url('public/navbar/navbar.css') ?>">

<nav class="navbar navbar-expand-lg custom-navbar sticky-top">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= app_url('index.php') ?>">
            <!-- Optional: Icon or Logo could go here -->
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-house-door-fill" viewBox="0 0 16 16">
                <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.495v3.505a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5z"/>
            </svg>
            Rental Lanka
        </a>

        <!-- Toggler for Mobile -->
        <button class="navbar-toggler shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#rentalLankaNavbar" aria-controls="rentalLankaNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="rentalLankaNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                
                <?php if (isset($user) && $user): ?>
                    <!-- Logged In State -->
                    <li class="nav-item me-2">
                        <span class="navbar-text">
                            Hello, <span class="fw-bold text-white"><?= htmlspecialchars($user['name'] ?? $user['email'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge bg-secondary ms-2" style="font-size: 0.75rem; vertical-align: middle;"><?= htmlspecialchars($user['role_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                    </li>
                    <?php if ($user['role_id'] == 2): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= app_url('admin/index/index.php') ?>">
                            Admin Dashboard
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($user['role_id'] == 4): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= app_url('public/user_type_change/send_user_type_change_request.php') ?>">
                            Become Owner
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($user['role_id'] == 3): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= app_url('owner/index/index.php') ?>">
                            Owner Dashboard
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= app_url('public/profile/profile.php') ?>">
                            Profile
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link btn btn-sm" style="border: 1px solid rgba(255,255,255,0.3); padding: 0.4rem 1rem !important;" href="<?= app_url('auth/logout') ?>">
                            Logout
                        </a>
                    </li>
                <?php else: ?>
                    <!-- Guest State -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= app_url('auth/login') ?>">Login</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link btn" style="background-color: var(--dry-sage); color: var(--hunter-green) !important; font-weight: 600; padding: 0.5rem 1.25rem !important;" href="<?= app_url('auth/register') ?>">
                            Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<script src="<?= app_url('public/navbar/navbar.js') ?>"></script>