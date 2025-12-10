<?php
require_once __DIR__ . '/../../../../../config/db.php';
ensure_session_started();

$user = current_user();
if (!$user || !in_array($user['role_id'], [1, 2])) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();

// Fetch Owners
$stmt = $pdo->prepare("
    SELECT u.*, s.status_name 
    FROM user u
    LEFT JOIN user_status s ON u.status_id = s.status_id
    WHERE u.role_id = 3
    ORDER BY u.created_at DESC
");
$stmt->execute();
$owners = $stmt->fetchAll();

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Owners - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="read_owner.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
    <?php require_once __DIR__ . '/../../../../../public/navbar/navbar.php'; ?>
    
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark"><i class="fas fa-user-tie me-2"></i>Owner List</h2>
                <p class="text-muted mb-0">Manage registered property owners.</p>
            </div>
            <div>
                <a href="../create/create_owner.php" class="btn btn-primary me-2"><i class="fas fa-plus me-2"></i> Add Owner</a>
                <a href="../manage.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Back</a>
            </div>
        </div>

        <?php if($success): ?><div class="alert alert-success shadow-sm"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger shadow-sm"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="ownerSearch" class="form-control border-start-0" placeholder="Search owners by name, email or mobile...">
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Owner</th>
                                <th>Contact Info</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($owners)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">No owners found.</td></tr>
                            <?php else: ?>
                                <?php foreach($owners as $owner): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle me-3">
                                                    <?php if($owner['profile_image']): ?>
                                                        <img src="<?= app_url($owner['profile_image']) ?>" alt="Avatar">
                                                    <?php else: ?>
                                                        <?= strtoupper(substr($owner['name'], 0, 1)) ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold owner-name"><?= htmlspecialchars($owner['name']) ?></div>
                                                    <div class="small text-muted">ID: <?= $owner['user_id'] ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="small owner-email"><i class="fas fa-envelope text-muted me-2"></i><?= htmlspecialchars($owner['email']) ?></span>
                                                <span class="small owner-mobile"><i class="fas fa-phone text-muted me-2"></i><?= htmlspecialchars($owner['mobile_number']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge status-badge bg-<?= $owner['status_id'] == 1 ? 'success' : 'secondary' ?>">
                                                <?= htmlspecialchars($owner['status_name']) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted small"><?= date('M j, Y', strtotime($owner['created_at'])) ?></td>
                                        <td class="text-end pe-4">
                                            <a href="../update/update_owner.php?id=<?= $owner['user_id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="../owner_stauts/status_change.php?id=<?= $owner['user_id'] ?>" class="btn btn-sm btn-outline-info me-1" title="Status"><i class="fas fa-user-cog"></i></a>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteOwner(<?= $owner['user_id'] ?>)" title="Delete"><i class="fas fa-trash-alt"></i></button>
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

    <form id="deleteForm" action="../delete/delete_owner.php" method="POST" style="display:none;">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        <input type="hidden" name="user_id" id="deleteInputId">
    </form>

    <script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="read_owner.js"></script>
</body>
</html>
