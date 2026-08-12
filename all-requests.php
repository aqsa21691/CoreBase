<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php"); exit;
}
$user_id = $_SESSION['user_id'];

// Controller for Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $target_id = (int)$_POST['target_id'];

    if ($action === 'accept') {
        $stmt = $pdo->prepare("UPDATE connections SET status = 'friends' WHERE receiver_id = ? AND sender_id = ?");
        $stmt->execute([$user_id, $target_id]);
    } elseif ($action === 'cancel' || $action === 'reject') {
        $stmt = $pdo->prepare("DELETE FROM connections WHERE (sender_id = ? AND receiver_id = ? AND status = 'pending') OR (receiver_id = ? AND sender_id = ? AND status = 'pending')");
        $stmt->execute([$user_id, $target_id, $user_id, $target_id]);
    }
    header("Location: all-requests.php"); exit;
}

// 1. Fetch Outgoing / Sent Requests (By Me)
$stmtOut = $pdo->prepare("SELECT c.*, u.fullname FROM connections c JOIN users u ON c.receiver_id = u.id WHERE c.sender_id = ? AND c.status = 'pending' ORDER BY u.fullname ASC");
$stmtOut->execute([$user_id]);
$sentRequests = $stmtOut->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch Incoming / Received Requests (To Me)
$stmtIn = $pdo->prepare("SELECT c.*, u.fullname FROM connections c JOIN users u ON c.sender_id = u.id WHERE c.receiver_id = ? AND c.status = 'pending' ORDER BY u.fullname ASC");
$stmtIn->execute([$user_id]);
$receivedRequests = $stmtIn->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoreBase - All Requests</title>
    <link rel="stylesheet" href="style.css?v=15">
</head>
<body style="background-color: #f7fafc; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    
    <?php include 'navbar.php'; ?>

    <div style="width: 100%; max-width: 800px; margin: 40px auto; padding: 0 20px; box-sizing: border-box;">
        
        <!-- SECTION 1: RECEIVED REQUESTS (INCOMING) -->
        <div style="width: 100%; background: #fff; border-radius: 16px; padding: 25px; margin-bottom: 30px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.03); box-sizing: border-box;">
            <h3 style="font-size: 18px; color: #0f294a; font-weight: 800; margin-top: 0; margin-bottom: 20px; border-bottom: 2px solid #edf2f7; padding-bottom: 10px;">Received Requests</h3>
            
            <?php 
            foreach($receivedRequests as $req): 
                $avatarLtr = strtoupper(substr($req['fullname'], 0, 1));
            ?>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #f7fafc; flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; align-items: center;">
                        <div style="width: 44px; height: 44px; border-radius: 50%; background: #0f294a; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-right: 15px; font-size: 16px; text-transform: uppercase;"><?= $avatarLtr ?></div>
                        <span style="font-weight: 700; color: #2d3748; font-size: 16px;"><?= htmlspecialchars($req['fullname']) ?></span>
                    </div>
                    <div>
                        <form action="all-requests.php" method="POST" style="display: inline-block; margin: 0;">
                            <input type="hidden" name="target_id" value="<?= $req['sender_id'] ?>">
                            <button type="submit" name="action" value="accept" style="padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; background: #48bb78; color: #fff; margin-right: 6px;">Accept</button>
                            <button type="submit" name="action" value="reject" style="padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; background: #f56565; color: #fff;">Reject</button>
                        </form>
                    </div>
                </div>
            <?php 
            endforeach; 
            if(empty($receivedRequests)):
                echo "<p style='color:#a0aec0; font-size:14px; margin: 0;'>No received requests pending.</p>";
            endif;
            ?>
        </div>

        <!-- SECTION 2: SENT REQUESTS (OUTGOING WITH CANCEL) -->
        <div style="width: 100%; background: #fff; border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.03); box-sizing: border-box;">
            <h3 style="font-size: 18px; color: #0f294a; font-weight: 800; margin-top: 0; margin-bottom: 20px; border-bottom: 2px solid #edf2f7; padding-bottom: 10px;">Sent Requests</h3>
            
            <?php 
            foreach($sentRequests as $req): 
                $avatarLtr = strtoupper(substr($req['fullname'], 0, 1));
            ?>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #f7fafc; flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; align-items: center;">
                        <div style="width: 44px; height: 44px; border-radius: 50%; background: #0f294a; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-right: 15px; font-size: 16px; text-transform: uppercase;"><?= $avatarLtr ?></div>
                        <span style="font-weight: 700; color: #2d3748; font-size: 16px;"><?= htmlspecialchars($req['fullname']) ?></span>
                    </div>
                    <div>
                        <form action="all-requests.php" method="POST" style="margin: 0;">
                            <input type="hidden" name="target_id" value="<?= $req['receiver_id'] ?>">
                            <button type="submit" name="action" value="cancel" style="padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; background: #f56565; color: #fff;">Cancel Request</button>
                        </form>
                    </div>
                </div>
            <?php 
            endforeach; 
            if(empty($sentRequests)):
                echo "<p style='color:#a0aec0; font-size:14px; margin: 0;'>No sent requests pending.</p>";
            endif;
            ?>
        </div>

    </div>
</body>
</html>