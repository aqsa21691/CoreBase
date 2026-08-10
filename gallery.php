<?php
require 'config.php';
require 'cloud_upload.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php"); exit;
}
$user_id = $_SESSION['user_id'];
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['gallery_file'])) {
    $caption = trim($_POST['caption']);
    $visibility = $_POST['visibility'] === 'private' ? 'private' : 'public';
    
    $file = $_FILES['gallery_file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

    if (in_array($ext, $allowed)) {
        $cloudData = uploadToDualCloud($file['tmp_name'], $file['name'], $file['type']);
        
        if (!empty($cloudData['gdrive_id'])) {
            $stmt = $pdo->prepare("INSERT INTO gallery (user_id, file_name, file_type, caption, visibility, gdrive_file_id, azure_blob_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $file['name'], $file['type'], $caption, $visibility, $cloudData['gdrive_id'], $cloudData['azure_url']]);
            
            $msg = "File successfully deployed to Google Drive!";
        } else {
            $msg = "Error: Cloud upload failed. Check API responses or token values.";
        }
    } else {
        $msg = "Error: Only .jpg, .png, and .pdf files are permissible.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoreBase - Gallery</title>
    <link rel="stylesheet" href="style.css?v=9">
    <style>
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; margin-top: 20px; text-align: left; }
        .gallery-item { border: 1px solid #eef0f4; border-radius: 12px; overflow: hidden; background: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.05); position: relative; display: flex; flex-direction: column; }
        .gallery-link { display: block; width: 100%; height: 180px; text-decoration: none; overflow: hidden; position: relative; }
        .gallery-media { width: 100%; height: 100%; object-fit: cover; display: block; background: #f8f9fa; transition: transform 0.2s ease; }
        .gallery-link:hover .gallery-media { transform: scale(1.05); }
        .gallery-info { padding: 15px; font-size: 14px; background: #fff; border-top: 1px solid #f1f3f7; }
        .gallery-caption { font-weight: 700; display: block; color: #1a202c; margin-bottom: 4px; }
        .gallery-meta { font-size: 11px; color: #a0aec0; }
        .badge-public { position: absolute; top: 12px; right: 12px; padding: 4px 8px; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; z-index: 10; color: #fff; background-color: #48bb78; }
        
        @media (max-width: 768px) {
            .gallery-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; }
            .gallery-link { height: 130px; }
            .card { padding: 15px !important; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-wrapper" style="max-width: 1200px; margin: 0 auto; padding: 15px;">
        <!-- Upload Form -->
        <div class="card" style="max-width: 100% !important; border-radius: 20px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
            <h3 style="color: #0f294a; font-weight: 800; text-align: center;">Upload to Cloud Gallery</h3>
            <?php if($msg): ?><p style="text-align:center; color: var(--navy-primary); font-weight:700; margin-bottom:10px;"><?= $msg ?></p><?php endif; ?>
            
            <form action="gallery.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Select File (.jpg, .png, .pdf)</label>
                    <input type="file" name="gallery_file" accept=".jpg,.jpeg,.png,.pdf" required>
                </div>
                <div class="form-group">
                    <label>File Caption</label>
                    <input type="text" name="caption" placeholder="Enter a descriptive caption..." required>
                </div>
                <div class="form-group">
                    <label>Privacy Setting</label>
                    <div class="visibility-selector">
                        <label><input type="radio" name="visibility" value="public" checked> 🌐 Public</label>
                        <label><input type="radio" name="visibility" value="private"> 🔒 Private</label>
                    </div>
                </div>
                <button type="submit" class="btn-primary">Upload & Sync Cloud</button>
            </form>
        </div>

        <br>

        <!-- Global Public Feed -->
        <div class="card" style="max-width: 100% !important; border-radius: 20px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center;">
            <h2 style="color: #0f294a; font-weight: 800; letter-spacing: 0.5px; margin-bottom: 5px;">GLOBAL PUBLIC FEED</h2>
            <p style="color: #718096; font-size: 14px; margin-bottom: 20px;">See what the community is streaming live from the cloud.</p>

            <div class="gallery-grid">
                <?php
                $stmt = $pdo->query("SELECT * FROM gallery WHERE visibility = 'public' ORDER BY uploaded_at DESC");
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach($items as $item):
                    $displayUrl = "https://lh3.googleusercontent.com/u/0/d/" . $item['gdrive_file_id'];
                    $viewUrl = "https://drive.google.com/file/d/" . $item['gdrive_file_id'] . "/view?usp=drivesdk";
                ?>
                    <div class="gallery-item">
                        <span class="badge-public">🌐 Public</span>
                        
                        <!-- Added View Link wrapper -->
                        <a href="<?= $viewUrl ?>" target="_blank" class="gallery-link">
                            <?php if (strpos($item['file_type'], 'pdf') !== false): ?>
                                <div class="gallery-media" style="display:flex; align-items:center; justify-content:center; color:#4a5568; font-weight:600;">
                                    <span>📄 View PDF</span>
                                </div>
                            <?php else: ?>
                                <img src="<?= $displayUrl ?>" class="gallery-media" alt="media" onerror="this.src='https://drive.google.com/uc?export=view&id=<?= $item['gdrive_file_id'] ?>';">
                            <?php endif; ?>
                        </a>
                        
                        <div class="gallery-info" style="text-align: left;">
                            <span class="gallery-caption"><?= htmlspecialchars($item['caption']) ?></span>
                            <span class="gallery-meta"><?= date('M d, Y', strtotime($item['uploaded_at'])) ?></span>
                        </div>
                    </div>
                <?php 
                endforeach; 
                if(empty($items)): 
                    echo "<p style='grid-column: 1/-1; text-align:center; color:#a0aec0; padding: 40px 0;'>No public files streaming yet.</p>"; 
                endif; 
                ?>
            </div>
        </div>
    </div>
</body>
</html>