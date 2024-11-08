<?php
if (!defined('DB_HOST')) {
    exit('Acceso directo al script no permitido.');
}

// Procesar eliminación si se recibe POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    try {
        // Verificar si el curso tiene ediciones
        $stmt = $conn->prepare("SELECT COUNT(*) FROM ediciones WHERE curso_id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $_SESSION['error'] = 'No se puede eliminar el curso porque tiene ediciones asociadas';
        } else {
            $stmt = $conn->prepare("DELETE FROM cursos WHERE id = ?");
            $stmt->execute([$_POST['delete_id']]);
            $_SESSION['message'] = 'Curso eliminado con éxito';
        }
        header('Location: ?module=cursos');
        exit;
    } catch(PDOException $e) {
        $error = 'Error al eliminar el curso';
    }
}

// Obtener lista de cursos con estadísticas
try {
    $stmt = $conn->query("
        SELECT c.*, 
               COUNT(DISTINCT e.id) as total_ediciones,
               COUNT(DISTINCT i.id) as total_inscripciones,
               MIN(e.fecha_inicio) as proxima_edicion
        FROM cursos c
        LEFT JOIN ediciones e ON c.id = e.curso_id AND e.fecha_inicio >= CURDATE()
        LEFT JOIN inscripciones i ON e.id = i.edicion_id
        GROUP BY c.id
        ORDER BY c.nombre
    ");
    $cursos = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Error al obtener los cursos';
    $cursos = [];
}
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Gestión de Cursos</h5>
        <a href="?module=cursos&action=create" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo Curso
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
                        <th>Nombre</th>
                        <th>Detalle</th>
                        <th class="text-center">Ediciones</th>
                        <th class="text-center">Inscripciones</th>
                        <th>Próxima Edición</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cursos)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No hay cursos registrados</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cursos as $curso): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($curso['nombre']); ?></td>
                                <td>
                                    <?php 
                                    echo !empty($curso['detalle']) 
                                        ? htmlspecialchars(substr($curso['detalle'], 0, 100)) . (strlen($curso['detalle']) > 100 ? '...' : '')
                                        : '<span class="text-muted">Sin detalle</span>';
                                    ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info">
                                        <?php echo $curso['total_ediciones']; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">
                                        <?php echo $curso['total_inscripciones']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($curso['proxima_edicion']) {
                                        echo date('d/m/Y', strtotime($curso['proxima_edicion']));
                                    } else {
                                        echo '<span class="text-muted">No programada</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="?module=cursos&action=edit&id=<?php echo $curso['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($curso['total_ediciones'] == 0): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    onclick="confirmDelete(<?php echo $curso['id']; ?>, '<?php echo htmlspecialchars($curso['nombre']); ?>')" 
                                                    title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
                ¿Está seguro que desea eliminar el curso <span id="cursoNombre"></span>?
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
function confirmDelete(id, nombre) {
    document.getElementById('deleteId').value = id;
    document.getElementById('cursoNombre').textContent = nombre;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>