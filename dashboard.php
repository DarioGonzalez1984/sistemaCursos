<?php

require_once 'includes/config.php';
checkLogin();
// Determinar qué módulo y acción mostrar
$module = isset($_GET['module']) ? cleanInput($_GET['module']) : 'alumnos';
$action = isset($_GET['action']) ? cleanInput($_GET['action']) : 'index';

// Validar módulo
$validModules = ['alumnos', 'cursos', 'ediciones', 'inscripciones'];
if (!in_array($module, $validModules)) {
    $module = 'alumnos';
}

// Validar acción
$validActions = ['index', 'create', 'edit', 'delete', 'historial', 'cambiar_estado', 'inscripciones'];
if (!in_array($action, $validActions)) {
    $action = 'index';
}

// Construir la ruta del archivo a incluir
$file_path = "modules/{$module}/{$action}.php";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Cursos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12 p-4">
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $module == 'alumnos' ? 'active' : ''; ?>" 
                           href="?module=alumnos">Alumnos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $module == 'cursos' ? 'active' : ''; ?>" 
                           href="?module=cursos">Cursos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $module == 'ediciones' ? 'active' : ''; ?>" 
                           href="?module=ediciones">Ediciones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $module == 'inscripciones' ? 'active' : ''; ?>" 
                           href="?module=inscripciones">Inscripciones</a>
                    </li>
                </ul>

                <div class="tab-content mt-4">
                    <?php include $file_path; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>