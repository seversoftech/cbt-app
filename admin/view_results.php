<?php
require '../config/db.php';
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

// ===== CSV EXPORT =====
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="cbt_results.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Student ID', 'Subject', 'Score', 'Total Questions', 'Percentage', 'Completed At']);
    $stmt = $pdo->query("SELECT * FROM results ORDER BY completed_at DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [$row['id'], $row['student_id'], $row['subject'], $row['score'], $row['total_questions'], $row['percentage'], $row['completed_at']]);
    }
    exit;
}

// ===== PAGINATION SETTINGS =====
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// ===== SEARCH FEATURE =====
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
$params = [];

if ($search !== '') {
    $where = "WHERE student_id LIKE :search OR subject LIKE :search OR completed_at LIKE :search";
    $params[':search'] = "%$search%";
}

// ===== TOTAL RESULTS =====
$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM results $where");
$total_stmt->execute($params);
$total_results = $total_stmt->fetchColumn();
$total_pages = ceil($total_results / $limit);

// ===== FETCH RESULTS WITH PAGINATION =====
$sql = "SELECT * FROM results $where ORDER BY completed_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>
<div class="card">
    <h2>View Test Results</h2>
    
    <div style="margin-bottom: 1rem; display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
        <a href="dashboard.php" class="btn">Back to Dashboard</a>
        <a href="?export=1" class="btn">Export to CSV</a>

        <!-- Search Form -->
        <form method="GET" style="margin-left: auto; display: flex; align-items: center; gap: 0.5rem;">
            <input type="text" name="search" placeholder="Search Student ID, Subject or Date..." 
                   value="<?php echo htmlspecialchars($search); ?>" 
                   style="padding: 0.5rem; width: 250px;">
            <button type="submit" class="btn">Search</button>
        </form>
    </div>
    
    <!-- Results Table -->
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f3f4f6;">
                <th style="padding: 0.75rem; text-align: left; border: 1px solid #d1d5db;">ID</th>
                <th style="padding: 0.75rem; text-align: left; border: 1px solid #d1d5db;">Student ID</th>
                <th style="padding: 0.75rem; text-align: left; border: 1px solid #d1d5db;">Subject</th>
                <th style="padding: 0.75rem; text-align: center; border: 1px solid #d1d5db;">Score</th>
                <th style="padding: 0.75rem; text-align: center; border: 1px solid #d1d5db;">Total Q</th>
                <th style="padding: 0.75rem; text-align: center; border: 1px solid #d1d5db;">Percentage</th>
                <th style="padding: 0.75rem; text-align: center; border: 1px solid #d1d5db;">Completed At</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($results)): ?>
                <?php foreach ($results as $r): ?>
                    <tr style="border: 1px solid #d1d5db;">
                        <td style="padding: 0.75rem;"><?php echo $r['id']; ?></td>
                        <td style="padding: 0.75rem;"><?php echo htmlspecialchars($r['student_id']); ?></td>
                        <td style="padding: 0.75rem;"><?php echo htmlspecialchars($r['subject']); ?></td>
                        <td style="padding: 0.75rem; text-align: center;"><?php echo $r['score']; ?></td>
                        <td style="padding: 0.75rem; text-align: center;"><?php echo $r['total_questions']; ?></td>
                        <td style="padding: 0.75rem; text-align: center;"><?php echo $r['percentage']; ?>%</td>
                        <td style="padding: 0.75rem; text-align: center;"><?php echo $r['completed_at']; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align:center; padding:1rem; color:#6b7280;">No results found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination Links -->
    <?php if ($total_pages > 1): ?>
        <div style="margin-top: 1rem; text-align: center;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="btn">&laquo; Prev</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                   class="btn" 
                   style="margin: 0 0.25rem; <?php echo ($i == $page) ? 'background:#2563eb;color:#fff;' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="btn">Next &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div><?php 
include '../includes/footer.php'; 
?>

<script src="../assets/js/script.js"></script>
</body></html>