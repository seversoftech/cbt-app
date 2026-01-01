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
    $question = $_POST['question'];
    $edit_id = $_POST['edit_id'];
    
    // Handle Image Update
    $imageUpdateSql = "";
    $params = [
        $question, $_POST['a'], $_POST['b'], $_POST['c'], $_POST['d'],
        $_POST['correct'], $_POST['category']
    ];

    if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['edit_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $newFilename = uniqid('q_', true) . '.' . $ext;
            $targetDir = '../assets/uploads/questions/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            
            $targetPath = $targetDir . $newFilename;
            if (move_uploaded_file($_FILES['edit_image']['tmp_name'], $targetPath)) {
                $imagePath = 'assets/uploads/questions/' . $newFilename;
                $imageUpdateSql = ", image = ?";
                $params[] = $imagePath; // Add to params
            }
        }
    }
    
    $params[] = $edit_id; // Add ID last

    $sql = "UPDATE questions 
            SET question = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_answer = ?, category = ? $imageUpdateSql
            WHERE id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    header('Location: view_questions.php?msg=updated');
    exit;
}

// ====== PAGINATION ======
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// ====== SEARCH FEATURE ======
// ====== SEARCH & FILTER ======
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$where = '';
$params = [];

if ($category_filter !== '') {
    $where = "WHERE category = :category";
    $params[':category'] = $category_filter;
} elseif ($search !== '') {
    $where = "WHERE question LIKE :search OR category LIKE :search";
    $params[':search'] = "%$search%";
}

// ====== TOTAL QUESTIONS ======
$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM questions $where");
$total_stmt->execute($params);
$total = $total_stmt->fetchColumn();
$total_pages = ceil($total / $limit);

// ====== FETCH PAGINATED QUESTIONS ======
$sql = "SELECT id, question, category, correct_answer, image, created_at FROM questions $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
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
                        <i class="fa-solid fa-list-check" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h2 style="margin: 0; font-size: 1.75rem;">Manage Questions</h2>
                        <p style="margin: 0; color: var(--text-light); font-size: 0.9rem;">Maintain and organize your question bank</p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 0.5rem;">
                    <a href="add_question.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Question
                    </a>
                    <a href="dashboard.php" class="btn" style="background: var(--text-light); box-shadow: none;">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($msg): ?>
                <div style="margin-bottom: 2rem; background: var(--bg-body); border-left: 4px solid var(--secondary); padding: 1rem; border-radius: var(--radius-md); color: var(--text-main); display: flex; align-items: center; gap: 10px; border: 1px solid var(--glass-border);">
                    <i class="fas fa-check-circle text-secondary"></i>
                    <span>
                        <?php 
                        if ($msg === 'deleted') echo 'Question deleted successfully.';
                        elseif ($msg === 'updated') echo 'Question updated successfully.';
                        elseif ($msg === 'category_deleted') echo "Category deleted successfully. Removed $msg_count question(s).";
                        elseif ($msg === 'category_not_found') echo 'Category not found or no questions to delete.';
                        ?>
                    </span>
                    <button onclick="this.parentElement.style.display='none'" style="margin-left: auto; background:none; border:none; cursor:pointer; color:inherit; font-size:1.2rem; opacity:0.5;">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Category Filter Active Badge -->
            <?php if (!empty($category_filter)): ?>
                <div style="margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
                    <div style="background: var(--primary); color: white; padding: 0.5rem 1rem; border-radius: 2rem; display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 600;">
                        <span>Filtering by: <?php echo htmlspecialchars($category_filter); ?></span>
                        <a href="view_questions.php" style="color: white; text-decoration: none; opacity: 0.8; margin-left: 0.5rem;" title="Clear filter"><i class="fas fa-times-circle"></i></a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Toolbar Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <!-- Search -->
                <div>
                    <label>Search Questions</label>
                    <form method="GET" style="display: flex; gap: 0.5rem;">
                        <div style="position: relative; flex: 1;">
                            <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search text or category..." 
                                   style="padding-left: 2.8rem;">
                        </div>
                        <button type="submit" class="btn">Search</button>
                    </form>
                </div>

                <!-- Category Bulk Delete -->
                <div>
                    <label>Bulk Actions</label>
                    <form id="categoryDeleteForm" method="GET" style="display: flex; gap: 0.5rem;">
                        <div style="flex: 1;">
                            <select name="delete_category" id="categorySelectDelete" required class="modern-select">
                                <option value="">Delete a category...</option>
                                <?php if ($categories): ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                                            "<?php echo htmlspecialchars($cat['category']); ?>" (<?php echo $cat['question_count']; ?> Qs)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <button type="button" class="btn btn-danger" onclick="handleCategoryDeleteConfirm()" title="Delete category">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($edit_q): ?>
                <!-- ===== EDIT FORM OVERLAY (MODERN) ===== -->
                <div style="background: var(--bg-body); border: 2px solid var(--primary); border-radius: var(--radius-xl); padding: 2rem; margin-bottom: 3rem; position: relative;">
                    <div style="position: absolute; top: 1rem; right: 1rem;">
                        <a href="view_questions.php" style="color: var(--text-light); text-decoration: none; font-size: 1.5rem;">&times;</a>
                    </div>
                    <h3 style="margin-top: 0; color: var(--primary); display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;">
                        <i class="fas fa-edit"></i> Edit Question #<?php echo $edit_q['id']; ?>
                    </h3>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="edit_id" value="<?php echo $edit_q['id']; ?>">
                        
                        <div style="margin-bottom: 1.5rem;">
                            <label>Question Text</label>
                            <textarea name="question" id="editSummernote" required rows="3"><?php echo htmlspecialchars($edit_q['question']); ?></textarea>
                        </div>

                        <!-- Edit Image -->
                        <div style="margin-bottom: 1.5rem; background: #f9fafb; padding: 1rem; border-radius: 0.5rem; border: 1px dashed var(--glass-border);">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 700; color: var(--dark);">Update Image</label>
                            <?php if (!empty($edit_q['image'])): ?>
                                <div style="margin-bottom: 0.5rem;">
                                    <img src="../<?php echo htmlspecialchars($edit_q['image']); ?>" style="max-height: 100px; border-radius: 4px; border: 1px solid #ddd;">
                                    <div style="font-size: 0.8rem; color: var(--text-light);">Current Image</div>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="edit_image" accept="image/*" style="width: 100%;">
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                            <div>
                                <label>Option A</label>
                                <input type="text" name="a" value="<?php echo htmlspecialchars($edit_q['option_a']); ?>" required>
                            </div>
                            <div>
                                <label>Option B</label>
                                <input type="text" name="b" value="<?php echo htmlspecialchars($edit_q['option_b']); ?>" required>
                            </div>
                            <div>
                                <label>Option C</label>
                                <input type="text" name="c" value="<?php echo htmlspecialchars($edit_q['option_c']); ?>" required>
                            </div>
                            <div>
                                <label>Option D</label>
                                <input type="text" name="d" value="<?php echo htmlspecialchars($edit_q['option_d']); ?>" required>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                             <div>
                                <label>Correct Answer</label>
                                <select name="correct" class="modern-select">
                                    <?php foreach (['A','B','C','D'] as $opt): ?>
                                        <option value="<?php echo $opt; ?>" <?php echo $edit_q['correct_answer'] === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label>Category</label>
                                <input type="text" name="category" value="<?php echo htmlspecialchars($edit_q['category']); ?>">
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                            <a href="view_questions.php" class="btn" style="background: var(--text-light); box-shadow: none;">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Questions Table -->
            <div class="table-container">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th width="8%">ID</th>
                                <th width="42%">Question</th>
                                <th width="15%">Category</th>
                                <th width="10%" style="text-align: center;">Answer</th>
                                <th width="15%" style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($questions): ?>
                                <?php foreach ($questions as $q): ?>
                                    <tr>
                                        <td style="color: var(--text-light); font-size: 0.85rem;">#<?php echo $q['id']; ?></td>
                                        <td style="font-weight: 500;">
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                <?php if (!empty($q['image'])): ?>
                                                    <img src="../<?php echo htmlspecialchars($q['image']); ?>" alt="Img" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid var(--glass-border);">
                                                <?php endif; ?>
                                                <div style="max-height: 3rem; overflow: hidden; text-overflow: ellipsis;">
                                                    <?php echo strip_tags(htmlspecialchars_decode($q['question'])); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="background: var(--primary-light); color: var(--primary); padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 700; white-space: nowrap;">
                                                <?php echo htmlspecialchars($q['category']); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span style="width: 2rem; height: 2rem; display: inline-flex; align-items: center; justify-content: center; background: rgba(16, 185, 129, 0.1); color: var(--secondary); border-radius: 50%; font-weight: 900;">
                                                <?php echo $q['correct_answer']; ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <div style="display: flex; gap: 0.4rem; justify-content: center;">
                                                <a href="?edit=<?php echo $q['id']; ?>&page=<?php echo $page; ?>&search=<?php echo urlencode($search); ?>" class="btn" style="padding: 0.4rem; background: var(--text-light); box-shadow: none;" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button onclick="showConfirmModal('Delete Question', 'Permenently delete this question?', () => window.location.href = '?delete=<?php echo $q['id']; ?>&page=<?php echo $page; ?>&search=<?php echo urlencode($search); ?>');" class="btn btn-danger" style="padding: 0.4rem;" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; padding: 3rem; color: var(--text-light);">
                                        <i class="fas fa-search-minus fa-3x" style="opacity: 0.2; display: block; margin-bottom: 1rem;"></i>
                                        No questions found.
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
                    $range = 2; // Number of pages before and after the current page
                    $show_dots_at = $range * 2 + 1;

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

<!-- Confirmation Modal -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="confirmTitle" style="margin: 0;">Confirm Action</h3>
            <span class="close" onclick="closeConfirmModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p id="confirmMessage" style="color: var(--text-main); font-size: 1.1rem; line-height: 1.4;"></p>
        </div>
        <div class="modal-footer">
            <button id="confirmNo" class="btn" style="background: var(--text-light); box-shadow: none;">Cancel</button>
            <button id="confirmYes" class="btn btn-danger">Yes, Proceed</button>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<script src="../assets/js/script.js"></script>
<script>
let confirmAction = null;
function showConfirmModal(title, message, action) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    confirmAction = action;
    const modal = document.getElementById('confirmModal');
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.classList.add('active'); // CSS hook for opacity
        modal.style.opacity = '1';
    }, 10);
}
function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    modal.style.opacity = '0';
    setTimeout(() => {
        modal.style.display = 'none';
        confirmAction = null;
    }, 300);
}
function handleCategoryDeleteConfirm() {
    const select = document.getElementById('categorySelectDelete');
    if (!select.value) return;
    const msg = `Delete EVERYTHING in "${select.value}"? This cannot be undone.`;
    showConfirmModal('Delete Category', msg, () => document.getElementById('categoryDeleteForm').submit());
}
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('confirmYes').addEventListener('click', () => {
        if (confirmAction) confirmAction();
        closeConfirmModal();
    });
    document.getElementById('confirmNo').addEventListener('click', closeConfirmModal);
    const modal = document.getElementById('confirmModal');
    modal.addEventListener('click', (e) => { if (e.target === modal) closeConfirmModal(); });
});
</script>
</script>
<script>
    // Initialize Summernote for Edit Modal if it exists
    if ($('#editSummernote').length) {
        $('#editSummernote').summernote({
            placeholder: 'Edit question...',
            tabsize: 2,
            height: 150,
            toolbar: [
              ['style', ['style']],
              ['font', ['bold', 'underline', 'italic', 'strikethrough', 'superscript', 'subscript', 'clear']],
              ['color', ['color']],
              ['para', ['ul', 'ol', 'paragraph']],
              ['table', ['table']],
              ['insert', ['link', 'picture', 'video']],
              ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
    }
</script>
</body>
</html>