<?php include '../includes/header.php'; ?>


<div class="card">
    <h1>Welcome to CBT Test</h1>
    <div id="startScreen" style="display: block;">
        <p id="welcomeMsg">Click Start to begin the 30-minute test.</p>
        <button id="startBtn" class="btn">Start Test</button>
        <div id="resumePrompt" style="display: none;">
            <p>A test is in progress. Do you want to <strong>resume</strong> from Question <span id="resumeQ"></span>?</p>
            <button id="resumeBtn" class="btn" style="margin-right: 1rem;">Resume</button>
            <button id="restartBtn" class="btn btn-danger">Restart New Test</button>
        </div>
    </div>
    <div id="testScreen" style="display: none;">
        <!-- Loaded via JS -->
    </div>
</div>
<script src="../assets/js/script.js"></script>
<script>
    let questions = [];
    let currentQuestionIndex = 0;

    let totalTestTime = 1800; // 30min


    // Check state on load
    async function checkTestState() {
        try {
            const response = await fetch('../config/get_test_state.php');
            const state = await response.json();
            if (state.active) {
                if (state.expired) {
                    alert('Test session expired. Starting a new test.');
                    await clearTestSession();
                    startNewTest();
                    return;
                }
                // Show resume prompt
                document.getElementById('resumeQ').textContent = state.current_index + 1;
                document.getElementById('resumePrompt').style.display = 'block';
                document.getElementById('startBtn').style.display = 'none';
                document.getElementById('welcomeMsg').style.display = 'none';

                // Resume button
                document.getElementById('resumeBtn').addEventListener('click', () => {
                    resumeTest(state);
                });
                // Restart button
                document.getElementById('restartBtn').addEventListener('click', async () => {
                    await clearTestSession();
                    startNewTest();
                });
            } else {
                // No active test
                document.getElementById('startBtn').addEventListener('click', startNewTest);
            }
        } catch (error) {
            console.error('State check error:', error);
            alert('Error checking test state: ' + error.message);
            document.getElementById('startBtn').addEventListener('click', startNewTest);
        }
    }

    async function startNewTest() {
        try {
            // Clear via param (reliable)
            const response = await fetch('../config/get_questions.php?restart=1');
            if (!response.ok) throw new Error('Failed to start test');
            const fetchedQuestions = await response.json();

            // Validation (unchanged)
            if (!Array.isArray(fetchedQuestions) || fetchedQuestions.length === 0) {
                throw new Error('Invalid questions data received. Please try again.');
            }

            questions = fetchedQuestions;
            currentQuestionIndex = 0;
            testStartTime = Date.now() / 1000;
            originalTimeLeft = totalTestTime;
            displayTestScreen();
        } catch (error) {
            console.error('Start test error:', error);
            alert('Error starting test: ' + error.message);
        }
    }

    async function resumeTest(state) {
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
        let html = '<form id="testForm"><div class="progress"><div class="progress-bar" id="progressBar" style="width:0%"></div></div><div id="timer" class="timer">30:00</div>';
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
    <div class="navigation" style="text-align: center; margin: 2rem 0;">
        <span id="questionIndicator" style="margin-right: 1rem; font-weight: 500;">Question <span id="currentQ">1</span> of <span id="totalQ">${questions.length}</span></span>
        <button type="button" id="prevBtn" class="btn" onclick="prevQuestion()" style="display: none; margin-right: 1rem;" disabled>Previous</button>
        <button type="button" id="nextBtn" class="btn" onclick="nextQuestion()" style="margin-right: 1rem;">Next</button>
        <button type="button" id="submitBtn" class="btn" onclick="submitTest()" style="display: none;">Submit</button>
    </div>
    </form>`;
        document.getElementById('testScreen').innerHTML = html;
        document.getElementById('startScreen').style.display = 'none';
        document.getElementById('testScreen').style.display = 'block';
        startTimer(testStartTime, totalTestTime); // Use from script.js
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
            await fetch('../config/update_test_state.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    answers,
                    current_index: currentQuestionIndex
                })
            });
        } catch (error) {
            console.error('Save state error:', error);
        }
    }

    // Navigation
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
            await fetch('../config/update_test_state.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    clear: true
                })
            });
        } catch (error) {
            console.error('Clear session error:', error);
        }
    }

    // Init
    document.addEventListener('DOMContentLoaded', checkTestState);
</script>
</body>

</html>