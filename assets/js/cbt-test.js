let questions = [];
let currentQuestionIndex = 0;
let selectedCategory = '';
let studentName = localStorage.getItem('cbt_student_name') || '';

// Use injected config or default to 30 minutes
let totalTestTime = (typeof EXAM_CONFIG !== 'undefined' && EXAM_CONFIG.durationMinutes)
    ? EXAM_CONFIG.durationMinutes * 60
    : 1800;

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
document.addEventListener('DOMContentLoaded', function () {
    const notificationModal = document.getElementById('notificationModal');
    notificationModal.addEventListener('click', (e) => {
        if (e.target === notificationModal) closeModal();
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
            const resumeCat = document.getElementById('resumeCategory');
            const resumeQ = document.getElementById('resumeQ');
            if (resumeCat) resumeCat.textContent = state.category || 'General';
            if (resumeQ) resumeQ.textContent = state.current_index + 1;

            const resumePrompt = document.getElementById('resumePrompt');
            const catScreen = document.getElementById('categoryScreen');
            if (resumePrompt) resumePrompt.style.display = 'block';
            if (catScreen) catScreen.style.display = 'none';

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

        // Add Mode Selector if not exists
        const container = document.getElementById('categoryScreen');
        let modeSelectDiv = document.getElementById('modeSelectDiv');
        if (!modeSelectDiv) {
            modeSelectDiv = document.createElement('div');
            modeSelectDiv.id = 'modeSelectDiv';
            modeSelectDiv.style.marginBottom = '20px';
            modeSelectDiv.style.textAlign = 'center';
            modeSelectDiv.innerHTML = `
                <div class="form-group" style="margin-bottom: 2rem; text-align: left;">
                    <label for="modeSelect" style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: var(--text-main);">Test Mode</label>
                    <div style="position: relative;">
                        <i class="fas fa-layer-group" style="position: absolute; left: 1.2rem; top: 50%; transform: translateY(-50%); color: var(--text-light); z-index: 2;"></i>
                        <select id="modeSelect" class="modern-select w-100" style="padding-left: 3rem;">
                            <option value="all">Mixed (Objective & Theory)</option>
                            <option value="objective">Objective Only</option>
                            <option value="theory">Theory Only</option>
                        </select>
                    </div>
                </div>
            `;
            // Insert before start button
            const startBtn = document.getElementById('startBtn');
            if (startBtn) startBtn.parentNode.insertBefore(modeSelectDiv, startBtn);
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
    const studentId = document.getElementById('studentId').value.trim();
    if (!studentId) {
        showModal('Please enter your Full Name / Surname before starting.', 'warning');
        return;
    }

    const category = document.getElementById('categorySelect').value;
    if (!category) {
        showModal('Please select a subject.', 'warning');
        return;
    }
    const mode = document.getElementById('modeSelect').value;

    selectedCategory = category;
    studentName = studentId;
    localStorage.setItem('cbt_student_name', studentId);
    try {
        // Fetch questions for selected category and pass student_id and mode
        const response = await fetch(`../config/get_questions.php?category=${encodeURIComponent(category)}&restart=1&student_id=${encodeURIComponent(studentId)}&type=${encodeURIComponent(mode)}`);
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
    studentName = state.student_id || localStorage.getItem('cbt_student_name') || 'Anonymous';
    if (studentName !== 'Anonymous') localStorage.setItem('cbt_student_name', studentName);
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

    // Restore text inputs for theory
    Object.entries(state.answers || {}).forEach(([indexStr, ans]) => {
        const index = parseInt(indexStr);
        const textarea = document.querySelector(`textarea[name="q${index}"]`);
        if (textarea) textarea.value = ans;

        // Update internal state
        if (questions[index]) questions[index].saved_answer = ans;
    });
}

function displayTestScreen() {
    let html = `
        <div class="test-inner">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                  <div style="font-weight: 700; color: var(--exam-text-muted); text-transform: uppercase; letter-spacing: 0.1em; font-size: 0.9rem;">
                     <i class="fas fa-book-open"></i> ${selectedCategory}
                  </div>
                  <div id="timer" class="timer" style="color: var(--exam-text);">30:00</div>
            </div>
            
            <div class="progress" style="margin-bottom: 2rem; background: var(--glass-border); border-color: var(--glass-border);"><div class="progress-bar" id="progressBar" style="width:0%"></div></div>
    `;

    questions.forEach((q, index) => {
        // Badges
        const typeBadge = q.type === 'theory'
            ? `<span style="background: #f97316; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; vertical-align: middle; margin-left: 10px; font-weight: bold; letter-spacing: 0.05em;">ESSAY</span>`
            : `<span style="background: #4f46e5; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; vertical-align: middle; margin-left: 10px; font-weight: bold; letter-spacing: 0.05em;">OBJECTIVE</span>`;

        const imageHtml = q.image ? `<div style="text-align:center; margin-bottom:15px;"><img src="../${q.image}" alt="Question Image" style="max-height: 400px; max-width: 100%; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"></div>` : '';

        // For theory questions, show textarea; for objective, show radios
        let optionsHtml = '';
        if (q.type === 'theory') {
            optionsHtml = `
                <div class="theory-answer-area">
                    <label style="display:block; margin-bottom:10px; color:var(--exam-text-muted); font-weight: 600;">Your Answer (Essay):</label>
                    <textarea 
                        name="q${index}" 
                        rows="12" 
                        class="form-control" 
                        style="width:100%; padding:15px; border-radius:8px; border:1px solid var(--exam-option-border); background: var(--exam-card-bg); color: var(--exam-text); font-size:1rem; line-height: 1.6;"
                        placeholder="Type your answer here..."
                        oninput="saveTheoryAnswer(${index}, this.value)"
                    >${q.saved_answer || ''}</textarea>
                </div>
            `;
        } else {
            // Objective options
            ['a', 'b', 'c', 'd'].forEach(opt => {
                const labelChar = String.fromCharCode(65 + ['a', 'b', 'c', 'd'].indexOf(opt));
                const optionKey = `option_${opt}`;
                // Only show if option exists (some might be null if manually edited db)
                if (q[optionKey]) {
                    optionsHtml += `
                        <label class="option-label" style="display: flex; align-items: start; padding: 1rem; margin-bottom: 0.5rem; background: var(--exam-option-bg); border: 1px solid var(--exam-option-border); border-radius: 8px; cursor: pointer; transition: all 0.2s;">
                            <input type="radio" name="q${index}" value="${opt}" ${q.saved_answer === opt ? 'checked' : ''} style="margin-top: 4px;">
                            <span class="option-marker" style="margin-left: 10px; font-weight: bold; color: var(--exam-text-muted); min-width: 20px;">${labelChar}.</span>
                            <span class="option-text" style="color: var(--exam-text); line-height: 1.4;">${q[optionKey]}</span>
                        </label>
                    `;
                }
            });
        }

        html += `
            <div class="question" style="display: ${index === currentQuestionIndex ? 'block' : 'none'}; animation: activeCard 0.4s ease-out;">
                <div class="question-grid">
                    <!-- Left Col: Question -->
                    <div class="q-content" style="background: var(--exam-card-bg); padding: 1.5rem; border-radius: 12px; color: var(--exam-text);">
                        <div style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem; line-height: 1.6; color: var(--exam-text);">
                            <span style="color: var(--exam-text-muted); margin-right: 0.5rem;">Q${index + 1}.</span>
                            ${typeBadge}
                        </div>
                        <div style="font-size: 1.25rem; line-height: 1.6; margin-bottom: 1.5rem;">
                            ${imageHtml}
                            <div class="q-text-body">${q.question}</div> 
                        </div>
                    </div>

                    <!-- Right Col: Answer -->
                    <div class="q-answer-area">
                         ${optionsHtml}
                    </div>
                </div>
            </div>
        `;
    });

    html += `
            <div class="navigation" style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--glass-border);">
                <div style="margin-bottom: 1.5rem; color: var(--exam-text-muted); font-weight: 600; text-align: center;">
                    Question <span id="currentQ">1</span> of <span id="totalQ">${questions.length}</span>
                </div>
                <div class="d-flex justify-content-center gap-3 w-100">
                    <button type="button" id="prevBtn" class="btn btn-outline-secondary" onclick="prevQuestion()" style="display: none; flex: 1; max-width: 200px; color: var(--exam-text); border-color: var(--exam-option-border);">
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <button type="button" id="nextBtn" class="btn btn-primary" onclick="nextQuestion()" style="flex: 1; max-width: 200px;">
                        Next <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="button" id="submitBtn" class="btn btn-success" onclick="submitTest()" style="display: none; flex: 1; max-width: 200px;">
                        Submit Examination <i class="fas fa-check-circle"></i>
                    </button>
                </div>
                <div style="text-align: center;">
                    <button type="button" id="exitBtn" class="btn btn-danger" onclick="exitToCategories()" style="margin-top: 2rem; background: transparent; border: 1px solid var(--danger); color: var(--danger); font-size: 0.8rem; padding: 0.5rem 1rem;">
                        Exit Test
                    </button>
                </div>
            </div>
            </form>
        </div>
        
        <style>
            .question-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 3rem;
                align-items: start;
            }
            @media (max-width: 768px) {
                .question-grid {
                    grid-template-columns: 1fr;
                    gap: 1.5rem;
                }
            }
            .q-answer-area {
                 padding: 0.5rem;
            }
            .option-label:hover {
                background: var(--exam-option-hover) !important;
                border-color: var(--primary) !important;
            }
            input[type="radio"]:checked + .option-marker {
                color: var(--primary) !important;
            }
        </style>
    `;
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
    // Capture Radios
    document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
        const nameMatch = radio.name.match(/q(\d+)/);
        if (nameMatch) answers[nameMatch[1]] = radio.value;
    });
    // Capture Textareas (Theory)
    document.querySelectorAll('textarea[name^="q"]').forEach(textarea => {
        const nameMatch = textarea.name.match(/q(\d+)/);
        if (nameMatch && textarea.value.trim() !== '') {
            answers[nameMatch[1]] = textarea.value;
        }
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
                category: selectedCategory,
                student_id: studentName
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
                // Update local model
                questions[currentQuestionIndex].saved_answer = e.target.value;
                updateProgress();
                await saveState();
            }
        });
    }
}

// New helper for theory
async function saveTheoryAnswer(index, value) {
    questions[index].saved_answer = value;
    // Debounce saveState if needed, but for now simple call is okay or maybe too frequent
    // To avoid spamming, we might want to just update local state and save on nav
    // But let's trigger save occasionally or on blur? 
    // For now, let's just update local 'questions' array. saveState uses the DOM or 'questions' array?
    // saveState currently reads from DOM inputs. Let's update it to read from 'questions' array or keep DOM source.
    // Actually, saveState reads DOM. So we don't strictly need to update questions[].saved_answer for saveState to work if we are looking at DOM.
    // BUT, we need it for persistence if we re-render or resume.

    // Let's rely on saveState() reading the textarea value.
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
            localStorage.removeItem('cbt_student_name');
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

// Timer variables
let timerInterval = null;

function startTimer(startTime, duration) {
    if (timerInterval) clearInterval(timerInterval);

    // Calculate end time based on start time
    // We update totalTestTime to reflect current remaining
    const now = Date.now() / 1000;
    const elapsed = now - startTime;
    totalTestTime = Math.max(0, duration - elapsed);

    updateTimer(); // Initial call
    timerInterval = setInterval(updateTimer, 1000);
}

function updateTimer() {
    if (totalTestTime <= 0) {
        clearInterval(timerInterval);
        timerInterval = null;
        showModal('Time is up! Your test handles will be submitted automatically.', 'info', 'Time Up', false, () => {
            submitTest(true);
        });
        // Force submit after closure if not handled
        submitTest(true);
        return;
    }

    const minutes = Math.floor(totalTestTime / 60);
    const seconds = Math.floor(totalTestTime % 60);

    const timerDisplay = document.getElementById('timer');
    if (timerDisplay) {
        timerDisplay.textContent =
            `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        // Visual warning for last 5 minutes (300 seconds)
        if (totalTestTime <= 300) {
            timerDisplay.style.color = '#ef4444';
            timerDisplay.style.animation = 'pulse 1s infinite';
        } else {
            timerDisplay.style.color = 'white';
            timerDisplay.style.animation = 'none';
        }
    }

    totalTestTime--;
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
                    if (timerInterval) clearInterval(timerInterval);

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
    // Collect answers (same logic as saveState)
    const answers = {};
    document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
        const nameMatch = radio.name.match(/q(\d+)/);
        if (nameMatch) {
            answers[nameMatch[1]] = radio.value;
        }
    });
    // Capture Textareas (Theory)
    document.querySelectorAll('textarea[name^="q"]').forEach(textarea => {
        const nameMatch = textarea.name.match(/q(\d+)/);
        if (nameMatch && textarea.value.trim() !== '') {
            answers[nameMatch[1]] = textarea.value;
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

// Helper to save theory answer on input
function saveTheoryAnswer(index, value) {
    if (questions[index]) {
        questions[index].saved_answer = value;
    }
}

async function performSubmit(answers) {
    try {
        // Use FormData for POST (matches expected $_POST["q0"], etc.)
        const formData = new FormData();
        Object.entries(answers).forEach(([index, ans]) => {
            formData.append(`q${index}`, ans);
        });
        formData.append('subject', selectedCategory); // Add subject to form data
        formData.append('student_id', studentName); // Add student_id to form data
        console.log('Submitting test for student:', studentName);


        const response = await fetch('../app/results.php', {
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

        // Stop the timer
        if (timerInterval) clearInterval(timerInterval);

        // Clear local state
        questions = [];
        currentQuestionIndex = 0;

        // Redirect to results with scored params
        window.location.href = `results.php?score=${data.score}&total=${data.total}&subject=${encodeURIComponent(selectedCategory)}`;
    } catch (error) {
        console.error('Submit error:', error);
        showModal('Error submitting test: ' + error.message, 'error');
    }
}

// Init
document.addEventListener('DOMContentLoaded', checkTestState);