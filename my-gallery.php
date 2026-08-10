<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php"); exit;
}
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoreBase - My Cloud Repository</title>
    <link rel="stylesheet" href="style.css?v=9">
    <style>
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; margin-top: 20px; }
        .gallery-item { border: 1px solid #eef0f4; border-radius: 12px; overflow: hidden; background: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.05); position: relative; display: flex; flex-direction: column; }
        .gallery-media { width: 100%; height: 180px; object-fit: cover; display: block; background: #f8f9fa; }
        .gallery-info { padding: 15px; font-size: 14px; background: #fff; border-top: 1px solid #f1f3f7; }
        .gallery-caption { font-weight: 700; display: block; color: #1a202c; margin-bottom: 4px; }
        .gallery-meta { font-size: 11px; color: #a0aec0; }
        .badge { position: absolute; top: 12px; right: 12px; padding: 4px 8px; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; z-index: 10; color: #fff; }
        .bg-success { background-color: #48bb78; }
        .bg-danger { background-color: #f56565; }
        
        /* Mobile specific spacing fixes */
        @media (max-width: 768px) {
            .gallery-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; }
            .gallery-media { height: 130px; }
            .card { padding: 15px !important; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="main-wrapper" style="max-width: 1200px; margin: 0 auto; padding: 15px;">
        <div class="card" style="max-width: 100% !important; border-radius: 20px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center;">
            <h2 style="color: #0f294a; font-weight: 800; letter-spacing: 0.5px; margin-bottom: 5px;">MY PERSONAL REPOSITORY</h2>
            <p style="color: #718096; font-size: 14px; margin-bottom: 20px;">Your private & public assets catalogued across systems.</p>

            <div class="gallery-grid">
                <?php
                $stmt = $pdo->prepare("SELECT * FROM gallery WHERE user_id = ? ORDER BY uploaded_at DESC");
                $stmt->execute([$user_id]);
                $myItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach($myItems as $item):
                    $displayUrl = "https://lh3.googleusercontent.com/u/0/d/" . $item['gdrive_file_id'];
                ?>
                    <div class="gallery-item">
                        <span class="badge <?= $item['visibility'] == 'public' ? 'bg-success' : 'bg-danger' ?>">
                            <?= htmlspecialchars($item['visibility']) ?>
                        </span>
                        
                        <?php if (strpos($item['file_type'], 'pdf') !== false): ?>
                            <a href="https://drive.google.com/file/d/<?= $item['gdrive_file_id'] ?>/view" target="_blank" class="gallery-media" style="display:flex; align-items:center; justify-content:center; text-decoration:none; color:#4a5568; font-weight:600;">
                                <span>📄 View PDF</span>
                            </a>
                        <?php else: ?>
                            <img src="<?= $displayUrl ?>" class="gallery-media" alt="media" onerror="this.src='https://drive.google.com/uc?export=view&id=<?= $item['gdrive_file_id'] ?>';">
                        <?php endif; ?>
                        
                        <div class="gallery-info" style="text-align: left;">
                            <span class="gallery-caption"><?= htmlspecialchars($item['caption']) ?></span>
                            <span class="gallery-meta"><?= date('M d, Y', strtotime($item['uploaded_at'])) ?></span>
                        </div>
                    </div>
                <?php 
                endforeach; 
                if(empty($myItems)): 
                    echo "<p style='grid-column: 1/-1; text-align:center; color:#a0aec0; padding: 40px 0;'>No files uploaded in your repository yet.</p>"; 
                endif; 
                ?>
            </div>
        </div>
    </div>
</body>
</html>