<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= app_url('public/navbar/navbar.css') ?>">

<nav class="navbar navbar-expand-lg custom-navbar sticky-top">
    <div class="container">
        <!-- Brand on Left -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= app_url('index.php') ?>">
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
            <!-- Category Links (After Brand) -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-center">
                <li class="nav-item">
                    <a class="nav-link" href="<?= app_url('public/property/view_all.php') ?>">
                        <i class="bi bi-house-door me-1"></i> All Properties
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= app_url('public/room/view_all.php') ?>">
                        <i class="bi bi-door-closed me-1"></i> All Rooms
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= app_url('public/vehicle/view_all.php') ?>">
                        <i class="bi bi-car-front me-1"></i> All Vehicles
                    </a>
                </li>
            </ul>

            <!-- Right Side - User Menu -->
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                
                <?php if (isset($user) && $user): ?>
                    <!-- Logged In State -->
                    <li class="nav-item me-2">
                        <span class="navbar-text">
                            Hello, <span class="fw-bold text-white"><?= htmlspecialchars($user['name'] ?? $user['email'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge bg-secondary ms-2" style="font-size: 0.75rem; vertical-align: middle;"><?= htmlspecialchars($user['role_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                    </li>
                    <?php if ($user['role_id'] == 1): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= app_url('super_admin/index/index.php') ?>">
                            <i class="bi bi-shield-lock me-1"></i> Super Admin Dashboard
                        </a>
                    </li>
                    <?php endif; ?>
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
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="ownerCreateDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Create
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="ownerCreateDropdown">
                            <li><a class="dropdown-item" href="<?= app_url('owner/property/create/property_create.php') ?>">Add Property</a></li>
                            <li><a class="dropdown-item" href="<?= app_url('owner/room/create/room_create.php') ?>">Add Room</a></li>
                            <li><a class="dropdown-item" href="<?= app_url('owner/vehicle/create/vehicle_create.php') ?>">Add Vehicle</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <?php
                    // Fetch unread notification count & latest notifications
                    $unread_count = 0;
                    $navbar_notifications = [];
                    if (isset($user['user_id'])) {
                        $pdo_nav = get_pdo();
                        // Count
                        $stmt = $pdo_nav->prepare("SELECT COUNT(*) FROM notification WHERE user_id = ? AND is_read = 0");
                        $stmt->execute([$user['user_id']]);
                        $unread_count = $stmt->fetchColumn();
                        
                        // Latest 5
                        $stmt_list = $pdo_nav->prepare("
                            SELECT * FROM notification 
                            WHERE user_id = ? 
                            ORDER BY created_at DESC 
                            LIMIT 5
                        ");
                        $stmt_list->execute([$user['user_id']]);
                        $navbar_notifications = $stmt_list->fetchAll();
                    }
                    ?>
                    <li class="nav-item dropdown me-2">
                        <a class="nav-link dropdown-toggle position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell-fill" style="font-size: 1.2rem;"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                                    <?= $unread_count > 99 ? '99+' : $unread_count ?>
                                    <span class="visually-hidden">unread messages</span>
                                </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end p-0 shadow overflow-hidden" style="width: 300px; max-height: 400px; overflow-y: auto;">
                            <li class="p-2 border-bottom d-flex justify-content-between align-items-center bg-white">
                                <span class="fw-bold small ms-1">Notifications</span>
                                <a href="<?= app_url('public/notification/notification.php') ?>" class="text-decoration-none small text-success">View All</a>
                            </li>
                            <?php if (empty($navbar_notifications)): ?>
                                <li class="p-3 text-center text-muted small">
                                    No notifications
                                </li>
                            <?php else: ?>
                                <?php foreach ($navbar_notifications as $notif): ?>
                                    <li>
                                        <a class="dropdown-item p-2 text-wrap" href="<?= app_url('public/property/view/property_view.php?id=' . $notif['property_id']) ?>">
                                            <div class="d-flex align-items-start gap-2">
                                                <div class="mt-1">
                                                    <?php if (!$notif['is_read']): ?>
                                                        <i class="bi bi-circle-fill text-success" style="font-size: 0.5rem;"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-circle text-muted" style="font-size: 0.5rem;"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold small text-truncate" style="max-width: 230px;"><?= htmlspecialchars($notif['title']) ?></div>
                                                    <div class="small text-muted text-truncate" style="max-width: 230px;"><?= htmlspecialchars($notif['message']) ?></div>
                                                    <div class="text-muted" style="font-size: 0.7rem;"><?= date('M d, H:i', strtotime($notif['created_at'])) ?></div>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-0"></li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <li class="text-center p-2 bg-light">
                                <a href="<?= app_url('public/notification/notification.php') ?>" class="small text-decoration-none fw-bold text-secondary">See all notifications</a>
                            </li>
                        </ul>
                    </li>
                    <?php
                    // Fetch wishlist count for customers
                    $wishlist_count = 0;
                    if (isset($user['user_id'])) {
                        $pdo_nav = get_pdo();
                        $stmt_p = $pdo_nav->prepare("SELECT COUNT(*) FROM property_wishlist WHERE customer_id = ?");
                        $stmt_p->execute([$user['user_id']]);
                        $count_p = $stmt_p->fetchColumn();
                        
                        $stmt_r = $pdo_nav->prepare("SELECT COUNT(*) FROM room_wishlist WHERE customer_id = ?");
                        $stmt_r->execute([$user['user_id']]);
                        $count_r = $stmt_r->fetchColumn();
                        
                        $stmt_v = $pdo_nav->prepare("SELECT COUNT(*) FROM vehicle_wishlist WHERE customer_id = ?");
                        $stmt_v->execute([$user['user_id']]);
                        $count_v = $stmt_v->fetchColumn();
                        
                        $wishlist_count = $count_p + $count_r + $count_v;
                    }
                    ?>
                    <li class="nav-item me-2">
                        <a class="nav-link position-relative" href="<?= app_url('public/wishlist/wishlist.php') ?>">
                            <i class="bi bi-heart-fill" style="font-size: 1.2rem;"></i>
                            <span id="wishlistCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem; <?= $wishlist_count > 0 ? '' : 'display:none;' ?>">
                                <?= $wishlist_count > 99 ? '99+' : $wishlist_count ?>
                                <span class="visually-hidden">wishlist items</span>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= app_url('public/my_rent/my_rent.php') ?>">
                            <i class="bi bi-calendar-check me-1"></i> My Rent
                        </a>
                    </li>
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