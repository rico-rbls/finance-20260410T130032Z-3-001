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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["register"])) {
    $username = trim($_POST["username"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $full_name = trim($_POST["full_name"]);
    $role = "student"; 

    if (empty($username) || empty($full_name)) {
        $error = "All fields are required.";
    } else {
        $check = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "Username already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $password, $full_name, $role);
            if ($stmt->execute()) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
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
            --primary: #1e6b3e;
            --primary-dark: #14492a;
            --accent: #d4fce4;
        }
        body {
            background-color: #f8faf9;
            background-image: 
                radial-gradient(at 0% 0%, rgba(30, 107, 62, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(45, 161, 95, 0.08) 0px, transparent 50%);
            font-family: "Plus Jakarta Sans", sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            overflow-x: hidden;
        }
        .login-card {
            border: none;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(30, 107, 62, 0.15);
            background: white;
            overflow: hidden;
            min-height: 600px;
        }
        .login-sidebar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
        }
        .form-container { padding: 4rem 3rem; }
        .nav-pills { background: #f1f5f2; padding: 6px; border-radius: 16px; }
        .nav-pills .nav-link { border-radius: 12px; color: #64748b; font-weight: 600; padding: 10px; transition: all 0.2s; }
        .nav-pills .nav-link.active { background-color: white; color: var(--primary); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .form-control { border-radius: 12px; padding: 0.75rem 1rem; background: #f8faf9; border: 1px solid #e2e8f0; }
        .form-control:focus { background-color: white; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(30, 107, 62, 0.1); }
        .btn-primary { background: var(--primary); border: none; border-radius: 12px; padding: 14px; font-weight: 600; transition: all 0.2s; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(30, 107, 62, 0.2); }
        .brand-icon-wrapper { width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; }
    </style>
</head>
<body>
<div class="container">
    <div class="row min-vh-100 align-items-center justify-content-center py-5">
        <div class="col-12 col-md-11 col-lg-10 shadow-lg login-card p-0">
            <div class="row g-0">
                <div class="col-lg-5 d-none d-lg-flex login-sidebar">
                    <div class="brand-icon-wrapper"><i class="bi bi-bank2 fs-2"></i></div>
                    <h2 class="fw-bold mb-3" style="font-family: \"Outfit\";">UniFinance</h2>
                    <h1 class="display-6 fw-bold mb-4" style="font-family: \"Outfit\";">Modern ERP for Modern Campuses</h1>
                    <p class="opacity-75 mb-5 px-3">Securely manage institutional funds, procurement workflows, and student accounts in one centralized platform.</p>
                </div>
                <div class="col-lg-7">
                    <div class="form-container">
                        <ul class="nav nav-pills mb-4 justify-content-center" id="accessTabs" role="tablist">
                            <li class="nav-item w-50"><button class="nav-link w-100 active" data-bs-toggle="pill" data-bs-target="#loginPane" type="button">Sign In</button></li>
                            <li class="nav-item w-50"><button class="nav-link w-100" data-bs-toggle="pill" data-bs-target="#registerPane" type="button">Register</button></li>
                        </ul>
                        <?php if(isset($error)): ?><div class="alert alert-danger border-0 rounded-4 py-2 small mb-4"><?php echo $error; ?></div><?php endif; ?>
                        <?php if(isset($success)): ?><div class="alert alert-success border-0 rounded-4 py-2 small mb-4"><?php echo $success; ?></div><?php endif; ?>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="loginPane">
                                <form method="POST">
                                    <input type="hidden" name="login" value="1">
                                    <div class="mb-3"><label class="form-label small fw-bold text-muted">USERNAME</label><input type="text" name="username" class="form-control" required></div>
                                    <div class="mb-4"><label class="form-label small fw-bold text-muted">PASSWORD</label><input type="password" name="password" class="form-control" required></div>
                                    <button type="submit" class="btn btn-primary w-100 mb-3 text-white fw-bold shadow-sm">Login to System</button>
                                </form>
                            </div>
                            <div class="tab-pane fade" id="registerPane">
                                <form method="POST">
                                    <input type="hidden" name="register" value="1">
                                    <div class="mb-3"><label class="form-label small fw-bold text-muted">FULL NAME</label><input type="text" name="full_name" class="form-control" required></div>
                                    <div class="mb-3"><label class="form-label small fw-bold text-muted">USERNAME</label><input type="text" name="username" class="form-control" required></div>
                                    <div class="mb-4"><label class="form-label small fw-bold text-muted">PASSWORD</label><input type="password" name="password" class="form-control" required></div>
                                    <button type="submit" class="btn btn-primary w-100 mb-3 text-white fw-bold shadow-sm">Create Account</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
