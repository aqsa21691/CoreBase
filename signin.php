<?php
require 'config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['is_verified'] == 0) {
            $otp = sprintf("%04d", mt_rand(0, 9999));
            $pdo->prepare("UPDATE users SET otp = ? WHERE email = ?")->execute([$otp, $email]);
            $_SESSION['verify_email'] = $email;
            sendOTPEmail($email, $otp);
            header("Location: verify.php?action=signup");
            exit;
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid entry criteria or password records match failed.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoreBase - Sign In</title>
    <!-- CSS version updated to match forced styles reset -->
    <link rel="stylesheet" href="style.css?v=6">
</head>
<body>

    <!-- Centered layout configuration wrapper -->
    <div class="auth-page-wrapper">
        <div class="card">
            
            <h2>Sign In</h2>
            
            <?php if(isset($_GET['msg']) && $_GET['msg']=='verified'): ?>
                <p style="color: #16a34a; text-align: center; margin-bottom: 15px; font-weight: 600;">
                    Account verified successfully! Please sign in.
                </p>
            <?php endif; ?>
            
            <?php if($error): ?>
                <p style="color: #dc2626; text-align: center; margin-bottom: 15px; font-weight: 600;">
                    <?= htmlspecialchars($error) ?>
                </p>
            <?php endif; ?>
            
            <form action="signin.php" method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <!-- Flex container to balance space alignment perfectly -->
                <div class="auth-links">
                    <span></span> <!-- Symmetrical spacer -->
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn-primary">Sign In</button>
            </form>
            
            <!-- Global centered alignment footer setup -->
            <div class="auth-footer">
                Don't have an account? <a href="signup.php">Sign Up</a>
            </div>

        </div>
    </div>

</body>
</html>