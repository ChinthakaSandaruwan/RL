<?php
require_once __DIR__ . '/../../../config/db.php';
ensure_session_started();
$user = current_user();

if (!$user || $user['role_id'] != 3) {
    header('Location: ' . app_url('auth/login.php'));
    exit;
}

$pdo = get_pdo();

$csrf_token = generate_csrf_token();

// Handle AJAX Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF Token']);
        exit;
    }

    ob_clean();
    header('Content-Type: application/json');
    try {
        $rentId = (int)$_POST['rent_id'];
        $action = $_POST['request_action'];
        $newStatus = ($action === 'approve') ? 1 : 3; 

        // Verify ownership
        $check = $pdo->prepare("
            SELECT r.rent_id 
            FROM vehicle_rent r 
            JOIN vehicle v ON r.vehicle_id = v.vehicle_id 
            WHERE r.rent_id = ? AND v.owner_id = ?
        ");
        $check->execute([$rentId, $user['user_id']]);
        
        if ($check->rowCount() > 0) {
            $upd = $pdo->prepare("UPDATE vehicle_rent SET status_id = ? WHERE rent_id = ?");
            if ($upd->execute([$newStatus, $rentId])) {
                echo json_encode(['success' => true, 'message' => 'Request ' . $action . 'd successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database update failed.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Request not found or permission denied.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
    }
    exit;
}

// Fetch Logic
$tab = $_GET['tab'] ?? 'pending';
$statusCondition = ($tab === 'history') ? 'r.status_id IN (1, 3)' : 'r.status_id = 2';

$sql = "
    SELECT r.*, v.title, v.vehicle_code, v.license_plate, u.name as customer_name, u.mobile_number, u.email, rs.status_name
    FROM vehicle_rent r
    JOIN vehicle v ON r.vehicle_id = v.vehicle_id
    JOIN user u ON r.customer_id = u.user_id
    JOIN rent_status rs ON r.status_id = rs.status_id
    WHERE v.owner_id = ? AND $statusCondition
    ORDER BY r.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user['user_id']]);
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vehicle Rent Approval - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $csrf_token ?>">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="rent_approval.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

<?php require_once __DIR__ . '/../../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark">Rent Approvals <span class="text-muted fs-4">| Vehicle</span></h2>
        <a href="<?= app_url('owner/index/index.php') ?>" class="btn btn-outline-secondary">Dashboard</a>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'pending' ? 'active' : '' ?>" href="?tab=pending">Pending Requests</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'history' ? 'active' : '' ?>" href="?tab=history">History</a>
        </li>
    </ul>

    <!-- Content -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Vehicle</th>
                            <th>Customer</th>
                            <th>Dates & Details</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <?php if ($tab === 'pending'): ?>
                            <th class="text-end pe-4">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="<?= $tab === 'pending' ? 6 : 5 ?>" class="text-center py-5 text-muted">
                                No <?= $tab ?> requests found.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $req): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?= htmlspecialchars($req['title']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($req['license_plate']) ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($req['customer_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($req['mobile_number']) ?></small>
                                </td>
                                <td>
                                    <div class="small">
                                        <div class="mb-1"><i class="bi bi-calendar-check me-1"></i> <?= date('M d, H:i', strtotime($req['pickup_date'])) ?> - <?= date('M d, H:i', strtotime($req['dropoff_date'])) ?></div>
                                        <?php if ($req['with_driver']): ?>
                                            <div class="badge bg-info text-dark"><i class="bi bi-person-badge"></i> With Driver</div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">LKR <?= number_format($req['total_amount'], 2) ?></div>
                                </td>
                                <td>
                                    <?php 
                                    $sClass = match($req['status_id']) {
                                        1 => 'bg-success',
                                        2 => 'bg-warning text-dark',
                                        3 => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge rounded-pill <?= $sClass ?>"><?= htmlspecialchars($req['status_name']) ?></span>
                                </td>
                                
                                <?php if ($tab === 'pending'): ?>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-approve btn-action me-1" 
                                            data-id="<?= $req['rent_id'] ?>" data-action="approve" title="Approve">
                                        <i class="bi bi-check-lg"></i> Approve
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger btn-action" 
                                            data-id="<?= $req['rent_id'] ?>" data-action="reject" title="Reject">
                                        <i class="bi bi-x-lg"></i> Reject
                                    </button>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="rent_approval.js"></script>
</body>
</html>
