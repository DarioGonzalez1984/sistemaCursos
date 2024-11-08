<?php
if (!defined('DB_HOST')) {
    exit('Acceso directo al script no permitido.');
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'ID de alumno no especificado';
    header('Location: ?module=alumnos');
    exit;
}

$id = cleanInput($_GET['id']);

try {
    // Verificar si el alumno tiene inscripciones
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM inscripciones 
        WHERE alumno_id = ?
    ");
    $stmt->execute([$id]);
    $tieneInscripciones = $stmt->fetchColumn() > 0;

    if ($tieneInscripciones) {
        $_SESSION['error'] = 'No se puede eliminar el alumno porque tiene inscripciones asociadas';
        header('Location: ?module=alumnos');
        exit;
    }

    // Si no tiene inscripciones, proceder con la eliminación
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar'])) {
        $stmt = $conn->prepare("DELETE FROM alumnos WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['message'] = 'Alumno eliminado exitosamente';
        header('Location: ?module=alumnos');
        exit;
    }

    // Obtener datos del alumno para mostrar en la confirmación
    $stmt = $conn->prepare("SELECT cedula, nombre, apellido FROM alumnos WHERE id = ?");
    $stmt->execute([$id]);
    $alumno = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$alumno) {
        $_SESSION['error'] = 'Alumno no encontrado';
        header('Location: ?module=alumnos');
        exit;
    }

} catch(PDOException $e) {
    $_SESSION['error'] = 'Error al procesar la solicitud';
    header('Location: ?module=alumnos');
    exit;
}
?>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Confirmar Eliminación</h5>
            <a href="?module=alumnos" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-danger">
            <h5>¿Está seguro que desea eliminar este alumno?</h5>
            <p>Esta acción no se puede deshacer.</p>
            
            <dl class="row mt-3">
                <dt class="col-sm-3">Cédula:</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($alumno['cedula']); ?></dd>
                
                <dt class="col-sm-3">Nombre:</dt>
                <dd class="col-sm-9">
                    <?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido']); ?>
                </dd>
            </dl>

            <form method="POST" class="mt-4">
                <input type="hidden" name="confirmar" value="1">
                <div class="d-flex gap-2">
                    <a href="?module=alumnos" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Confirmar Eliminación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>