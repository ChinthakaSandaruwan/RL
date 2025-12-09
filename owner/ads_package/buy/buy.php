<?php
require __DIR__ . '/../../../config/db.php';

ensure_session_started();
$user = current_user();

// Check if user is Owner (role_id = 3)
if (!$user || $user['role_id'] != 3) {
    header('Location: ' . app_url('index.php'));
    exit;
}

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$pdo = get_pdo();
$errors = [];
$success = null;

// Display message from redirect
$packageMessage = $_SESSION['package_required_message'] ?? null;
unset($_SESSION['package_required_message']);

// Fetch all active packages
$stmt = $pdo->query("
    SELECT p.*, pt.type_name
    FROM package p
    JOIN package_type pt ON p.package_type_id = pt.type_id
    WHERE p.status_id = 1
    ORDER BY p.price ASC
");
$packages = $stmt->fetchAll();

// Fetch Admin Bank Details
$stmt = $pdo->query("
    SELECT aba.*, b.bank_name 
    FROM admin_bank_account aba
    JOIN admin_bank b ON aba.bank_id = b.bank_id
");
$bankAccounts = $stmt->fetchAll();

// Handle package purchase with slip
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['package_id'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF Token';
    } else {
        $packageId = intval($_POST['package_id']);
        
        // Get package details
        $stmt = $pdo->prepare("SELECT * FROM package WHERE package_id = ? AND status_id = 1");
        $stmt->execute([$packageId]);
        $package = $stmt->fetch();
        
        if ($package) {
            // Validate Image
            if (empty($_FILES['payment_slip']['name'])) {
                $errors[] = "Please upload the payment slip.";
            } else {
                $file = $_FILES['payment_slip'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'application/pdf'];
                
                if (!in_array($file['type'], $allowedTypes)) {
                    $errors[] = "Invalid file type. Only JPG, PNG, WEBP, and PDF allowed.";
                } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB
                    $errors[] = "File size too large. Max 5MB.";
                } else {
                    // Process Upload
                    $uploadDir = __DIR__ . '/../../../public/uploads/slips/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $newFileName = 'slip_' . $user['user_id'] . '_' . time() . '.' . $ext;
                    $uploadPath = $uploadDir . $newFileName;
                    $dbPath = 'public/uploads/slips/' . $newFileName;
                    
                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        try {
                            $pdo->beginTransaction();
                            
                            // 1. Insert Bought Package (Inactive initially)
                            // Note: expires_date is calculated but effective only after activation usually. 
                            // Or we set start date upon approval. For now, let's set it, but admin might need to reset/adjust it on approval.
                            // Better: Set expires_date NULL or based on approval? 
                            // Logic: Inserting now.
                            
                            $duration = $package['duration_days'] ? intval($package['duration_days']) : 30; // Default 30 if null? or infinite?
                            // If infinite (null), date stays null.
                            $expiresDate = null;
                            if ($package['duration_days']) {
                                // We'll set a provisional date, but really it should start from approval
                                $expiresDate = date('Y-m-d H:i:s', strtotime("+$duration days"));
                            }

                            $stmt = $pdo->prepare("
                                INSERT INTO bought_package (
                                    user_id, package_id, bought_date, expires_date,
                                    remaining_properties, remaining_rooms, remaining_vehicles,
                                    status_id, payment_slip, payment_status_id
                                ) VALUES (?, ?, NOW(), ?, ?, ?, ?, 2, ?, 1) 
                            "); 
                            // status_id = 2 (Inactive/Expired - waiting for approval), payment_status_id = 1 (Pending)
                            
                            $stmt->execute([
                                $user['user_id'],
                                $packageId,
                                $expiresDate,
                                $package['max_properties'],
                                $package['max_rooms'],
                                $package['max_vehicles'],
                                $dbPath
                            ]);
                            $boughtPackageId = $pdo->lastInsertId();
                            
                            // 2. Insert Transaction
                            $stmt = $pdo->prepare("
                                INSERT INTO transaction (
                                    user_id, amount, payment_method_id, status, related_type, related_id, proof_image
                                ) VALUES (?, ?, 2, 'pending', 'package', ?, ?)
                            ");
                             // Payment Method 2 = Bank Transfer (hardcoded for now as it's slip upload)
                            $stmt->execute([
                                $user['user_id'],
                                $package['price'],
                                $boughtPackageId,
                                $dbPath
                            ]);
                            
                            $pdo->commit();
                            $success = "Request submitted successfully! Access will be granted after admin approval.";
                            
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            // unlink uploaded file
                            if (file_exists($uploadPath)) unlink($uploadPath);
                            $errors[] = "Database Error: " . $e->getMessage();
                        }
                    } else {
                        $errors[] = "Failed to upload image.";
                    }
                }
            }
        } else {
            $errors[] = "Invalid package.";
        }
    }
}

// Fetch owner's package history
$stmt = $pdo->prepare("
    SELECT bp.*, p.package_name, p.max_properties, p.max_rooms, p.max_vehicles, 
           ss.status_name as subscription_status, ps.status_name as payment_status
    FROM bought_package bp
    JOIN package p ON bp.package_id = p.package_id
    JOIN subscription_status ss ON bp.status_id = ss.status_id
    JOIN payment_status ps ON bp.payment_status_id = ps.status_id
    WHERE bp.user_id = ?
    ORDER BY bp.bought_date DESC
");
$stmt->execute([$user['user_id']]);
$myPackages = $stmt->fetchAll();

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Buy Package - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="buy.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    
    <div class="header-section text-center mb-5">
        <h1 class="fw-bold text-dark">Premium Plans</h1>
        <p class="text-muted lead">Unlock the full potential of your rental business</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success shadow-sm mb-4 text-center py-3">
            <i class="fa-solid fa-circle-check fa-lg me-2"></i> <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger shadow-sm mb-4">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?><li><?= $err ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Packages Grid -->
    <div class="row g-4 justify-content-center">
        <?php foreach ($packages as $pkg): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card package-card h-100 border-0 shadow-sm position-relative">
                    <?php if ($pkg['type_name'] === 'yearly'): ?>
                        <div class="badge-recommend">Best Value</div>
                    <?php endif; ?>
                    
                    <div class="card-body p-4 text-center d-flex flex-column">
                        <h4 class="package-title fw-bold mb-3"><?= htmlspecialchars($pkg['package_name']) ?></h4>
                        
                        <div class="price-section mb-4">
                            <span class="currency">LKR</span>
                            <span class="amount"><?= number_format($pkg['price'], 0) ?></span>
                            <span class="period">/<?= $pkg['duration_days'] ? $pkg['duration_days'] . 'd' : 'life' ?></span>
                        </div>
                        
                        <ul class="features-list text-start mx-auto mb-4 flex-grow-1">
                            <li><i class="fa-solid fa-house-chimney text-success me-2"></i> <?= $pkg['max_properties'] ?> Properties</li>
                            <li><i class="fa-solid fa-bed text-success me-2"></i> <?= $pkg['max_rooms'] ?> Rooms</li>
                            <li><i class="fa-solid fa-car text-success me-2"></i> <?= $pkg['max_vehicles'] ?> Vehicles</li>
                        </ul>
                        
                        <button class="btn btn-primary w-100 btn-lg btn-buy" 
                                data-id="<?= $pkg['package_id'] ?>" 
                                data-name="<?= htmlspecialchars($pkg['package_name']) ?>"
                                data-price="<?= number_format($pkg['price'], 2) ?>">
                            <i class="fa-solid fa-cart-shopping me-2"></i> Buy Now
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Confirm Purchase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <h6 class="text-muted">You are purchasing</h6>
                        <h3 class="text-primary fw-bold" id="modalPackageName">Package</h3>
                        <div class="display-6 fw-bold text-dark mt-2" id="modalPackagePrice">LKR 0.00</div>
                    </div>

                    <div class="card bg-light border-0 p-3 mb-4">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-building-columns me-2"></i>Bank Details</h6>
                        <?php if(!empty($bankAccounts)): ?>
                            <?php foreach($bankAccounts as $acc): ?>
                                <div class="mb-2 small">
                                    <strong><?= htmlspecialchars($acc['bank_name']) ?></strong><br>
                                    <?= htmlspecialchars($acc['account_number']) ?> - <?= htmlspecialchars($acc['branch']) ?><br>
                                    <?= htmlspecialchars($acc['account_holder_name']) ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="small text-muted mb-0">Please contact admin for bank details.</p>
                        <?php endif; ?>
                    </div>

                    <form method="post" enctype="multipart/form-data" id="purchaseForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="package_id" id="modalPackageId">
                        
                        <div class="mb-3">
                            <label class="form-label fw-medium">Upload Payment Slip <span class="text-danger">*</span></label>
                            <input type="file" name="payment_slip" id="paymentSlip" class="form-control" accept="image/*,application/pdf" required>
                            <div class="form-text">JPG, PNG, WEBP or PDF. Max 5MB.</div>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100 py-2 fw-bold">
                            Submit Payment <i class="fa-solid fa-cloud-arrow-up ms-2"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- History -->
    <?php if (!empty($myPackages)): ?>
    <div class="mt-5">
        <h4 class="fw-bold mb-3">Order History</h4>
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3">Package</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myPackages as $hist): ?>
                            <tr>
                                <td class="ps-3 fw-medium"><?= htmlspecialchars($hist['package_name']) ?></td>
                                <td class="text-muted small"><?= date('M d, Y', strtotime($hist['bought_date'])) ?></td>
                                <td>
                                    <?php if ($hist['subscription_status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($hist['payment_status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php elseif ($hist['payment_status'] === 'paid' || $hist['payment_status'] === 'success'): ?>
                                        <span class="badge bg-success">Paid</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Failed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="buy.js"></script>
</body>
</html>
