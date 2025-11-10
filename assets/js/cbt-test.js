let questions = [];
let currentQuestionIndex = 0;
let selectedCategory = '';

let totalTestTime = 1800; 

let confirmationCallback = null; 

// Modal functions
function showModal(message, type = 'info', title = 'Notification', isConfirm = false, onConfirm = null) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalMessage').textContent = message;
    const modal = document.getElementById('notificationModal');
    const header = document.querySelector('.modal-header');
    const footer = document.getElementById('modalFooter');
    header.className = 'modal-header'; // Reset
    if (type === 'error') {
        header.style.borderBottomColor = '#ef4444';
        header.style.backgroundColor = 'rgba(239, 68, 68, 0.1)';
    } else if (type === 'warning') {
        header.style.borderBottomColor = '#f59e0b';
        header.style.backgroundColor = 'rgba(245, 158, 11, 0.1)';
    } else if (type === 'success') {
        header.style.borderBottomColor = '#10b981';
        header.style.backgroundColor = 'rgba(16, 185, 129, 0.1)';
    }
    // Handle footer for confirmation
    if (isConfirm) {
        footer.innerHTML = `
            <button class="btn btn-danger" id="confirmYesBtn" style="margin-right: 0.5rem;">Yes</button>
            <button class="btn" id="confirmNoBtn">No</button>
        `;
        document.getElementById('confirmYesBtn').addEventListener('click', () => {
            closeModal();
            if (onConfirm) onConfirm(true);
        });
        document.getElementById('confirmNoBtn').addEventListener('click', () => {
            closeModal();
            if (onConfirm) onConfirm(false);
        });
        confirmationCallback = onConfirm;
    } else {
        footer.innerHTML = '<button class="btn" onclick="closeModal()">OK</button>';
    }
    modal.style.display = 'flex';

    modal.addEventListener('click', (e) => {
        if (e.target === modal) e.stopPropagation();
    });
    
}

function closeModal(confirmed = false) {
    document.getElementById('notificationModal').style.display = 'none';
    if (confirmationCallback && !confirmed) {
        confirmationCallback(false);
    }
    confirmationCallback = null;
}

// Info Modal functions
function showInfoModal() {
    const modal = document.getElementById('infoModal');
    modal.style.display = 'flex';
    // Optional: Store flag to prevent re-showing (e.g., sessionStorage)
    sessionStorage.setItem('infoShown', 'true');
    //prevent outside click close
    modal.addEventListener('click', (e) => {
        if (e.target === modal) e.stopPropagation();
    });
}

function closeInfoModal() {
    document.getElementById('infoModal').style.display = 'none';

    const modal = document.getElementById('infoModal');
    modal.removeEventListener('click', (e) => {
        if (e.target === modal) e.stopPropagation();
    });
}

// Close modals on outside click
document.addEventListener('DOMContentLoaded', function() {
    const notificationModal = document.getElementById('notificationModal');
    notificationModal.addEventListener('click', (e) => {
        if (e.target === notificationModal) closeModal();
    });

    const infoModal = document.getElementById('infoModal');
    infoModal.addEventListener('click', (e) => {
        if (e.target === infoModal) closeInfoModal();
    });
});

// Check state on load
async function checkTestState() {
    try {
        const response = await fetch('../config/get_test_state.php');
        if (!response.ok) throw new Error('Failed to fetch state: ' + response.status);
        const state = await response.json();
        if (state.active) {
            if (state.expired) {
                showModal('Test session expired. Starting a new test.', 'warning');
                await clearTestSession();
                loadCategories();
                return;
            }
            // Show resume prompt with category
            document.getElementById('resumeCategory').textContent = state.category || 'General';
            document.getElementById('resumeQ').textContent = state.current_index + 1;
            document.getElementById('resumePrompt').style.display = 'block';
            document.getElementById('categoryScreen').style.display = 'none';

            // Resume button
            document.getElementById('resumeBtn').addEventListener('click', () => {
                resumeTest(state);
            });
            // Restart button - go back to category selection
            document.getElementById('restartBtn').addEventListener('click', async () => {
                console.log('Restart clicked - clearing session...');
                const cleared = await clearTestSession();
                if (cleared) {
                    console.log('Session cleared successfully - reloading to category screen');
                    loadCategories(); // Show categories
                } else {
                    showModal('Failed to clear session. Reloading page...', 'error');
                    window.location.reload(); // Fallback
                }
            });
        } else {
            // No active test - load categories
            loadCategories();
        }
    } catch (error) {
        console.error('State check error:', error);
        showModal('Error checking test state: ' + error.message, 'error');
        loadCategories();
    }
}

async function loadCategories() {
    try {
        const response = await fetch('../config/get_categories.php');
        if (!response.ok) throw new Error('Failed to load categories: ' + response.status);
        const text = await response.text();
        if (!text.trim()) throw new Error('Empty response from server');
        const categories = JSON.parse(text);
        if (!Array.isArray(categories)) throw new Error('Invalid categories format');
        const select = document.getElementById('categorySelect');
        select.innerHTML = '<option value="">Select a subject</option>';
        categories.forEach(cat => {
            if (cat) { // Skip emptys
                const option = document.createElement('option');
                option.value = cat;
                option.textContent = cat;
                select.appendChild(option);
            }
        });
        if (select.options.length <= 1) {
            select.innerHTML = '<option value="">No subjects available</option>';
        }
        document.getElementById('categoryScreen').style.display = 'block'; 
        document.getElementById('categoryList').style.display = 'block';
        document.getElementById('resumePrompt').style.display = 'none';
        document.getElementById('startBtn').addEventListener('click', startNewTest);

        if (!sessionStorage.getItem('infoShown')) {
            showInfoModal();
        }
    } catch (error) {
        console.error('Load categories error:', error);
        showModal('Error loading subjects: ' + error.message + '\n\nCheck console for details.', 'error');
    }
}

async function startNewTest() {
    const category = document.getElementById('categorySelect').value;
    if (!category) {
        showModal('Please select a subject.', 'warning');
        return;
    }
    selectedCategory = category;
    try {
        // Fetch questions for selected category
        const response = await fetch(`../config/get_questions.php?category=${encodeURIComponent(category)}&restart=1`);
        if (!response.ok) throw new Error('Failed to start test: ' + response.status);
        const text = await response.text();
        if (!text.trim()) throw new Error('Empty response from server');
        const fetchedQuestions = JSON.parse(text);

        if (!Array.isArray(fetchedQuestions) || fetchedQuestions.length === 0) {
            throw new Error(`No questions available for ${category}. Please add some questions first.`);
        }

        questions = fetchedQuestions;
        currentQuestionIndex = 0;
        testStartTime = Date.now() / 1000;
        originalTimeLeft = totalTestTime;
        displayTestScreen();
    } catch (error) {
        console.error('Start test error:', error);
        showModal('Error starting test: ' + error.message, 'error');
    }
}

async function resumeTest(state) {
    selectedCategory = state.category || 'General';
    questions = state.questions;
    currentQuestionIndex = state.current_index;
    testStartTime = state.start_time;
    originalTimeLeft = totalTestTime;
    displayTestScreen();
    // Restore answers AFTER display
    Object.entries(state.answers || {}).forEach(([indexStr, ans]) => {
        const index = parseInt(indexStr);
        const radio = document.querySelector(`input[name="q${index}"][value="${ans}"]`);
        if (radio) radio.checked = true;
    });
    updateProgress();
}

function displayTestScreen() {
    let html = `
        <div style="text-align: center; margin-bottom: 1rem; font-weight: 500; color: #6366f1;">
            Subject: ${selectedCategory}
        </div>
        <form id="testForm">
            <div class="progress"><div class="progress-bar" id="progressBar" style="width:0%"></div></div>
            <div id="timer" class="timer">30:00</div>
    `;
    questions.forEach((q, index) => {
        html += `<div class="question" style="display: ${index === currentQuestionIndex ? 'block' : 'none'};"><h3>${index+1}. ${q.question}</h3>`;
        ['a', 'b', 'c', 'd'].forEach(opt => {
            const label = String.fromCharCode(65 + ['a', 'b', 'c', 'd'].indexOf(opt));
            const optionKey = `option_${opt}`;
            html += `<label><input type="radio" name="q${index}" value="${opt}"> ${label}. ${q[optionKey]} </label><br>`;
        });
        html += '</div>';
    });
    html += `
        <div class="navigation text-center mt-4">
            <div class="mb-3">
                <span id="questionIndicator" class="d-block d-sm-inline fw-semibold text-muted">Question <span id="currentQ">1</span> of <span id="totalQ">${questions.length}</span></span>
            </div>
            <div class="d-flex justify-content-center flex-wrap gap-2 mb-3">
                <button type="button" id="prevBtn" class="btn btn-outline-secondary" onclick="prevQuestion()" style="display: none; min-width: 120px; flex: 1 1 45%;" disabled>Previous</button>
                <button type="button" id="nextBtn" class="btn btn-primary" onclick="nextQuestion()" style="min-width: 120px; flex: 1 1 45%;">Next</button>
                <button type="button" id="submitBtn" class="btn btn-success" onclick="submitTest()" style="display: none; min-width: 120px; flex: 1 1 45%;">Submit</button>
            </div>
            <p>
            <div class="w-100">
                <button type="button" id="exitBtn" class="btn btn-warning w-100" onclick="exitToCategories()">Change Subject</button>
            </div>
        </div>
        </form>`;
    document.getElementById('testScreen').innerHTML = html;
    document.getElementById('startScreen').style.display = 'none';
    document.getElementById('testScreen').style.display = 'block';
    startTimer(testStartTime, totalTestTime); 
    updateProgress();
    attachRadioListeners();
    updateNavigation();
    updateQuestionIndicator();
}

async function saveState() {
    const answers = {};
    document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
        const nameMatch = radio.name.match(/q(\d+)/);
        if (nameMatch) answers[nameMatch[1]] = radio.value;
    });
    try {
        const response = await fetch('../config/update_test_state.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                answers,
                current_index: currentQuestionIndex,
                category: selectedCategory
            })
        });
        if (!response.ok) throw new Error('Save failed: ' + response.status);
    } catch (error) {
        console.error('Save state error:', error);
    }
}

// Navigation functions remain the same
function nextQuestion() {
    if (currentQuestionIndex < questions.length - 1) {
        hideCurrentQuestion();
        currentQuestionIndex++;
        showCurrentQuestion();
        updateNavigation();
        updateQuestionIndicator();
        saveState();
    }
}

function prevQuestion() {
    if (currentQuestionIndex > 0) {
        hideCurrentQuestion();
        currentQuestionIndex--;
        showCurrentQuestion();
        updateNavigation();
        updateQuestionIndicator();
        saveState();
    }
}

function hideCurrentQuestion() {
    const questionsDivs = document.querySelectorAll('.question');
    if (questionsDivs[currentQuestionIndex]) questionsDivs[currentQuestionIndex].style.display = 'none';
}

function showCurrentQuestion() {
    const questionsDivs = document.querySelectorAll('.question');
    if (questionsDivs[currentQuestionIndex]) questionsDivs[currentQuestionIndex].style.display = 'block';
}

function updateNavigation() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    if (!prevBtn || !nextBtn || !submitBtn) return;

    if (currentQuestionIndex === 0) {
        prevBtn.style.display = 'none';
        prevBtn.disabled = true;
    } else {
        prevBtn.style.display = 'inline-block';
        prevBtn.disabled = false;
    }

    if (currentQuestionIndex === questions.length - 1) {
        nextBtn.style.display = 'none';
        submitBtn.style.display = 'inline-block';
    } else {
        nextBtn.style.display = 'inline-block';
        submitBtn.style.display = 'none';
    }
}

function updateQuestionIndicator() {
    const currentQEl = document.getElementById('currentQ');
    const totalQEl = document.getElementById('totalQ');
    if (currentQEl) currentQEl.textContent = currentQuestionIndex + 1;
    if (totalQEl) totalQEl.textContent = questions.length;
}

function updateProgress() {
    const total = questions.length;
    const answered = document.querySelectorAll('input[type="radio"]:checked').length;
    const progress = total > 0 ? (answered / total) * 100 : 0;
    const progressBar = document.getElementById('progressBar');
    if (progressBar) progressBar.style.width = progress + '%';
}

function attachRadioListeners() {
    const testScreen = document.getElementById('testScreen');
    if (testScreen) {
        testScreen.addEventListener('change', async (e) => {
            if (e.target && e.target.type === 'radio') {
                updateProgress();
                await saveState();
            }
        });
    }
}

async function clearTestSession() {
    try {
        console.log('Clearing session via POST...');
        const response = await fetch('../config/update_test_state.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                clear: true
            })
        });
        if (!response.ok) {
            throw new Error('Clear failed: ' + response.status);
        }
        const result = await response.json();
        if (result.cleared) {
            console.log('Session cleared successfully');
            return true;
        } else {
            console.warn('Clear response missing "cleared" flag:', result);
            return false;
        }
    } catch (error) {
        console.error('Clear session error:', error);
        return false;
    }
}

async function exitToCategories() {
    showModal(
        `Are you sure you want to exit? Your progress on "${selectedCategory}" will be lost, and you'll return to subject selection.`,
        'warning',
        'Confirm Exit',
        true, // isConfirm
        async (confirmed) => {
            if (confirmed) {
                try {
                    // Stop the timer if active
                    if (typeof stopTimer === 'function') {
                        stopTimer();
                    }
                    // Clear the test session
                    const cleared = await clearTestSession();
                    if (cleared) {
                        // Reset local state
                        questions = [];
                        currentQuestionIndex = 0;
                        selectedCategory = '';
                        // Switch screens
                        document.getElementById('testScreen').style.display = 'none';
                        document.getElementById('startScreen').style.display = 'block';
                        document.getElementById('categoryScreen').style.display = 'block';
                        document.getElementById('categoryList').style.display = 'block';
                        document.getElementById('resumePrompt').style.display = 'none';
                        // Reload categories to ensure fresh list
                        await loadCategories();
                        showModal('Returned to subject selection. Choose a different subject to start.', 'success');
                    } else {
                        showModal('Failed to clear session. Please refresh the page.', 'error');
                    }
                } catch (error) {
                    console.error('Exit error:', error);
                    showModal('Error exiting test: ' + error.message, 'error');
                }
            }
        }
    );
}

async function submitTest() {
    // Collect answers (same logic as saveState)
    const answers = {};
    document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
        const nameMatch = radio.name.match(/q(\d+)/);
        if (nameMatch) {
            answers[nameMatch[1]] = radio.value;
        }
    });

    // Optional: Warn if not all questions answered
    const totalAnswered = Object.keys(answers).length;
    if (totalAnswered < questions.length) {
        showModal(
            `You have answered ${totalAnswered} out of ${questions.length} questions. Submit anyway?`,
            'warning',
            'Confirm Submission',
            true,
            async (confirmed) => {
                if (confirmed) {
                    await performSubmit(answers);
                }
            }
        );
        return;
    }

    // If all answered, submit directly
    await performSubmit(answers);
}

async function performSubmit(answers) {
    try {
        // Use FormData for POST (matches expected $_POST["q0"], etc.)
        const formData = new FormData();
        Object.entries(answers).forEach(([index, ans]) => {
            formData.append(`q${index}`, ans);
        });
        formData.append('subject', selectedCategory); // Add subject to form data

        const response = await fetch('results.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`Submit failed: ${response.status}`);
        }

        const data = await response.json();
        if (data.error) {
            throw new Error(data.error);
        }

        // Stop the timer (assumes stopTimer() from script.js)
        if (typeof stopTimer === 'function') {
            stopTimer();
        }

        // Clear local state
        questions = [];
        currentQuestionIndex = 0;

        // Redirect to results with scored params
        window.location.href = `results.php?score=${data.score}&total=${data.total}`;
    } catch (error) {
        console.error('Submit error:', error);
        showModal('Error submitting test: ' + error.message, 'error');
    }
}

// Init
document.addEventListener('DOMContentLoaded', checkTestState);