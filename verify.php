<?php
require 'config.php';
$error = '';
$action = $_GET['action'] ?? 'signup';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp']);
    $email = $_SESSION['verify_email'] ?? '';

    if ($action === 'signup') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND otp = ?");
        $stmt->execute([$email, $otp]);
        if ($stmt->fetch()) {
            $update = $pdo->prepare("UPDATE users SET is_verified = 1, otp = NULL WHERE email = ?");
            $update->execute([$email]);
            unset($_SESSION['verify_email']);
            header("Location: signin.php?msg=verified");
            exit;
        } else {
            $error = "Invalid 4-digit code structural format validation failed.";
        }
    } elseif ($action === 'forgot') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND otp = ?");
        $stmt->execute([$email, $otp]);
        if ($stmt->fetch()) {
            $_SESSION['reset_allowed'] = true;
            header("Location: reset_password.php");
            exit;
        } else {
            $error = "Invalid 4-digit code.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <h2>Email Verification</h2>
        <p style="text-align: center; margin-bottom:15px;">Please enter the 4-digit OTP code sent to your email.</p>
        <?php if($error): ?><p style="color:red; text-align:center;"><?= $error ?></p><?php endif; ?>
        <form action="verify.php?action=<?= htmlspecialchars($action) ?>" method="POST">
            <div class="form-group">
                <label>Enter 4-Digit OTP</label>
                <input type="text" name="otp" maxlength="4" pattern="\d{4}" style="text-align:center; font-size:20px; letter-spacing:5px;" required>
            </div>
            <button type="submit" class="btn-primary">Verify Code</button>
        </form>
    </div>
</body>
</html>