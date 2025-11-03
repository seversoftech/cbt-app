// assets/js/script.js - Full Timer Implementation
let timeLeft = 30 * 60; // Default 30min
let timer;
let testStartTime; // For resume
let originalTimeLeft = 1800; // For resume calc

// Prevent back button (original, safe)
window.history.pushState(null, null, window.location.href);
window.onpopstate = () => window.history.go(1);

// Full Timer Functions
function startTimer(startTime = null, totalTime = 1800) {
    testStartTime = startTime || Date.now() / 1000;
    originalTimeLeft = totalTime;
    const timerEl = document.getElementById('timer');
    if (!timerEl) {
        console.warn('Timer element not found; skipping start.');
        return;
    }

    // Calculate initial time left
    const now = Date.now() / 1000;
    const elapsed = Math.floor(now - testStartTime);
    timeLeft = Math.max(0, originalTimeLeft - elapsed);

    updateTimerDisplay();
    timer = setInterval(() => {
        timeLeft--;
        updateTimerDisplay();
        if (timeLeft <= 0) {
            stopTimer();
            submitTest(); // Auto-submit on expiry
        }
        // Urgency color
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

// Submit - Safe with checks
async function submitTest() {
    stopTimer();
    const form = document.getElementById('testForm');
    if (!form) {
        alert('Test form not found. Please restart.');
        return;
    }
    const formData = new FormData(form);
    try {
        const response = await fetch('../student/results.php', { method: 'POST', body: formData });
        const data = await response.json();
        window.location.href = `results.php?score=${data.score}&total=${data.total}`;
    } catch (error) {
        alert('Error submitting: ' + error.message);
    }
}

// No auto-start; inline handles
document.addEventListener('DOMContentLoaded', () => {
    // Only non-conflicting init here
});