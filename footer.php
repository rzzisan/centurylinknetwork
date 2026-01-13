</main>
        <footer class="footer">
            <p>&copy; <?php echo date('Y'); ?> Devloped By and Design By  <a href="https://fb.me/zisanme">Zisan Ahammed</a>. All Rights Reserved.</p>
        </footer>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
});
</script>
</body>
</html>