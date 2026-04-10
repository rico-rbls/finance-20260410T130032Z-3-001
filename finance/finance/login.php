<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, password_hash, role, full_name FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($pass, $row['password_hash'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['full_name'] = $row['full_name'];
            header("Location: dashboard.php");
            exit();
        }
    }
    $error = "Invalid username or password.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | UniFinance ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e6b3e;
            --primary-dark: #14492a;
        }
        body {
            background-color: #f8faf9;
            background-image: 
                radial-gradient(at 0% 0%, rgba(30, 107, 62, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(45, 161, 95, 0.08) 0px, transparent 50%);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            border: none;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(30, 107, 62, 0.15);
            background: white;
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .login-header h4 {
            font-family: 'Outfit', sans-serif;
            letter-spacing: -0.5px;
        }
        .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
        }
        .form-control:focus {
            background-color: white;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(30, 107, 62, 0.1);
        }
        .btn-primary {
            background: var(--primary);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(30, 107, 62, 0.2);
        }
        .brand-icon {
            width: 48px;
            height: 48px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card login-card">
                    <div class="login-header">
                        <div class="brand-icon"><i class="bi bi-bank2"></i></div>
                        <h4 class="mb-1 fw-bold">UniFinance ERP</h4>
                        <p class="mb-0 opacity-75 small uppercase fw-semibold" style="letter-spacing: 1px;">Secure Sign In</p>
                    </div>
                    <div class="card-body p-4 p-lg-5">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger border-0 small rounded-3" style="background-color: #fef2f2; color: #991b1b;"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">USERNAME</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-person text-muted"></i></span>
                                    <input type="text" name="username" class="form-control border-start-0 rounded-end-3" placeholder="Enter username" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted">PASSWORD</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-lock text-muted"></i></span>
                                    <input type="password" name="password" class="form-control border-start-0 rounded-end-3" placeholder="Enter password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-2">Sign into Dashboard</button>
                        </form>
                    </div>
                </div>
                <div class="text-center mt-5">
                    <div class="d-flex justify-content-center gap-2 mb-2 text-muted small">
                        <span>UniFinance 2026</span>
                        <span>&bull;</span>
                        <span>Institutional Systems</span>
                    </div>
                    <p class="text-muted small px-4">Contact IT helpdesk at <strong>ricdrobles@gmail.com</strong> if you have trouble accessing your account.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>