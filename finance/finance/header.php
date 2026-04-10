<?php
session_start();
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header("Location: login.php"); exit();
}
function restrictTo($roles) {
    if (!in_array($_SESSION['role'], $roles)) die("Access Denied.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Finance ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; }
        .navbar { background: #1a1d20; border-bottom: 3px solid #0d6efd; }
        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); border-radius: 8px; }
        .card-header { font-weight: bold; border-radius: 8px 8px 0 0 !important; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark mb-4 sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php"><i class="bi bi-bank2"></i> UniFinance</a>
        <?php if(isset($_SESSION['user_id'])): ?>
        <div class="navbar-text text-white">
            <span class="badge bg-primary me-2"><?= strtoupper($_SESSION['role']) ?></span>
            <a href="logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
        </div>
        <?php endif; ?>
    </div>
</nav>
