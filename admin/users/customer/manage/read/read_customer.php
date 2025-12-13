<?php
require_once __DIR__ . '/../../../../../config/db.php';
ensure_session_started();

$user = current_user();
if (!$user || !in_array($user['role_id'], [1, 2])) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();

// Fetch Customers
$stmt = $pdo->prepare("
    SELECT u.*, s.status_name 
    FROM user u
    LEFT JOIN user_status s ON u.status_id = s.status_id
    WHERE u.role_id = 4
    ORDER BY u.created_at DESC
");
$stmt->execute();
$customers = $stmt->fetchAll();

// Get flash messages if any (passed via session usually, or GET params for simple deleted action)
$success = $_SESSION['_flash']['success'] ?? '';
$error = $_SESSION['_flash']['error'] ?? '';
unset($_SESSION['_flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Customers - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="read_customer.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
    <?php require_once __DIR__ . '/../../../../../public/navbar/navbar.php'; ?>
    
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark"><i class="fas fa-users me-2"></i>Customer List</h2>
                <p class="text-muted mb-0">View and manage registered customers.</p>
            </div>
            <a href="../manage.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back
            </a>
        </div>

        <?php if($success): ?>
            <div class="alert alert-success shadow-sm"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger shadow-sm"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Search -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="customerSearch" class="form-control border-start-0" placeholder="Search customers by name, email or mobile...">
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Customer</th>
                                <th>Contact Info</th>
                                <th>Status</th>
                                <th>Joined Date</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($customers)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">No customers found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($customers as $cust): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle me-3">
                                                    <?php if($cust['profile_image']): ?>
                                                        <img src="<?= app_url($cust['profile_image']) ?>" alt="Avatar">
                                                    <?php else: ?>
                                                        <?= strtoupper(substr($cust['name'], 0, 1)) ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold customer-name"><?= htmlspecialchars($cust['name']) ?></div>
                                                    <div class="small text-muted">ID: <?= $cust['user_id'] ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="small customer-email"><i class="fas fa-envelope text-muted me-2"></i><?= htmlspecialchars($cust['email']) ?></span>
                                                <span class="small customer-mobile"><i class="fas fa-phone text-muted me-2"></i><?= htmlspecialchars($cust['mobile_number']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge status-badge bg-<?= $cust['status_id'] == 1 ? 'success' : ($cust['status_id'] == 3 ? 'danger' : 'secondary') ?>">
                                                <?= htmlspecialchars($cust['status_name']) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted small">
                                            <?= date('M j, Y', strtotime($cust['created_at'])) ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="../customer_stauts/status_change.php?id=<?= $cust['user_id'] ?>" class="btn btn-sm btn-outline-info me-1" title="Change Status">
                                                <i class="fas fa-user-cog"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteCustomer(<?= $cust['user_id'] ?>)" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
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

    <!-- Hidden form for deletion -->
    <form id="deleteForm" action="../delete/delete_customer.php" method="POST" style="display:none;">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        <input type="hidden" name="user_id" id="deleteInputId">
    </form>

    <script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="read_customer.js"></script>
</body>
</html>
