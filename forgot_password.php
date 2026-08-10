<!-- forgot_password.php -->
<?php
require 'config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $otp = sprintf("%04d", mt_rand(0, 9999));
        $pdo->prepare("UPDATE users SET otp = ? WHERE email = ?")->execute([$otp, $email]);
        $_SESSION['verify_email'] = $email;
        sendOTPEmail($email, $otp, "Reset Password Verification OTP");
        header("Location: verify.php?action=forgot");
        exit;
    } else {
        $error = "No user found account directory registration database check matched.";
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head><title>Forgot Password</title><link rel="stylesheet" href="style.css"></head><body>
<div class="auth-container">
    <h2>Forgot Password</h2>
    <?php if($error): ?><p style="color:red;"><?= $error ?></p><?php endif; ?>
    <form action="forgot_password.php" method="POST">
        <div class="form-group"><label>Enter Registered Email</label><input type="email" name="email" required></div>
        <button type="submit" class="btn-primary">Send Reset OTP</button>
    </form>
</div></body></html>