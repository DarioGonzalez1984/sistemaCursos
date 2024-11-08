<?php
if (!defined('DB_HOST')) {
    exit('Acceso directo al script no permitido.');
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'ID de curso no especificado';
    header('Location: ?module=cursos');
    exit;
}

$id = cleanInput($_GET['id']);

try {
    // Verificar si el curso existe y obtener sus datos
    $stmt = $conn->prepare("SELECT * FROM cursos WHERE id = ?");
    $stmt->execute([$id]);
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$curso) {
        $_SESSION['error'] = 'Curso no encontrado';
        header('Location: ?module=cursos');
        exit;
    }

    // Verificar si tiene ediciones
    $stmt = $conn->prepare("
        SELECT e.*, COUNT(i.id) as total_inscripciones
        FROM ediciones e
        LEFT JOIN inscripciones i ON e.id = i.edicion_id
        WHERE e.curso_id = ?
        GROUP BY e.id
    ");
    $stmt->execute([$id]);
    $ediciones = $stmt->fetchAll();

    if (count($ediciones) > 0) {
        $_SESSION['error'] = 'No se puede eliminar el curso porque tiene ediciones asociadas';
        header('Location: ?module=cursos');
        exit;
    }

    // Si se confirmó la eliminación
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar'])) {
        $stmt = $conn->prepare("DELETE FROM cursos WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['message'] = 'Curso eliminado exitosamente';
        header('Location: ?module=cursos');
        exit;
    }

} catch(PDOException $e) {
    $_SESSION['error'] = 'Error al procesar la solicitud';
    header('Location: ?module=cursos');
    exit;
}
?>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Confirmar Eliminación</h5>
            <a href="?module=cursos" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-danger">
            <h5>¿Está seguro que desea eliminar este curso?</h5>
            <p>Esta acción no se puede deshacer.</p>
            
            <dl class="row mt-3">
                <dt class="col-sm-3">Nombre:</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($curso['nombre']); ?></dd>
                
                <?php if (!empty($curso['detalle'])): ?>
                    <dt class="col-sm-3">Detalle:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($curso['detalle']); ?></dd>
                <?php endif; ?>
            </dl>

            <form method="POST" class="mt-4">
                <input type="hidden" name="confirmar" value="1">
                <div class="d-flex gap-2">
                    <a href="?module=cursos" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Confirmar Eliminación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>