<?php
// Dynamic year
$current_year = date('Y');
?>

<footer class="app-footer">
    <div class="footer-content">
        <div class="footer-section">
            <h4>Seversoft CBT App</h4>
            <p>A secure platform for computer-based testing. Empowering education with fair, efficient exams.</p>
            <div class="social-links">
                <!-- Add icons via Font Awesome if included -->
                <a href="#" aria-label="GitHub"><i class="fab fa-github"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
            </div>
        </div>
        <!-- <div class="footer-section">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="student/index.php">Student Portal</a></li>
                <li><a href="admin/login.php">Admin Login</a></li>
                <li><a href="#">Contact Us</a></li>
            </ul>
        </div> -->
        <div class="footer-section">
            <h4>Support</h4>
            <p>Email: info@seversoftech.com</p>
            <p>Phone: +2347033409667</p>
          
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo $current_year; ?> Seversoft CBT App. All rights reserved. | Built with ❤️ by Seversoft Technologies</p>
    </div>
</footer>


<script src="../assets/js/script.js"></script>

</body>
</html>