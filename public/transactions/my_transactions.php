<?php
require_once __DIR__ . '/../../config/db.php';

ensure_session_started();

$user = current_user();
if (!$user) {
    header("Location: " . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();

// Fetch transactions for the current user
$stmt = $pdo->prepare("
    SELECT 
        t.*,
        pm.method_name
    FROM transaction t
    LEFT JOIN payment_method pm ON t.payment_method_id = pm.method_id
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$user['user_id']]);
$transactions = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Transactions - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= app_url('public/favicon/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= app_url('public/favicon/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= app_url('public/favicon/favicon-16x16.png') ?>">
    <link rel="manifest" href="<?= app_url('public/favicon/site.webmanifest') ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="my_transactions.css">
</head>
<body>

<?php require_once __DIR__ . '/../../public/navbar/navbar.php'; ?>

<div class="transaction-dashboard">
    <div class="container">
        
        <div class="dashboard-header">
            <h1 class="dashboard-title">Transaction History</h1>
            <p class="dashboard-subtitle">Track your payments and subscriptions</p>
        </div>

        <div class="transaction-card">
            <div class="transaction-card-header">
                <h5 class="mb-0 fw-bold">Recent Transactions</h5>
                <span class="badge bg-light text-dark border"><?= count($transactions) ?> Records</span>
            </div>

            <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <div class="empty-icon-wrapper">
                        <i class="fa-solid fa-receipt empty-icon"></i>
                    </div>
                    <h5 class="fw-bold text-muted">No Transactions Found</h5>
                    <p class="text-muted small">You haven't made any payments yet.</p>
                    <a href="<?= app_url('index.php') ?>" class="btn btn-primary mt-3">Explore & Rent</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Transaction ID</th>
                                <th>Description</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $t): ?>
                                <?php 
                                    // Status Badge Logic
                                    $statusClass = 'status-pending';
                                    $iconClass = 'fa-clock';
                                    
                                    if ($t['status'] === 'success') {
                                        $statusClass = 'status-success';
                                        $iconClass = 'fa-check-circle';
                                    } elseif ($t['status'] === 'failed') {
                                        $statusClass = 'status-failed';
                                        $iconClass = 'fa-circle-xmark';
                                    } elseif ($t['status'] === 'refunded') {
                                        $statusClass = 'status-refunded';
                                        $iconClass = 'fa-arrow-rotate-left';
                                    }

                                    // Description Logic
                                    $description = ucfirst($t['related_type']);
                                    if ($t['related_type'] == 'package') {
                                        $description = 'Ads Package #' . $t['related_id'];
                                    } elseif ($t['related_type'] == 'rent') {
                                        $description = 'Rental Payment #' . $t['related_id'];
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= date('M d, Y', strtotime($t['created_at'])) ?></div>
                                        <small class="text-muted"><?= date('h:i A', strtotime($t['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <?php if($t['reference_id']): ?>
                                            <span class="trans-id" title="Reference ID: <?= htmlspecialchars($t['reference_id']) ?>">
                                                <?= htmlspecialchars(substr($t['reference_id'], 0, 8)) ?>...
                                            </span>
                                        <?php else: ?>
                                            <span class="trans-id">#<?= str_pad($t['transaction_id'], 6, '0', STR_PAD_LEFT) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($description) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($t['method_name'] ?? 'Unknown') ?>
                                    </td>
                                    <td>
                                        <span class="currency-text"><?= htmlspecialchars($t['currency']) ?></span>
                                        <span class="amount-text"><?= number_format($t['amount'], 2) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-rounded <?= $statusClass ?>">
                                            <i class="fa-solid <?= $iconClass ?> me-1"></i> <?= ucfirst($t['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($t['proof_image']): ?>
                                            <a href="<?= app_url($t['proof_image']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="View Slip">
                                                <i class="fa-solid fa-image"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
