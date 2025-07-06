<?php
session_start();
require '../config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare and execute the statement to find an admin user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    // Verify admin existence and password
    if ($admin && password_verify($password, $admin['password'])) {
        // Set session for admin
        $_SESSION['admin_id'] = $admin['id'];
        header('Location: dashboard.php');
        exit;
    } else {
        // Set error message for invalid credentials
        $message = '<div class="alert alert-danger">Invalid email or password. Please try again.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Exam Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
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
            background: linear-gradient(135deg, #2c3e50, #34495e);
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
            opacity: 0.8;
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
            color: #2c3e50;
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
            box-shadow: 0 0 0 4px rgba(44, 62, 80, 0.2);
            border-color: #2c3e50;
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
            color: #2c3e50;
        }
        .btn-primary {
            background: #2c3e50;
            border: none;
            border-radius: 8px;
            padding: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .btn-primary:hover {
            background: #34495e;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .alert {
            border-radius: 8px;
        }
        .switch-link {
            font-size: 0.9rem;
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
            <i class="fas fa-user-shield fa-4x mb-4"></i>
            <h1>Admin Panel</h1>
            <p>Manage exams, questions, and student results with powerful tools at your fingertips.</p>
        </div>
        <div class="login-form-container">
            <h2>Administrator Login</h2>
            <p class="form-text text-muted">Please enter your credentials to continue.</p>

            <?php if (!empty($message)) echo $message; ?>

            <form method="POST">
                <div class="form-group">
                    <input type="email" name="email" id="email" class="form-control" placeholder="Email address" required autofocus>
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="form-group">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                    <i class="fas fa-lock"></i>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
            <div class="text-center mt-4">
                <p class="switch-link"><a href="../student/login.php">Switch to Student Login</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
