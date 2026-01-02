<?php require '../config/db.php'; ?>
<?php include '../includes/header.php'; ?>

<div class="container" style="padding: 4rem 1rem; max-width: 800px; margin: 0 auto;">
    <div class="card">
        <h1 style="margin-bottom: 1.5rem; color: var(--text-header);">Privacy Policy</h1>
        <p class="text-muted" style="margin-bottom: 2rem;">Last Updated: <?php echo date('F d, Y'); ?></p>

        <div style="line-height: 1.7; color: var(--text-main);">
            <p style="margin-bottom: 1rem;">At <strong>Seversoft CBT</strong>, we value your privacy and are committed to protecting your personal information. This policy outlines how we handle your data during examinations.</p>

            <h3 style="margin-bottom: 1rem; margin-top: 2rem; color: var(--primary);">1. Information We Collect</h3>
            <p style="margin-bottom: 1rem;">We collect minimal information necessary to conduct examinations, including:</p>
            <ul style="margin-bottom: 1rem; padding-left: 1.5rem;">
                <li>Your Full Name / Student ID.</li>
                <li>Exam progress, answers, and scores.</li>
                <li>Session timestamps and duration.</li>
            </ul>

            <h3 style="margin-bottom: 1rem; margin-top: 2rem; color: var(--primary);">2. How We Use Your Data</h3>
            <p style="margin-bottom: 1rem;">Your data is used solely for:</p>
            <ul style="margin-bottom: 1rem; padding-left: 1.5rem;">
                <li>Authenticating and identifying students during tests.</li>
                <li>Calculating and recording exam results.</li>
                <li>Improving the functionality of the CBT platform.</li>
            </ul>

            <h3 style="margin-bottom: 1rem; margin-top: 2rem; color: var(--primary);">3. Data Security</h3>
            <p style="margin-bottom: 1rem;">We implement industry-standard security measures to protect your exam data from unauthorized access, alteration, or destruction. We do not share your personal data with third parties.</p>
        </div>

        <div style="text-align: center; margin-top: 3rem;">
            <a href="index.php" class="btn btn-outline-secondary" style="color: var(--text-main); border-color: var(--glass-border);">Back to Home</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
