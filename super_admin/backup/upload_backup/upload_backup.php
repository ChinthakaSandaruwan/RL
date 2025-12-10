<?php
require_once __DIR__ . '/../../../config/db.php';
ensure_session_started();

$user = current_user();
// Ensure user is Super Admin (role_id = 1)
if (!$user || $user['role_id'] != 1) {
    header('Location: ' . app_url('auth/login'));
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    
    // Increase execution time for large backups
    set_time_limit(0);
    ini_set('memory_limit', '512M');

    $sourceDir = realpath(__DIR__ . '/../../../public/uploads');
    $zipFilename = 'uploads_backup_' . date('Y-m-d_H-i-s') . '.zip';
    $zipFilePath = sys_get_temp_dir() . '/' . $zipFilename;

    if (!$sourceDir || !is_dir($sourceDir)) {
        $error = "Uploads directory not found.";
    } else {
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceDir),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                // Skip directories (they would be added automatically)
                if (!$file->isDir()) {
                    // Get real and relative path for current file
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($sourceDir) + 1);

                    // Add current file to archive
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();

            if (file_exists($zipFilePath)) {
                // Serve the file
                header('Content-Description: File Transfer');
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . basename($zipFilename) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($zipFilePath));
                
                flush(); // Flush system output buffer
                readfile($zipFilePath);
                
                // Remove the file after download
                unlink($zipFilePath);
                exit;
            } else {
                $error = "Failed to create zip file.";
            }
        } else {
            $error = "Could not initialize ZipArchive.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Uploads Backup - Super Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="upload_backup.css">
</head>
<body class="bg-light">

    <?php require_once __DIR__ . '/../../../public/navbar/navbar.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-folder-symlink me-2"></i>Backup Uploads Directory</h4>
                    </div>
                    <div class="card-body p-4 text-center">
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <i class="bi bi-cloud-download text-primary" style="font-size: 4rem;"></i>
                        </div>
                        
                        <p class="lead mb-4">
                            Generate a ZIP archive of the entire <code>public/uploads</code> directory.
                            <br>This allows you to backup all property, room, and vehicle images.
                        </p>
                        
                        <div class="alert alert-warning text-start">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Note:</strong>
                            <ul class="mb-0 mt-2">
                                <li>The process might take some time depending on the size of the uploads folder.</li>
                                <li>The download will start automatically once the zip file is ready.</li>
                                <li>Please do not close this tab while the backup is being generated.</li>
                            </ul>
                        </div>

                        <form method="POST" id="backupForm">
                            <input type="hidden" name="create_backup" value="1">
                            <button type="submit" class="btn btn-primary btn-lg px-5" id="backupBtn">
                                <span id="btnText"><i class="bi bi-download me-2"></i>Download Backup</span>
                                <span id="btnLoading" style="display:none;">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    Generating Backup...
                                </span>
                            </button>
                        </form>

                    </div>
                    <div class="card-footer bg-light py-3">
                        <a href="<?= app_url('super_admin/index/index.php') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= app_url('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="upload_backup.js"></script>
</body>
</html>
