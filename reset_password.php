<!-- reset_password.php -->
<?php
require 'config.php';
if (!isset($_SESSION['reset_allowed']) || !isset($_SESSION['verify_email'])) {
    header("Location: signin.php"); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $email = $_SESSION['verify_email'];
    $stmt = $pdo->prepare("UPDATE users SET password = ?, otp = NULL WHERE email = ?");
    $stmt->execute([$new_pass, $email]);
    unset($_SESSION['reset_allowed'], $_SESSION['verify_email']);
    header("Location: signin.php?msg=verified"); exit;
}
?>
<!DOCTYPE html>
<html lang="en"><head><title>Create New Password</title><link rel="stylesheet" href="style.css"></head><body>
<div class="auth-container">
    <h2>Reset Password</h2>
    <form action="reset_password.php" method="POST">
        <div class="form-group"><label>New Password</label><input type="password" name="password" required></div>
        <button type="submit" class="btn-primary">Update Password</button>
    </form>
</div></body></html>