<?php
session_start();
require '../config/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'student'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generate a secure, unique token
        $token = bin2hex(random_bytes(50));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store the token and expiry in the users table
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE email = ?");
        $stmt->execute([$token, $expires, $email]);
        
        // Construct the reset link
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;

        // Send the email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'engtisarkuru13@gmail.com';
            $mail->Password = 'cpqs qqei cvve uobm';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('engtisarkuru13@gmail.com', 'Exam Portal');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "Dear User,<br><br>You have requested to reset your password. Please click the link below to proceed:<br><br>"
                        . "<a href='{$reset_link}'>{$reset_link}</a><br><br>"
                        . "This link will expire in 1 hour.<br><br>"
                        . "If you did not request a password reset, please ignore this email.<br><br>"
                        . "Best regards,<br>The Exam Portal Team";
            $mail->send();
            $message = '<div class="alert alert-success">If an account with that email exists, a password reset link has been sent. Please check your inbox.</div>';
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">Could not send email. Mailer Error: ' . $mail->ErrorInfo . '</div>';
        }
    } else {
        // Show a generic message to prevent user enumeration
        $message = '<div class="alert alert-success">If an account with that email exists, a password reset link has been sent. Please check your inbox.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Exam Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <h2 class="text-center fw-bold mb-3">Forgot Your Password?</h2>
        <p class="text-center text-muted mb-4">Enter your email address and we will send you a link to reset your password.</p>
        <?php echo $message; ?>
        <form method="POST">
            <div class="form-group mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Send Reset Link</button>
            </div>
        </form>
        <div class="text-center mt-3">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>
