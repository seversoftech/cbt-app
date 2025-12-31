<?php require '../config/db.php';
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

// For full Excel, install PhpSpreadsheet: composer require phpoffice/phpspreadsheet
// Fallback: Assume CSV upload (rename .xlsx to .csv)
if ($_POST && isset($_FILES['excel']['tmp_name'])) {
    $file = $_FILES['excel']['tmp_name'];
    if (($handle = fopen($file, "r")) !== FALSE) {
        $row = 1;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($row == 1) { $row++; continue; } // Skip header
            if (count($data) >= 7) {
                $stmt = $pdo->prepare("INSERT INTO questions (question, option_a, option_b, option_c, option_d, correct_answer, category) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6] ?? 'General']);
            }
            $row++;
        }
        fclose($handle);
        $success = 'Questions uploaded!';
    }
}
?>
<?php include '../includes/header.php'; 
include '../includes/admin_nav.php'; // Unified Admin Navbar
?>

<main style="padding-bottom: 4rem;">
    <div class="container" style="padding-top: 2rem;">
        <div class="card">
            <!-- Page Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="background: var(--primary-light); color: var(--primary); padding: 0.75rem; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-file-excel" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h2 style="margin: 0; font-size: 1.75rem;">Import Questions</h2>
                        <p style="margin: 0; color: var(--text-light); font-size: 0.9rem;">Bulk upload exam questions via CSV or Excel</p>
                    </div>
                </div>
                
                <a href="dashboard.php" class="btn" style="background: var(--text-light); box-shadow: none;">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
            </div>

            <!-- Messages -->
            <?php if (isset($success)): ?>
                <div style="margin-bottom: 2rem; background: var(--bg-body); border-left: 4px solid var(--secondary); padding: 1rem; border-radius: var(--radius-md); color: var(--text-main); display: flex; align-items: center; gap: 10px; border: 1px solid var(--glass-border);">
                    <i class="fas fa-check-circle text-secondary"></i>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; align-items: start;">
                <!-- Upload Zone -->
                <section>
                    <h3 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.1rem; color: var(--text-header);">
                        <i class="fas fa-cloud-upload-alt text-primary"></i> Select File
                    </h3>
                    <form method="POST" enctype="multipart/form-data">
                        <div style="border: 2px dashed var(--glass-border); padding: 3rem 2rem; border-radius: var(--radius-xl); text-align: center; background: rgba(255,255,255,0.02); transition: var(--transition);" 
                             onmouseover="this.style.borderColor='var(--primary)'; this.style.background='rgba(var(--primary-light-rgb), 0.05)'" 
                             onmouseout="this.style.borderColor='var(--glass-border)'; this.style.background='rgba(255,255,255,0.02)'">
                            <i class="fas fa-file-csv fa-3x" style="color: var(--text-light); margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p style="margin-bottom: 1.5rem; color: var(--text-light);">Drag and drop or click to browse</p>
                            <input type="file" name="excel" accept=".csv,.xlsx" required 
                                   style="width: 100%; max-width: 300px; margin: 0 auto; color: var(--text-main);">
                        </div>

                        <div style="margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 1rem;">
                                <i class="fas fa-upload"></i> Complete Upload
                            </button>
                        </div>
                    </form>
                </section>

                <!-- Instructions -->
                <section>
                    <h3 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.1rem; color: var(--text-header);">
                        <i class="fas fa-info-circle text-secondary"></i> Spreadsheet Format
                    </h3>
                    <div style="background: var(--bg-body); border: 1px solid var(--glass-border); border-radius: var(--radius-lg); padding: 1.5rem;">
                        <p style="font-size: 0.9rem; color: var(--text-light); margin-bottom: 1.5rem;">
                            Please ensure your CSV uses the following column order (no header row required, but skip if present):
                        </p>
                        <ul style="list-style: none; padding: 0; display: grid; gap: 0.75rem;">
                            <li style="display: flex; gap: 1rem; align-items: center; font-size: 0.85rem;">
                                <span style="width: 30px; height: 30px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; flex-shrink: 0;">1</span>
                                <strong>Question Text</strong>
                            </li>
                            <li style="display: flex; gap: 1rem; align-items: center; font-size: 0.85rem;">
                                <span style="width: 30px; height: 30px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; flex-shrink: 0;">2-5</span>
                                <span>Options <strong>A, B, C, D</strong></span>
                            </li>
                            <li style="display: flex; gap: 1rem; align-items: center; font-size: 0.85rem;">
                                <span style="width: 30px; height: 30px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; flex-shrink: 0;">6</span>
                                <span>Correct Answer <strong>(A, B, C, or D)</strong></span>
                            </li>
                            <li style="display: flex; gap: 1rem; align-items: center; font-size: 0.85rem;">
                                <span style="width: 30px; height: 30px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; flex-shrink: 0;">7</span>
                                <span>Subject/Category <strong>(e.g. Maths)</strong></span>
                            </li>
                        </ul>
                        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--glass-border); font-size: 0.8rem; color: var(--text-light); font-style: italic;">
                            Tip: For best results, use standard CSV (Comma Separated Values) format.
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
<script src="../assets/js/script.js"></script>
</body>
</html>