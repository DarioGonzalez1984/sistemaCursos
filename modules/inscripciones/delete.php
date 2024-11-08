<?php
if (!defined('DB_HOST')) {
    exit('Acceso directo al script no permitido.');
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'ID de inscripción no especificado';
    header('Location: ?module=inscripciones');
    exit;
}

$id = cleanInput($_GET['id']);

try {
    // Obtener datos de la inscripción
    $stmt = $conn->prepare("
        SELECT i.*, 
               a.nombre as alumno_nombre, a.apellido,
               c.nombre as curso_nombre,
               e.fecha_inicio, e.fecha_fin
        FROM inscripciones i
        JOIN alumnos a ON i.alumno_id = a.id
        JOIN ediciones e ON i.edicion_id = e.id
        JOIN cursos c ON e.curso_id = c.id
        WHERE i.id = ?
    ");
    $stmt->execute([$id]);
    $inscripcion = $stmt->fetch();

    if (!$inscripcion) {
        $_SESSION['error'] = 'Inscripción no encontrada';
        header('Location: ?module=inscripciones');
        exit;
    }

    // Verificar que la inscripción esté en estado 'inscrito'
    if ($inscripcion['estado'] !== 'inscrito') {
        $_SESSION['error'] = 'Solo se pueden eliminar inscripciones en estado inicial';
        header('Location: ?module=inscripciones');
        exit;
    }

    // Si se confirmó la eliminación
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar'])) {
        $stmt = $conn->prepare("DELETE FROM inscripciones WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['message'] = 'Inscripción eliminada exitosamente';
        header('Location: ?module=inscripciones');
        exit;
    }

} catch(PDOException $e) {
    $_SESSION['error'] = 'Error al procesar la solicitud';
    header('Location: ?module=inscripciones');
    exit;
}
?>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Confirmar Eliminación</h5>
            <a href="?module=inscripciones" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-danger">
            <h5>¿Está seguro que desea eliminar esta inscripción?</h5>
            <p>Esta acción no se puede deshacer.</p>
            
            <dl class="row mt-3">
                <dt class="col-sm-3">Alumno:</dt>
                <dd class="col-sm-9">
                    <?php echo htmlspecialchars($inscripcion['apellido'] . ', ' . $inscripcion['alumno_nombre']); ?>
                </dd>
                
                <dt class="col-sm-3">Curso:</dt>
                <dd class="col-sm-9">
                    <?php echo htmlspecialchars($inscripcion['curso_nombre']); ?>
                </dd>

                <dt class="col-sm-3">Período:</dt>
                <dd class="col-sm-9">
                    <?php 
                    echo date('d/m/Y', strtotime($inscripcion['fecha_inicio'])) . ' al ' .
                         date('d/m/Y', strtotime($inscripcion['fecha_fin']));
                    ?>
                </dd>

                <dt class="col-sm-3">Fecha Inscripción:</dt>
                <dd class="col-sm-9">
                    <?php echo date('d/m/Y', strtotime($inscripcion['fecha_inscripcion'])); ?>
                </dd>
            </dl>

            <form method="POST" class="mt-4">
                <input type="hidden" name="confirmar" value="1">
                <div class="d-flex gap-2">
                    <a href="?module=inscripciones" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Confirmar Eliminación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>