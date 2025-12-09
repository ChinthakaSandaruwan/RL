<?php
require __DIR__ . '/../../../../config/db.php';
ensure_session_started();
$user = current_user();

// Check if user is admin (role_id = 2)
if (!$user || $user['role_id'] != 2) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();
$success = null;
$errors = [];

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed");
    }

    $action = $_POST['action'];

    if ($action === 'change_status') {
        $userId = intval($_POST['user_id']);
        $newStatusId = intval($_POST['status_id']);
        $reason = trim($_POST['reason'] ?? '');

        try {
            $pdo->beginTransaction();

            // Update user status
            $stmt = $pdo->prepare("UPDATE user SET status_id = ? WHERE user_id = ? AND role_id = 4");
            $stmt->execute([$newStatusId, $userId]);

            if ($stmt->rowCount() > 0) {
                // Log the status change
                $stmt = $pdo->prepare("
                    INSERT INTO user_status_log (user_id, changed_by, old_status_id, new_status_id, reason, changed_at)
                    SELECT ?, ?, status_id, ?, ?, NOW()
                    FROM user WHERE user_id = ?
                ");
                $stmt->execute([$userId, $user['user_id'], $newStatusId, $reason, $userId]);

                $pdo->commit();
                $success = "Customer status updated successfully!";
            } else {
                $pdo->rollBack();
                $errors[] = "Customer not found or not a customer role.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

// Fetch all customers with their current status
$stmt = $pdo->query("
    SELECT u.*, us.status_name,
           COALESCE(u.name, SUBSTRING_INDEX(u.email, '@', 1)) as display_name,
           COUNT(DISTINCT pr.rent_id) as property_rentals,
           COUNT(DISTINCT rr.rent_id) as room_rentals,
           COUNT(DISTINCT vr.rent_id) as vehicle_rentals
    FROM user u
    LEFT JOIN user_status us ON u.status_id = us.status_id
    LEFT JOIN property_rent pr ON u.user_id = pr.customer_id
    LEFT JOIN room_rent rr ON u.user_id = rr.customer_id
    LEFT JOIN vehicle_rent vr ON u.user_id = vr.customer_id
    WHERE u.role_id = 4
    GROUP BY u.user_id
    ORDER BY u.created_at DESC
");
$customers = $stmt->fetchAll();

// Fetch available statuses
$stmt = $pdo->query("SELECT * FROM user_status ORDER BY status_id");
$statuses = $stmt->fetchAll();

$csrf = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Status Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="status_change.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold"><i class="bi bi-people me-2"></i>Customer Status Management</h2>
            <p class="text-muted mb-0">Manage customer account statuses</p>
        </div>
        <a href="<?= app_url('admin/index/index.php') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i><?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <?php
        $totalCustomers = count($customers);
        $activeCustomers = count(array_filter($customers, fn($c) => $c['status_name'] === 'active'));
        $inactiveCustomers = count(array_filter($customers, fn($c) => $c['status_name'] === 'inactive'));
        $bannedCustomers = count(array_filter($customers, fn($c) => $c['status_name'] === 'banned'));
        ?>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Total Customers</p>
                            <h3 class="mb-0 fw-bold"><?= $totalCustomers ?></h3>
                        </div>
                        <div class="stat-icon bg-primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Active</p>
                            <h3 class="mb-0 fw-bold text-success"><?= $activeCustomers ?></h3>
                        </div>
                        <div class="stat-icon bg-success">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Inactive</p>
                            <h3 class="mb-0 fw-bold text-warning"><?= $inactiveCustomers ?></h3>
                        </div>
                        <div class="stat-icon bg-warning">
                            <i class="bi bi-pause-circle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Banned</p>
                            <h3 class="mb-0 fw-bold text-danger"><?= $bannedCustomers ?></h3>
                        </div>
                        <div class="stat-icon bg-danger">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customers Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>All Customers</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($customers)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1"></i>
                    <p class="mt-3">No customers found.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Customer</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Rentals</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-2">
                                            <?= strtoupper(substr($customer['display_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-medium"><?= htmlspecialchars($customer['display_name']) ?></div>
                                            <small class="text-muted">ID: <?= $customer['user_id'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($customer['email']) ?></td>
                                <td><?= htmlspecialchars($customer['mobile_number'] ?? 'N/A') ?></td>
                                <td>
                                    <small class="text-muted">
                                        P:<?= $customer['property_rentals'] ?> |
                                        R:<?= $customer['room_rentals'] ?> |
                                        V:<?= $customer['vehicle_rentals'] ?>
                                    </small>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = match($customer['status_name']) {
                                        'active' => 'success',
                                        'inactive' => 'warning',
                                        'banned' => 'danger',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $statusClass ?>">
                                        <?= ucfirst($customer['status_name']) ?>
                                    </span>
                                </td>
                                <td class="text-muted small"><?= date('M d, Y', strtotime($customer['created_at'])) ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="openStatusModal(<?= htmlspecialchars(json_encode($customer)) ?>)">
                                        <i class="bi bi-pencil-square"></i> Change Status
                                    </button>
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

<!-- Status Change Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Customer Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="statusForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="change_status">
                    <input type="hidden" name="user_id" id="modal_user_id">

                    <div class="mb-3">
                        <label class="form-label fw-medium">Customer</label>
                        <div class="alert alert-light mb-0">
                            <div id="modal_customer_name" class="fw-bold"></div>
                            <small class="text-muted" id="modal_customer_email"></small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Current Status</label>
                        <div id="modal_current_status"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">New Status *</label>
                        <select name="status_id" class="form-select" required>
                            <option value="">Select Status</option>
                            <?php foreach ($statuses as $status): ?>
                            <option value="<?= $status['status_id'] ?>"><?= ucfirst($status['status_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Reason</label>
                        <textarea name="reason" class="form-control" rows="3" 
                                  placeholder="Optional: Reason for status change"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="status_change.js"></script>
</body>
</html>
