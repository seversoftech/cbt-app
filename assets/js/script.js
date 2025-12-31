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

// submission handled in cbt-test.js

document.addEventListener('DOMContentLoaded', () => {
});
