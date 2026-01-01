<?php
require '../config/db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = '';

// Handle Font Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_font'])) {
    try {
        $font = $_POST['app_font'];
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('app_font', ?) 
                                ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$font, $font]);
        $message = "Font settings updated successfully!";
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Error updating settings: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Fetch current font
$current_font = 'Source Sans 3';
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'app_font'");
    $stmt->execute();
    $result = $stmt->fetchColumn();
    if ($result) $current_font = $result;
} catch (PDOException $e) {}

$fonts = [
    'Inter' => 'Designed for screens and UI. Excellent readability at small sizes.',
    'Roboto' => 'Clean, neutral, modern. Very legible for paragraphs and UI.',
    'Open Sans' => 'Friendly yet professional. Works well in long reading sessions.',
    'Lato' => 'Slightly rounded, approachable feel. Good for both body and UI.',
    'Source Sans Pro' => 'Designed specifically for user interfaces. Great balance.',
    'Source Sans 3' => 'The modern successor to Source Sans Pro. Refined and sleek.',
    'Montserrat' => 'A classic geometric font. Bold, stylish, and high-impact.',
    'Poppins' => 'A geometric sans-serif that is exceptionally clean and modern.',
    'Outfit' => 'Clean, geometric, and technical. Perfect for digital interfaces.',
    'Manrope' => 'Contemporary and ultra-modern. Great for headers and UI.'
];

include '../includes/header.php';
include '../includes/admin_nav.php';
?>

<main>
    <div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem;">
                <div style="background: var(--primary-light); color: var(--primary); padding: 0.75rem; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-cog" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <h2 style="margin: 0; font-size: 1.75rem;">System Settings</h2>
                    <p style="margin: 0; color: var(--text-light); font-size: 0.9rem;">Customize the look and feel of your examination portal</p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 2rem; padding: 1rem; border-radius: var(--radius-md); background: <?php echo $messageType==='success'?'rgba(16,185,129,0.1)':'rgba(239, 68, 68, 0.1)'; ?>; border: 1px solid <?php echo $messageType==='success'?'var(--secondary)':'var(--danger)'; ?>; color: <?php echo $messageType==='success'?'var(--secondary)':'var(--danger)'; ?>;">
                    <i class="fas <?php echo $messageType==='success'?'fa-check-circle':'fa-exclamation-circle'; ?>"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem;">Typography Configuration</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
                    <?php foreach ($fonts as $name => $desc): ?>
                        <label class="font-option-card" style="cursor: pointer;">
                            <input type="radio" name="app_font" value="<?php echo $name; ?>" <?php echo ($current_font === $name) ? 'checked' : ''; ?> style="display: none;">
                            <div class="font-preview-box <?php echo ($current_font === $name) ? 'active' : ''; ?>" style="padding: 1.5rem; border: 2px solid var(--glass-border); border-radius: var(--radius-lg); transition: var(--transition); background: var(--bg-card); height: 100%;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                    <span style="font-weight: 800; font-size: 1.1rem; color: var(--text-header);"><?php echo $name; ?></span>
                                    <div class="check-mark" style="width: 20px; height: 20px; border-radius: 50%; border: 2px solid var(--glass-border); display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-check" style="font-size: 0.7rem; color: white; display: <?php echo ($current_font === $name) ? 'block' : 'none'; ?>;"></i>
                                    </div>
                                </div>
                                <p style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 1rem;"><?php echo $desc; ?></p>
                                <div style="font-family: '<?php echo $name; ?>', sans-serif; font-size: 1.5rem; border-top: 1px solid var(--glass-border); pt-3; color: var(--primary);">
                                    ABC abc 123
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <style>
                    .font-option-card input:checked + .font-preview-box {
                        border-color: var(--primary);
                        background: var(--primary-light);
                        box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.1);
                    }
                    .font-option-card input:checked + .font-preview-box .check-mark {
                        background: var(--primary);
                        border-color: var(--primary);
                    }
                    .font-option-card input:checked + .font-preview-box .check-mark i {
                        display: block !important;
                    }
                    .font-preview-box:hover {
                        transform: translateY(-2px);
                        border-color: var(--primary-hover);
                    }
                </style>

                <div style="text-align: right; border-top: 1px solid var(--glass-border); padding-top: 2rem;">
                    <button type="submit" class="btn big-btn" style="min-width: 200px;">
                        Save Settings <i class="fas fa-save" style="margin-left: 0.5rem;"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<!-- Load all font options for preview -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Roboto:wght@400;700&family=Open+Sans:wght@400;700&family=Lato:wght@400;700&family=Source+Sans+Pro:wght@400;700&family=Source+Sans+3:wght@400;700&family=Montserrat:wght@400;700&family=Poppins:wght@400;700&family=Outfit:wght@400;700&family=Manrope:wght@400;700&display=swap" rel="stylesheet">

<?php include '../includes/footer.php'; ?>
</body>
</html>
