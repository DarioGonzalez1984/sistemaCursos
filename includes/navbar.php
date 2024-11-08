<?php
// Verificar que el usuario está logueado y que tenemos la información
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header('Location: index.php');
    exit;
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
    <div class="container-fluid">
        <!-- Logo/Nombre del Sistema -->
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-book me-2"></i>
            Sistema de Gestión de Cursos
        </a>

        <!-- Botón para móvil -->
        <button class="navbar-toggler" 
                type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#navbarContent" 
                aria-controls="navbarContent" 
                aria-expanded="false" 
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Contenido del navbar -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- Menú principal a la izquierda -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="?module=alumnos">
                        <i class="bi bi-people"></i> Alumnos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="?module=cursos">
                        <i class="bi bi-journals"></i> Cursos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="?module=ediciones">
                        <i class="bi bi-calendar-event"></i> Ediciones
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="?module=inscripciones">
                        <i class="bi bi-pencil-square"></i> Inscripciones
                    </a>
                </li>
            </ul>

            <!-- Menú de usuario a la derecha -->
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" 
                       href="#" 
                       id="navbarDropdown" 
                       role="button" 
                       data-bs-toggle="dropdown" 
                       aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li class="dropdown-item text-muted">
                            <small>Sesión iniciada como</small><br>
                            <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Agregar algo de espacio después del navbar -->
<div class="mb-4"></div>