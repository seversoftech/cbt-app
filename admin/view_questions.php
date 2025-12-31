<?php
require '../config/db.php';

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
        <div class="<?php echo strpos($msg, 'deleted') !== false ? 'success' : 'success'; // simple logic for now ?>" style="margin-bottom: 1rem; color: var(--secondary); background: #ecfdf5; padding: 1rem; border-radius: 0.5rem;">
            <?php 
            if ($msg === 'deleted') echo 'Question deleted successfully.';
            elseif ($msg === 'updated') echo 'Question updated successfully.';
            elseif ($msg === 'category_deleted') echo "Category deleted successfully. Removed $msg_count question(s).";
            elseif ($msg === 'category_not_found') echo 'Category not found or no questions to delete.';
            ?>
        </div>
    <?php endif; ?>

    <div style="margin-bottom: 2rem; display: flex; flex-wrap: wrap; gap: 1rem; align-items: center;">
        <a href="dashboard.php" class="btn" style="background: var(--text-light);">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <form method="GET" style="display: flex; gap: 0.5rem; flex: 1;">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search question or category..." class="form-control" style="max-width: 300px;">
            <button type="submit" class="btn">Search</button>
        </form>
    </div>

    <!-- ===== CATEGORY MANAGEMENT SECTION ===== -->
    <div class="card" style="margin: 0 0 2rem 0; background: #f9fafb; border: 1px dashed #d1d5db; padding: 1.5rem; width: 100%; box-shadow: none;">
        <h3>Manage Categories</h3>
        <p style="font-size: 0.9rem; color: var(--text-light); margin-bottom: 1rem;">Select a category to delete it along with all its questions.</p>
        <?php if (empty($categories)): ?>
            <p>No categories available.</p>
        <?php else: ?>
            <form id="categoryDeleteForm" method="GET" style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <input type="hidden" name="page" value="<?php echo $page; ?>">
                <select name="delete_category" id="categorySelectDelete" required class="form-control" style="max-width: 300px;">
                    <option value="">Select a category to delete</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                            <?php echo htmlspecialchars($cat['category']); ?> (<?php echo $cat['question_count']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-danger" onclick="handleCategoryDeleteConfirm()">
                    Delete Category
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($edit_q): ?>
        <!-- ===== EDIT FORM ===== -->
        <div class="card" style="margin: 0 0 2rem 0; background: #fef3c7; border: 1px solid #fcd34d; width: 100%; box-shadow: none;">
            <h3>Edit Question (ID: <?php echo $edit_q['id']; ?>)</h3>
            <form method="POST" style="display: grid; gap: 1rem;">
                <input type="hidden" name="edit_id" value="<?php echo $edit_q['id']; ?>">
                
                <div class="form-group">
                    <label>Question</label>
                    <textarea name="question" required rows="3" class="form-control"><?php echo htmlspecialchars($edit_q['question']); ?></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div class="form-group">
                        <label>Option A</label>
                        <input type="text" name="a" value="<?php echo htmlspecialchars($edit_q['option_a']); ?>" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Option B</label>
                        <input type="text" name="b" value="<?php echo htmlspecialchars($edit_q['option_b']); ?>" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Option C</label>
                        <input type="text" name="c" value="<?php echo htmlspecialchars($edit_q['option_c']); ?>" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Option D</label>
                        <input type="text" name="d" value="<?php echo htmlspecialchars($edit_q['option_d']); ?>" required class="form-control">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Correct Answer</label>
                        <select name="correct" class="form-control">
                            <?php foreach (['A','B','C','D'] as $opt): ?>
                                <option value="<?php echo $opt; ?>" <?php echo $edit_q['correct_answer'] === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" name="category" value="<?php echo htmlspecialchars($edit_q['category']); ?>" class="form-control">
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button type="submit" class="btn">Update Question</button>
                    <a href="view_questions.php" class="btn btn-danger" style="background: var(--text-light);">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- ===== QUESTIONS TABLE ===== -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th style="width: 40%;">Question</th>
                    <th>Category</th>
                    <th>Correct</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($questions): ?>
                    <?php foreach ($questions as $q): ?>
                        <tr>
                            <td><?php echo $q['id']; ?></td>
                            <td><?php echo htmlspecialchars(substr($q['question'], 0, 80)) . (strlen($q['question']) > 80 ? '...' : ''); ?></td>
                            <td><span style="background: #e0e7ff; color: #4338ca; padding: 0.2rem 0.6rem; border-radius: 99px; font-size: 0.85rem;"><?php echo htmlspecialchars($q['category']); ?></span></td>
                            <td style="font-weight: bold;"><?php echo $q['correct_answer']; ?></td>
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                    <a href="?edit=<?php echo $q['id']; ?>&page=<?php echo $page; ?>&search=<?php echo urlencode($search); ?>" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; background: var(--text-light);">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="showConfirmModal('Delete Question', 'Delete this question?', () => window.location.href = '?delete=<?php echo $q['id']; ?>&page=<?php echo $page; ?>&search=<?php echo urlencode($search); ?>');" class="btn btn-danger" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding: 2rem; color: var(--text-light);">No questions found.</td></tr>
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

<!-- Confirmation Modal -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="confirmTitle">Confirm</h3>
            <span class="close" onclick="closeConfirmModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p id="confirmMessage"></p>
        </div>
        <div class="modal-footer">
            <button id="confirmYes" class="btn btn-danger">Yes</button>
            <button id="confirmNo" class="btn">No</button>
        </div>
    </div>
</div>

<?php 
include '../includes/footer.php'; 
?>

<script src="../assets/js/script.js"></script>
<script>
let confirmAction = null;

function showConfirmModal(title, message, action) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    confirmAction = action;
    document.getElementById('confirmModal').style.display = 'flex';
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
    confirmAction = null;
}

function handleCategoryDeleteConfirm() {
    const select = document.getElementById('categorySelectDelete');
    if (!select.value) {
        alert('Please select a category first.'); // Fallback for no selection
        return;
    }
    const countMatch = select.selectedOptions[0].textContent.match(/\((\d+) questions\)/);
    const count = countMatch ? countMatch[1] : 0;
    const msg = `Are you sure? This will delete the selected category and all ${count} related questions. This cannot be undone.`;
    showConfirmModal('Delete Category', msg, () => {
        document.getElementById('categoryDeleteForm').submit();
    });
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('confirmYes').addEventListener('click', () => {
        if (confirmAction) {
            confirmAction();
        }
        closeConfirmModal();
    });
    document.getElementById('confirmNo').addEventListener('click', closeConfirmModal);

    // Close on outside click
    const modal = document.getElementById('confirmModal');
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeConfirmModal();
        }
    });
});
</script>
</body>
</html>