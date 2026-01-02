<?php require '../config/db.php'; ?>
<?php include '../includes/header.php'; ?>

<div class="container" style="padding: 4rem 1rem; max-width: 800px; margin: 0 auto;">
    <div class="card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="width: 60px; height: 60px; background: var(--primary-light); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                <i class="fas fa-headset" style="font-size: 1.75rem; color: var(--primary);"></i>
            </div>
            <h1 style="color: var(--text-header);">Support Center</h1>
            <p style="color: var(--text-light);">We are here to help you have a smooth examination experience.</p>
        </div>

        <div style="display: grid; gap: 1.5rem;">
            <!-- FAQ Section -->
            <div style="margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem; color: var(--primary);">Frequently Asked Questions</h3>
                
                <div style="margin-bottom: 1rem;">
                    <h5 style="margin-bottom: 0.5rem; font-weight: 700;">How do I start a test?</h5>
                    <p style="color: var(--text-main); font-size: 0.95rem;">Enter your full name on the landing page, select your subject from the dropdown, choose the test mode (if applicable), and click "Start Examination".</p>
                </div>

                <div style="margin-bottom: 1rem;">
                    <h5 style="margin-bottom: 0.5rem; font-weight: 700;">What happens if my internet disconnects?</h5>
                    <p style="color: var(--text-main); font-size: 0.95rem;">Do not panic. Do not close the tab. Check your connection using another device. Once reconnected, click "Submit" if you are done. If the page reloaded, the system may offer to "Resume" your previous session.</p>
                </div>

                <div style="margin-bottom: 1rem;">
                    <h5 style="margin-bottom: 0.5rem; font-weight: 700;">Can I return to a previous question?</h5>
                    <p style="color: var(--text-main); font-size: 0.95rem;">Yes, use the "Previous" and "Next" buttons at the bottom of the screen to navigate through questions before submitting.</p>
                </div>
            </div>

            <!-- Contact Section -->
            <div style="background: var(--bg-body); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--glass-border);">
                <h3 style="margin-bottom: 1rem;">Contact Us</h3>
                <p style="margin-bottom: 1rem;">If you are facing technical difficulties or have account issues:</p>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 0.5rem;"><i class="fas fa-envelope text-primary" style="width: 20px;"></i> info@seversoftech.com</li>
                    <li style="margin-bottom: 0.5rem;"><i class="fas fa-phone text-primary" style="width: 20px;"></i> +234 7033409667</li>
                    <li><i class="fas fa-map-marker-alt text-primary" style="width: 20px;"></i> Seversoft Technologies</li>
                </ul>
            </div>
        </div>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="index.php" class="btn">Back to Home</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
