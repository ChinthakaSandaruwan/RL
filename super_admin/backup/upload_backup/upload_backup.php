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

if (isset($_GET['create_backup'])) {
    
    // Increase execution time for large backups
    set_time_limit(0);
    ini_set('memory_limit', '512M');

    $sourceDir = realpath(__DIR__ . '/../../../public/uploads');
    $zipFilename = 'uploads_backup_' . date('Y-m-d_H-i-s') . '.zip';
    $zipFilePath = sys_get_temp_dir() . '/' . $zipFilename;

    if (!$sourceDir || !is_dir($sourceDir)) {
        $error = "Uploads directory not found.";
    } else {
        // Check if directory is empty
        $isEmpty = (count(scandir($sourceDir)) == 2);
        
        if ($isEmpty) {
             // Directory is empty (only . and ..)
             // We can either return error or create a zip with a placeholder
             // Let's create a placeholder file to prevent zip failure
             $placeholder = $sourceDir . '/README.txt';
             file_put_contents($placeholder, "Backup generated on " . date('Y-m-d H:i:s') . ".\nThe uploads directory was empty.");
        }

        if (class_exists('ZipArchive')) {
            // Use PHP ZipArchive
            $zip = new ZipArchive();
            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($sourceDir),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($sourceDir) + 1);
                        $zip->addFile($filePath, $relativePath);
                    }
                }

                $zip->close();
            } else {
                $error = "Could not initialize ZipArchive.";
            }
        } elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Fallback for Windows: Use PowerShell
            // Use 2>&1 to capture error output
            // We strip trailing slash from sourceDir for consistency
            $sourceDirClean = rtrim($sourceDir, '\\/');
            $command = "powershell -Command \"Compress-Archive -Path '$sourceDirClean\*' -DestinationPath '$zipFilePath' -Force\" 2>&1";
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                 $error = "Failed to create zip file using PowerShell. Output: " . implode("\n", $output);
            }
        } else {
             // Fallback for Linux/Mac: Use zip command
             $command = "cd '$sourceDir' && zip -r '$zipFilePath' . 2>&1";
             exec($command, $output, $returnCode);
             if ($returnCode !== 0) {
                 $error = "Failed to create zip using system command. Output: " . implode("\n", $output);
             }
        }
        
        // Remove placeholder if we created it
        if (isset($placeholder) && file_exists($placeholder)) {
            unlink($placeholder);
        }

        // Check if file was created successfully
        if (empty($error) && file_exists($zipFilePath)) {
            if (filesize($zipFilePath) > 0) {
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
                $error = "Zip file was created but is empty.";
                unlink($zipFilePath);
            }
        } elseif (empty($error)) {
            $error = "Zip file was not created. Please check permissions.";
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

                        <form method="GET" id="backupForm">
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
