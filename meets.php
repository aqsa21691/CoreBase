<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php"); exit;
}
$user_id = $_SESSION['user_id'];

// Core Request Logic Controllers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $target_id = (int)$_POST['target_id'];

    if ($action === 'send') {
        $chk = $pdo->prepare("SELECT * FROM connections WHERE sender_id = ? AND receiver_id = ?");
        $chk->execute([$user_id, $target_id]);
        if (!$chk->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO connections (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$user_id, $target_id]);
        }
    } elseif ($action === 'accept') {
        // Updated to update status context to 'friends' upon acceptance
        $stmt = $pdo->prepare("UPDATE connections SET status = 'friends' WHERE receiver_id = ? AND sender_id = ?");
        $stmt->execute([$user_id, $target_id]);
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("DELETE FROM connections WHERE (receiver_id = ? AND sender_id = ?) OR (sender_id = ? AND receiver_id = ?)");
        $stmt->execute([$user_id, $target_id, $user_id, $target_id]);
    }
    header("Location: meets.php" . (!empty($_GET['search']) ? "?search=" . urlencode($_GET['search']) : "")); exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoreBase - Meets</title>
    <link rel="stylesheet" href="style.css?v=14">
</head>
<body style="background-color: #f7fafc; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    
    <?php include 'navbar.php'; ?>

    <div style="width: 100%; max-width: 800px; margin: 40px auto; padding: 0 20px; box-sizing: border-box;">
        
        <!-- SEARCH COMPONENT ONLY -->
        <div style="width: 100%; background: #fff; border-radius: 16px; padding: 25px; margin-bottom: 30px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.03); box-sizing: border-box;">
            <h3 style="font-size: 16px; color: #0f294a; font-weight: 800; margin-top: 0; margin-bottom: 15px;">Search Registered User</h3>
            
            <form action="meets.php" method="GET" style="width: 100%; margin: 0;">
                <input type="text" name="search" 
                       style="width: 100%; padding: 12px 20px; border-radius: 8px; border: 2px solid #e2e8f0; font-size: 15px; outline: none; box-sizing: border-box;" 
                       placeholder="🔍 Type name to search..." 
                       value="<?= htmlspecialchars($search) ?>">
            </form>
        </div>

        <!-- 1. INCOMING REQUESTS PANEL -->
        <div style="width: 100%; background: #fff; border-radius: 16px; padding: 25px; margin-bottom: 30px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.03); box-sizing: border-box;">
            <h3 style="font-size: 18px; color: #0f294a; font-weight: 800; margin-top: 0; margin-bottom: 20px; border-bottom: 2px solid #edf2f7; padding-bottom: 10px;">Incoming Requests</h3>
            
            <?php
            $stmt = $pdo->prepare("SELECT c.*, u.fullname FROM connections c JOIN users u ON c.sender_id = u.id WHERE c.receiver_id = ? AND c.status = 'pending'");
            $stmt->execute([$user_id]);
            $requestsIn = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach($requestsIn as $req):
                $avatarLtr = strtoupper(substr($req['fullname'], 0, 1));
            ?>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #f7fafc;">
                    <div style="display: flex; align-items: center;">
                        <div style="width: 44px; height: 44px; border-radius: 50%; background: #0f294a; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-right: 15px; font-size: 16px; text-transform: uppercase;"><?= $avatarLtr ?></div>
                        <span style="font-weight: 700; color: #2d3748; font-size: 16px;"><?= htmlspecialchars($req['fullname']) ?></span>
                    </div>
                    <div>
                        <form action="meets.php?search=<?= urlencode($search) ?>" method="POST" style="display: inline-block; margin: 0;">
                            <input type="hidden" name="target_id" value="<?= $req['sender_id'] ?>">
                            <button type="submit" name="action" value="accept" style="padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; background: #48bb78; color: #fff; margin-right: 6px;">Accept</button>
                            <button type="submit" name="action" value="reject" style="padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; background: #f56565; color: #fff;">Reject</button>
                        </form>
                    </div>
                </div>
            <?php 
            endforeach; 
            if(empty($requestsIn)):
                echo "<p style='color:#a0aec0; font-size:14px; margin: 0;'>No incoming connection requests currently.</p>";
            endif;
            ?>
        </div>

        <!-- 2. ALL LOGGED IN USERS LIST -->
        <div style="width: 100%; background: #fff; border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.03); box-sizing: border-box;">
            <h3 style="font-size: 18px; color: #0f294a; font-weight: 800; margin-top: 0; margin-bottom: 20px; border-bottom: 2px solid #edf2f7; padding-bottom: 10px;">All Logged-in Users</h3>
            
            <div style="width: 100%;">
                <?php
                if ($search !== '') {
                    $stmt = $pdo->prepare("SELECT id, fullname FROM users WHERE id != ? AND fullname LIKE ? ORDER BY fullname ASC");
                    $stmt->execute([$user_id, "%$search%"]);
                } else {
                    $stmt = $pdo->prepare("SELECT id, fullname FROM users WHERE id != ? ORDER BY fullname ASC");
                    $stmt->execute([$user_id]);
                }
                $registeredUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach($registeredUsers as $userItem):
                    $t_id = $userItem['id'];
                    $avatarLtr = strtoupper(substr($userItem['fullname'], 0, 1));

                    // Check relationship status context
                    $rel = $pdo->prepare("SELECT * FROM connections WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
                    $rel->execute([$user_id, $t_id, $t_id, $user_id]);
                    $state = $rel->fetch(PDO::FETCH_ASSOC);
                ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #f7fafc;">
                        <div style="display: flex; align-items: center;">
                            <div style="width: 44px; height: 44px; border-radius: 50%; background: #0f294a; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-right: 15px; font-size: 16px; text-transform: uppercase;"><?= $avatarLtr ?></div>
                            <span style="font-weight: 700; color: #2d3748; font-size: 16px;"><?= htmlspecialchars($userItem['fullname']) ?></span>
                        </div>
                        <div>
                            <?php if(!$state): ?>
                                <form action="meets.php?search=<?= urlencode($search) ?>" method="POST" style="margin: 0;">
                                    <input type="hidden" name="target_id" value="<?= $t_id ?>">
                                    <button type="submit" name="action" value="send" style="padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; background: #0f294a; color: #fff;">Send Request</button>
                                </form>
                            <?php elseif($state['status'] == 'pending' && $state['sender_id'] == $user_id): ?>
                                <button style="padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 700; border: none; background: #edf2f7; color: #718096; cursor: not-allowed;" disabled>Sent</button>
                            <?php elseif($state['status'] == 'pending' && $state['receiver_id'] == $user_id): ?>
                                <form action="meets.php?search=<?= urlencode($search) ?>" method="POST" style="display:inline-block; margin: 0;">
                                    <input type="hidden" name="target_id" value="<?= $t_id ?>">
                                    <button type="submit" name="action" value="accept" style="padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; background: #48bb78; color: #fff; margin-right: 6px;">Accept</button>
                                    <button type="submit" name="action" value="reject" style="padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; background: #f56565; color: #fff;">Reject</button>
                                </form>
                            <?php elseif($state['status'] == 'friends' || $state['status'] == 'accepted'): ?>
                                <!-- Updated rendering text label display to explicitly state Friends status -->
                                <button style="padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 700; border: none; background: #48bb78; color: #fff; cursor: not-allowed;" disabled>Friends</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php 
                endforeach; 
                if(empty($registeredUsers)):
                    echo "<p style='color:#a0aec0; text-align:center; margin: 20px 0 0 0; font-size: 14px;'>No registered users found.</p>";
                endif;
                ?>
            </div>
        </div>

    </div>
</body>
</html>