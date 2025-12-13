<?php
require_once __DIR__ . '/../../config/db.php';

ensure_session_started();
$user = current_user();
if (!$user || $user['role_id'] != 2) { // 2 = Admin
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();
$message = $_SESSION['_flash']['success'] ?? '';
$error = $_SESSION['_flash']['error'] ?? '';
unset($_SESSION['_flash']);

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect Inputs
    $company_name = trim($_POST['company_name']);
    $about_text = trim($_POST['about_text']);
    $address = trim($_POST['address']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $copyright = trim($_POST['copyright_text']);
    
    // Social Links
    $fb = trim($_POST['facebook_link']);
    $tw = trim($_POST['twitter_link']);
    $go = trim($_POST['google_link']);
    $in = trim($_POST['instagram_link']);
    $li = trim($_POST['linkedin_link']);
    $gh = trim($_POST['github_link']);
    
    // Toggles
    $show_social = isset($_POST['show_social_links']) ? 1 : 0;
    $show_products = isset($_POST['show_products']) ? 1 : 0;
    $show_useful = isset($_POST['show_useful_links']) ? 1 : 0;
    $show_contact = isset($_POST['show_contact']) ? 1 : 0;

    try {
        // Check if record exists
        $stmt = $pdo->query("SELECT footer_id FROM footer_content WHERE footer_id = 1");
        if ($stmt->fetch()) {
            // Update
            $sql = "UPDATE footer_content SET 
                    company_name=?, about_text=?, address=?, email=?, phone=?, 
                    facebook_link=?, twitter_link=?, google_link=?, instagram_link=?, linkedin_link=?, github_link=?, 
                    copyright_text=?, show_social_links=?, show_products=?, show_useful_links=?, show_contact=? 
                    WHERE footer_id = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $company_name, $about_text, $address, $email, $phone,
                $fb, $tw, $go, $in, $li, $gh,
                $copyright, $show_social, $show_products, $show_useful, $show_contact
            ]);
            $_SESSION['_flash']['success'] = "Footer settings updated successfully!";
        } else {
            // Insert (Should happen via seed, but fallback)
            $sql = "INSERT INTO footer_content 
                    (footer_id, company_name, about_text, address, email, phone, 
                    facebook_link, twitter_link, google_link, instagram_link, linkedin_link, github_link,
                    copyright_text, show_social_links, show_products, show_useful_links, show_contact) 
                    VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $company_name, $about_text, $address, $email, $phone,
                $fb, $tw, $go, $in, $li, $gh,
                $copyright, $show_social, $show_products, $show_useful, $show_contact
            ]);
            $_SESSION['_flash']['success'] = "Footer settings created successfully!";
        }
    } catch (PDOException $e) {
        $_SESSION['_flash']['error'] = "Database Error: " . $e->getMessage();
    }
    
    // Redirect (PRG)
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Fetch Current Data
$data = [];
try {
    $stmt = $pdo->query("SELECT * FROM footer_content WHERE footer_id = 1");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { /* ignore */ }

// Defaults if empty
if (!$data) {
    $data = [
        'company_name' => '', 'about_text' => '', 'address' => '', 'email' => '', 'phone' => '',
        'facebook_link' => '', 'twitter_link' => '', 'google_link' => '', 'instagram_link' => '', 'linkedin_link' => '', 'github_link' => '',
        'copyright_text' => '',
        'show_social_links' => 1, 'show_products' => 1, 'show_useful_links' => 1, 'show_contact' => 1
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Footer - Admin</title>
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="footer_details.css">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

    <!-- Navbar Placeholder -->
    <?php require_once __DIR__ . '/../../public/navbar/navbar.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold text-dark"><i class="fas fa-columns me-2"></i> Manage Footer Content</h2>
                </div>



                <form method="POST" action="">
                    
                    <!-- General Settings -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-info-circle me-2"></i> General Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Company Name</label>
                                    <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($data['company_name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Copyright Text</label>
                                    <input type="text" name="copyright_text" class="form-control" value="<?= htmlspecialchars($data['copyright_text']) ?>" placeholder="e.g. Copyright 2025">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">About Text (Short Description)</label>
                                    <textarea name="about_text" class="form-control" rows="3"><?= htmlspecialchars($data['about_text']) ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Info -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold text-success"><i class="fas fa-address-book me-2"></i> Contact Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Address</label>
                                    <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($data['address']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($data['email']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($data['phone']) ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Social Links -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold text-info"><i class="fas fa-share-alt me-2"></i> Social Media Links</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label"><i class="fab fa-facebook text-primary"></i> Facebook</label>
                                    <input type="url" name="facebook_link" class="form-control" value="<?= htmlspecialchars($data['facebook_link']) ?>" placeholder="https://...">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><i class="fab fa-twitter text-info"></i> Twitter</label>
                                    <input type="url" name="twitter_link" class="form-control" value="<?= htmlspecialchars($data['twitter_link']) ?>" placeholder="https://...">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><i class="fab fa-instagram text-danger"></i> Instagram</label>
                                    <input type="url" name="instagram_link" class="form-control" value="<?= htmlspecialchars($data['instagram_link']) ?>" placeholder="https://...">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><i class="fab fa-linkedin text-primary"></i> LinkedIn</label>
                                    <input type="url" name="linkedin_link" class="form-control" value="<?= htmlspecialchars($data['linkedin_link']) ?>" placeholder="https://...">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><i class="fab fa-google text-danger"></i> Google</label>
                                    <input type="url" name="google_link" class="form-control" value="<?= htmlspecialchars($data['google_link']) ?>" placeholder="https://...">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"><i class="fab fa-github"></i> GitHub</label>
                                    <input type="url" name="github_link" class="form-control" value="<?= htmlspecialchars($data['github_link']) ?>" placeholder="https://...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Display Settings -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold text-secondary"><i class="fas fa-eye me-2"></i> Visibility Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="show_social_links" id="show_social" <?= $data['show_social_links'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="show_social">Show Social Links</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="show_products" id="show_prod" <?= $data['show_products'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="show_prod">Show Products Col</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="show_useful_links" id="show_useful" <?= $data['show_useful_links'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="show_useful">Show Useful Links</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="show_contact" id="show_contact" <?= $data['show_contact'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="show_contact">Show Contact Col</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-5">
                        <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm"><i class="fas fa-save me-2"></i> Save Changes</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
    <script>
        window.flashMessage = <?php echo json_encode([
            'success' => $message,
            'error' => $error
        ]); ?>;
    </script>
    <script src="footer_details.js"></script>
</body>
</html>
