<?php include '../includes/header.php';
// include '../includes/footer.php'; 
?>


<style>
@import url('https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap');

body {
    font-family: 'Lato', Arial, Helvetica, sans-serif;
    background-image: url('assets/images/bg.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
    min-height: 100vh;
    background-color: #f8f9fa;
}


.card {
    background: rgba(255, 255, 255, 0.95); 
    backdrop-filter: blur(10px); 
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.modal-content {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(10px);
}
</style>


<div class="card">
    <h1>Welcome to Seversoft CBT</h1>
    <div id="startScreen" style="display: block;">
        <div id="categoryScreen">
            <p id="welcomeMsg">Select a subject to begin the 30-minute test.</p>
            <div id="categoryList" style="display: none;">
                <select id="categorySelect" class="form-control" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <option value="">Loading subjects...</option>
                </select>
                <button id="startBtn" class="btn" style="margin-top: 1rem; width: 100%;">Start Test</button>
            </div>
        </div>
        <div id="resumePrompt" style="display: none;">
            <p>A <strong id="resumeCategory"></strong> test is in progress. Do you want to <strong>resume</strong> from Question <span id="resumeQ"></span>?</p>
            <button id="resumeBtn" class="btn" style="margin-right: 1rem;">Resume</button>
            <button id="restartBtn" class="btn btn-danger">Restart New Test</button>
        </div>
    </div>
    <div id="testScreen" style="display: none;">
        <!-- Loaded via JS -->
    </div>
</div>

<!-- Notification Modal -->
<div id="notificationModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 id="modalTitle">Notification</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p id="modalMessage"></p>
        </div>
        <div class="modal-footer" id="modalFooter">
            <button class="btn" onclick="closeModal()">OK</button>
        </div>
    </div>
</div>

<!-- Test Info Dialog Modal -->
<div id="infoModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 id="infoTitle">Important Information </h3>
            <span class="close" onclick="closeInfoModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div id="infoMessage">
                <p><strong>Get ready to begin your exam.</strong></p>
                <ul>
    <li>
        <strong>Purpose:</strong> 
        This CBT platform is designed to help students preparing for the <strong>National Certificate Examination</strong> 
        practice effectively. It provides a simulated computer-based testing environment where learners can familiarize 
        themselves with the CBT interface and answer similar questions to those they’re likely to encounter in real examinations.
    </li>
    <li>
        <strong>Duration:</strong> 
        You have exactly <strong>30 minutes</strong> to complete the test. The timer starts immediately upon selecting 
        "Start Test" and cannot be paused.
    </li>
    <li>
        <strong>Questions:</strong> 
        Each subject contains multiple-choice questions (4 options: A, B, C, D). Select only one answer per question.
    </li>
    <li>
        <strong>Scoring:</strong> 
        1 mark per correct answer. Passing score is 50%. Results are saved automatically and cannot be retaken without restarting.
    </li>
    <li>
        <strong>Rules:</strong> 
        No external aids or collaboration allowed. The test auto-submits on time expiry. Your session can be resumed if interrupted 
        (browser must remain open).
    </li>
</ul>

                <p><em>Read carefully before proceeding. Good luck!</em></p>
            </div>
        </div>
        <div class="modal-footer">
            <button id="acknowledgeBtn" class="btn btn-primary" onclick="closeInfoModal()">I Understand - Proceed</button>
        </div>
    </div>
</div>

<?php 
include '../includes/footer.php'; 
?>


<script src="../assets/js/script.js"></script>
<script src="../assets/js/cbt-test.js"></script>
</body>
</html>