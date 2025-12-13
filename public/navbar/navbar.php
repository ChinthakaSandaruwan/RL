<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= app_url('public/navbar/navbar.css') ?>">

<nav class="navbar navbar-expand-lg custom-navbar sticky-top">
    <div class="container">
        <!-- Brand on Left -->
        <a class="navbar-brand d-flex align-items-center gap-2 text-white fw-bold" href="<?= app_url('index.php') ?>">
            <img src="<?= app_url('public/favicon/apple-touch-icon.png') ?>" alt="Logo" width="30" height="30" class="d-inline-block align-text-top rounded">
            Rental Lanka
        </a>

        <!-- Toggler for Mobile -->
        <button class="navbar-toggler shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#rentalLankaNavbar" aria-controls="rentalLankaNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>



        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="rentalLankaNavbar">
            <!-- Category Links (After Brand) -->
            <?php
            // Fetch types for navbar dropdowns (CACHED for performance)
            $pdo_nav = get_pdo();
            
            // Ensure session/user context if not already set by parent
            if (function_exists('ensure_session_started')) {
                 ensure_session_started();
            }
            if (!isset($user) && function_exists('current_user')) {
                $user = current_user();
            }

            // Use cached versions to prevent 3 DB queries on every page load
            $navPropertyTypes = get_cached_types($pdo_nav, 'property_type');
            $navRoomTypes = get_cached_types($pdo_nav, 'room_type');
            $navVehicleTypes = get_cached_types($pdo_nav, 'vehicle_type');

            if (!function_exists('str_contains')) {
                function str_contains($haystack, $needle)
                {
                    return $needle !== '' && mb_strpos($haystack, $needle) !== false;
                }
            }

            // Helper function to get icon for property type
            function getPropertyIcon($typeName) {
                $lower = strtolower($typeName);
                if (str_contains($lower, 'house')) return 'bi-house';
                if (str_contains($lower, 'apartment')) return 'bi-building';
                if (str_contains($lower, 'villa')) return 'bi-house-fill';
                if (str_contains($lower, 'condo')) return 'bi-buildings';
                if (str_contains($lower, 'office')) return 'bi-briefcase';
                if (str_contains($lower, 'shop')) return 'bi-shop';
                if (str_contains($lower, 'warehouse') || str_contains($lower, 'ware house')) return 'bi-box-seam';
                if (str_contains($lower, 'hotel')) return 'bi-building';
                if (str_contains($lower, 'land')) return 'bi-geo';
                return 'bi-house-door';
            }
            
            // Helper function to get icon for room type
            function getRoomIcon($typeName) {
                $lower = strtolower($typeName);
                if (str_contains($lower, 'single')) return 'bi-door-closed';
                if (str_contains($lower, 'double') || str_contains($lower, 'twin')) return 'bi-door-open';
                if (str_contains($lower, 'suite') || str_contains($lower, 'deluxe')) return 'bi-door-open-fill';
                if (str_contains($lower, 'shared') || str_contains($lower, 'dorm')) return 'bi-people';
                if (str_contains($lower, 'studio')) return 'bi-house-door';
                if (str_contains($lower, 'family')) return 'bi-people-fill';
                return 'bi-door-closed';
            }
            
            // Helper function to get icon for vehicle type
            function getVehicleIcon($typeName) {
                $lower = strtolower($typeName);
                if (str_contains($lower, 'car') || str_contains($lower, 'sedan') || str_contains($lower, 'hatchback')) return 'bi-car-front-fill';
                if (str_contains($lower, 'van')) return 'bi-truck';
                if (str_contains($lower, 'suv') || str_contains($lower, 'pickup')) return 'bi-truck-front-fill';
                if (str_contains($lower, 'motorcycle') || str_contains($lower, 'bike')) return 'bi-bicycle';
                return 'bi-car-front';
            }

            // Fetch Notifications for Dropdown
            $navUnreadCount = 0;
            $navNotifications = [];
            if (isset($user) && $user) {
                // Unread Count
                $stmt = $pdo_nav->prepare("SELECT COUNT(*) FROM notification WHERE user_id = ? AND is_read = 0");
                $stmt->execute([$user['user_id']]);
                $navUnreadCount = $stmt->fetchColumn();

                // Recent Notifications
                $stmt = $pdo_nav->prepare("
                    SELECT n.*, nt.type_name 
                    FROM notification n
                    LEFT JOIN notification_type nt ON n.type_id = nt.type_id
                    WHERE n.user_id = ?
                    ORDER BY n.created_at DESC
                    LIMIT 5
                ");
                $stmt->execute([$user['user_id']]);
                $navNotifications = $stmt->fetchAll();
            }
            ?>
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-center">
                <!-- Create Dropdown -->
                <?php if (isset($user) && $user['role_id'] == 3): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="createDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-plus-circle me-1"></i> Create
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="createDropdown">
                        <li><a class="dropdown-item" href="<?= app_url('owner/property/manage/create/property_create.php') ?>">
                            <i class="bi bi-house-add me-2"></i>Property
                        </a></li>
                        <li><a class="dropdown-item" href="<?= app_url('owner/room/manage/create/room_create.php') ?>">
                            <i class="bi bi-door-open me-2"></i>Room
                        </a></li>
                        <li><a class="dropdown-item" href="<?= app_url('owner/vehicle/manage/create/vehicle_create.php') ?>">
                            <i class="bi bi-car-front me-2"></i>Vehicle
                        </a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- All Properties Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="<?= app_url('public/property/view_all/view_all.php') ?>" id="propertiesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-house-door me-1"></i> Properties
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="propertiesDropdown">
                        <li><a class="dropdown-item" href="<?= app_url('public/property/view_all/view_all.php') ?>">
                            <i class="bi bi-grid-3x3-gap me-2"></i>All Properties
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php foreach ($navPropertyTypes as $type): ?>
                        <li><a class="dropdown-item" href="<?= app_url('public/property/view_all/view_all.php?type=' . $type['type_id']) ?>">
                            <i class="<?= getPropertyIcon($type['type_name']) ?> me-2"></i><?= htmlspecialchars(ucfirst($type['type_name'])) ?>
                        </a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>

                <!-- All Rooms Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="<?= app_url('public/room/view_all/view_all.php') ?>" id="roomsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-door-closed me-1"></i> Rooms
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="roomsDropdown">
                        <li><a class="dropdown-item" href="<?= app_url('public/room/view_all/view_all.php') ?>">
                            <i class="bi bi-grid-3x3-gap me-2"></i>All Rooms
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php foreach ($navRoomTypes as $type): ?>
                        <li><a class="dropdown-item" href="<?= app_url('public/room/view_all/view_all.php?type=' . $type['type_id']) ?>">
                            <i class="<?= getRoomIcon($type['type_name']) ?> me-2"></i><?= htmlspecialchars($type['type_name']) ?>
                        </a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>

                <!-- All Vehicles Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="<?= app_url('public/vehicle/view_all/view_all.php') ?>" id="vehiclesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-car-front me-1"></i> Vehicles
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="vehiclesDropdown">
                        <li><a class="dropdown-item" href="<?= app_url('public/vehicle/view_all/view_all.php') ?>">
                            <i class="bi bi-grid-3x3-gap me-2"></i>All Vehicles
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php foreach ($navVehicleTypes as $type): ?>
                        <li><a class="dropdown-item" href="<?= app_url('public/vehicle/view_all/view_all.php?type=' . $type['type_id']) ?>">
                            <i class="<?= getVehicleIcon($type['type_name']) ?> me-2"></i><?= htmlspecialchars(ucfirst($type['type_name'])) ?>
                        </a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>

            <!-- Right Side - User Menu -->
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                
                <?php if (isset($user) && $user): ?>
                    <!-- Logged In State -->
                    <li class="nav-item me-2">
                        <a class="nav-link position-relative" href="<?= app_url('public/wishlist/wishlist.php') ?>" title="My Wishlist">
                            <i class="bi bi-heart-fill"></i>
                        </a>
                    </li>
                    <li class="nav-item dropdown me-2">
                        <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                            <i class="bi bi-bell-fill"></i>
                            <?php if ($navUnreadCount > 0): ?>
                                <span class="badge bg-danger badge-notification"><?= $navUnreadCount ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow notification-dropdown" aria-labelledby="notificationDropdown">
                            <li class="notification-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Notifications</span>
                                    <?php if ($navUnreadCount > 0): ?>
                                        <span class="badge bg-light text-dark"><?= $navUnreadCount ?> New</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <li>
                                <div class="notification-list">
                                    <?php if (empty($navNotifications)): ?>
                                        <div class="text-center py-4 text-muted">
                                            <i class="bi bi-bell-slash mb-2 d-block fs-4"></i>
                                            No notifications
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($navNotifications as $notif): ?>
                                            <a href="<?= app_url('public/notification/notification.php') ?>" class="notification-item <?= !$notif['is_read'] ? 'unread' : '' ?>">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <strong class="text-truncate" style="max-width: 200px;"><?= htmlspecialchars($notif['title']) ?></strong>
                                                    <small class="text-muted" style="font-size: 0.75rem;"><?= date('M j', strtotime($notif['created_at'])) ?></small>
                                                </div>
                                                <div class="small text-muted text-truncate"><?= htmlspecialchars($notif['message']) ?></div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <li class="notification-footer">
                                <a href="<?= app_url('public/notification/notification.php') ?>">View All Notifications</a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= app_url('public/my_rent/my_rent.php') ?>">
                            <i class="bi bi-calendar-check me-1"></i> My Rent
                        </a>
                    </li>
                    <!-- User Menu Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="fw-bold text-white"><?= htmlspecialchars($user['name'] ?? $user['email'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if (!empty($user['profile_image'])): ?>
                                <img src="<?= app_url($user['profile_image']) ?>" alt="Profile" class="rounded-circle" width="30" height="30" style="object-fit: cover; border: 2px solid white;">
                            <?php else: ?>
                                <div class="rounded-circle bg-light text-secondary d-flex justify-content-center align-items-center" style="width: 30px; height: 30px;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                             <li class="px-3 py-2 border-bottom">
                                <small class="text-muted d-block">Signed in as</small>
                                <span class="fw-bold d-block text-truncate" style="max-width: 15rem;"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="badge bg-secondary mt-1"><?= htmlspecialchars($user['role_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                            
                            <?php if ($user['role_id'] == 1): ?>
                            <li>
                                <a class="dropdown-item" href="<?= app_url('super_admin/index/index.php') ?>">
                                    <i class="bi bi-shield-lock me-2"></i>Super Admin Dashboard
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if ($user['role_id'] == 2): ?>
                            <li>
                                <a class="dropdown-item" href="<?= app_url('admin/index/index.php') ?>">
                                    <i class="bi bi-speedometer2 me-2"></i>Admin Dashboard
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if ($user['role_id'] == 3): ?>
                            <li>
                                <a class="dropdown-item" href="<?= app_url('owner/index/index.php') ?>">
                                    <i class="bi bi-speedometer2 me-2"></i>Owner Dashboard
                                </a>
                            </li>
                            <?php endif; ?>

                            <li><hr class="dropdown-divider"></li>

                            <li>
                                <a class="dropdown-item" href="<?= app_url('public/profile/profile.php') ?>">
                                    <i class="bi bi-person-circle me-2"></i>Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= app_url('public/transactions/my_transactions.php') ?>">
                                    <i class="bi bi-receipt me-2"></i>My Transactions
                                </a>
                            </li>
                            
                            <?php if ($user['role_id'] == 4): ?>
                            <li>
                                <a class="dropdown-item" href="<?= app_url('public/user_type_change/send_user_type_change_request.php') ?>">
                                    <i class="bi bi-shop-window me-2"></i>Become Owner
                                </a>
                            </li>
                            <?php endif; ?>

                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= app_url('auth/logout/index.php') ?>">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Guest State -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= app_url('auth/login/index.php') ?>">Login</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link btn" style="background-color: var(--dry-sage); color: var(--hunter-green) !important; font-weight: 600; padding: 0.5rem 1.25rem !important;" href="<?= app_url('auth/register/index.php') ?>">
                            Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<script src="<?= app_url('public/navbar/navbar.js') ?>"></script>