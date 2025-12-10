<?php
require __DIR__ . '/../../../config/db.php';
require __DIR__ . '/../../../services/email.php';
require __DIR__ . '/../../notification/owner/ads_package_approval_notification/package_approval_notification_auto.php';
require __DIR__ . '/invoice/invoice.php';

ensure_session_started();
$currentUser = current_user();
$user = $currentUser; // For navbar compatibility

// Check if user is Admin (Role ID 2 or Super Admin 1)
if (!$currentUser || !in_array($currentUser['role_id'], [1, 2])) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();
$errors = [];
$success = null;

// Handle Actions (Approve/Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF Token';
    } else {
        $action = $_POST['action'] ?? '';
        $boughtPackageId = intval($_POST['bought_package_id'] ?? 0);
        
        if ($boughtPackageId) {
            try {
                $pdo->beginTransaction();

                // Fetch User Info for Email/Notification
                $stmt = $pdo->prepare("
                    SELECT u.user_id, u.email, u.name, p.package_name, p.price,
                           t.transaction_id, t.created_at, t.amount, pm.method_name 
                    FROM bought_package bp
                    JOIN user u ON bp.user_id = u.user_id
                    JOIN package p ON bp.package_id = p.package_id
                    LEFT JOIN transaction t ON t.related_type = 'package' AND t.related_id = bp.bought_package_id AND t.status = 'pending'
                    LEFT JOIN payment_method pm ON t.payment_method_id = pm.method_id
                    WHERE bp.bought_package_id = ?
                ");
                $stmt->execute([$boughtPackageId]);
                $userInfo = $stmt->fetch();

                if ($action === 'approve') {
                    // Update Bought Package
                    $stmt = $pdo->prepare("UPDATE bought_package SET payment_status_id = 2, status_id = 1 WHERE bought_package_id = ?");
                    $stmt->execute([$boughtPackageId]);

                    // Update Transaction (if exists)
                    $stmt = $pdo->prepare("UPDATE transaction SET status = 'success' WHERE related_type = 'package' AND related_id = ? AND status = 'pending'");
                    $stmt->execute([$boughtPackageId]);

                    if ($userInfo) {
                        // Generate Invoice Data
                        $invoiceData = [
                            'invoice_no' => 'INV-' . str_pad($userInfo['transaction_id'] ?? rand(1000,9999), 6, '0', STR_PAD_LEFT),
                            'date' => date('Y-m-d'),
                            'owner_name' => $userInfo['name'],
                            'owner_email' => $userInfo['email'],
                            'package_name' => $userInfo['package_name'],
                            'amount' => $userInfo['amount'] ?? $userInfo['price'],
                            'payment_method' => $userInfo['method_name'] ?? 'Bank Transfer'
                        ];

                        // Generate Invoice HTML for email body
                        $invoiceHtml = get_invoice_html($invoiceData);

                        // Generate Invoice PDF
                        $pdfPath = generate_invoice_pdf($invoiceData);
                        
                        // Determine filename for attachment
                        $pdfFilename = $invoiceData['invoice_no'] . '.pdf';
                        if (strpos($pdfPath, '.html') !== false) {
                            $pdfFilename = $invoiceData['invoice_no'] . '.html';
                        }

                        // Send Invoice Email with PDF attachment
                        send_email_with_attachment(
                            $userInfo['email'], 
                            "Payment Invoice - Rental Lanka", 
                            $invoiceHtml, 
                            $userInfo['name'],
                            [$pdfPath => $pdfFilename]
                        );

                        // Clean up old invoice files
                        cleanup_old_invoices();

                        // Delete the current invoice file after sending (optional - comment out if you want to keep it)
                        if (file_exists($pdfPath)) {
                            // Keep file for 24 hours, cleanup_old_invoices() will handle it
                        }

                        // Create Notification
                        notify_owner_package_status($userInfo['user_id'], $userInfo['package_name'], 'approved');
                    }

                    $success = "Package request approved successfully and invoice sent with PDF attachment.";
                } elseif ($action === 'reject') {
                    // Update Bought Package
                    $stmt = $pdo->prepare("UPDATE bought_package SET payment_status_id = 3, status_id = 2 WHERE bought_package_id = ?"); // 3=failed, 2=inactive
                    $stmt->execute([$boughtPackageId]);

                    // Update Transaction
                    $stmt = $pdo->prepare("UPDATE transaction SET status = 'failed' WHERE related_type = 'package' AND related_id = ? AND status = 'pending'");
                    $stmt->execute([$boughtPackageId]);

                    if ($userInfo) {
                         // Send Email
                         send_package_status_email($userInfo['email'], $userInfo['name'], $userInfo['package_name'], 'rejected');
 
                         // Create Notification
                         notify_owner_package_status($userInfo['user_id'], $userInfo['package_name'], 'rejected');
                    }

                    $success = "Package request rejected.";
                }

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = "Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch Pending Approvals
// We look for bought_packages with payment_status_id = 1 (Pending)
$query = "
    SELECT 
        bp.bought_package_id,
        bp.created_at as request_date,
        bp.price_paid, -- Note: Schema didn't explicit price_paid column in bought_package, but package has price. Let's use package price or transaction amount.
        p.package_name,
        p.price as package_price,
        u.name as owner_name,
        u.mobile_number,
        u.email,
        t.transaction_id,
        t.proof_image,
        t.reference_id,
        t.payment_method_id,
        pm.method_name as payment_method
    FROM bought_package bp
    JOIN package p ON bp.package_id = p.package_id
    JOIN user u ON bp.user_id = u.user_id
    LEFT JOIN transaction t ON t.related_type = 'package' AND t.related_id = bp.bought_package_id
    LEFT JOIN payment_method pm ON t.payment_method_id = pm.method_id
    WHERE bp.payment_status_id = 1
    ORDER BY bp.created_at ASC
";

// Note: In schema `bought_package` does NOT have `price_paid`. We assume standard package price or transaction amount.
// `transaction` has `amount`.

$query = "
    SELECT 
        bp.bought_package_id,
        bp.created_at as request_date,
        p.package_name,
        p.price as package_price,
        u.name as owner_name,
        u.mobile_number,
        u.email,
        t.transaction_id,
        t.amount as transaction_amount,
        COALESCE(bp.payment_slip, t.proof_image) as proof_image,
        t.reference_id,
        t.payment_method_id,
        pm.method_name as payment_method
    FROM bought_package bp
    JOIN package p ON bp.package_id = p.package_id
    JOIN user u ON bp.user_id = u.user_id
    LEFT JOIN transaction t ON t.related_type = 'package' AND t.related_id = bp.bought_package_id
    LEFT JOIN payment_method pm ON t.payment_method_id = pm.method_id
    WHERE bp.payment_status_id = 1
    ORDER BY bp.created_at ASC
";

$stmt = $pdo->query($query);
$requests = $stmt->fetchAll();

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Package Approvals - Rental Lanka Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="package_approval.css">
</head>
<body>

<?php require __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Package Approvals</h2>
        <span class="badge bg-primary fs-6"><?= count($requests) ?> Pending Requests</span>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success shadow-sm"><?= $success ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger shadow-sm">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?><li><?= $err ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="ps-4">Request Details</th>
                            <th>Owner Info</th>
                            <th>Package</th>
                            <th>Payment</th>
                            <th>Proof</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-regular fa-folder-open fa-3x mb-3 d-block"></i>
                                    No pending package requests found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold">ID: #<?= $req['bought_package_id'] ?></div>
                                        <div class="small text-muted"><?= date('M d, Y h:i A', strtotime($req['request_date'])) ?></div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-2"><?=  strtoupper(substr($req['owner_name'], 0, 1)) ?></div>
                                            <div>
                                                <div class="fw-medium"><?= htmlspecialchars($req['owner_name']) ?></div>
                                                <div class="small text-muted"><?= htmlspecialchars($req['mobile_number']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badgex badge-package"><?= htmlspecialchars($req['package_name']) ?></span>
                                        <div class="small mt-1 text-muted">Price: <?= number_format($req['package_price'], 2) ?> LKR</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= number_format($req['transaction_amount'] ?? $req['package_price'], 2) ?> LKR</div>
                                        <div class="small text-muted">
                                            Via <?= ucfirst($req['payment_method'] ?? 'Unknown') ?>
                                            <?php if ($req['reference_id']): ?>
                                                <br>Ref: <?= htmlspecialchars($req['reference_id']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($req['proof_image']): ?>
                                            <button class="btn btn-sm btn-outline-primary view-proof-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#proofModal"
                                                    data-image="<?= app_url($req['proof_image']) ?>">
                                                <i class="fa-regular fa-image me-1"></i> View Slip
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small">No Image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-2">
                                            <form method="post" class="action-form">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <input type="hidden" name="bought_package_id" value="<?= $req['bought_package_id'] ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="button" class="btn btn-success btn-sm btn-action-approve">
                                                    <i class="fa-solid fa-check"></i> Approve
                                                </button>
                                            </form>
                                            <form method="post" class="action-form">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <input type="hidden" name="bought_package_id" value="<?= $req['bought_package_id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="button" class="btn btn-danger btn-sm btn-action-reject">
                                                    <i class="fa-solid fa-xmark"></i> Reject
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Proof Modal -->
<div class="modal fade" id="proofModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Proof</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center bg-light">
                <img src="" id="modalProofImage" class="img-fluid rounded shadow-sm" alt="Payment Proof">
                <iframe src="" id="modalProofPdf" class="w-100 rounded shadow-sm" style="height: 500px; display: none;" frameborder="0"></iframe>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="package_approval.js"></script>
</body>
</html>
