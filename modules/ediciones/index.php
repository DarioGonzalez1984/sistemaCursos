<?php
if (!defined('DB_HOST')) {
    exit('Acceso directo al script no permitido.');
}

// Procesar eliminación
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    try {
        // Verificar si tiene inscripciones
        $stmt = $conn->prepare("SELECT COUNT(*) FROM inscripciones WHERE edicion_id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $_SESSION['error'] = 'No se puede eliminar la edición porque tiene inscripciones asociadas';
        } else {
            $stmt = $conn->prepare("DELETE FROM ediciones WHERE id = ?");
            $stmt->execute([$_POST['delete_id']]);
            $_SESSION['message'] = 'Edición eliminada con éxito';
        }
        header('Location: ?module=ediciones');
        exit;
    } catch (PDOException $e) {
        $error = 'Error al eliminar la edición';
    }
}

// Obtener lista de ediciones con información del curso
try {
    $stmt = $conn->query("
        SELECT e.*, c.nombre as curso_nombre,
               COUNT(i.id) as total_inscripciones,
               MIN(CASE WHEN i.estado = 'inscrito' THEN 1 ELSE 0 END) as tiene_inscripciones
        FROM ediciones e
        JOIN cursos c ON e.curso_id = c.id
        LEFT JOIN inscripciones i ON e.id = i.edicion_id
        GROUP BY e.id
        ORDER BY e.fecha_inicio DESC
    ");
    $ediciones = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error al obtener las ediciones';
    $ediciones = [];
}

// Función auxiliar para el estado de la edición
function getEstadoEdicion($fechaInicio, $fechaFin)
{
    $hoy = new DateTime();
    $inicio = new DateTime($fechaInicio);
    $fin = new DateTime($fechaFin);

    if ($hoy < $inicio) {
        return ['Próxima', 'info'];
    } elseif ($hoy > $fin) {
        return ['Finalizada', 'secondary'];
    } else {
        return ['En curso', 'success'];
    }
}
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Gestión de Ediciones</h5>
        <a href="?module=ediciones&action=create" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nueva Edición
        </a>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Estado</th>
                        <th>Inscripciones</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ediciones as $edicion): ?>
                        <?php
                        $estado = getEstadoEdicion($edicion['fecha_inicio'], $edicion['fecha_fin']);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($edicion['curso_nombre']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($edicion['fecha_inicio'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($edicion['fecha_fin'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $estado[1]; ?>">
                                    <?php echo $estado[0]; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php echo $edicion['total_inscripciones']; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="?module=ediciones&action=edit&id=<?php echo $edicion['id']; ?>"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="?module=ediciones&action=inscripciones&id=<?php echo $edicion['id']; ?>"
                                        class="btn btn-sm btn-outline-info"
                                        title="Ver inscripciones">
                                        <i class="bi bi-people"></i>
                                    </a>
                                    <?php if (!$edicion['tiene_inscripciones']): ?>
                                        <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="confirmDelete(<?php echo $edicion['id']; ?>)"
                                            title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de confirmación de eliminación -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro que desea eliminar esta edición?
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <input type="hidden" name="delete_id" id="deleteId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id) {
        document.getElementById('deleteId').value = id;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
</script>