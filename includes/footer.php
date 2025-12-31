<?php
$current_year = date('Y');
?>
<footer class="app-footer">
    <div class="footer-content">
        <div class="footer-left">
            <span>&copy; <?php echo $current_year; ?> Seversoft CBT App</span>
            <span class="divider">|</span>
            <span>Built with <i class="fas fa-heart text-danger"></i> by Seversoft</span>
        </div>
        <div class="footer-right">
            <a href="#" aria-label="GitHub"><i class="fab fa-github"></i></a>
            <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
            <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
        </div>
    </div>
</footer>

<!-- Theme Toggle Button -->
<button class="theme-toggle" id="themeToggle" aria-label="Toggle Dark Mode">
    <i class="fas fa-moon"></i>
</button>

<script>
    const toggleBtn = document.getElementById('themeToggle');
    const html = document.documentElement;
    const icon = toggleBtn.querySelector('i');

    // Set initial icon based on current theme
    if (html.getAttribute('data-theme') === 'dark') {
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
    }

    toggleBtn.addEventListener('click', () => {
        const currentTheme = html.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        // Toggle Icon
        if (newTheme === 'dark') {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        } else {
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        }
    });
</script>

<script src="../assets/js/script.js"></script>
</body>
</html>