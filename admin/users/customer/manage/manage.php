<?php
require_once __DIR__ . '/../../../../config/db.php';
ensure_session_started();

$user = current_user();
if (!$user || !in_array($user['role_id'], [1, 2])) {
    header('Location: ' . app_url('auth/login'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Customers - Rental Lanka</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .feature-card {
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
            border-radius: 15px;
            overflow: hidden;
            height: 100%;
            text-decoration: none;
            color: inherit;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        .icon-box {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }
    </style>
</head>
<body class="bg-light">
    <?php require_once __DIR__ . '/../../../../public/navbar/navbar.php'; ?>
    
    <div class="container py-5">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="fw-bold text-dark">Customer Management</h2>
                <p class="text-muted">Manage registered customers, view details, and handle account status.</p>
            </div>
        </div>

        <div class="row justify-content-center g-4">
            <!-- View All Customers -->
            <div class="col-md-5 col-lg-4">
                <a href="read/read_customer.php" class="feature-card d-block">
                    <div class="card bg-white h-100 p-4 shadow-sm text-center">
                        <div class="card-body">
                            <div class="icon-box bg-primary bg-opacity-10 text-primary mx-auto">
                                <i class="fas fa-users"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-3">View Customers</h4>
                            <p class="text-muted small">List all customers, search by name, and manage their accounts (Delete/Status).</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Placeholder for future modules -->
            <div class="col-md-5 col-lg-4">
                 <div class="card feature-card bg-white h-100 p-4 shadow-sm text-center opacity-75">
                    <div class="card-body">
                        <div class="icon-box bg-info bg-opacity-10 text-info mx-auto">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-3">Customer Analytics</h4>
                        <p class="text-muted small">View customer growth and activity statistics. (Coming Soon)</p>
                    </div>
                </div>
            </div>
            
            <div class="col-12 text-center mt-5">
                <a href="<?= app_url('admin/index/index.php') ?>" class="btn btn-outline-secondary px-4">
                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
