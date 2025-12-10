<?php
require_once __DIR__ . '/../../../../../config/db.php';
ensure_session_started();

$user = current_user();
if (!$user || !in_array($user['role_id'], [1, 2])) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Location: ../read/read_customer.php?error=Invalid CSRF Token');
        exit;
    }

    $userId = intval($_POST['user_id'] ?? 0);
    
    if ($userId > 0) {
        $pdo = get_pdo();
        try {
            // Ensure we only delete customers (role_id = 4)
            $stmt = $pdo->prepare("DELETE FROM user WHERE user_id = ? AND role_id = 4");
            $stmt->execute([$userId]);
            
            if ($stmt->rowCount() > 0) {
                header('Location: ../read/read_customer.php?success=Customer deleted successfully');
            } else {
                header('Location: ../read/read_customer.php?error=Customer not found or invalid role');
            }
        } catch (Exception $e) {
            header('Location: ../read/read_customer.php?error=Error deleting customer: ' . urlencode($e->getMessage()));
        }
    } else {
        header('Location: ../read/read_customer.php?error=Invalid User ID');
    }
} else {
    header('Location: ../read/read_customer.php');
}
exit;
