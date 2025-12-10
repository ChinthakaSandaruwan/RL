<?php
// Maintenance Mode Page
require_once __DIR__ . '/config/db.php';
// We don't necessarily need database here if it's maintenance, but for assets/urls we do.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Under Maintenance - Rental Lanka</title>
    <link rel="stylesheet" href="<?= app_url('bootstrap-5.3.8-dist/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .maintenance-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 3rem;
            text-align: center;
            max-width: 600px;
            width: 90%;
            border-top: 5px solid #198754;
        }
        .icon-wrapper {
            width: 100px;
            height: 100px;
            background: #e6f4ea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
        }
        .bi-gear-wide-connected {
            font-size: 3rem;
            color: #198754;
            animation: spin 10s linear infinite;
        }
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
        h1 {
            color: #212529;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        p {
            color: #6c757d;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .btn-home {
            background: #198754;
            color: white;
            border-radius: 50px;
            padding: 10px 30px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-home:hover {
            background: #157347;
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
        }
    </style>
</head>
<body>
    <div class="maintenance-card">
        <div class="icon-wrapper">
            <i class="bi bi-gear-wide-connected"></i>
        </div>
        <h1>We'll be back soon!</h1>
        <p>
            Sorry for the inconvenience but we're performing some scheduled maintenance at the moment. 
            We'll be back online shortly!
        </p>
        <p class="small text-muted mb-0">&mdash; The Rental Lanka Team</p>
    </div>
</body>
</html>
