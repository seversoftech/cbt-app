<?php
require '../config/db.php';
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

// ====== HANDLE DELETE INDIVIDUAL QUESTION ======
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: view_questions.php?msg=deleted');
    exit;
}

// ====== HANDLE DELETE CATEGORY ======
if (isset($_GET['delete_category']) && !empty($_GET['delete_category'])) {
    $category_to_delete = trim($_GET['delete_category']);
    $stmt = $pdo->prepare("DELETE FROM questions WHERE category = ?");
    $deleted_count = $stmt->execute([$category_to_delete]);
    if ($deleted_count > 0) {
        header('Location: view_questions.php?msg=category_deleted&count=' . $deleted_count);
    } else {
        header('Location: view_questions.php?msg=category_not_found');
    }
    exit;
}

// ====== HANDLE EDIT SUBMIT ======
if ($_POST && isset($_POST['edit_id'])) {
    $stmt = $pdo->prepare("UPDATE questions 
        SET question = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_answer = ?, category = ?
        WHERE id = ?");
    $stmt->execute([
        $_POST['question'], $_POST['a'], $_POST['b'], $_POST['c'], $_POST['d'],
        $_POST['correct'], $_POST['category'], $_POST['edit_id']
    ]);
    header('Location: view_questions.php?msg=updated');
    exit;
}

// ====== PAGINATION ======
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// ====== SEARCH FEATURE ======
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
$params = [];

if ($search !== '') {
    $where = "WHERE question LIKE :search OR category LIKE :search";
    $params[':search'] = "%$search%";
}

// ====== TOTAL QUESTIONS ======
$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM questions $where");
$total_stmt->execute($params);
$total = $total_stmt->fetchColumn();
$total_pages = ceil($total / $limit);

// ====== FETCH PAGINATED QUESTIONS ======
$sql = "SELECT * FROM questions $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ====== FETCH CATEGORIES FOR MANAGEMENT ======
$cat_stmt = $pdo->query("SELECT DISTINCT category, COUNT(*) as question_count FROM questions WHERE category != '' GROUP BY category ORDER BY category");
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// ====== EDIT FORM DATA ======
$edit_q = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
    $edit_stmt->execute([$_GET['edit']]);
    $edit_q = $edit_stmt->fetch(PDO::FETCH_ASSOC);
}

// ====== MESSAGES ======
$msg = $_GET['msg'] ?? '';
$msg_count = $_GET['count'] ?? 0;
?>

<?php include '../includes/header.php'; ?>
<div class="card">
    <h2>Manage Questions</h2>
    
    <!-- Messages -->
    <?php if ($msg): ?>
        <div class="success" style="margin-bottom: 1rem;">
            <?php 
            if ($msg === 'deleted') echo 'Question deleted successfully.';
            elseif ($msg === 'updated') echo 'Question updated successfully.';
            elseif ($msg === 'category_deleted') echo "Category deleted successfully. Removed $msg_count question(s).";
            elseif ($msg === 'category_not_found') echo 'Category not found or no questions to delete.';
            ?>
        </div>
    <?php endif; ?>

    <div style="margin-bottom: 1rem;">
        <a href="dashboard.php" class="btn" style="margin-right: 1rem;">Back to Dashboard</a>
        <form method="GET" style="display: inline;">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search question or category..." style="padding: 0.5rem; width: 250px;">
            <button type="submit" class="btn">Search</button>
        </form>
    </div>

    <!-- ===== CATEGORY MANAGEMENT SECTION ===== -->
    <div class="card" style="margin-bottom: 1rem; background: #f9fafb;">
        <h3>Manage Categories</h3>
        <p><small>Select a category from the dropdown and click Delete to remove it along with all related questions.</small></p>
        <?php if (empty($categories)): ?>
            <p>No categories available. Add questions with categories first.</p>
        <?php else: ?>
            <form method="GET" style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <select name="delete_category" id="categorySelectDelete" required style="padding: 0.5rem; width: 300px; border: 1px solid #d1d5db; border-radius: 4px;">
                    <option value="">Select a category to delete</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                            <?php echo htmlspecialchars($cat['category']); ?> (<?php echo $cat['question_count']; ?> questions)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure? This will delete the selected category and all ' + (document.getElementById('categorySelectDelete').selectedOptions[0]?.textContent.match(/\((\d+) questions\)/)?.[1] || 0) + ' related questions. This cannot be undone.');">
                    Delete Selected Category
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($edit_q): ?>
        <!-- ===== EDIT FORM ===== -->
        <div class="card" style="margin-bottom: 1rem; background: #f9fafb;">
            <h3>Edit Question (ID: <?php echo $edit_q['id']; ?>)</h3>
            <form method="POST">
                <input type="hidden" name="edit_id" value="<?php echo $edit_q['id']; ?>">
                <textarea name="question" placeholder="Question" required rows="3"><?php echo htmlspecialchars($edit_q['question']); ?></textarea>
                <input type="text" name="a" placeholder="Option A" value="<?php echo htmlspecialchars($edit_q['option_a']); ?>" required>
                <input type="text" name="b" placeholder="Option B" value="<?php echo htmlspecialchars($edit_q['option_b']); ?>" required>
                <input type="text" name="c" placeholder="Option C" value="<?php echo htmlspecialchars($edit_q['option_c']); ?>" required>
                <input type="text" name="d" placeholder="Option D" value="<?php echo htmlspecialchars($edit_q['option_d']); ?>" required>
                <label>Correct Answer:</label>
                <select name="correct">
                    <?php foreach (['A','B','C','D'] as $opt): ?>
                        <option value="<?php echo $opt; ?>" <?php echo $edit_q['correct_answer'] === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="category" placeholder="Category" value="<?php echo htmlspecialchars($edit_q['category']); ?>">
                <button type="submit" class="btn">Update</button>
                <a href="view_questions.php" class="btn btn-danger">Cancel</a>
            </form>
        </div>
    <?php endif; ?>

    <!-- ===== QUESTIONS TABLE ===== -->
    <div class="card">
        <h3>Questions List</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f3f4f6;">
                    <th style="padding: 0.75rem; border: 1px solid #d1d5db;">ID</th>
                    <th style="padding: 0.75rem; border: 1px solid #d1d5db;">Question</th>
                    <th style="padding: 0.75rem; border: 1px solid #d1d5db;">Category</th>
                    <th style="padding: 0.75rem; border: 1px solid #d1d5db;">Correct</th>
                    <th style="padding: 0.75rem; text-align:center; border: 1px solid #d1d5db;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($questions): ?>
                    <?php foreach ($questions as $q): ?>
                        <tr style="border: 1px solid #d1d5db;">
                            <td style="padding: 0.75rem;"><?php echo $q['id']; ?></td>
                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars(substr($q['question'], 0, 80)) . (strlen($q['question']) > 80 ? '...' : ''); ?></td>
                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($q['category']); ?></td>
                            <td style="padding: 0.75rem;"><?php echo $q['correct_answer']; ?></td>
                            <td style="padding: 0.75rem; text-align: center;">
                                <a href="?edit=<?php echo $q['id']; ?>" class="btn" style="padding: 0.4rem;">Edit</a>
                                <a href="?delete=<?php echo $q['id']; ?>" class="btn btn-danger" style="padding: 0.4rem;" onclick="return confirm('Delete this question?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding:1rem;">No questions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- ===== PAGINATION ===== -->
      <!-- ===== PAGINATION ===== -->
<?php if ($total_pages > 1): ?>
    <div style="text-align:center; margin-top: 1rem;">
        <?php
        // Config for smart pagination
        $delta = 2; // Pages before/after current (total around current: 1 + 2*delta = 5)
        $show_first_last = true; // Show page 1 and last
        $ellipsis = '...'; // Text for gaps

        // Prev button
        if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="btn">&laquo; Prev</a>
        <?php endif; ?>

        <?php
        // Always show page 1
        if ($show_first_last && $page > $delta + 2): ?>
            <a href="?page=1&search=<?php echo urlencode($search); ?>" class="btn">1</a>
            <?php if ($page > $delta + 3): echo '<span class="pagination-ellipsis">' . $ellipsis . '</span>'; endif; ?>
        <?php endif;

        // Pages around current
        for ($i = max(2, $page - $delta); $i <= min($total_pages - 1, $page + $delta); $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="btn" style="margin:0 0.2rem; <?php echo ($i == $page) ? 'background:#2563eb;color:#fff;' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor;

        // Always show last page
        if ($show_first_last && $page < $total_pages - $delta): ?>
            <?php if ($page < $total_pages - $delta - 1): echo '<span class="pagination-ellipsis">' . $ellipsis . '</span>'; endif; ?>
            <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>" class="btn"><?php echo $total_pages; ?></a>
        <?php endif; ?>

       
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="btn">Next &raquo;</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
    </div>
</div>
<?php 
include '../includes/footer.php'; 
?>
<script src="../assets/js/script.js"></script>
</body>
</html>