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
        $_SESSION['_flash']['error'] = 'Invalid CSRF Token';
        header('Location: ../read/read_customer.php');
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
                 $_SESSION['_flash']['success'] = 'Customer deleted successfully';
            } else {
                 $_SESSION['_flash']['error'] = 'Customer not found or invalid role';
            }
        } catch (Exception $e) {
             $_SESSION['_flash']['error'] = 'Error deleting customer: ' . $e->getMessage();
        }
    } else {
         $_SESSION['_flash']['error'] = 'Invalid User ID';
    }
}
header('Location: ../read/read_customer.php');
exit;
