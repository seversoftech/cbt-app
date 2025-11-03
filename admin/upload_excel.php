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
<?php include '../includes/header.php'; ?>
<div class="card">
    <h2>Upload Excel/CSV</h2>
    <?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
    <p>Excel format: Col1=Question, Col2=A, Col3=B, Col4=C, Col5=D, Col6=Correct (A/B/C/D), Col7=Category. Use CSV for simplicity.</p>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="excel" accept=".csv,.xlsx" required>

        <!-- Flex container for buttons -->
        <div style="display: flex; gap: 10px; margin-top: 10px;">
          
            <button type="button" class="btn" style="flex: 1;" onclick="window.location.href='dashboard.php'">Back to Dashboard</button>
            <button type="submit" class="btn" style="flex: 1;">Upload</button>
        </div>
    </form>
</div>

<script src="../assets/js/script.js"></script>
</body></html>