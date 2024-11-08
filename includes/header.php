<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Sistema de Gestión de Cursos</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/styles.css" rel="stylesheet">
    <link href="assets/css/loader.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <style>
    /* Estilos para el loader */
    .loader-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.9);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        transition: opacity 0.3s;
    }

    .loader {
        width: 48px;
        height: 48px;
        border: 5px solid #007bff;
        border-bottom-color: transparent;
        border-radius: 50%;
        animation: rotation 1s linear infinite;
    }

    .loader-content {
        text-align: center;
    }

    .loader-text {
        margin-top: 1rem;
        color: #007bff;
        font-size: 0.9rem;
        font-weight: 500;
    }

    @keyframes rotation {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

    /* Ocultar contenido mientras carga */
    .content-wrapper {
        opacity: 0;
        transition: opacity 0.3s;
    }
    .content-wrapper.loaded {
        opacity: 1;
    }
    /* Estilos adicionales para el loader alternativo */
.loader-icon {
    color: #007bff;
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.7;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
    </style>
</head>
<body class="loading">
    <!-- Preloader -->
    <div id="preloader">
        <div class="spinner-wrapper">
            <div class="spinner"></div>
            <div class="loading-text">Cargando...</div>
        </div>
    </div>
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php include 'navbar.php'; ?>
    <?php endif; ?>

    <!-- Contenedor para mensajes de sesión -->
    <div class="container-fluid">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>