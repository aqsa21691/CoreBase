<?php
require 'config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $otp = sprintf("%04d", mt_rand(0, 9999));

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $error = "Email already registered!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, otp, is_verified) VALUES (?, ?, ?, ?, 0)");
        if ($stmt->execute([$fullname, $email, $password, $otp])) {
            $_SESSION['verify_email'] = $email;
            sendOTPEmail($email, $otp, "Verify Your DialDock Registration");
            header("Location: verify.php?action=signup");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoreBase - Sign Up</title>
    <!-- CSS version updated to ?v=6 to force clean style updates -->
    <link rel="stylesheet" href="style.css?v=6">
</head>
<body>

    <!-- Perfect centered box grid wrapper -->
    <div class="auth-page-wrapper">
        <div class="card">
            
            <h2>Sign Up</h2>
            
            <?php if($error): ?>
                <p style="color: #dc2626; text-align: center; margin-bottom: 15px; font-weight: 600;">
                    <?= htmlspecialchars($error) ?>
                </p>
            <?php endif; ?>
            
            <form action="signup.php" method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" required>
                </div>
                
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-primary">Sign Up</button>
            </form>
            
            <!-- Fixed alignment for bottom footer link -->
            <div class="auth-footer">
                Already have an account? <a href="signin.php">Sign In</a>
            </div>

        </div>
    </div>

</body>
</html>