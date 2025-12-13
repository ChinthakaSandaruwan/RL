<?php
require_once __DIR__ . '/../../config/db.php';

ensure_session_started();
$user = current_user();

// Check if user is Super Admin (role_id = 1)
if (!$user || $user['role_id'] != 1) {
    header('Location: ' . app_url('index.php'));
    exit;
}

// Handle Backup Generation
if (isset($_GET['backup'])) {
    try {
        $pdo = get_pdo();
        
        // Get all tables
        $tables = [];
        $result = $pdo->query('SHOW TABLES');
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        $sqlScript = "";
        $sqlScript .= "-- Rental Lanka Database Backup\n";
        $sqlScript .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sqlScript .= "-- Host: " . env('DB_HOST', 'localhost') . "\n";
        $sqlScript .= "-- Database: " . env('DB_DATABASE', 'rentallanka') . "\n";
        $sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $sqlScript .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n\n";

        foreach ($tables as $table) {
            $sqlScript .= "--\n-- Table structure for table `$table`\n--\n";
            $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
            
            $row2 = $pdo->query('SHOW CREATE TABLE ' . $table)->fetch(PDO::FETCH_NUM);
            $sqlScript .= $row2[1] . ";\n\n";
            
            $sqlScript .= "--\n-- Dumping data for table `$table`\n--\n";
            $result = $pdo->query('SELECT * FROM ' . $table);
            $columnCount = $result->columnCount();
            
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $sqlScript .= "INSERT INTO `$table` VALUES(";
                for ($j = 0; $j < $columnCount; $j++) {
                    $row[$j] = $row[$j];
                    
                    if (isset($row[$j])) {
                        $sqlScript .= '"' . addslashes($row[$j]) . '"';
                    } else {
                        $sqlScript .= 'NULL';
                    }
                    if ($j < ($columnCount - 1)) {
                        $sqlScript .= ',';
                    }
                }
                $sqlScript .= ");\n";
            }
            $sqlScript .= "\n";
        }
        
        $sqlScript .= "SET FOREIGN_KEY_CHECKS=1;\n";

        // Download the file
        $backup_name = 'backup_rentallanka_' . date('Y_m_d_H_i_s') . '.sql';
        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"" . $backup_name . "\""); 
        echo $sqlScript;
        exit;

    } catch (Exception $e) {
        $error = "Backup failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup - Rental Lanka</title>
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="database_backup.css">
</head>
<body class="bg-light">

<?php require __DIR__ . '/../../public/navbar/navbar.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-database-fill-gear me-2 text-primary"></i>Database Backup</h4>
                </div>
                <div class="card-body p-4">
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <div class="text-center mb-4">
                        <div class="display-1 text-primary mb-3"><i class="bi bi-cloud-download"></i></div>
                        <h5>Create a Full Database Backup</h5>
                        <p class="text-muted">This will generate a downloadable SQL file containing all tables and data from the current database.</p>
                    </div>

                    <div class="alert alert-warning border-start border-warning border-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Warning:</strong> Ensure that no critical operations are running before starting the backup. Large databases may take some time to download.
                    </div>

                    <form method="get" action="">
                        <input type="hidden" name="backup" value="true">
                        <div class="d-grid gap-2 col-md-6 mx-auto mt-4">
                            <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('Are you sure you want to download a database backup?');">
                                <i class="bi bi-download me-2"></i> Download SQL Backup
                            </button>
                            <a href="<?= app_url('super_admin/index/index.php') ?>" class="btn btn-outline-secondary">Back to Dashboard</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
<script src="database_backup.js"></script>
</body>
</html>
