<?php
require __DIR__ . '/../../config/db.php';

ensure_session_started();
$user = current_user();

if (!$user) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$pdo = get_pdo();
$errors = [];
$success = null;
$csrf_token = generate_csrf_token(); // Helper from db.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF Token');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $nic = trim($_POST['nic'] ?? '');
        
        // Validation
        if ($name === '' || $email === '' || $nic === '') {
            $errors[] = 'All fields (Name, Email, NIC) are required.';
        }
        if (!preg_match('/^[a-zA-Z\s]{3,}$/', $name)) {
            $errors[] = 'Name must contain only letters and spaces.';
        }
        if (!preg_match('/^([0-9]{9}[x|X|v|V]|[0-9]{12})$/', $nic)) {
            $errors[] = 'Invalid NIC format.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        }

        // Check for duplicates (excluding current user)
        if (!$errors) {
            $stmt = $pdo->prepare('SELECT 1 FROM `user` WHERE (`email` = ? OR `nic` = ?) AND `user_id` != ? LIMIT 1');
            $stmt->execute([$email, $nic, $user['user_id']]);
            if ($stmt->fetch()) {
                $errors[] = 'Email or NIC already in use by another account.';
            }
        }

        if (!$errors) {
            // Handle Profile Image Upload
            $profileImage = $user['profile_image']; // Default to existing
            
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $fileTmp = $_FILES['profile_image']['tmp_name'];
                $fileName = $_FILES['profile_image']['name'];
                $fileSize = $_FILES['profile_image']['size'];
                $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (!in_array($fileType, $allowedTypes)) {
                    $errors[] = 'Invalid file type. Only JPG, JPEG, PNG, and WEBP are allowed.';
                } elseif ($fileSize > 2 * 1024 * 1024) { // 2MB
                    $errors[] = 'File size exceeds 2MB limit.';
                } else {
                    // Create upload dir if not exists (redundant check but safe)
                    $uploadDir = __DIR__ . '/../../public/uploads/profiles/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    // Generate unique name
                    $newFileName = 'profile_' . $user['user_id'] . '_' . time() . '.' . $fileType;
                    $uploadPath = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($fileTmp, $uploadPath)) {
                        // Delete old image if exists and not a default/external one
                        if ($user['profile_image'] && file_exists($uploadDir . basename($user['profile_image']))) {
                            unlink($uploadDir . basename($user['profile_image']));
                        }
                        $profileImage = 'public/uploads/profiles/' . $newFileName;
                    } else {
                        $errors[] = 'Failed to upload image.';
                    }
                }
            }

            if (!$errors) {
                $stmt = $pdo->prepare('UPDATE `user` SET `name` = ?, `email` = ?, `nic` = ?, `profile_image` = ? WHERE `user_id` = ?');
                $stmt->execute([$name, $email, $nic, $profileImage, $user['user_id']]);
                $success = 'Profile updated successfully.';
                
                // Refresh user data
                $user = current_user(); 
            }
        }


    } elseif ($action === 'delete_image') {
        if ($user['profile_image']) {
            $filePath = __DIR__ . '/../../' . $user['profile_image'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $stmt = $pdo->prepare('UPDATE `user` SET `profile_image` = NULL WHERE `user_id` = ?');
            $stmt->execute([$user['user_id']]);
            $success = 'Profile image removed.';
            $user = current_user();
        }

    } elseif ($action === 'delete') {
        // Hard delete user logic
        $pdo->prepare('DELETE FROM `user` WHERE `user_id` = ?')->execute([$user['user_id']]);
        
        // Logout via helper
        logout_user();
        
        // Redirect to home with a param for potential JS alert on index (not implemented yet, but safe redirect)
        header('Location: ' . app_url('index.php'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Profile - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= app_url('public/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= app_url('public/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= app_url('public/favicon/favicon-16x16.png') ?>">
    <link rel="manifest" href="<?= app_url('public/favicon/site.webmanifest') ?>">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="profile.css">
</head>
<body>

<?php require __DIR__ . '/../../public/navbar/navbar.php'; ?>

<div class="profile-header-section text-center">
    <div class="container">
        <h1 class="display-5 fw-bold">My Profile</h1>
        <p class="lead opacity-75">Update your personal details</p>
    </div>
</div>

<div class="container profile-container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Edit Information</h4>
                </div>
                <div class="card-body p-4">
                    
                    <form method="post" id="updateForm" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="action" value="update">
                        
                        <div class="text-center mb-4">
                            <div class="profile-image-wrapper">
                                <?php 
                                    $imgSrc = $user['profile_image'] ? app_url($user['profile_image']) : 'https://ui-avatars.com/api/?name='.urlencode($user['name']).'&background=random&size=150';
                                ?>
                                <img src="<?= $imgSrc ?>" alt="Profile Image" class="profile-image" id="profileImagePreview">
                                <label for="profileImageInput" class="profile-image-overlay">
                                    <span>Change</span>
                                </label>
                            </div>
                            <?php if ($user['profile_image']): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="removeImageBtn">Remove Image</button>
                            <?php endif; ?>
                            <input type="file" name="profile_image" id="profileImageInput" class="d-none" accept="image/png, image/jpeg, image/webp">
                        </div>
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Mobile Number (Read-Only)</label>
                                <input type="text" class="form-control info-readonly" value="<?= htmlspecialchars($user['mobile_number'] ?? '') ?>" readonly>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">NIC Number</label>
                                <input type="text" name="nic" class="form-control" value="<?= htmlspecialchars($user['nic'] ?? '') ?>" required>
                            </div>
                            
                            <div class="col-12 mt-4 text-end">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </div>
                    </form>

                    <hr class="my-5 text-muted">

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="text-danger fw-bold mb-1">Delete Account</h5>
                            <p class="text-muted small mb-0">Permanently remove your account and all data.</p>
                        </div>
                        <form method="post" id="deleteForm">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="button" id="deleteAccountBtn" class="btn btn-danger">Delete Account</button>
                        </form>
                        
                        <form method="post" id="deleteImageForm" class="d-none">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="action" value="delete_image">
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.homeUrl = "<?= app_url('index.php') ?>";
    window.serverMessages = {
        success: <?= json_encode($success) ?>,
        errors: <?= json_encode($errors) ?>
    };
</script>
<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="profile.js"></script>
</body>
</html>
