<?php
require __DIR__ . '/../../../../../config/db.php';
ensure_session_started();

$user = current_user();
if (!$user || !in_array($user['role_id'], [1, 2])) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();
$errors = $_SESSION['_flash']['errors'] ?? [];
$success = $_SESSION['_flash']['success'] ?? '';
unset($_SESSION['_flash']);
$csrf_token = generate_csrf_token();
$targetUser = null;

if (isset($_GET['id'])) {
    $targetId = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM user WHERE user_id = ? AND role_id = 3");
    $stmt->execute([$targetId]);
    $targetUser = $stmt->fetch();
}

if (!$targetUser) {
    $_SESSION['_flash']['error'] = 'Owner not found';
    header('Location: ../read/read_owner.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentActionErrors = [];

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $currentActionErrors[] = 'Invalid CSRF Token';
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $nic = trim($_POST['nic'] ?? '');
    
    if (!$name || !$email || !$mobile) $currentActionErrors[] = 'Name, Email and Mobile are required.';

    // Check unique constraints (excluding self)
    if (empty($currentActionErrors)) {
        $stmt = $pdo->prepare("SELECT user_id FROM user WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $targetUser['user_id']]);
        if ($stmt->fetch()) $currentActionErrors[] = 'Email is already taken by another user.';

         $stmt = $pdo->prepare("SELECT user_id FROM user WHERE mobile_number = ? AND user_id != ?");
        $stmt->execute([$mobile, $targetUser['user_id']]);
        if ($stmt->fetch()) $currentActionErrors[] = 'Mobile number is already taken by another user.';
        
        if($nic) {
            $stmt = $pdo->prepare("SELECT user_id FROM user WHERE nic = ? AND user_id != ?");
            $stmt->execute([$nic, $targetUser['user_id']]);
            if ($stmt->fetch()) $currentActionErrors[] = 'NIC is already taken by another user.';
        }
    }

    // Image Upload
    $profileImagePath = $targetUser['profile_image'];
    if (empty($currentActionErrors) && !empty($_FILES['profile_image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $newName = 'user_' . uniqid() . '.' . $ext;
            $uploadDir = __DIR__ . '/../../../../../public/uploads/users/';
             if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadDir . $newName)) {
                $profileImagePath = 'public/uploads/users/' . $newName;
            } else {
                $currentActionErrors[] = 'Failed to upload image.';
            }
        } else {
            $currentActionErrors[] = 'Invalid file type.';
        }
    }

    if (empty($currentActionErrors)) {
        try {
            $stmt = $pdo->prepare("UPDATE user SET name = ?, email = ?, mobile_number = ?, nic = ?, profile_image = ? WHERE user_id = ?");
            $stmt->execute([$name, $email, $mobile, $nic, $profileImagePath, $targetUser['user_id']]);
            $_SESSION['_flash']['success'] = "Owner updated successfully!";
        } catch (Exception $e) {
            $_SESSION['_flash']['errors'][] = "System Error: " . $e->getMessage();
        }
    } else {
        $_SESSION['_flash']['errors'] = $currentActionErrors;
    }
    
    // Redirect to self (PRG)
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Owner - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="../create/create_owner.css"> <!-- Reusing CSS -->
</head>
<body class="bg-light">
    <?php require __DIR__ . '/../../../../../public/navbar/navbar.php'; ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold text-dark mb-0">Update Owner</h2>
                    <a href="../read/read_owner.php" class="btn btn-outline-secondary"><i class="fas fa-times me-2"></i> Cancel</a>
                </div>
                <?php if ($success): ?><div class="alert alert-success shadow-sm"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                <?php if (!empty($errors)): ?><div class="alert alert-danger shadow-sm"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
                
                <div class="card form-card">
                    <div class="card-body p-5">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <div class="text-center mb-4">
                                <?php $img = $targetUser['profile_image'] ? app_url($targetUser['profile_image']) : app_url('public/assets/images/profile-placeholder.png'); ?>
                                <img id="imagePreview" src="<?= $img ?>" alt="Preview" class="profile-preview">
                                <div><label for="profile_image" class="btn btn-sm btn-outline-primary">Change Photo</label><input type="file" name="profile_image" id="profile_image" class="d-none" accept="image/*"></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($targetUser['name']) ?>"></div>
                                <div class="col-md-6"><label class="form-label">NIC Number</label><input type="text" name="nic" class="form-control" value="<?= htmlspecialchars($targetUser['nic']) ?>"></div>
                                <div class="col-md-6"><label class="form-label">Email Address</label><input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($targetUser['email']) ?>"></div>
                                <div class="col-md-6"><label class="form-label">Mobile Number</label><input type="text" name="mobile" class="form-control" required value="<?= htmlspecialchars($targetUser['mobile_number']) ?>"></div>
                            </div>
                            <div class="mt-4 d-grid"><button type="submit" class="btn btn-primary btn-lg">Save Changes</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
    <script>
        // Simple Image Preview
        document.getElementById('profile_image').addEventListener('change', function(e) {
            if(e.target.files[0]){
                 const reader = new FileReader();
                 reader.onload = function(e) { document.getElementById('imagePreview').src = e.target.result; }
                 reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>
</body>
</html>
