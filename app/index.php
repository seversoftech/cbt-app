<?php require '../config/db.php'; ?>
<?php include '../includes/header.php'; ?>

<main style="position: relative;">
    <div class="container-fluid" style="padding: 2rem 5%; display: flex; align-items: center; justify-content: center; min-height: 85vh;">
        
        <!-- SPLIT LAYOUT CONTAINER (Landing Screen) -->
        <div id="landingContainer" class="split-layout-container" style="width: 100%; max-width: 1400px; position: relative;">
            
            <!-- LEFT PANEL: HERO (Visual & Instructions) -->
            <div class="hero-panel">
                <div class="hero-content">
                    <h1 style="font-size: 3.5rem; margin-bottom: 1.5rem; line-height: 1.1;">Ready to <br><span style="color: var(--secondary);">Excel?</span></h1>
                    <p style="font-size: 1.2rem; opacity: 0.9; margin-bottom: 2rem; max-width: 90%;">
                        Experience a secure, seamless, and modern examination environment with Seversoft CBT.
                    </p>
                    
                    <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 1.5rem; border-radius: 1rem; border: 1px solid rgba(255,255,255,0.2);">
                        <h4 style="color: white; margin-bottom: 1rem;"><i class="fas fa-info-circle"></i> Quick Guide</h4>
                        <ul style="list-style: none; padding: 0; font-size: 0.95rem;">
                            <li style="margin-bottom: 0.5rem;"><i class="fas fa-check text-secondary" style="margin-right: 0.5rem;"></i> Select your subject to begin.</li>
                            <li style="margin-bottom: 0.5rem;"><i class="fas fa-check text-secondary" style="margin-right: 0.5rem;"></i> Time: <strong>30 minutes</strong> per session.</li>
                            <li><i class="fas fa-check text-secondary" style="margin-right: 0.5rem;"></i> Auto-submits on time expiry.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- RIGHT PANEL: INTERACTION (Login/Selection) -->
            <div class="interaction-panel">
                <div id="startScreen" style="width: 100%; max-width: 450px; margin: 0 auto;">
                    
                    <div id="categoryScreen">
                        <div style="text-align: center; margin-bottom: 2.5rem;">
                             <div style="width: 60px; height: 60px; background: var(--primary-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                                <i class="fas fa-user-graduate" style="font-size: 1.75rem; color: var(--primary);"></i>
                             </div>
                            <h2 style="color: var(--text-header);">Student Portal</h2>
                            <p id="welcomeMsg" style="color: var(--text-light); margin-bottom: 1rem;">Select your examination subject</p>
                            <button onclick="document.getElementById('helpModal').style.display='flex'" style="background: transparent; border: 1px solid var(--primary); color: var(--primary); padding: 0.3rem 0.8rem; border-radius: 1rem; font-size: 0.8rem; cursor: pointer;">
                                <i class="fas fa-question-circle"></i> Need Help?
                            </button>
                            <a href="check_results.php" style="background: transparent; border: 1px solid var(--secondary); color: var(--secondary); padding: 0.3rem 0.8rem; border-radius: 1rem; font-size: 0.8rem; text-decoration: none; margin-left: 0.5rem; display: inline-block;">
                                <i class="fas fa-history"></i> Check Results
                            </a>
                        </div>
                        
                        <div id="categoryList" style="display: none; animation: fadeInUp 0.5s ease-out;">
                            <!-- Student ID Input -->
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: var(--text-main);">Full Name / Surname</label>
                                <div style="position: relative;">
                                    <i class="fas fa-id-card" style="position: absolute; left: 1.2rem; top: 50%; transform: translateY(-50%); color: var(--text-light); z-index: 2;"></i>
                                    <input type="text" id="studentId" placeholder="Enter your full name" style="padding-left: 3rem;" required>
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 2rem;">
                                <label for="categorySelect" style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: var(--text-main);">Course / Subject</label>
                                <div style="position: relative;">
                                    <i class="fas fa-book" style="position: absolute; left: 1.2rem; top: 50%; transform: translateY(-50%); color: var(--text-light); z-index: 2;"></i>
                                    <select id="categorySelect" class="modern-select w-100" style="padding-left: 3rem;">
                                        <option value="">Loading subjects...</option>
                                    </select>
                                </div>
                            </div>
                            <button id="startBtn" class="btn big-btn w-100">
                                Start Examination <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i>
                            </button>
                        </div>
                    </div>

                    <div id="resumePrompt" style="display: none; background: var(--primary-light); padding: 2rem; border-radius: 1rem; border: 1px solid var(--primary); text-align: center; animation: fadeInUp 0.5s;">
                        <i class="fas fa-history text-primary" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <h4 style="margin-bottom: 0.5rem;">Resume Session?</h4>
                        <p style="margin-bottom: 1.5rem; font-size: 0.95rem;">You have an active <strong><span id="resumeCategory"></span></strong> test in progress, starting at question <strong>#<span id="resumeQ">1</span></strong>.</p>
                        <div style="display: flex; gap: 1rem; justify-content: center;">
                            <button id="restartBtn" class="btn btn-danger" style="flex: 1;">Restart</button>
                            <button id="resumeBtn" class="btn" style="flex: 1;">Resume</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TEST SCREEN (Full Overlay - Direct Child of Main) -->
    <div id="testScreen">
        <!-- Loaded via JS with .test-inner wrapper -->
    </div>
</main>

<!-- Modals -->
<div id="notificationModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header"><h3 id="modalTitle">Notification</h3></div>
        <div class="modal-body"><p id="modalMessage"></p></div>
        <div class="modal-footer" id="modalFooter">
            <button class="btn" onclick="closeModal()">OK</button>
        </div>
    </div>
</div>

<div id="infoModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header"><h3>Important Information</h3></div>
        <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
             <div id="infoMessage">
                <p><strong>Please read carefully before starting.</strong></p>
                <ul>
                    <li><strong>Duration:</strong> 30 minutes. No pauses.</li>
                    <li><strong>Questions:</strong> Multiple choice. 1 mark each.</li>
                    <li><strong>Connectivity:</strong> Ensure stable internet.</li>
                    <li><strong>Integrity:</strong> No collaboration allowed.</li>
                </ul>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline-secondary" onclick="closeInfoModal()" style="color: var(--text-main); border-color: var(--glass-border);">Cancel</button>
            <button id="acknowledgeBtn" class="btn" onclick="closeInfoModal()">I'm Ready - Start</button>
        </div>
    </div>
</div>

<div id="helpModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header"><h3><i class="fas fa-life-ring text-primary"></i> Student Guide</h3></div>
        <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
             <p>Follow these steps to take your exam:</p>
             <ol style="padding-left: 1.5rem; line-height: 1.8; color: var(--text-main);">
                 <li>Enter your <strong>Full Name</strong> in the identification field.</li>
                 <li>Select your <strong>Course / Subject</strong> from the dropdown list.</li>
                 <li>Choose your <strong>Test Mode</strong> (if available): "Objective", "Theory", or "Mixed".</li>
                 <li>Click <strong>Start Examination</strong>.</li>
                 <li>Answer the questions within the allocated time.</li>
                 <li>Click <strong>Submit</strong> when you are finished.</li>
             </ol>
             <hr style="border: 0; border-top: 1px solid var(--glass-border); margin: 1rem 0;">
             <p style="font-size: 0.9rem; color: var(--text-light);">
                <strong>Note:</strong> Do not refresh the page during the test. If your internet disconnects, the system will attempt to save your progress.
             </p>
        </div>
        <div class="modal-footer">
            <button class="btn" onclick="document.getElementById('helpModal').style.display='none'">Close Guide</button>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<script>
    // Inject Server Settings
    const EXAM_CONFIG = {
        durationMinutes: <?php echo $app_settings['default_duration'] ?? 30; ?>,
        shuffleQuestions: <?php echo ($app_settings['shuffle_questions'] === 'yes') ? 'true' : 'false'; ?>
    };
</script>
<script src="../assets/js/cbt-test.js?v=1.2"></script>