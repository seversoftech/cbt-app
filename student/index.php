<?php include '../includes/header.php';
// include '../includes/footer.php'; 
?>


<div class="card">
    <h1>Welcome to CBT Test</h1>
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
        <div class="modal-footer">
            <button class="btn" onclick="closeModal()">OK</button>
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