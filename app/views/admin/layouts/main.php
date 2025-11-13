<?php
/**
 * Main Layout for Admin Panel
 * Tuân thủ nguyên tắc MVC/OOP
 */

// Biến được truyền từ Controller:
// $title - tiêu đề trang
// $content - nội dung chính của trang
// $stats, $recentOrders, $bestSellers - dữ liệu cho dashboard
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Sức Admin - <?= $title ?? 'Dashboard' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/Ecom_website/app/views/admin/assets/css/variables.css">
    <link rel="stylesheet" href="/Ecom_website/app/views/admin/assets/css/main.css">
    
    <!-- Page-specific CSS -->
    <?php 
    $currentPage = $_GET['url'] ?? 'dashboard';
    $cssFile = __DIR__ . "/../assets/css/{$currentPage}.css";
    if (file_exists($cssFile)): 
    ?>
        <link rel="stylesheet" href="/Ecom_website/app/views/admin/assets/css/<?= $currentPage ?>.css">
    <?php endif; ?>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../partials/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <?php include __DIR__ . '/../partials/header.php'; ?>

            <!-- Content -->
            <main class="content" style="margin-left: -250px;">
                <?= $content ?>
            </main>
        </div>
    </div>

    <!-- Page Configuration -->
    <script>
        // Set active page for sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = '<?= $_GET['url'] ?? 'dashboard' ?>';
            const sidebarLinks = document.querySelectorAll('.sidebar-nav-link');
            
            sidebarLinks.forEach(link => {
                if (link.getAttribute('data-page') === currentPage) {
                    link.classList.add('active');
                }
            });
        });
    </script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Main JS -->
    <script src="app/views/admin/assets/js/main.js"></script>
    
    <!-- Page-specific JS -->
    <?php 
    $currentPage = $_GET['url'] ?? 'dashboard';
    $jsFile = "app/views/admin/assets/js/{$currentPage}.js";
    if (file_exists($jsFile)): 
    ?>
        <script src="<?= $jsFile ?>"></script>
    <?php endif; ?>
</body>
</html>
