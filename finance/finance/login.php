<?php
session_start();
include "db_connect.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["login"])) {
    $user = $_POST["username"];
    $pass = $_POST["password"];

    $stmt = $conn->prepare("SELECT user_id, password_hash, role, full_name FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($pass, $row["password_hash"])) {
            $_SESSION["user_id"] = $row["user_id"];
            $_SESSION["role"] = $row["role"];
            $_SESSION["full_name"] = $row["full_name"];
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
    <title>Access | UniFinance ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e6b3e; /* Forest Green */
            --primary-dark: #14492a;
            --accent: #d4fce4;
            --soft-bg: #f5f9f7;
            --card-shadow: 0 40px 100px -20px rgba(30, 107, 62, 0.15);
        }
        body {
            background-color: var(--soft-bg);
            background-image: 
                radial-gradient(at 0% 0%, rgba(30, 107, 62, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(30, 107, 62, 0.05) 0px, transparent 50%);
            font-family: "Plus Jakarta Sans", sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            border: none;
            border-radius: 32px;
            box-shadow: var(--card-shadow);
            background: white;
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
            display: flex;
            min-height: 640px;
        }
        .login-sidebar {
            background: linear-gradient(165deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 60px;
            width: 45%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }
        .login-sidebar::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url("https://www.transparenttextures.com/patterns/cubes.png");
            opacity: 0.1;
            pointer-events: none;
        }
        .form-container { 
            padding: 60px; 
            width: 55%; 
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .brand-icon-wrapper { 
            width: 56px; height: 56px; 
            background: rgba(255,255,255,0.15); 
            backdrop-filter: blur(8px);
            border-radius: 16px; 
            display: flex; align-items: center; justify-content: center; 
            margin-bottom: 32px;
        }
        .sidebar-title { font-family: "Outfit", sans-serif; font-size: 2.5rem; line-height: 1.1; margin-bottom: 1.5rem; }
        .sidebar-text { font-size: 1.1rem; opacity: 0.85; line-height: 1.6; }
        
        .form-control { 
            border-radius: 14px; 
            padding: 0.85rem 1.2rem; 
            background: #f8faf9; 
            border: 1px solid #e2e8f0; 
            transition: all 0.3s ease; 
        }
        .form-control:focus { 
            background-color: white; 
            border-color: var(--primary); 
            box-shadow: 0 0 0 5px rgba(30, 107, 62, 0.08); 
        }
        .input-group-text { 
            border: 1px solid #e2e8f0; 
            background: #f8faf9;
            border-radius: 14px; 
            padding-left: 1.2rem;
            padding-right: 0.8rem;
        }
        .input-group > .form-control { border-left: none; }
        .input-group > .input-group-text { border-right: none; }

        .btn-primary { 
            background: var(--primary); 
            border: none; 
            border-radius: 14px; 
            padding: 16px; 
            font-weight: 700; 
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            margin-top: 10px;
        }
        .btn-primary:hover { 
            background: var(--primary-dark); 
            transform: translateY(-2px); 
            box-shadow: 0 15px 30px -5px rgba(30, 107, 62, 0.3); 
        }
        
        .tab-heading { font-family: "Outfit", sans-serif; color: var(--primary); font-size: 1.75rem; font-weight: 700; margin-bottom: 8px; }
        .tab-subheading { color: #64748b; font-size: 0.95rem; margin-bottom: 32px; }
        
        @media (max-width: 991px) {
            .login-card { flex-direction: column; max-width: 500px; min-height: auto; }
            .login-sidebar { width: 100%; padding: 40px; text-align: center; align-items: center; }
            .form-container { width: 100%; padding: 40px; }
            .brand-icon-wrapper { margin-left: auto; margin-right: auto; }
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-sidebar">
        <div class="brand-icon-wrapper"><i class="bi bi-bank2 fs-2"></i></div>
        <h1 class="sidebar-title">Modern ERP for Finance</h1>
        <p class="sidebar-text">Precision-engineered for institutional workflows. Manage procurement, budgeting, and billing through a single, intelligent interface.</p>
        
        <div class="mt-auto pt-5 d-none d-lg-block">
            <div class="d-flex align-items-center gap-3 opacity-75">
                <div class="bg-white rounded-circle" style="width: 8px; height: 8px;"></div>
                <span class="small fw-semibold">Cloud-Native Architecture</span>
            </div>
        </div>
    </div>
    
    <div class="form-container">
        <div class="text-center mb-4">
            <h2 class="tab-heading">Dashboard Access</h2>
            <p class="tab-subheading">Enter your secure credentials to continue</p>
        </div>

        <?php if(isset($_GET['logged_out'])): ?>
            <div class="alert alert-success border-0 rounded-4 py-3 px-4 small mb-4 shadow-sm">
                <i class="bi bi-check-circle-fill me-2"></i> You have been logged out successfully.
            </div>
        <?php endif; ?>

        <?php if(isset($error)): ?><div class="alert alert-danger border-0 rounded-4 py-3 px-4 small mb-4 shadow-sm"><?php echo $error; ?></div><?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="login" value="1">
            <div class="mb-4">
                <label class="form-label small fw-bold text-muted mb-2" style="letter-spacing: 0.8px; font-size: 0.65rem;">IDENTITY / USERNAME</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope text-muted"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="username" required>
                </div>
            </div>
            <div class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label small fw-bold text-muted mb-0" style="letter-spacing: 0.8px; font-size: 0.65rem;">SECURITY / PASSWORD</label>
                    <a href="#" class="text-primary small text-decoration-none fw-bold" style="font-size: 0.75rem;">Forgot?</a>
                </div>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-shield-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-4 text-white shadow-sm">
                Enter Dashboard <i class="bi bi-chevron-right ms-2"></i>
            </button>
        </form>

        <div class="text-center mt-4">
            <p class="text-muted small mb-0">Institutional Finance Portal &copy; <?= date('Y') ?></p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
