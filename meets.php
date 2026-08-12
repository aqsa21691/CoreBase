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
        $chk = $pdo->prepare("SELECT * FROM connections WHERE (sender_id = ? AND receiver_id = ?) OR (receiver_id = ? AND sender_id = ?)");
        $chk->execute([$user_id, $target_id, $user_id, $target_id]);
        if (!$chk->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO connections (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$user_id, $target_id]);
        }
    } elseif ($action === 'cancel' || $action === 'reject' || $action === 'unfriend') {
        // Safe Delete Row
        $stmt = $pdo->prepare("DELETE FROM connections WHERE (sender_id = ? AND receiver_id = ?) OR (receiver_id = ? AND sender_id = ?)");
        $stmt->execute([$user_id, $target_id, $user_id, $target_id]);
    }
    
    $search_param = !empty($_GET['search']) ? "?search=" . urlencode($_GET['search']) : "";
    $page_param = !empty($_GET['page']) ? (empty($search_param) ? "?page=" : "&page=") . (int)$_GET['page'] : "";
    header("Location: meets.php" . $search_param . $page_param); exit;
}

// Lazy Loading Settings
$limit = 10; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Count total users
if ($search !== '') {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id != ? AND fullname LIKE ?");
    $count_stmt->execute([$user_id, "%$search%"]);
} else {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id != ?");
    $count_stmt->execute([$user_id]);
}
$total_users = $count_stmt->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Fetch Paginated Users
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT id, fullname FROM users WHERE id != ? AND fullname LIKE ? ORDER BY fullname ASC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(3, $limit, PDO::PARAM_INT);
    $stmt->bindValue(4, $offset, PDO::PARAM_INT);
} else {
    $stmt = $pdo->prepare("SELECT id, fullname FROM users WHERE id != ? ORDER BY fullname ASC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
}
$stmt->execute();
$registeredUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoreBase - Meets</title>
</head>
<body style="background-color: #f7fafc; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    
    <?php include 'navbar.php'; ?>

    <div style="width: 100%; max-width: 800px; margin: 40px auto; padding: 0 20px; box-sizing: border-box;">
        
        <!-- SEARCH COMPONENT -->
        <div style="width: 100%; background: #fff; border-radius: 16px; padding: 25px; margin-bottom: 30px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.03); box-sizing: border-box;">
            <h3 style="font-size: 16px; color: #0f294a; font-weight: 800; margin-top: 0; margin-bottom: 15px;">Search Registered User</h3>
            <form action="meets.php" method="GET" style="width: 100%; margin: 0;">
                <input type="text" name="search" 
                       style="width: 100%; padding: 12px 20px; border-radius: 8px; border: 2px solid #e2e8f0; font-size: 15px; outline: none; box-sizing: border-box;" 
                       placeholder="🔍 Type name to search..." 
                       value="<?= htmlspecialchars($search) ?>">
            </form>
        </div>

        <!-- USERS LIST -->
        <div style="width: 100%; background: #fff; border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.03); box-sizing: border-box;">
            <h3 style="font-size: 18px; color: #0f294a; font-weight: 800; margin-top: 0; margin-bottom: 20px; border-bottom: 2px solid #edf2f7; padding-bottom: 10px;">All Logged-in Users</h3>
            
            <div style="width: 100%;">
                <?php
                foreach($registeredUsers as $userItem):
                    $t_id = $userItem['id'];
                    $avatarLtr = strtoupper(substr($userItem['fullname'], 0, 1));

                    $rel = $pdo->prepare("SELECT * FROM connections WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
                    $rel->execute([$user_id, $t_id, $t_id, $user_id]);
                    $state = $rel->fetch(PDO::FETCH_ASSOC);
                ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #f7fafc; flex-wrap: wrap; gap: 10px;">
                        <div style="display: flex; align-items: center;">
                            <div style="width: 44px; height: 44px; border-radius: 50%; background: #0f294a; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-right: 15px; font-size: 16px; text-transform: uppercase;"><?= $avatarLtr ?></div>
                            <span style="font-weight: 700; color: #2d3748; font-size: 16px;"><?= htmlspecialchars($userItem['fullname']) ?></span>
                        </div>
                        <div>
                            <?php if(!$state): ?>
                                <form action="meets.php?search=<?= urlencode($search) ?>&page=<?= $page ?>" method="POST" style="margin: 0;">
                                    <input type="hidden" name="target_id" value="<?= $t_id ?>">
                                    <button type="submit" name="action" value="send" style="padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; background: #0f294a; color: #fff; display: inline-block;">Send Request</button>
                                </form>
                            <?php elseif($state['status'] === 'pending' && $state['sender_id'] == $user_id): ?>
                                <form action="meets.php?search=<?= urlencode($search) ?>&page=<?= $page ?>" method="POST" style="margin: 0;">
                                    <input type="hidden" name="target_id" value="<?= $t_id ?>">
                                    <button type="submit" name="action" value="cancel" style="padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; background: #f56565; color: #fff; display: inline-block;">Cancel Request</button>
                                </form>
                            <?php elseif($state['status'] === 'pending' && $state['receiver_id'] == $user_id): ?>
                                <button style="padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 700; border: none; background: #edf2f7; color: #718096; cursor: not-allowed; display: inline-block;" disabled>Requested You</button>
                            <?php else: ?>
                                <!-- FIXED: Standard HTML submit button used, zero Javascript popups -->
                                <form action="meets.php?search=<?= urlencode($search) ?>&page=<?= $page ?>" method="POST" style="margin: 0; display: inline-block;">
                                    <input type="hidden" name="target_id" value="<?= $t_id ?>">
                                    <button type="submit" name="action" value="unfriend" style="padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; background: #0f294a; color: #ffffff; display: block;">Unfriend</button>
                                </form>
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

            <!-- Pagination Controls -->
            <?php if($total_pages > 1): ?>
                <div style="display: flex; justify-content: center; align-items: center; margin-top: 25px; gap: 8px;">
                    <?php if($page > 1): ?>
                        <a href="meets.php?search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>" style="padding: 8px 14px; background: #0f294a; color: #fff; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 700;">Prev</a>
                    <?php endif; ?>
                    
                    <span style="font-size: 14px; color: #718096; font-weight: 600;">Page <?= $page ?> of <?= $total_pages ?></span>
                    
                    <?php if($page < $total_pages): ?>
                        <a href="meets.php?search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>" style="padding: 8px 14px; background: #0f294a; color: #fff; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 700;">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>
