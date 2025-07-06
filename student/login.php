<?php
session_start();
require '../config/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'student'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if (!$user['verified']) {
            $code = rand(100000, 999999);
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            
            $stmt = $pdo->prepare("INSERT INTO verification_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $code, $expires]);

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
                $mail->Subject = 'Verify Your Email Address';
                $mail->Body = "Dear User,<br><br>Your new verification code is: <b>$code</b><br><br>This code will expire in 10 minutes.<br><br>Best regards,<br>The Exam Portal Team";
                $mail->send();
                header('Location: verify.php?email=' . urlencode($email) . '&resent=true');
                exit;
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger">Mail error: ' . $mail->ErrorInfo . '</div>';
            }
        } elseif (password_verify($password, $user['password'])) {
            $_SESSION['student_id'] = $user['id'];
            header('Location: dashboard.php');
            exit;
        } else {
            $message = '<div class="alert alert-danger">Invalid email or password.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Invalid email or password.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - Exam Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #71b7e6, #9b59b6);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            display: flex;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            animation: fadeIn 1s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .login-branding {
            background: linear-gradient(to bottom, rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://placehold.co/600x800/9b59b6/ffffff?text=Exam+Portal') no-repeat center center;
            background-size: cover;
            color: #fff;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            width: 45%;
        }
        .login-branding h1 {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .login-branding p {
            font-size: 1.1rem;
            max-width: 300px;
        }
        .login-form-container {
            padding: 50px;
            width: 55%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-form-container h2 {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        .login-form-container .form-text {
            color: #666;
            margin-bottom: 30px;
        }
        .form-group {
            position: relative;
            margin-bottom: 25px;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 15px 20px 15px 45px;
            height: 50px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(155, 89, 182, 0.2);
            border-color: #9b59b6;
        }
        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            transition: color 0.3s ease;
        }
        .form-control:focus + i {
            color: #9b59b6;
        }
        .btn-primary {
            background: linear-gradient(135deg, #9b59b6, #71b7e6);
            border: none;
            border-radius: 8px;
            padding: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .alert {
            border-radius: 8px;
        }
        .switch-link {
            font-size: 0.9rem;
        }
        .forgot-password {
            text-align: right;
            margin-top: -15px;
            margin-bottom: 15px;
        }
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            .login-branding, .login-form-container {
                width: 100%;
            }
            .login-branding {
                padding: 30px;
            }
            .login-form-container {
                padding: 30px;
            }
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-branding">
            <i class="fas fa-graduation-cap fa-4x mb-4"></i>
            <h1>Welcome Back!</h1>
            <p>Your journey to success continues here. Please log in to access your exams.</p>
        </div>
        <div class="login-form-container">
            <h2>Student Login</h2>
            <p class="form-text text-muted">Enter your credentials to access your account.</p>

            <?php echo $message; ?>

            <form method="POST">
                <div class="form-group">
                    <input type="email" name="email" id="email" class="form-control" placeholder="Email address" required>
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="form-group">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                    <i class="fas fa-lock"></i>
                </div>
                <div class="forgot-password">
                    <a href="forgot_password.php" class="switch-link">Forgot Password?</a>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Login Securely</button>
                </div>
            </form>
            <div class="text-center mt-4">
                <p class="switch-link">Don't have an account? <a href="register.php">Register here</a></p>
                <p class="switch-link mt-2"><a href="../admin/login.php">Switch to Admin Login</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
