<?php
// Global settings loader
// This should be included in db.php or header.php after session_start()

if (!isset($pdo)) {
    require_once dirname(__FILE__) . '/db.php';
}

$app_settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $app_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Table might not exist yet, fallback to defaults
    $app_settings['app_font'] = 'Source Sans 3';
}

// Mapping font names to Google Fonts families
$font_mapping = [
    'Inter' => 'Inter:wght@300;400;600;700',
    'Roboto' => 'Roboto:wght@300;400;500;700',
    'Open Sans' => 'Open+Sans:wght@300;400;600;700',
    'Lato' => 'Lato:wght@300;400;700;900',
    'Source Sans Pro' => 'Source+Sans+Pro:wght@300;400;600;700',
    'Source Sans 3' => 'Source+Sans+3:wght@300;400;600;700',
    'Montserrat' => 'Montserrat:wght@300;400;600;700;900',
    'Poppins' => 'Poppins:wght@300;400;600;700',
    'Outfit' => 'Outfit:wght@300;400;600;700',
    'Manrope' => 'Manrope:wght@300;400;600;800'
];

// Fallback defaults if keys missing
$defaults = [
    'app_font' => 'Source Sans 3',
    'institution_name' => 'Seversoft CBT',
    'brand_color' => '#4f46e5',
    'default_duration' => '30',
    'pass_mark' => '50',
    'shuffle_questions' => 'yes',
    'show_results' => 'yes'
];

$app_settings = array_merge($defaults, $app_settings);

$current_font_family = $app_settings['app_font'];
$google_font_query = $font_mapping[$current_font_family] ?? $font_mapping['Source Sans 3'];
?>
