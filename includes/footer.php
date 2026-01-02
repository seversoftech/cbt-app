<?php
$current_year = date('Y');
?>
    <footer class="app-footer">
        <div class="footer-content">
            <div class="footer-left">
                <div class="footer-brand">
                    <i class="fas fa-graduation-cap" style="color: var(--primary);"></i>
                    <span><?php echo $app_settings['institution_name'] ?? 'Seversoft CBT'; ?></span>
                </div>
                <div class="copyright">
                    &copy; <?php echo date('Y'); ?> All Rights Reserved.
                </div>
            </div>
            
            <div class="footer-center">
                <a href="../app/support.php" class="footer-link">Support</a>
                <a href="../app/privacy.php" class="footer-link">Privacy Policy</a>
                <a href="../app/terms.php" class="footer-link">Terms of Use</a>
            </div>

            <div class="footer-right">
                <a href="https://github.com/seversoftech" target="_blank" class="social-icon" aria-label="Github"><i class="fab fa-github"></i></a>
                 <a href="https://linkedin.com/in/seversoftech" target="_blank" class="social-icon" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                <a href="https://facebook.com/seversoftech" target="_blank" class="social-icon" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="https://twitter.com/seversoftech" target="_blank" class="social-icon" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
               
               
                 <a href="https://instagram.com/seversoftech" target="_blank" class="social-icon" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

<!-- Theme Toggle Button -->
<button class="theme-toggle" id="themeToggle" aria-label="Toggle Dark Mode">
    <i class="fas fa-moon"></i>
</button>

<!-- Custom Loader -->
<div id="appLoader" class="custom-loader-overlay">
    <div class="spinner"></div>
    <div class="loader-text">Processing...</div>
</div>

<script>
    // Original theme toggle logic
    const toggleBtn = document.getElementById('themeToggle');
    
    // Set initial icon based on current theme
    document.addEventListener('DOMContentLoaded', () => {
        const saved = localStorage.getItem('theme') || 'light';
        const icon = document.querySelector('.theme-toggle i');
        if(icon) icon.className = saved === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    });

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            toggleTheme();
        });
    }

    // Global Scripts
    // Toggle Theme
    function toggleTheme() {
        const html = document.documentElement;
        const current = html.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        
        // Update icon
        const icon = document.querySelector('.theme-toggle i');
        if (icon) icon.className = next === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }

    // Loader Helpers
    const AppLoader = {
        show: (text = 'Processing...') => {
            const loader = document.getElementById('appLoader');
            if (loader) {
                const textEl = loader.querySelector('.loader-text');
                if (textEl) textEl.textContent = text;
                loader.style.display = 'flex';
            }
        },
        hide: () => {
            const loader = document.getElementById('appLoader');
            if (loader) loader.style.display = 'none';
        }
    };
</script>

<script src="../assets/js/script.js"></script>
</body>
</html>