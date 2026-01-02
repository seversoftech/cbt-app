<?php
require '../config/db.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = '';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $allowed_keys = [
            'app_font', 
            'institution_name', 
            'brand_color', 
            'default_duration', 
            'pass_mark', 
            'shuffle_questions', 
            'show_results'
        ];

        foreach ($allowed_keys as $key) {
            if (isset($_POST[$key])) {
                $value = trim($_POST[$key]);
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                                        ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$key, $value, $value]);
            }
        }
        
        $message = "Settings updated successfully!";
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Error updating settings: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Fetch all current settings
$current_settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $current_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {}

// Defaults
$defaults = [
    'app_font' => 'Source Sans 3',
    'institution_name' => 'Seversoft CBT',
    'brand_color' => '#4f46e5',
    'default_duration' => '30',
    'pass_mark' => '50',
    'shuffle_questions' => 'yes',
    'show_results' => 'yes'
];

$settings = array_merge($defaults, $current_settings);

$fonts = [
    'Inter' => 'Designed for screens and UI. Excellent readability.',
    'Roboto' => 'Clean, neutral, modern. Very legible.',
    'Open Sans' => 'Friendly yet professional.',
    'Lato' => 'Slightly rounded, approachable feel.',
    'Source Sans 3' => 'The modern successor to Source Sans Pro.',
    'Montserrat' => 'A classic geometric font. Bold and stylish.',
    'Poppins' => 'Geometric sans-serif, clean and modern.',
    'Outfit' => 'Clean, geometric, and technical.'
];

include '../includes/header.php';
include '../includes/admin_nav.php';
?>

<main>
    <div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
        <div class="card" style="max-width: 900px; margin: 0 auto;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem;">
                <div style="background: var(--primary-light); color: var(--primary); padding: 0.75rem; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-cogs" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <h2 style="margin: 0; font-size: 1.75rem;">System Configuration</h2>
                    <p style="margin: 0; color: var(--text-light); font-size: 0.9rem;">Manage application defaults, branding, and behavior.</p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 2rem; padding: 1rem; border-radius: var(--radius-md); background: <?php echo $messageType==='success'?'rgba(16,185,129,0.1)':'rgba(239, 68, 68, 0.1)'; ?>; border: 1px solid <?php echo $messageType==='success'?'var(--secondary)':'var(--danger)'; ?>; color: <?php echo $messageType==='success'?'var(--secondary)':'var(--danger)'; ?>;">
                    <i class="fas <?php echo $messageType==='success'?'fa-check-circle':'fa-exclamation-circle'; ?>"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                
                <!-- Section 1: Branding -->
                <div style="background: #f9fafb; padding: 1.5rem; border-radius: 1rem; margin-bottom: 2rem; border: 1px solid #e5e7eb;">
                    <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--primary);"><i class="fas fa-paint-brush me-2"></i> Branding & Identity</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div>
                            <label for="institution_name">Institution / App Name</label>
                            <input type="text" name="institution_name" id="institution_name" value="<?php echo htmlspecialchars($settings['institution_name']); ?>" required>
                        </div>
                        <div>
                            <label for="brand_color">Primary Brand Color</label>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <input type="color" name="brand_color" id="brand_color" value="<?php echo htmlspecialchars($settings['brand_color']); ?>" style="width: 50px; height: 45px; padding: 0; border: none; border-radius: 0.5rem; cursor: pointer;">
                                <input type="text" value="<?php echo htmlspecialchars($settings['brand_color']); ?>" readonly style="width: 100px; font-family: monospace;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Exam Defaults -->
                <div style="background: #f0fdf4; padding: 1.5rem; border-radius: 1rem; margin-bottom: 2rem; border: 1px solid #bbf7d0;">
                    <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--secondary);"><i class="fas fa-clock me-2"></i> Exam Defaults</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div>
                            <label for="default_duration">Default Duration (Minutes)</label>
                            <input type="number" name="default_duration" id="default_duration" min="5" max="180" value="<?php echo htmlspecialchars($settings['default_duration']); ?>" required>
                            <p style="font-size: 0.8rem; color: var(--text-light); margin-top: 0.25rem;">Standard time allocated for new tests.</p>
                        </div>
                        <div>
                            <label for="pass_mark">Pass Mark (%)</label>
                            <input type="number" name="pass_mark" id="pass_mark" min="1" max="100" value="<?php echo htmlspecialchars($settings['pass_mark']); ?>" required>
                        </div>
                        <div>
                            <label>Shuffle Questions?</label>
                            <select name="shuffle_questions" class="modern-select">
                                <option value="yes" <?php echo $settings['shuffle_questions'] === 'yes' ? 'selected' : ''; ?>>Yes, Randomize Order</option>
                                <option value="no" <?php echo $settings['shuffle_questions'] === 'no' ? 'selected' : ''; ?>>No, Keep Static</option>
                            </select>
                        </div>
                        <div>
                            <label>Show Results Immediately?</label>
                            <select name="show_results" class="modern-select">
                                <option value="yes" <?php echo $settings['show_results'] === 'yes' ? 'selected' : ''; ?>>Yes, Show Score</option>
                                <option value="no" <?php echo $settings['show_results'] === 'no' ? 'selected' : ''; ?>>No, Hide Score</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Typography -->
                <div style="background: #fff; padding: 1.5rem; border-radius: 1rem; margin-bottom: 2rem; border: 1px dashed var(--glass-border);">
                    <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--dark);"><i class="fas fa-font me-2"></i> Typography</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                        <?php foreach ($fonts as $name => $desc): ?>
                            <label class="font-option-card" style="cursor: pointer;">
                                <input type="radio" name="app_font" value="<?php echo $name; ?>" <?php echo ($settings['app_font'] === $name) ? 'checked' : ''; ?> style="display: none;">
                                <div class="font-preview-box" style="padding: 1rem; border: 1px solid var(--glass-border); border-radius: 0.5rem; transition: var(--transition); background: var(--bg-card); height: 100%;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                        <span style="font-weight: 700; font-family: '<?php echo $name; ?>', sans-serif;"><?php echo $name; ?></span>
                                        <i class="fas fa-check-circle" style="color: var(--primary); display: <?php echo ($settings['app_font'] === $name) ? 'block' : 'none'; ?>;"></i>
                                    </div>
                                    <div style="font-family: '<?php echo $name; ?>', sans-serif; font-size: 1.2rem; color: var(--text-main);">Aa Bb Cc</div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <style>
                    .font-option-card input:checked + .font-preview-box {
                        border-color: var(--primary);
                        background: var(--primary-light);
                        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.1);
                    }
                </style>

                <div style="text-align: right; border-top: 1px solid var(--glass-border); padding-top: 2rem; position: sticky; bottom: 0; background: var(--bg-card); padding-bottom: 1rem; margin: 0 -1.5rem; padding-right: 1.5rem;">
                    <button type="submit" class="btn big-btn" style="min-width: 200px; box-shadow: 0 4px 14px rgba(0,0,0,0.1);">
                        Save Settings <i class="fas fa-save" style="margin-left: 0.5rem;"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<!-- Load all font options for preview -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Roboto:wght@400;700&family=Open+Sans:wght@400;700&family=Lato:wght@400;700&family=Source+Sans+Pro:wght@400;700&family=Source+Sans+3:wght@400;700&family=Montserrat:wght@400;700&family=Poppins:wght@400;700&family=Outfit:wght@400;700&display=swap" rel="stylesheet">

<?php include '../includes/footer.php'; ?>
</body>
</html>
