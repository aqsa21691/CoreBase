<?php
require 'config.php';
$user_id = $_SESSION['user_id'];

// Core execution handler actions
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'delete') {
        $id = $_GET['id'];
        $pdo->prepare("DELETE FROM contacts WHERE id = ? AND user_id = ?")->execute([$id, $user_id]);
        header("Location: contacts.php"); exit;
    }
    
    // Favorite toggle karne ka robust query logic
    if ($_GET['action'] == 'toggle_fav') {
        $id = $_GET['id'];
        $pdo->prepare("UPDATE contacts SET is_favorite = CASE WHEN is_favorite = 1 THEN 0 ELSE 1 END WHERE id = ? AND user_id = ?")->execute([$id, $user_id]);
        header("Location: contacts.php"); exit;
    }
    
    if ($_GET['action'] == 'log_call') {
        $phone = $_GET['phone'];
        $stmt = $pdo->prepare("SELECT id FROM contacts WHERE phone = ? AND user_id = ?");
        $stmt->execute([$phone, $user_id]);
        $c = $stmt->fetch();
        $contact_id = $c ? $c['id'] : null;
        
        $pdo->prepare("INSERT INTO history (user_id, contact_id, phone_dialed) VALUES (?, ?, ?)")->execute([$user_id, $contact_id, $phone]);
        echo "Logged"; exit;
    }
}

// Handle Add Contact Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_contact'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $photo_filename = null;

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $photo_filename = $target_dir . time() . "_" . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo_filename);
    }

    $stmt = $pdo->prepare("INSERT INTO contacts (user_id, name, email, phone, photo) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $name, $email, $phone, $photo_filename]);
    header("Location: contacts.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>CoreBase - Contacts Platform</title>
    <link rel="stylesheet" href="style.css?v=6">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <!-- MAIN CONTACTS SCREEN VIEWPORT -->
    <div class="main-wrapper">
        
        <!-- 1. CREATE NEW CONTACT FORM -->
        <div class="card" id="contactFormContainer" style="margin-bottom: 30px;">
            <h3>Create New Contact</h3>
            <form action="contacts.php" method="POST" enctype="multipart/form-data" style="margin-top:15px;">
                <input type="hidden" name="add_contact" value="1">
                <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email"></div>
                <div class="form-group"><label>Phone Number</label><input type="text" id="formPhoneField" name="phone" placeholder="e.g. 923001234567" required></div>
                <div class="form-group"><label>Photo (Optional)</label><input type="file" name="photo" accept="image/*"></div>
                <button type="submit" class="btn-primary">Create New Contact</button>
            </form>
        </div>

        <!-- 2. ALL CONTACTS LIST DIRECTORY -->
        <div class="card">
            <h3>All Contacts Directory</h3>
            <div style="margin-top:15px;" id="contactsListContainer">
                <?php
                $c_stmt = $pdo->prepare("SELECT * FROM contacts WHERE user_id = ? ORDER BY is_favorite DESC, name ASC");
                $c_stmt->execute([$user_id]);
                $contacts_array = $c_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach($contacts_array as $contact):
                    $is_fav = $contact['is_favorite'];
                    
                    // WhatsApp international prefix handler
                    $wa_phone = preg_replace('/[^0-9]/', '', $contact['phone']);
                    if (substr($wa_phone, 0, 1) === '0') {
                        $wa_phone = '92' . substr($wa_phone, 1);
                    }
                ?>
                    <div class="contact-item <?= $is_fav ? 'favorite' : '' ?>" data-phone="<?= htmlspecialchars($contact['phone']) ?>">
                        
                        <!-- Avatar Container -->
                        <?php if($contact['photo']): ?>
                            <img src="<?= htmlspecialchars($contact['photo']) ?>" class="contact-avatar" alt="photo">
                        <?php else: ?>
                            <div class="contact-avatar" style="background:#002060; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:14px;"><?= strtoupper(substr($contact['name'],0,1)) ?></div>
                        <?php endif; ?>
                        
                        <!-- Details Wrapper Block -->
                        <div>
                            <strong>
                                <?= htmlspecialchars($contact['name']) ?> 
                                <a href="contacts.php?action=toggle_fav&id=<?= $contact['id'] ?>" class="fav-star" title="Toggle Favorite">
                                    <?= $is_fav ? '★' : '☆' ?>
                                </a>
                            </strong>
                            
                            <p><?= htmlspecialchars($contact['phone']) ?> | <?= htmlspecialchars($contact['email']) ?></p>
                            
                            <!-- Action Icons Stacked Perfectly Under Phone/Email -->
                            <div class="action-icons">
                                <!-- 1. PHONE CALL -->
                                <a href="tel:<?= htmlspecialchars($contact['phone']) ?>" class="icon-circle" onclick="logCallHistory('<?= htmlspecialchars($contact['phone']) ?>')" title="Call">
                                    <svg width="16" height="16" viewBox="0 0 24 24"><path d="M20 15.5c-1.2 0-2.4-.2-3.6-.6-.3-.1-.7 0-1 .3l-2.2 2.2c-2.8-1.4-5.1-3.8-6.6-6.6l2.2-2.2c.3-.3.4-.7.2-1-.3-1.1-.5-2.3-.5-3.5 0-.6-.4-1-1-1H4c-.6 0-1 .4-1 1 0 9.4 7.6 17 17 17 .6 0 1-.4 1-1v-3.5c0-.6-.4-1-1-1z"/></svg>
                                </a>

                                <!-- 2. WHATSAPP CHAT -->
                                <a href="https://api.whatsapp.com/send?phone=<?= $wa_phone ?>" target="_blank" class="icon-circle" title="WhatsApp">
                                    <svg width="16" height="16" viewBox="0 0 24 24"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2zm5.83 14.03c-.24.68-1.2 1.24-1.65 1.29-.45.05-1.02.07-3.11-.79-2.67-1.1-4.39-3.81-4.52-3.99-.13-.17-1.09-1.45-1.09-2.76 0-1.31.68-1.96.93-2.22.25-.26.54-.32.72-.32.18 0 .36 0 .51.01.16.01.37-.06.58.45.22.53.75 1.83.82 1.97.07.15.11.32.01.52-.1.2-.21.32-.36.49-.15.17-.32.39-.46.52-.16.15-.33.31-.14.63.19.32.83 1.37 1.79 2.22.19.17.36.3.56.39.2.09.39.11.54.08.38-.07.93-.38 1.06-.74.13-.36.13-.67.09-.74-.04-.07-.15-.11-.32-.19z"/></svg>
                                </a>

                                <!-- 3. EMAIL CLIENT -->
                                <a href="mailto:<?= htmlspecialchars($contact['email']) ?>" class="icon-circle" title="Email">
                                    <svg width="16" height="16" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                </a>

                                <!-- 4. DELETE CONTACT -->
                                <a href="contacts.php?action=delete&id=<?= $contact['id'] ?>" class="icon-circle btn-delete" onclick="return confirm('Delete contact permanently?')" title="Delete">
                                    <svg width="14" height="14" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                </a>
                            </div>
                        </div>
                        
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- RECENT HISTORY POPUP MODAL -->
    <div id="historyModalOverlay" class="history-modal-overlay" onclick="closeModalOnBackdrop(event, 'historyModalOverlay')">
        <div class="history-modal-content">
            <button class="close-modal-btn" onclick="toggleModal('historyModalOverlay', false)">&times;</button>
            <h3 style="text-align: center; border-bottom: 2px solid var(--navy-blue); padding-bottom: 10px;">Recent History</h3>
            <div class="modal-history-list">
                <?php
                $hist_stmt = $pdo->prepare("SELECT h.*, c.name FROM history h LEFT JOIN contacts c ON h.contact_id = c.id WHERE h.user_id = ? ORDER BY h.dialed_at DESC LIMIT 15");
                $hist_stmt->execute([$user_id]);
                $has_history = false;
                while($h = $hist_stmt->fetch()):
                    $has_history = true;
                ?>
                    <div style="font-size:13px; padding:10px 0; border-bottom:1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="color: var(--navy-blue);"><?= $h['name'] ? htmlspecialchars($h['name']) : htmlspecialchars($h['phone_dialed']) ?></strong>
                            <span style="display:block; color:gray; font-size:11px; margin-top:2px;"><?= $h['dialed_at'] ?></span>
                        </div>
                        <a href="tel:<?= htmlspecialchars($h['phone_dialed']) ?>" onclick="logCallHistory('<?= htmlspecialchars($h['phone_dialed']) ?>')" style="text-decoration:none; font-size:16px;">📞</a>
                    </div>
                <?php 
                endwhile; 
                if(!$has_history):
                    echo "<p style='text-align:center; color:gray; padding:20px;'>No call history logs found.</p>";
                endif;
                ?>
            </div>
        </div>
    </div>

    <!-- FIXED BOTTOM Navigation FOOTER -->
    <div class="bottom-nav-footer">
        <button class="nav-tab" onclick="toggleModal('historyModalOverlay', true)">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="#ffffff">
                <path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/>
            </svg>
            History
        </button>
    </div>

    <script>
        function toggleModal(modalId, show) {
            const overlay = document.getElementById(modalId);
            overlay.style.display = show ? 'flex' : 'none';
        }

        function closeModalOnBackdrop(e, modalId) {
            if(e.target.id === modalId) {
                toggleModal(modalId, false);
            }
        }

        function logCallHistory(phone) {
            fetch('contacts.php?action=log_call&phone=' + encodeURIComponent(phone))
            .then(response => response.text());
        }
    </script>
</body>
</html>