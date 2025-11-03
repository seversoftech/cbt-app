<?php require '../config/db.php';
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

$success = $error = null;

// Fetch unique categories/subjects
$cat_stmt = $pdo->query("SELECT DISTINCT category FROM questions WHERE category != '' AND category != 'General' ORDER BY category ASC");
$categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);

if ($_POST) {
    $question = trim($_POST['question']);
    $option_a = trim($_POST['a']);
    $option_b = trim($_POST['b']);
    $option_c = trim($_POST['c']);
    $option_d = trim($_POST['d']);
    $correct = $_POST['correct'];
    $category = trim($_POST['category'] ?? 'General');
    $new_category = trim($_POST['new_category'] ?? '');

    if (empty($question) || empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d) || empty($correct)) {
        $error = 'All fields are required.';
    } elseif (!in_array($correct, ['A', 'B', 'C', 'D'])) {
        $error = 'Invalid correct answer selection.';
    } else {
        // Use new_category if provided, else category
        $final_category = !empty($new_category) ? $new_category : $category;

        try {
            $stmt = $pdo->prepare("INSERT INTO questions (question, option_a, option_b, option_c, option_d, correct_answer, category) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$question, $option_a, $option_b, $option_c, $option_d, $correct, $final_category]);
            $success = 'Question added successfully!';
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<?php include '../includes/header.php'; ?>
<div class="card">
    <h2>Add Question</h2>
    <?php if (isset($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST" id="questionForm">
        <textarea name="question" placeholder="Enter the question here..." required rows="3"></textarea>
        
   
        
        <!-- Options -->
        <div class="form-group">
            <label>Answer Options</label>
            <div class="options-group">
                <input type="text" name="a" placeholder="Option A" required>
                <input type="text" name="b" placeholder="Option B" required>
                <input type="text" name="c" placeholder="Option C" required>
                <input type="text" name="d" placeholder="Option D" required>
            </div>
        </div>
        

             <!-- Correct Answer Select (before options) -->
             <div class="form-group">
            <label for="correct">Correct Answer</label>
            <select name="correct" id="correct" required>
                <option value="">Select correct answer</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
                <option value="D">D</option>
            </select>
        </div>
        <!-- Subject (Category) -->
        <div class="form-group">
            <label for="category">Subject</label>
            <select name="category" id="category" onchange="toggleNewCategory()">
                <option value="General">General</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                <?php endforeach; ?>
                <option value="new">Add New Subject</option>
            </select>
            <input type="text" name="new_category" id="newCategory" placeholder="Enter new subject name..." style="display: none; margin-top: 0.5rem;">
        </div>
        
        <!-- Buttons -->
        <div class="form-buttons">
            <button type="button" class="btn btn-secondary" onclick="window.location.href='dashboard.php'">Back to Dashboard</button>
            <button type="submit" class="btn">Add Question</button>
        </div>
    </form>
</div>

<script src="../assets/js/script.js"></script>
<script>
// Toggle new category input
function toggleNewCategory() {
    const select = document.getElementById('category');
    const newInput = document.getElementById('newCategory');
    if (select.value === 'new') {
        newInput.style.display = 'block';
        newInput.required = true;
    } else {
        newInput.style.display = 'none';
    
        newInput.value = '';
    }
}

// Optional: Highlight correct option preview (client-side)
document.getElementById('correct').addEventListener('change', function() {
    const correct = this.value;
    const options = document.querySelectorAll('input[name^="a"], input[name^="b"], input[name^="c"], input[name^="d"]');
    options.forEach(opt => opt.style.borderColor = '#d1d5db');
    if (correct) {
        const correctOpt = document.querySelector(`input[name="${correct.toLowerCase()}"]`);
        if (correctOpt) correctOpt.style.borderColor = '#10b981';
    }
});
</script>
</body></html>