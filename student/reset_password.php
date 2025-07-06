<?php
session_start();
require '../config/db.php';

$message = '';
$token = $_GET['token'] ?? '';
$show_form = false;

if (empty($token)) {
    $message = '<div class="alert alert-danger">Invalid password reset link.</div>';
} else {
    // Check for the token in the users table
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $show_form = true;
    } else {
        $message = '<div class="alert alert-danger">This password reset link is invalid or has expired.</div>';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $show_form) {
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if ($password === $password_confirm) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $email = $user['email'];

        // Update user's password and clear the reset token
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE email = ?");
        $stmt->execute([$hashed_password, $email]);

        $message = '<div class="alert alert-success">Your password has been reset successfully! You can now <a href="login.php">login</a> with your new password.</div>';
        $show_form = false;
    } else {
        $message = '<div class="alert alert-danger">Passwords do not match.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Exam Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #71b7e6, #9b59b6);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .form-container {
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2 class="text-center fw-bold mb-4">Reset Your Password</h2>
        <?php echo $message; ?>
        <?php if ($show_form): ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password_confirm" class="form-label">Confirm New Password</label>
                <input type="password" name="password_confirm" id="password_confirm" class="form-control" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
