        </main>
    </div>
    
    <script>
        // Sidebar Toggle Functionality
        (function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            if (!sidebar || !sidebarToggle) {
                console.error('Sidebar elements not found');
                return;
            }
            
            // Check if we're on desktop (window width > 1024)
            function isDesktop() {
                return window.innerWidth > 1024;
            }
            
            function openSidebar() {
                sidebar.classList.add('active');
                sidebar.classList.remove('collapsed');
                if (sidebarOverlay) sidebarOverlay.classList.add('active');
                document.body.classList.add('sidebar-open');
                console.log('Sidebar opened');
            }
            
            function closeSidebar() {
                sidebar.classList.remove('active');
                if (isDesktop()) {
                    sidebar.classList.add('collapsed');
                }
                if (sidebarOverlay) sidebarOverlay.classList.remove('active');
                document.body.classList.remove('sidebar-open');
                console.log('Sidebar closed');
            }
            
            function toggleSidebar(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Toggle clicked, isDesktop:', isDesktop());
                
                if (isDesktop()) {
                    // Desktop: toggle collapsed state
                    if (sidebar.classList.contains('collapsed')) {
                        sidebar.classList.remove('collapsed');
                    } else {
                        sidebar.classList.add('collapsed');
                    }
                } else {
                    // Mobile/Tablet: toggle active state
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
            
            // Close sidebar when clicking on a nav link (mobile/tablet only)
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!isDesktop()) {
                        closeSidebar();
                    }
                });
            });
            
            // Close on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeSidebar();
                }
            });
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (isDesktop()) {
                    // On desktop, remove active class and handle collapsed state
                    sidebar.classList.remove('active');
                    if (sidebarOverlay) sidebarOverlay.classList.remove('active');
                } else {
                    // On mobile, remove collapsed class
                    sidebar.classList.remove('collapsed');
                }
            });
        })();
    </script>
</body>
</html>
