<?php
if (!defined('DB_HOST')) {
    exit('Acceso directo al script no permitido.');
}

// Procesar eliminación si se recibe POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM alumnos WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $_SESSION['message'] = 'Alumno eliminado con éxito';
        header('Location: ?module=alumnos');
        exit;
    } catch(PDOException $e) {
        $error = 'Error al eliminar el alumno';
    }
}

// Obtener lista de alumnos
try {
    $stmt = $conn->query("SELECT * FROM alumnos ORDER BY apellido, nombre");
    $alumnos = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Error al obtener los alumnos';
    $alumnos = [];
}
?>

<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Gestión de Alumnos</h5>
    <a href="?module=alumnos&action=create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo Alumno
    </a>
</div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
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
                        <th>Cédula</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Teléfono</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alumnos as $alumno): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($alumno['cedula']); ?></td>
                            <td><?php echo htmlspecialchars($alumno['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($alumno['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($alumno['telefono']); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="?module=alumnos&action=edit&id=<?php echo $alumno['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="?module=alumnos&action=historial&id=<?php echo $alumno['id']; ?>" 
                                       class="btn btn-sm btn-outline-info" 
                                       title="Ver historial">
                                        <i class="bi bi-clock-history"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmDelete(<?php echo $alumno['id']; ?>)" 
                                            title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
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
                ¿Está seguro que desea eliminar este alumno?
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