<?php
// Active page ka naam nikalne ke liye
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding: 15px 20px; background-color: #0f294a;">
    <link rel="stylesheet" href="style.css?v=6">
    
    <div class="logo" style="color: #ffffff; font-size: 22px; font-weight: 800; white-space: nowrap;">CoreBase</div>
    
    <ul class="nav-links" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; justify-content: flex-end; list-style: none; margin: 0; padding: 0;">
        <a href="dashboard.php" style="white-space: nowrap; font-size: 14px; text-decoration: none; 
           <?= ($current_page == 'dashboard.php') ? 'font-weight: 800; text-decoration: underline; text-underline-offset: 6px; color: #ffffff;' : ''; ?>">Home</a>
        
        <a href="contacts.php" style="white-space: nowrap; font-size: 14px; text-decoration: none; 
           <?= ($current_page == 'contacts.php') ? 'font-weight: 800; text-decoration: underline; text-underline-offset: 6px; color: #ffffff;' : ''; ?>">Contact</a>
        
        <a href="gallery.php" style="white-space: nowrap; font-size: 14px; text-decoration: none; 
           <?= ($current_page == 'gallery.php') ? 'font-weight: 800; text-decoration: underline; text-underline-offset: 6px; color: #ffffff;' : ''; ?>">Public Gallery</a>
        
        <a href="my-gallery.php" style="white-space: nowrap; font-size: 14px; text-decoration: none; 
           <?= ($current_page == 'my-gallery.php') ? 'font-weight: 800; text-decoration: underline; text-underline-offset: 6px; color: #ffffff;' : ''; ?>">My Gallery</a>
        
        <a href="meets.php" style="white-space: nowrap; font-size: 14px; text-decoration: none; 
           <?= ($current_page == 'meets.php') ? 'font-weight: 800; text-decoration: underline; text-underline-offset: 6px; color: #ffffff;' : ''; ?>">Meets</a>
        
        <!-- Forced Text Color to Red via !important -->
        <a href="signout.php" style="white-space: nowrap; font-size: 14px; text-decoration: none; color: #f56565 !important; font-weight: 700; padding: 6px 12px; border: 1px solid #f56565 !important; border-radius: 6px; margin-left: 5px; background-color: transparent; transition: all 0.2s;">Sign Out</a>
    </ul>
</nav>
