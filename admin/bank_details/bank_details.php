<?php
require __DIR__ . '/../../config/db.php';
ensure_session_started();
$user = current_user();

// Check if user is admin (role_id = 2)
if (!$user || $user['role_id'] != 2) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();
$success = $_SESSION['_flash']['success'] ?? null;
$errors = $_SESSION['_flash']['errors'] ?? [];
unset($_SESSION['_flash']);

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die("CSRF validation failed");

    $action = $_POST['action'] ?? '';

    if ($action === 'add_bank') {
        $bank_name = trim($_POST['bank_name']);
        try {
            $stmt = $pdo->prepare("INSERT INTO admin_bank (bank_name) VALUES (?)");
            $stmt->execute([$bank_name]);
            $_SESSION['_flash']['success'] = "Bank added successfully!";
        } catch (Exception $e) {
            $_SESSION['_flash']['errors'][] = "Error: " . $e->getMessage();
        }
    } elseif ($action === 'add_account') {
        $bank_id = intval($_POST['bank_id']);
        $branch = trim($_POST['branch']);
        $account_number = trim($_POST['account_number']);
        $holder_name = trim($_POST['holder_name']);

        try {
            $stmt = $pdo->prepare("INSERT INTO admin_bank_account (bank_id, branch, account_number, account_holder_name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$bank_id, $branch, $account_number, $holder_name]);
            $_SESSION['_flash']['success'] = "Bank account added successfully!";
        } catch (Exception $e) {
            $_SESSION['_flash']['errors'][] = "Error: " . $e->getMessage();
        }
    } elseif ($action === 'update_account') {
        $account_id = intval($_POST['account_id']);
        $bank_id = intval($_POST['bank_id']);
        $branch = trim($_POST['branch']);
        $account_number = trim($_POST['account_number']);
        $holder_name = trim($_POST['holder_name']);

        try {
            $stmt = $pdo->prepare("UPDATE admin_bank_account SET bank_id=?, branch=?, account_number=?, account_holder_name=? WHERE account_id=?");
            $stmt->execute([$bank_id, $branch, $account_number, $holder_name, $account_id]);
            $_SESSION['_flash']['success'] = "Bank account updated successfully!";
        } catch (Exception $e) {
            $_SESSION['_flash']['errors'][] = "Error: " . $e->getMessage();
        }
    } elseif ($action === 'delete_account') {
        $account_id = intval($_POST['account_id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM admin_bank_account WHERE account_id = ?");
            $stmt->execute([$account_id]);
            $_SESSION['_flash']['success'] = "Bank account deleted successfully!";
        } catch (Exception $e) {
            $_SESSION['_flash']['errors'][] = "Error: " . $e->getMessage();
        }
    } elseif ($action === 'delete_bank') {
        $bank_id = intval($_POST['bank_id']);
        try {
            // Check if bank has accounts
            $check = $pdo->prepare("SELECT COUNT(*) FROM admin_bank_account WHERE bank_id = ?");
            $check->execute([$bank_id]);
            if ($check->fetchColumn() > 0) {
                $_SESSION['_flash']['errors'][] = "Cannot delete bank with existing accounts. Delete accounts first.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM admin_bank WHERE bank_id = ?");
                $stmt->execute([$bank_id]);
                $_SESSION['_flash']['success'] = "Bank deleted successfully!";
            }
        } catch (Exception $e) {
            $_SESSION['_flash']['errors'][] = "Error: " . $e->getMessage();
        }
    }
    
    // Redirect (PRG)
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Fetch all banks
$banks = $pdo->query("SELECT * FROM admin_bank ORDER BY bank_name")->fetchAll();

// Fetch all accounts with bank names
$accounts = $pdo->query("
    SELECT a.*, b.bank_name 
    FROM admin_bank_account a 
    JOIN admin_bank b ON a.bank_id = b.bank_id 
    ORDER BY b.bank_name, a.created_at DESC
")->fetchAll();

$csrf = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bank Details Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="bank_details.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../../public/navbar/navbar.php'; ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="bi bi-bank me-2"></i>Bank Details Management</h2>
        <div>
            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addBankModal">
                <i class="bi bi-plus-circle me-1"></i> Add Bank
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                <i class="bi bi-plus-circle me-1"></i> Add Account
            </button>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <!-- Banks Section -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-building me-2"></i>Registered Banks</h5>
        </div>
        <div class="card-body">
            <?php if (empty($banks)): ?>
                <p class="text-muted text-center py-3">No banks registered yet.</p>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($banks as $bank): ?>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title"><?= htmlspecialchars($bank['bank_name']) ?></h6>
                                <button class="btn btn-sm btn-outline-danger mt-2" onclick="deleteBank(<?= $bank['bank_id'] ?>)">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Accounts Section -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-credit-card me-2"></i>Bank Accounts</h5>
        </div>
        <div class="card-body">
            <?php if (empty($accounts)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-credit-card fs-1"></i>
                    <p class="mt-3">No bank accounts added yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Bank</th>
                                <th>Branch</th>
                                <th>Account Number</th>
                                <th>Account Holder</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accounts as $acc): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($acc['bank_name']) ?></strong></td>
                                <td><?= htmlspecialchars($acc['branch']) ?></td>
                                <td><code><?= htmlspecialchars($acc['account_number']) ?></code></td>
                                <td><?= htmlspecialchars($acc['account_holder_name']) ?></td>
                                <td><?= date('M d, Y', strtotime($acc['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="editAccount(<?= htmlspecialchars(json_encode($acc)) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteAccount(<?= $acc['account_id'] ?>)">
                                        <i class="bi bi-trash"></i>
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

<!-- Add Bank Modal -->
<div class="modal fade" id="addBankModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Bank</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="add_bank">
                    <div class="mb-3">
                        <label class="form-label">Bank Name *</label>
                        <input type="text" name="bank_name" class="form-control" required placeholder="e.g., Bank of Ceylon">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Bank</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Bank Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="add_account">
                    
                    <div class="mb-3">
                        <label class="form-label">Bank *</label>
                        <select name="bank_id" class="form-select" required>
                            <option value="">Select Bank</option>
                            <?php foreach ($banks as $bank): ?>
                            <option value="<?= $bank['bank_id'] ?>"><?= htmlspecialchars($bank['bank_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Branch *</label>
                        <input type="text" name="branch" class="form-control" required placeholder="e.g., Colombo Main Branch">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account Number *</label>
                        <input type="text" name="account_number" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account Holder Name *</label>
                        <input type="text" name="holder_name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Account Modal -->
<div class="modal fade" id="editAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Bank Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="update_account">
                    <input type="hidden" name="account_id" id="edit_account_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Bank *</label>
                        <select name="bank_id" id="edit_bank_id" class="form-select" required>
                            <?php foreach ($banks as $bank): ?>
                            <option value="<?= $bank['bank_id'] ?>"><?= htmlspecialchars($bank['bank_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Branch *</label>
                        <input type="text" name="branch" id="edit_branch" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account Number *</label>
                        <input type="text" name="account_number" id="edit_account_number" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account Holder Name *</label>
                        <input type="text" name="holder_name" id="edit_holder_name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden forms for delete actions -->
<form method="POST" id="deleteBankForm" style="display:none">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
    <input type="hidden" name="action" value="delete_bank">
    <input type="hidden" name="bank_id" id="delete_bank_id">
</form>

<form method="POST" id="deleteAccountForm" style="display:none">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
    <input type="hidden" name="action" value="delete_account">
    <input type="hidden" name="account_id" id="delete_account_id">
</form>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script>
const banks = <?= json_encode($banks) ?>;
</script>
<script src="bank_details.js"></script>
</body>
</html>
