window.history.pushState(null, null, window.location.href);
window.onpopstate = () => window.history.go(1);

function startTimer(startTime = null, totalTime = 1800) {
    testStartTime = startTime || Date.now() / 1000;
    originalTimeLeft = totalTime;
    const timerEl = document.getElementById('timer');
    if (!timerEl) {
        console.warn('Timer element not found; skipping start.');
        return;
    }

    const now = Date.now() / 1000;
    const elapsed = Math.floor(now - testStartTime);
    timeLeft = Math.max(0, originalTimeLeft - elapsed);

    updateTimerDisplay();
    timer = setInterval(() => {
        timeLeft--;
        updateTimerDisplay();
        if (timeLeft <= 0) {
            stopTimer();
            submitTest();
        }
        if (timeLeft < 300) timerEl.style.color = '#ef4444';
    }, 1000);
}

function stopTimer() {
    if (timer) {
        clearInterval(timer);
        timer = null;
    }
}

function updateTimerDisplay() {
    const timerEl = document.getElementById('timer');
    if (!timerEl) return;
    let minutes = Math.floor(timeLeft / 60);
    let seconds = timeLeft % 60;
    timerEl.innerHTML = `${minutes}:${seconds.toString().padStart(2, '0')}`;
}

async function submitTest() {
    const answers = {};
    document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
        const nameMatch = radio.name.match(/q(\d+)/);
        if (nameMatch) answers[nameMatch[1]] = radio.value;
    });

    let score = 0;
    questions.forEach((q, index) => {
        if (answers[index] === q.correct_answer) score++;
    });

    const formData = new FormData();
    formData.append('score', score);
    formData.append('total', questions.length);
    formData.append('category', selectedCategory);

    try {
        const response = await fetch('../student/results.php', { method: 'POST', body: formData });
        const data = await response.json();
        window.location.href = `../student/results.php?score=${data.score}&total=${data.total}&category=${encodeURIComponent(selectedCategory)}`;
    } catch (error) {
        console.error('Submit error:', error);
    }
}

document.addEventListener('DOMContentLoaded', () => {
});
