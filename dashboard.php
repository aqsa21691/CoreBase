<?php
require 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoreBase  - Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="main-wrapper">
        <div class="dashboard-hero">
            <h1>Welcome, <?= htmlspecialchars($_SESSION['fullname']) ?>!</h1>
           
        </div>
    </div>
</body>
</html>