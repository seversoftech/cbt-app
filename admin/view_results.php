<?php
require '../config/db.php';

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

// ===== JSON EXPORT (For Client-side Excel/PDF) =====
if (isset($_GET['export_json'])) {
    header('Content-Type: application/json');
    $stmt = $pdo->query("SELECT * FROM results ORDER BY completed_at DESC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
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
                        <i class="fas fa-chart-bar" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h2 style="margin: 0; font-size: 1.75rem;">Test Results</h2>
                        <p style="margin: 0; color: var(--text-light); font-size: 0.9rem;">Monitor student performance and exam metrics</p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 0.5rem;">
                    <button onclick="exportExcel()" class="btn" style="background: #107c41; border: none; box-shadow: none;">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                    <button onclick="exportPDF()" class="btn" style="background: #ef4444; border: none; box-shadow: none;">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                    <a href="?export=1" class="btn btn-success">
                        <i class="fas fa-file-csv"></i> CSV
                    </a>
                    <a href="dashboard.php" class="btn" style="background: var(--text-light); box-shadow: none;">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a>
                </div>
            </div>

            <!-- Search Toolbar -->
            <div style="margin-bottom: 2rem;">
                <form method="GET" style="display: flex; gap: 0.5rem; max-width: 500px;">
                    <div style="position: relative; flex: 1;">
                        <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search student, subject or date..." 
                               style="padding-left: 2.8rem;">
                    </div>
                    <button type="submit" class="btn">Search</button>
                    <?php if ($search): ?>
                        <a href="view_results.php" class="btn" style="background: var(--text-light); box-shadow: none;" title="Clear search">&times;</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Results Table -->
            <div class="table-container">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="20%">Student ID</th>
                                <th width="25%">Subject</th>
                                <th width="10%" style="text-align: center;">Score</th>
                                <th width="10%" style="text-align: center;">Total</th>
                                <th width="12%" style="text-align: center;">Percentage</th>
                                <th width="10%" style="text-align: center;">Status</th>
                                <th width="12%" style="text-align: center;">Completed</th>
                                <th width="10%" style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($results)): ?>
                                <?php foreach ($results as $r): 
                                    $p = $r['percentage'];
                                    $pClass = $p >= 75 ? 'text-secondary' : ($p >= 50 ? 'text-warning' : 'text-danger');
                                    $status = $r['status'] ?? 'completed';
                                ?>
                                    <tr>
                                        <td style="color: var(--text-light);">#<?php echo $r['id']; ?></td>
                                        <td style="font-weight: 600;"><?php echo htmlspecialchars($r['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($r['subject']); ?></td>
                                        <td style="text-align: center; font-weight: 700;"><?php echo $r['score']; ?></td>
                                        <td style="text-align: center; color: var(--text-light);"><?php echo $r['total_questions']; ?></td>
                                        <td style="text-align: center;">
                                            <span style="font-weight: 800; font-size: 1rem;" class="<?php echo $pClass; ?>">
                                                <?php echo number_format($p, 1); ?>%
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($status === 'pending_grading'): ?>
                                                <span class="badge" style="background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; padding: 0.25rem 0.5rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 700;">Pending</span>
                                            <?php else: ?>
                                                <span class="badge" style="background: #ecfdf5; color: #10b981; padding: 0.25rem 0.5rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 700;">Graded</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center; color: var(--text-light); font-size: 0.85rem;">
                                            <?php echo date('M j, H:i', strtotime($r['completed_at'])); ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($status === 'pending_grading'): ?>
                                                <a href="grade_result.php?id=<?php echo $r['id']; ?>" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                                    <i class="fas fa-marker"></i> Grade
                                                </a>
                                            <?php else: ?>
                                                <button class="btn" style="background: var(--text-light); box-shadow: none; opacity: 0.5; cursor: not-allowed; padding: 0.4rem 0.8rem; font-size: 0.8rem;" disabled>
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; padding: 3rem; color: var(--text-light);">
                                        <i class="fas fa-folder-open fa-3x" style="opacity: 0.2; display: block; margin-bottom: 1rem;"></i>
                                        No results found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="pagination-link">&laquo;</a>
                    <?php endif; ?>

                    <?php 
                    $range = 2; // Number of pages before and after current
                    for ($i = 1; $i <= $total_pages; $i++): 
                        if ($i == 1 || $i == $total_pages || ($i >= $page - $range && $i <= $page + $range)): 
                    ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                           class="pagination-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php 
                        elseif ($i == 2 || $i == $total_pages - 1): 
                            echo '<span style="color: var(--text-light); padding: 0.5rem;">...</span>';
                        endif; 
                    endfor; 
                    ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="pagination-link">&raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
<script src="../assets/js/script.js"></script>

<!-- Export Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>

<script>
async function fetchAllData() {
    try {
        const res = await fetch('?export_json=1');
        return await res.json();
    } catch (error) {
        alert("Failed to fetch data for export.");
        console.error(error);
        return [];
    }
}

async function exportExcel() {
    const data = await fetchAllData();
    if(data.length === 0) return;
    
    // Format data for Excel
    const formattedData = data.map(row => ({
        ID: row.id,
        "Student ID": row.student_id,
        Subject: row.subject,
        Score: row.score,
        "Total Questions": row.total_questions,
        "Percentage": row.percentage + '%',
        "Completed At": row.completed_at
    }));

    const worksheet = XLSX.utils.json_to_sheet(formattedData);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Results");
    XLSX.writeFile(workbook, "cbt_results.xlsx");
}

async function exportPDF() {
    const data = await fetchAllData();
    if(data.length === 0) return;

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    doc.text("CBT Test Results", 14, 15);
    doc.setFontSize(10);
    doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 22);
    
    const tableColumn = ["ID", "Student ID", "Subject", "Score", "Total", "%", "Date"];
    const tableRows = [];

    data.forEach(row => {
        const result = [
            row.id,
            row.student_id,
            row.subject,
            row.score,
            row.total_questions,
            row.percentage + '%',
            row.completed_at
        ];
        tableRows.push(result);
    });

    doc.autoTable({
        head: [tableColumn],
        body: tableRows,
        startY: 30,
        theme: 'grid',
        styles: { fontSize: 8 },
        headStyles: { fillColor: [79, 70, 229] } // Primary color
    });
    
    doc.save("cbt_results.pdf");
}
</script>
</body>
</html>