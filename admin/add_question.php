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

        // Image Upload Logic
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $newFilename = uniqid('q_', true) . '.' . $ext;
                $targetDir = '../assets/uploads/questions/';
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                
                $targetPath = $targetDir . $newFilename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $imagePath = 'assets/uploads/questions/' . $newFilename; // Store relative path for frontend
                }
            } else {
                $error = 'Invalid image format. Only JPG, PNG, and GIF are allowed.';
            }
        }

        if (!$error) { // Proceed if no upload error
            try {
                $stmt = $pdo->prepare("INSERT INTO questions (question, image, option_a, option_b, option_c, option_d, correct_answer, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$question, $imagePath, $option_a, $option_b, $option_c, $option_d, $correct, $final_category]);
                $success = 'Question added successfully!';
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}
?>
<?php include '../includes/header.php';
include '../includes/admin_nav.php'; // Unified Admin Navbar
?>
<!-- Summernote CSS -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">

<main style="padding-bottom: 4rem;">
    <div class="container" style="padding-top: 2rem; padding-bottom: 2rem; max-width: 900px;">
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid #f3f4f6; padding-bottom: 1rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="background: var(--primary-light); color: var(--primary); padding: 0.75rem; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-plus" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <h2 style="margin: 0; font-size: 1.75rem;">Add Question</h2>
                    <p style="margin: 0; color: var(--text-light); font-size: 0.9rem;">Create a new question for the exam bank</p>
                </div>
            </div>
            
            <a href="dashboard.php" class="btn" style="background: var(--text-light); box-shadow: none;">
                <i class="fa-solid fa-arrow-left"></i> Cancel
            </a>
        </div>

        <?php if (isset($success)): ?>
             <div style="background-color: #ecfdf5; border-left: 4px solid var(--secondary); color: #065f46; padding: 1rem; margin-bottom: 2rem; border-radius: 0.5rem; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div style="background-color: #fee2e2; border-left: 4px solid var(--danger); color: #b91c1c; padding: 1rem; margin-bottom: 2rem; border-radius: 0.5rem; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" id="questionForm" enctype="multipart/form-data">
            <!-- Subject Selection -->
            <div style="margin-bottom: 2rem; background: #f9fafb; padding: 1.5rem; border-radius: 1rem;">
                <label for="category" style="display: block; margin-bottom: 0.5rem; font-weight: 700; color: var(--dark);"><i class="fa-solid fa-folder-open me-2"></i>Subject Category</label>
                <select name="category" id="category" onchange="toggleNewCategory()" style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid #d1d5db; font-size: 1rem; background: white;">
                    <option value="" disabled selected>-- Select Subject --</option>
                    <option value="General">General</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                    <option value="new" style="font-weight: bold; color: var(--primary);">➕ Add New Subject...</option>
                </select>

                <input type="text" name="new_category" id="newCategory" placeholder="Enter name for new subject..." 
                       style="display: none; margin-top: 1rem; width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 2px solid var(--primary); background: #ffffff;">
            </div>

            <!-- Question Text -->
            <div style="margin-bottom: 2rem;">
                <label for="question" style="display: block; margin-bottom: 0.5rem; font-weight: 700; color: var(--dark);"><i class="fa-solid fa-circle-question me-2"></i>Question Text</label>
                <textarea name="question" id="summernote" required></textarea>
            </div>

            <!-- Image Upload -->
            <div style="margin-bottom: 2rem; background: #f9fafb; padding: 1.5rem; border-radius: 1rem; border: 1px dashed var(--glass-border);">
                <label for="image" style="display: block; margin-bottom: 0.5rem; font-weight: 700; color: var(--dark);"><i class="fas fa-image me-2"></i>Optional Image</label>
                <input type="file" name="image" id="image" accept="image/*" style="width: 100%;">
                <p style="margin: 0.5rem 0 0; font-size: 0.8rem; color: var(--text-light);">Supported formats: JPG, PNG, GIF</p>
            </div>
            
            <!-- Options Grid -->
            <div style="margin-bottom: 2rem;">
                 <label style="display: block; margin-bottom: 1rem; font-weight: 700; color: var(--dark);"><i class="fa-solid fa-list-ul me-2"></i>Answer Options</label>
                 
                 <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                     <!-- Option A -->
                     <div class="option-input-group" style="position: relative;">
                         <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-weight: 800; color: var(--text-light); background: #f3f4f6; padding: 0.25rem 0.75rem; border-radius: 0.5rem;">A</span>
                         <input type="text" name="a" placeholder="Option A Answer" required
                                style="width: 100%; padding: 0.75rem 0.75rem 0.75rem 3.5rem; border-radius: 0.75rem; border: 1px solid #d1d5db;">
                     </div>
                     <!-- Option B -->
                     <div class="option-input-group" style="position: relative;">
                         <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-weight: 800; color: var(--text-light); background: #f3f4f6; padding: 0.25rem 0.75rem; border-radius: 0.5rem;">B</span>
                         <input type="text" name="b" placeholder="Option B Answer" required
                                style="width: 100%; padding: 0.75rem 0.75rem 0.75rem 3.5rem; border-radius: 0.75rem; border: 1px solid #d1d5db;">
                     </div>
                     <!-- Option C -->
                     <div class="option-input-group" style="position: relative;">
                         <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-weight: 800; color: var(--text-light); background: #f3f4f6; padding: 0.25rem 0.75rem; border-radius: 0.5rem;">C</span>
                         <input type="text" name="c" placeholder="Option C Answer" required
                                style="width: 100%; padding: 0.75rem 0.75rem 0.75rem 3.5rem; border-radius: 0.75rem; border: 1px solid #d1d5db;">
                     </div>
                     <!-- Option D -->
                     <div class="option-input-group" style="position: relative;">
                         <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-weight: 800; color: var(--text-light); background: #f3f4f6; padding: 0.25rem 0.75rem; border-radius: 0.5rem;">D</span>
                         <input type="text" name="d" placeholder="Option D Answer" required
                                style="width: 100%; padding: 0.75rem 0.75rem 0.75rem 3.5rem; border-radius: 0.75rem; border: 1px solid #d1d5db;">
                     </div>
                 </div>
            </div>

            <!-- Correct Answer Selection -->
             <div style="margin-bottom: 2.5rem; background: #ecfdf5; padding: 1.5rem; border-radius: 1rem; border: 1px dashed var(--secondary);">
                <label for="correct" style="display: block; margin-bottom: 0.5rem; font-weight: 700; color: #065f46;"><i class="fa-solid fa-check-circle me-2"></i>Select Correct Answer</label>
                <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                    <label style="cursor: pointer; display: flex; align-items: center; gap: 0.5rem; background: white; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #d1d5db;">
                        <input type="radio" name="correct" value="A" required onchange="highlightCorrect('a')"> 
                        <span style="font-weight: 600;">Option A</span>
                    </label>
                    <label style="cursor: pointer; display: flex; align-items: center; gap: 0.5rem; background: white; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #d1d5db;">
                        <input type="radio" name="correct" value="B" required onchange="highlightCorrect('b')"> 
                        <span style="font-weight: 600;">Option B</span>
                    </label>
                    <label style="cursor: pointer; display: flex; align-items: center; gap: 0.5rem; background: white; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #d1d5db;">
                        <input type="radio" name="correct" value="C" required onchange="highlightCorrect('c')"> 
                        <span style="font-weight: 600;">Option C</span>
                    </label>
                    <label style="cursor: pointer; display: flex; align-items: center; gap: 0.5rem; background: white; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #d1d5db;">
                        <input type="radio" name="correct" value="D" required onchange="highlightCorrect('d')"> 
                        <span style="font-weight: 600;">Option D</span>
                    </label>
                </div>
            </div>
            
            <!-- Submit Button -->
            <button type="submit" class="btn" style="width: 100%; padding: 1rem; font-size: 1.2rem; justify-content: center;">
                <i class="fa-solid fa-save"></i> Save Question
            </button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="../assets/js/script.js"></script>
<script>
// Toggle new category input
function toggleNewCategory() {
    const select = document.getElementById('category');
    const newInput = document.getElementById('newCategory');
    if (select.value === 'new') {
        newInput.style.display = 'block';
        newInput.required = true;
        newInput.focus();
    } else {
        newInput.style.display = 'none';
        newInput.required = false; // Important: remove required if hidden
        newInput.value = '';
    }
}

// Highlight correct option visual feedback
function highlightCorrect(optionLower) {
    // Reset all borders
    const allInputs = document.querySelectorAll('input[name="a"], input[name="b"], input[name="c"], input[name="d"]');
    allInputs.forEach(input => {
        input.style.borderColor = '#d1d5db';
        input.style.borderWidth = '1px';
        input.style.boxShadow = 'none';
        input.parentElement.querySelector('span').style.background = '#f3f4f6';
        input.parentElement.querySelector('span').style.color = 'var(--text-light)';
    });

    // Highlight selected
    const selectedInput = document.querySelector(`input[name="${optionLower}"]`);
    if (selectedInput) {
        selectedInput.style.borderColor = 'var(--secondary)';
        selectedInput.style.borderWidth = '2px';
        selectedInput.style.boxShadow = '0 0 0 4px #ecfdf5';
        
        // Highlight the badge too
        const badge = selectedInput.parentElement.querySelector('span');
        badge.style.background = 'var(--secondary)';
        badge.style.color = 'white';
    }
}
</script>
</script>
<!-- Summernote JS -->
<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
    $('#summernote').summernote({
        placeholder: 'Type your question here...',
        tabsize: 2,
        height: 150,
        toolbar: [
          ['style', ['style']],
          ['font', ['bold', 'underline', 'clear']],
          ['color', ['color']],
          ['para', ['ul', 'ol', 'paragraph']],
          ['maxHeight', ['400px']]
        ]
    });
</script>
</body></html>