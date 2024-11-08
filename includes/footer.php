</div> <!-- Cierre del container-fluid -->
        
        <!-- Footer -->
        <footer class="footer mt-auto py-3 bg-white border-top">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12 col-md-4 text-center text-md-start">
                        <span class="text-muted">
                            © <?php echo date('Y'); ?> Sistema de Gestión de Cursos
                        </span>
                    </div>
                    <div class="col-12 col-md-4 text-center">
                        <span class="text-muted">
                            Versión 1.0
                        </span>
                    </div>
                    <div class="col-12 col-md-4 text-center text-md-end">
                        <span class="text-muted">
                            Usuario: <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>
                        </span>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Bootstrap Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Custom JavaScript -->
        <script src="assets/js/main.js"></script>

        <!-- Script para cerrar automáticamente las alertas -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto cerrar alertas después de 5 segundos
            const alerts = document.querySelectorAll('.alert:not(.alert-danger)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const closeButton = alert.querySelector('.btn-close');
                    if (closeButton) {
                        closeButton.click();
                    }
                }, 5000);
            });
        });
        document.addEventListener('DOMContentLoaded', function() {
        const loader = document.getElementById('loader');
        const contentWrapper = document.querySelector('.content-wrapper');
        
        // Función para ocultar el loader
        function hideLoader() {
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.style.display = 'none';
                contentWrapper.classList.add('loaded');
            }, 300);
        }

        // Ocultar loader cuando la página esté completamente cargada
        window.addEventListener('load', hideLoader);

        // Por si acaso, ocultar el loader después de 5 segundos
        setTimeout(hideLoader, 5000);

        // Mostrar loader en cambios de página
        document.addEventListener('click', function(e) {
            // Si el click fue en un enlace que no sea de la misma página
            if (e.target.tagName === 'A' && 
                !e.target.hasAttribute('data-bs-toggle') && // Ignorar dropdowns y modales
                !e.target.closest('.dropdown-menu') && // Ignorar items del dropdown
                e.target.getAttribute('href') && 
                !e.target.getAttribute('href').startsWith('#')) {
                loader.style.display = 'flex';
                loader.style.opacity = '1';
            }
        });

        // Mostrar loader en envío de formularios
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                if (this.checkValidity()) {
                    loader.style.display = 'flex';
                    loader.style.opacity = '1';
                }
            });
        });
    });
    </script>

    <!-- Script para cerrar automáticamente las alertas -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto cerrar alertas después de 5 segundos
            const alerts = document.querySelectorAll('.alert:not(.alert-danger)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const closeButton = alert.querySelector('.btn-close');
                    if (closeButton) {
                        closeButton.click();
                    }
                }, 5000);
            });
        });
        </script>
          <script src="assets/js/loader.js"></script>
    </body>
</html>