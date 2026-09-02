<?php
require_once 'auth.php';

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (($_POST['user'] ?? '') === $admin_user && ($_POST['pass'] ?? '') === $admin_pass) {
        $_SESSION['admin_status'] = 'logged_in';
        header("Location: index.php");
        exit;
    } else {
        $error = "Username atau Password salah!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Admin</title>
    <style>
        body { background: #2c1a47; color: white; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-card { background: #1f1235; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); width: 320px; }
        input { width: 100%; padding: 12px; margin: 10px 0; border-radius: 8px; border: 1px solid #4c3575; background: #2c1a47; color: white; box-sizing: border-box; }
        button { background: #ffcc00; border: none; padding: 12px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        .error { color: #ff4757; text-align: center; margin-bottom: 10px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 style="text-align: center;">Dashboard Login</h2>
        <?php if($error) echo "<div class='error'>$error</div>"; ?>
        <form method="POST">
            <input type="text" name="user" placeholder="Username" required>
            <input type="password" name="pass" placeholder="Password" required>
            <button type="submit">MASUK</button>
        </form>
    </div>
</body>
</html>