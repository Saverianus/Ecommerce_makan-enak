<?php
session_start();
require_once 'config/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = $conn->real_escape_string(trim($_POST['email']));
    $password = $_POST['password'];
    $res = $conn->query("SELECT * FROM users WHERE email='$email' AND is_active=1");
    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['name']    = $user['name'];
            header('Location: ' . ($user['role'] === 'admin' ? 'admin/products.php' : 'index.php'));
            exit;
        }
    }
    $error = 'Email atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - MakanEnak</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        .login-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--bg); }
        .login-card { background: #fff; border-radius: 20px; padding: 40px; width: 400px; border: 0.5px solid var(--border); }
        .login-card h1 { font-family: var(--ff-display); font-size: 28px; margin-bottom: 8px; }
        .login-card p  { color: var(--muted); font-size: 14px; margin-bottom: 28px; }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="logo" style="margin-bottom:24px">Makan<span>Enak</span></div>
        <h1>Selamat Datang 👋</h1>
        <p>Masuk untuk melanjutkan</p>
        <?php if ($error): ?>
            <div class="alert-error"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="email@kamu.com" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;padding:13px;margin-top:8px;font-size:15px;border-radius:12px">
                Masuk →
            </button>
        </form>
        <p style="text-align:center;margin-top:16px;font-size:13px;color:var(--muted)">
            <a href="index.php">← Kembali ke Beranda</a>
        </p>
    </div>
</div>
</body>
</html>
