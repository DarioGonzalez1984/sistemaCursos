<?php
if (!defined('DB_HOST')) {
    exit('Acceso directo al script no permitido.');
}

// Filtros
$filtro_estado = isset($_GET['estado']) ? cleanInput($_GET['estado']) : '';
$filtro_curso = isset($_GET['curso_id']) ? cleanInput($_GET['curso_id']) : '';

try {
    // Obtener lista de cursos para el filtro
    $stmt = $conn->query("SELECT id, nombre FROM cursos ORDER BY nombre");
    $cursos = $stmt->fetchAll();

    // Construir la consulta base
    $sql = "
        SELECT i.*, 
               a.cedula, a.nombre as alumno_nombre, a.apellido,
               c.nombre as curso_nombre,
               e.fecha_inicio as edicion_inicio, e.fecha_fin as edicion_fin,
               CASE 
                   WHEN i.estado = 'inscrito' THEN 'info'
                   WHEN i.estado = 'en_curso' THEN 'primary'
                   WHEN i.estado = 'finalizado' THEN 'success'
                   WHEN i.estado = 'abandonado' THEN 'danger'
                   ELSE 'secondary'
               END as estado_color
        FROM inscripciones i
        JOIN alumnos a ON i.alumno_id = a.id
        JOIN ediciones e ON i.edicion_id = e.id
        JOIN cursos c ON e.curso_id = c.id
        WHERE 1=1
    ";

    $params = [];

    if ($filtro_estado) {
        $sql .= " AND i.estado = ?";
        $params[] = $filtro_estado;
    }

    if ($filtro_curso) {
        $sql .= " AND c.id = ?";
        $params[] = $filtro_curso;
    }

    $sql .= " ORDER BY i.fecha_inscripcion DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $inscripciones = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error al obtener las inscripciones';
    $inscripciones = [];
}

// Array de estados para el filtro
$estados = [
    'inscrito' => 'Inscrito',
    'en_curso' => 'En Curso',
    'finalizado' => 'Finalizado',
    'abandonado' => 'Abandonado'
];
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Gestión de Inscripciones</h5>
        <a href="?module=inscripciones&action=create" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nueva Inscripción
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

        <!-- Filtros -->
        <form class="mb-4 row g-3">
            <input type="hidden" name="module" value="inscripciones">

            <div class="col-md-4">
                <label for="curso_id" class="form-label">Filtrar por Curso</label>
                <select class="form-select" id="curso_id" name="curso_id">
                    <option value="">Todos los cursos</option>
                    <?php foreach ($cursos as $curso): ?>
                        <option value="<?php echo $curso['id']; ?>"
                            <?php echo $filtro_curso == $curso['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($curso['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="estado" class="form-label">Filtrar por Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="">Todos los estados</option>
                    <?php foreach ($estados as $valor => $texto): ?>
                        <option value="<?php echo $valor; ?>"
                            <?php echo $filtro_estado === $valor ? 'selected' : ''; ?>>
                            <?php echo $texto; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-secondary me-2">
                    <i class="bi bi-filter"></i> Filtrar
                </button>
                <a href="?module=inscripciones" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Limpiar
                </a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Alumno</th>
                        <th>Curso</th>
                        <th>Edición</th>
                        <th>Estado</th>
                        <th>Fecha Inscripción</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inscripciones)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No hay inscripciones registradas</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inscripciones as $inscripcion): ?>
                            <tr>
                                <td>
                                    <?php
                                    echo htmlspecialchars($inscripcion['apellido'] . ', ' .
                                        $inscripcion['alumno_nombre']) .
                                        '<br><small class="text-muted">CI: ' .
                                        htmlspecialchars($inscripcion['cedula']) . '</small>';
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($inscripcion['curso_nombre']); ?></td>
                                <td>
                                    <?php
                                    echo date('d/m/Y', strtotime($inscripcion['edicion_inicio'])) .
                                        ' al ' .
                                        date('d/m/Y', strtotime($inscripcion['edicion_fin']));
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $inscripcion['estado_color']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $inscripcion['estado'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($inscripcion['fecha_inscripcion'])); ?></td>
                                <td>
                                    <?php
                                    echo $inscripcion['fecha_inicio']
                                        ? date('d/m/Y', strtotime($inscripcion['fecha_inicio']))
                                        : '-';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    echo $inscripcion['fecha_fin']
                                        ? date('d/m/Y', strtotime($inscripcion['fecha_fin']))
                                        : '-';
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-primary dropdown-toggle"
                                            data-bs-toggle="dropdown">
                                            Cambiar Estado
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php
                                            $estados = [
                                                'inscrito' => 'Inscrito',
                                                'en_curso' => 'En Curso',
                                                'finalizado' => 'Finalizado',
                                                'abandonado' => 'Abandonado'
                                            ];
                                            foreach ($estados as $valor => $texto):
                                                if ($valor != $inscripcion['estado']):
                                            ?>
                                                    <li>
                                                        <form method="POST" action="?module=inscripciones&action=cambiar_estado" style="display: inline;">
                                                            <input type="hidden" name="inscripcion_id" value="<?php echo $inscripcion['id']; ?>">
                                                            <input type="hidden" name="nuevo_estado" value="<?php echo $valor; ?>">
                                                            <button type="submit" class="dropdown-item">
                                                                <?php echo $texto; ?>
                                                            </button>
                                                        </form>
                                                    </li>
                                            <?php
                                                endif;
                                            endforeach;
                                            ?>
                                        </ul>
                                        <?php if ($inscripcion['estado'] == 'inscrito'): ?>
                                            <a href="?module=inscripciones&action=edit&id=<?php echo $inscripcion['id']; ?>"
                                                class="btn btn-sm btn-outline-secondary"
                                                title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="confirmDelete(<?php echo $inscripcion['id']; ?>)"
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
                ¿Está seguro que desea eliminar esta inscripción?
            </div>
            <div class="modal-footer">
                <form method="POST" action="?module=inscripciones&action=delete">
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