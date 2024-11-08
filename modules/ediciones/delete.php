<?php
if (!defined('DB_HOST')) {
    exit('Acceso directo al script no permitido.');
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'ID de edición no especificado';
    header('Location: ?module=ediciones');
    exit;
}

$id = cleanInput($_GET['id']);

try {
    // Verificar si la edición existe y obtener sus datos
    $stmt = $conn->prepare("
        SELECT e.*, c.nombre as curso_nombre 
        FROM ediciones e
        JOIN cursos c ON e.curso_id = c.id 
        WHERE e.id = ?
    ");
    $stmt->execute([$id]);
    $edicion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$edicion) {
        $_SESSION['error'] = 'Edición no encontrada';
        header('Location: ?module=ediciones');
        exit;
    }

    // Verificar si tiene inscripciones
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN estado IN ('en_curso', 'finalizado') THEN 1 ELSE 0 END) as activas
        FROM inscripciones 
        WHERE edicion_id = ?
    ");
    $stmt->execute([$id]);
    $inscripciones = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($inscripciones['activas'] > 0) {
        $_SESSION['error'] = 'No se puede eliminar la edición porque tiene inscripciones activas o finalizadas';
        header('Location: ?module=ediciones');
        exit;
    }

    // Si se confirmó la eliminación
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar'])) {
        // Primero eliminar las inscripciones pendientes si existen
        $stmt = $conn->prepare("
            DELETE FROM inscripciones 
            WHERE edicion_id = ? AND estado = 'inscrito'
        ");
        $stmt->execute([$id]);

        // Luego eliminar la edición
        $stmt = $conn->prepare("DELETE FROM ediciones WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['message'] = 'Edición eliminada exitosamente';
        header('Location: ?module=ediciones');
        exit;
    }

} catch(PDOException $e) {
    $_SESSION['error'] = 'Error al procesar la solicitud';
    header('Location: ?module=ediciones');
    exit;
}
?>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Confirmar Eliminación</h5>
            <a href="?module=ediciones" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-danger">
            <h5>¿Está seguro que desea eliminar esta edición?</h5>
            <p>Esta acción no se puede deshacer.</p>
            
            <dl class="row mt-3">
                <dt class="col-sm-3">Curso:</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($edicion['curso_nombre']); ?></dd>
                
                <dt class="col-sm-3">Fecha Inicio:</dt>
                <dd class="col-sm-9">
                    <?php echo date('d/m/Y', strtotime($edicion['fecha_inicio'])); ?>
                </dd>

                <dt class="col-sm-3">Fecha Fin:</dt>
                <dd class="col-sm-9">
                    <?php echo date('d/m/Y', strtotime($edicion['fecha_fin'])); ?>
                </dd>

                <?php if ($inscripciones['total'] > 0): ?>
                    <dt class="col-sm-3">Inscripciones pendientes:</dt>
                    <dd class="col-sm-9">
                        <?php echo $inscripciones['total'] - $inscripciones['activas']; ?>
                        <small class="text-muted">(serán eliminadas)</small>
                    </dd>
                <?php endif; ?>
            </dl>

            <form method="POST" class="mt-4">
                <input type="hidden" name="confirmar" value="1">
                <div class="d-flex gap-2">
                    <a href="?module=ediciones" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Confirmar Eliminación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>