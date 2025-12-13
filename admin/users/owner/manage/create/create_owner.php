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
$old = $_SESSION['_flash']['old'] ?? [];
unset($_SESSION['_flash']);

$name = $old['name'] ?? '';
$email = $old['email'] ?? '';
$mobile = $old['mobile'] ?? '';
$nic = $old['nic'] ?? '';
$status_id = intval($old['status_id'] ?? 1);

$csrf_token = generate_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentActionErrors = [];

    // Basic CSRF Check
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $currentActionErrors[] = 'Invalid CSRF Token';
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $nic = trim($_POST['nic'] ?? '');
    $status_id = intval($_POST['status_id'] ?? 1);

    if (!$name || !$email || !$mobile) {
        $currentActionErrors[] = 'All required fields must be filled.';
    }

    if (!$currentActionErrors) {
        $stmt = $pdo->prepare("SELECT user_id FROM user WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $currentActionErrors[] = 'Email is already registered.';

        $stmt = $pdo->prepare("SELECT user_id FROM user WHERE mobile_number = ?");
        $stmt->execute([$mobile]);
        if ($stmt->fetch()) $currentActionErrors[] = 'Mobile number is already registered.';

        if ($nic) {
            $stmt = $pdo->prepare("SELECT user_id FROM user WHERE nic = ?");
            $stmt->execute([$nic]);
            if ($stmt->fetch()) $currentActionErrors[] = 'NIC is already registered.';
        }
    }

    $profileImagePath = null;
    if (!$currentActionErrors && !empty($_FILES['profile_image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
         if (in_array($ext, $allowed)) {
            if ($_FILES['profile_image']['size'] <= 2 * 1024 * 1024) {
                $newName = 'user_' . uniqid() . '.' . $ext;
                $uploadDir = __DIR__ . '/../../../../../public/uploads/users/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadDir . $newName)) {
                    $profileImagePath = 'public/uploads/users/' . $newName;
                } else {
                    $currentActionErrors[] = 'Failed to upload image.';
                }
            } else {
                $currentActionErrors[] = 'Image size too large (Max 2MB).';
            }
        } else {
            $currentActionErrors[] = 'Invalid file type. Only JPG, PNG, WEBP allowed.';
        }
    }

    if (empty($currentActionErrors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO user (name, email, mobile_number, nic, profile_image, role_id, status_id) VALUES (?, ?, ?, ?, ?, 3, ?)");
            $stmt->execute([$name, $email, $mobile, $nic, $profileImagePath, $status_id]);
            $_SESSION['_flash']['success'] = "Owner account created successfully!";
            // Clear old inputs to clear the form
            unset($_SESSION['_flash']['old']); 
        } catch (Exception $e) {
            $_SESSION['_flash']['errors'][] = "System Error: " . $e->getMessage();
            $_SESSION['_flash']['old'] = $_POST;
        }
    } else {
        $_SESSION['_flash']['errors'] = $currentActionErrors;
        $_SESSION['_flash']['old'] = $_POST;
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
    <title>Create Owner - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="create_owner.css">
</head>
<body class="bg-light">
    <?php require __DIR__ . '/../../../../../public/navbar/navbar.php'; ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold text-dark mb-0">Create New Owner</h2>
                    <a href="../manage.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
                <?php if ($success): ?><div class="alert alert-success shadow-sm"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                <?php if (!empty($errors)): ?><div class="alert alert-danger shadow-sm"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
                <div class="card form-card">
                    <div class="card-body p-5">
                        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <div class="text-center mb-4">
                                <img id="imagePreview" src="<?= app_url('public/assets/images/profile-placeholder.png') ?>" alt="Preview" class="profile-preview">
                                <div><label for="profile_image" class="btn btn-sm btn-outline-primary">Upload Photo</label><input type="file" name="profile_image" id="profile_image" class="d-none" accept="image/*"></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Full Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($name ?? '') ?>"></div>
                                <div class="col-md-6"><label class="form-label">NIC Number</label><input type="text" name="nic" class="form-control" value="<?= htmlspecialchars($nic ?? '') ?>"></div>
                                <div class="col-md-6"><label class="form-label">Email Address <span class="text-danger">*</span></label><input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($email ?? '') ?>"></div>
                                <div class="col-md-6"><label class="form-label">Mobile Number <span class="text-danger">*</span></label><input type="text" name="mobile" class="form-control" required placeholder="07XXXXXXXX" value="<?= htmlspecialchars($mobile ?? '') ?>"></div>
                                <div class="col-md-6"><label class="form-label">Status</label><select name="status_id" class="form-select"><option value="1">Active</option><option value="2">Inactive</option></select></div>
                            </div>
                            <div class="mt-4 d-grid"><button type="submit" class="btn btn-primary btn-lg">Create Owner Account</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="create_owner.js"></script>
</body>
</html>
