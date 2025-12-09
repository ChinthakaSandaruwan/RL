<?php
require_once __DIR__ . '/../../../config/db.php';
ensure_session_started();

$user = current_user();
if (!$user || $user['role_id'] != 2) { // Admin check
    header('Location: ' . app_url('auth/login'));
    exit;
}

$pdo = get_pdo();
$message = '';
$error = '';

// Handle Send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    $customer_ids = $_POST['customer_ids'] ?? [];
    $title = trim($_POST['title'] ?? '');
    $messageText = trim($_POST['message'] ?? '');
    $send_to_all = isset($_POST['send_to_all']);
    
    if ($title && $messageText) {
        try {
            if ($send_to_all) {
                // Get all customer IDs
                $stmt = $pdo->query("SELECT user_id FROM user WHERE role_id = 4");
                $customer_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
            
            if (empty($customer_ids)) {
                $error = "Please select at least one customer or enable 'Send to All'.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO notification (user_id, title, message, type_id) VALUES (?, ?, ?, 1)");
                foreach ($customer_ids as $cid) {
                    $stmt->execute([intval($cid), $title, $messageText]);
                }
                $message = "Notification sent to " . count($customer_ids) . " customer(s) successfully!";
            }
        } catch (Exception $e) {
            $error = "Error sending notification: " . $e->getMessage();
        }
    } else {
        $error = "Title and Message are required.";
    }
}

// Fetch all customers
$customers = $pdo->query("SELECT user_id, name, email FROM user WHERE role_id = 4 ORDER BY name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Notification to Customers - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

    <?php require_once __DIR__ . '/../../../public/navbar/navbar.php'; ?>

    <div class="container py-5">
        <div class="mb-4">
            <h2 class="fw-bold text-dark">
                <i class="fas fa-paper-plane me-2"></i> Send Notification to Customers
            </h2>
            <p class="text-muted">Send announcements or updates to customer accounts.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="mb-4">
                    <label class="form-label fw-bold">Notification Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="e.g., System Maintenance Notice" required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Message <span class="text-danger">*</span></label>
                    <textarea name="message" class="form-control" rows="5" placeholder="Enter your message here..." required></textarea>
                </div>

                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="send_to_all" id="sendToAll" onchange="toggleCustomerList()">
                        <label class="form-check-label fw-bold" for="sendToAll">
                            Send to All Customers
                        </label>
                    </div>
                </div>

                <div id="customerListContainer">
                    <label class="form-label fw-bold">Select Customers <span class="text-danger">*</span></label>
                    <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                        <?php if (empty($customers)): ?>
                            <p class="text-muted text-center">No customers found.</p>
                        <?php else: ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleAllCustomers(this)">
                                <label class="form-check-label fw-bold" for="selectAll">Select All</label>
                            </div>
                            <hr>
                            <?php foreach ($customers as $customer): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input customer-checkbox" type="checkbox" name="customer_ids[]" value="<?= $customer['user_id'] ?>" id="customer_<?= $customer['user_id'] ?>">
                                    <label class="form-check-label" for="customer_<?= $customer['user_id'] ?>">
                                        <?= htmlspecialchars($customer['name']) ?> <span class="text-muted small">(<?= htmlspecialchars($customer['email']) ?>)</span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" name="send" class="btn btn-primary px-4">
                        <i class="fas fa-paper-plane me-2"></i> Send Notification
                    </button>
                    <a href="<?= app_url('admin/index/index.php') ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
            </div>
        </form>
    </div>

    <script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
    <script>
        function toggleCustomerList() {
            const sendToAll = document.getElementById('sendToAll').checked;
            const container = document.getElementById('customerListContainer');
            container.style.display = sendToAll ? 'none' : 'block';
        }

        function toggleAllCustomers(source) {
            const checkboxes = document.querySelectorAll('.customer-checkbox');
            checkboxes.forEach(cb => cb.checked = source.checked);
        }

        // Flash message
        <?php if ($message): ?>
            Swal.fire({ icon: 'success', title: 'Success', text: '<?= addslashes($message) ?>', timer: 3000 });
        <?php endif; ?>
        <?php if ($error): ?>
            Swal.fire({ icon: 'error', title: 'Error', text: '<?= addslashes($error) ?>' });
        <?php endif; ?>
    </script>
</body>
</html>
