        </main>
    </div>
    
    <script>
        // Sidebar Toggle Functionality
        (function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            if (!sidebar || !sidebarToggle) return;
            
            function isDesktop() {
                return window.innerWidth > 1024;
            }
            
            function openSidebar() {
                sidebar.classList.add('active');
                sidebar.classList.remove('collapsed');
                if (sidebarOverlay) sidebarOverlay.classList.add('active');
                document.body.classList.add('sidebar-open');
            }
            
            function closeSidebar() {
                sidebar.classList.remove('active');
                if (isDesktop()) {
                    sidebar.classList.add('collapsed');
                }
                if (sidebarOverlay) sidebarOverlay.classList.remove('active');
                document.body.classList.remove('sidebar-open');
            }
            
            function toggleSidebar(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (isDesktop()) {
                    if (sidebar.classList.contains('collapsed')) {
                        sidebar.classList.remove('collapsed');
                    } else {
                        sidebar.classList.add('collapsed');
                    }
                } else {
                    if (sidebar.classList.contains('active')) {
                        closeSidebar();
                    } else {
                        openSidebar();
                    }
                }
            }
            
            sidebarToggle.addEventListener('click', toggleSidebar);
            
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeSidebar();
                });
            }
            
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (!isDesktop()) {
                        closeSidebar();
                    }
                });
            });
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeSidebar();
                }
            });
            
            window.addEventListener('resize', function() {
                if (isDesktop()) {
                    sidebar.classList.remove('active');
                    if (sidebarOverlay) sidebarOverlay.classList.remove('active');
                } else {
                    sidebar.classList.remove('collapsed');
                }
            });
        })();
    </script>
</body>
</html>
