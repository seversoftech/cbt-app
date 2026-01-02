<?php require '../config/db.php';
if (!isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

$success = $error = null;

// Fetch unique categories/subjects
$cat_stmt = $pdo->query("SELECT DISTINCT category FROM questions WHERE category != '' AND category != 'General' ORDER BY category ASC");
$categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);

if ($_POST) {
    $question = trim($_POST['question']);
    // Capture options with fallbacks to empty string
    $option_a = trim($_POST['a'] ?? '');
    $option_b = trim($_POST['b'] ?? '');
    $option_c = trim($_POST['c'] ?? '');
    $option_d = trim($_POST['d'] ?? '');
    $correct = $_POST['correct'] ?? ''; // Handle undefined for theory
    $category = trim($_POST['category'] ?? 'General');
    $new_category = trim($_POST['new_category'] ?? '');
    $type = $_POST['type'] ?? 'objective';

    // If Theory, force objective fields to match DB constraints (empty strings)
    if ($type === 'theory') {
        $option_a = $option_b = $option_c = $option_d = '';
        $correct = ''; // Ensure not NULL
    }

    if (empty($question) || ($type === 'objective' && (empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d) || empty($correct)))) {
        $error = 'All fields are required for objective questions.';
    } elseif ($type === 'objective' && !in_array($correct, ['A', 'B', 'C', 'D'])) {
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
                // Ensure we handle potential DB constraints by using empty string instead of null if needed
                // Assuming columns are VARCHAR/TEXT. If ENUM, '' might fail but usually works as '0' or invalid.
                // Given the error was "cannot be NULL", '' is the correct fix.
                $stmt = $pdo->prepare("INSERT INTO questions (question, image, option_a, option_b, option_c, option_d, correct_answer, category, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$question, $imagePath, $option_a, $option_b, $option_c, $option_d, $correct, $final_category, $type]);
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

            <!-- Question Type -->
            <div style="margin-bottom: 2rem; background: #f9fafb; padding: 1.5rem; border-radius: 1rem;">
                <label for="qType" style="display: block; margin-bottom: 0.5rem; font-weight: 700; color: var(--dark);"><i class="fa-solid fa-layer-group me-2"></i>Question Type</label>
                <select name="type" id="qType" onchange="toggleType()" style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid #d1d5db; font-size: 1rem; background: white;">
                    <option value="objective">Objective (Multiple Choice)</option>
                    <option value="theory">Theory (Text Answer)</option>
                </select>
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
            
            <!-- Split Layout for Answers -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; align-items: start;">
                
                <!-- Left Column: Objective Options -->
                <div id="objectiveColumn" style="background: white; border: 1px solid #e5e7eb; border-radius: 1rem; padding: 1.5rem; transition: all 0.3s ease;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                         <h4 style="margin: 0; color: var(--dark); font-weight: 700;"><i class="fa-solid fa-list-ul me-2"></i>Objective Options</h4>
                         <div class="badge-pill" style="background: #e0e7ff; color: #4338ca; font-size: 0.75rem; padding: 0.25rem 0.75rem; border-radius: 1rem;">Default</div>
                    </div>

                    <div style="display: grid; gap: 1rem;">
                        <!-- Option A -->
                         <div class="option-input-group" style="position: relative;">
                             <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-weight: 800; color: var(--text-light); background: #f3f4f6; padding: 0.25rem 0.75rem; border-radius: 0.5rem;">A</span>
                             <input type="text" name="a" id="inputA" placeholder="Option A" 
                                    style="width: 100%; padding: 0.75rem 0.75rem 0.75rem 3.5rem; border-radius: 0.75rem; border: 1px solid #d1d5db;">
                         </div>
                         <!-- Option B -->
                         <div class="option-input-group" style="position: relative;">
                             <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-weight: 800; color: var(--text-light); background: #f3f4f6; padding: 0.25rem 0.75rem; border-radius: 0.5rem;">B</span>
                             <input type="text" name="b" id="inputB" placeholder="Option B" 
                                    style="width: 100%; padding: 0.75rem 0.75rem 0.75rem 3.5rem; border-radius: 0.75rem; border: 1px solid #d1d5db;">
                         </div>
                         <!-- Option C -->
                         <div class="option-input-group" style="position: relative;">
                             <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-weight: 800; color: var(--text-light); background: #f3f4f6; padding: 0.25rem 0.75rem; border-radius: 0.5rem;">C</span>
                             <input type="text" name="c" id="inputC" placeholder="Option C" 
                                    style="width: 100%; padding: 0.75rem 0.75rem 0.75rem 3.5rem; border-radius: 0.75rem; border: 1px solid #d1d5db;">
                         </div>
                         <!-- Option D -->
                         <div class="option-input-group" style="position: relative;">
                             <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-weight: 800; color: var(--text-light); background: #f3f4f6; padding: 0.25rem 0.75rem; border-radius: 0.5rem;">D</span>
                             <input type="text" name="d" id="inputD" placeholder="Option D" 
                                    style="width: 100%; padding: 0.75rem 0.75rem 0.75rem 3.5rem; border-radius: 0.75rem; border: 1px solid #d1d5db;">
                         </div>
                    </div>

                    <!-- Correct Answer Selection -->
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px dashed #e5e7eb;">
                        <label style="display: block; margin-bottom: 0.75rem; font-weight: 700; color: #065f46; font-size: 0.9rem;">Correct Answer:</label>
                        <div style="display: flex; gap: 1rem; justify-content: space-between;">
                            <label class="radio-card"><input type="radio" name="correct" value="A" id="radA"> A</label>
                            <label class="radio-card"><input type="radio" name="correct" value="B" id="radB"> B</label>
                            <label class="radio-card"><input type="radio" name="correct" value="C" id="radC"> C</label>
                            <label class="radio-card"><input type="radio" name="correct" value="D" id="radD"> D</label>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Theory Details -->
                <div id="theoryColumn" style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 1rem; padding: 1.5rem; opacity: 0.5; pointer-events: none; transition: all 0.3s ease;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                         <h4 style="margin: 0; color: var(--dark); font-weight: 700;"><i class="fas fa-pen-alt me-2"></i>Theory Details</h4>
                         <div class="badge-pill" style="background: #ffedd5; color: #9a3412; font-size: 0.75rem; padding: 0.25rem 0.75rem; border-radius: 1rem;">Essay / Text</div>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-main); font-size: 0.9rem;">Model Answer / Grading Guide</label>
                        <textarea name="model_answer" id="modelAnswer" rows="8" 
                                  style="width: 100%; padding: 1rem; border-radius: 0.75rem; border: 1px solid #d1d5db; background: #f9fafb;" 
                                  placeholder="Enter the expected answer here..."></textarea>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-light); line-height: 1.5;">
                        <i class="fas fa-info-circle me-1"></i> Students will see a text area to type their response. This guide is for the specific marking script or manual review.
                    </p>
                </div>
            </div>

            <!-- Submit Button (Centered) -->
            <div style="margin-top: 2rem; text-align: center;">
                <button type="submit" class="btn btn-lg" style="width: 100%; max-width: 400px; padding: 1rem; font-size: 1.1rem; border-radius: 2rem;">
                    <i class="fa-solid fa-save me-2"></i> Save Question
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Local overrides for radio cards */
.radio-card {
    flex: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    background: #f9fafb;
    padding: 0.5rem;
    border-radius: 0.5rem;
    border: 1px solid #d1d5db;
    font-weight: 600;
    transition: all 0.2s;
}
.radio-card:hover {
    background: white;
    border-color: var(--primary);
}
input[type="radio"]:checked + .radio-card, 
.radio-card:has(input:checked) {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}
</style>

<?php include '../includes/footer.php'; ?>

<script src="../assets/js/script.js"></script>
<script>
// Toggle new category input
function toggleNewCategory() {
    const select = document.getElementById('category');
    const newInput = document.getElementById('newCategory');
    if (select.value === 'new') {
        newInput.style.display = 'block';
        newInput.required = true; // Make required
        newInput.focus();
    } else {
        newInput.style.display = 'none';
        newInput.required = false; // Remove required
        newInput.value = '';
    }
}

// Ensure Type Toggle works on Load and Change
const typeSelect = document.getElementById('qType');
if (typeSelect) {
    typeSelect.addEventListener('change', updateTypeUI);
    // Initial call
    updateTypeUI();
}

function updateTypeUI() {
    const type = document.getElementById('qType').value;
    const objCol = document.getElementById('objectiveColumn');
    const theoryCol = document.getElementById('theoryColumn');
    
    // Inputs
    const objInputs = ['inputA', 'inputB', 'inputC', 'inputD'];
    const objRadios = ['radA', 'radB', 'radC', 'radD'];

    if (type === 'theory') {
        // Active Theory
        theoryCol.style.opacity = '1';
        theoryCol.style.pointerEvents = 'auto';
        theoryCol.style.background = '#fff7ed'; // Slight orange tint
        theoryCol.style.borderColor = '#fdba74';

        // Dim Objective
        objCol.style.opacity = '0.4';
        objCol.style.pointerEvents = 'none';
        objCol.style.background = '#f3f4f6';
        objCol.style.borderColor = '#e5e7eb';

        // Remove required from Objective
        objInputs.forEach(id => {
            const el = document.getElementById(id);
            if(el) el.required = false;
        });
        objRadios.forEach(id => {
            const el = document.getElementById(id);
            if(el) el.required = false;
        });

    } else {
        // Active Objective
        objCol.style.opacity = '1';
        objCol.style.pointerEvents = 'auto';
        objCol.style.background = 'white';
        objCol.style.borderColor = '#e5e7eb';

        // Dim Theory
        theoryCol.style.opacity = '0.4';
        theoryCol.style.pointerEvents = 'none';
        theoryCol.style.background = '#f3f4f6'; // Gray out
        theoryCol.style.borderColor = '#e5e7eb';

        // Add required to Objective
        objInputs.forEach(id => {
            const el = document.getElementById(id);
            if(el) el.required = true;
        });
        // Radios: Require at least one? HTML5 handles radio group required if one has it. 
        // We'll set it on all for safety, browser handles the group logic.
        objRadios.forEach(id => {
            const el = document.getElementById(id);
            if(el) el.required = true;
        });
    }
}
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