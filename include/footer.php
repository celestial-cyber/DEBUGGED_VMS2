<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Sidebar toggle functionality
  const sidebar = document.getElementById('sidebar');
  const toggle = document.getElementById('toggleSidebar');
  if (toggle) {
    toggle.addEventListener('click', () => {
      sidebar.classList.toggle('show');
    });
  }

  // Form validation and other common functionality
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize any common form validation or functionality here
  });
</script>
</body>
</html>