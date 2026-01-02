<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($_SESSION['admin']) ? 'Seversoft CBT Admin Dashboard' : 'Seversoft CBT Exam App'; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <link rel="icon" href="../assets/images/favicon.ico" type="image/x-icon"> 
    
    <?php 
    // Load app settings
    require_once dirname(__FILE__) . '/../config/settings_loader.php'; 
    ?>
    <!-- Dynamic Font Loading -->
    <link href="https://fonts.googleapis.com/css2?family=<?php echo $google_font_query; ?>&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --app-font: '<?php echo $current_font_family; ?>', sans-serif;
            --primary: <?php echo $app_settings['brand_color']; ?>;
            --primary-hover: <?php echo $app_settings['brand_color']; ?>dd; /* Slight opacity for hover */
            --primary-light: <?php echo $app_settings['brand_color']; ?>15; /* 10% opacity */
        }
    </style>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script>
        // Check local storage for theme preference
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
</head>
