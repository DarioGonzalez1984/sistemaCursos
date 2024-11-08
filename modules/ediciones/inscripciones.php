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
$error = '';

try {
    // Obtener datos de la edición y el curso
    $stmt = $conn->prepare("
        SELECT e.*, c.nombre as curso_nombre 
        FROM ediciones e
        JOIN cursos c ON e.curso_id = c.id
        WHERE e.id = ?
    ");
    $stmt->execute([$id]);
    $edicion = $stmt->fetch();

    if (!$edicion) {
        $_SESSION['error'] = 'Edición no encontrada';
        header('Location: ?module=ediciones');
        exit;
    }

    // Obtener estadísticas de la edición
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_inscripciones,
            SUM(CASE WHEN estado = 'inscrito' THEN 1 ELSE 0 END) as total_inscritos,
            SUM(CASE WHEN estado = 'en_curso' THEN 1 ELSE 0 END) as total_en_curso,
            SUM(CASE WHEN estado = 'finalizado' THEN 1 ELSE 0 END) as total_finalizados,
            SUM(CASE WHEN estado = 'abandonado' THEN 1 ELSE 0 END) as total_abandonos
        FROM inscripciones 
        WHERE edicion_id = ?
    ");
    $stmt->execute([$id]);
    $stats = $stmt->fetch();

    // Obtener lista de inscripciones
    $stmt = $conn->prepare("
        SELECT i.*, 
               a.cedula, a.nombre as alumno_nombre, a.apellido,
               CASE 
                   WHEN i.estado = 'inscrito' THEN 'info'
                   WHEN i.estado = 'en_curso' THEN 'primary'
                   WHEN i.estado = 'finalizado' THEN 'success'
                   WHEN i.estado = 'abandonado' THEN 'danger'
                   ELSE 'secondary'
               END as estado_color
        FROM inscripciones i
        JOIN alumnos a ON i.alumno_id = a.id
        WHERE i.edicion_id = ?
        ORDER BY a.apellido, a.nombre
    ");
    $stmt->execute([$id]);
    $inscripciones = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = 'Error al cargar los datos';
}

// Estados disponibles para el cambio
$estados = [
    'inscrito' => 'Inscrito',
    'en_curso' => 'En Curso',
    'finalizado' => 'Finalizado',
    'abandonado' => 'Abandonado'
];
?>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                Inscripciones - <?php echo htmlspecialchars($edicion['curso_nombre']); ?>
                <small class="text-muted">
                    (<?php echo date('d/m/Y', strtotime($edicion['fecha_inicio'])); ?> - 
                     <?php echo date('d/m/Y', strtotime($edicion['fecha_fin'])); ?>)
                </small>
            </h5>
            <div>
                <a href="?module=ediciones" class="btn btn-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
                <?php if (strtotime($edicion['fecha_fin']) >= time()): ?>
                    <a href="?module=inscripciones&action=create&edicion_id=<?php echo $edicion['id']; ?>" 
                       class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Nueva Inscripción
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
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

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card bg-light">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col">
                                <h5 class="mb-0"><?php echo $stats['total_inscripciones']; ?></h5>
                                <small class="text-muted">Total Inscripciones</small>
                            </div>
                            <div class="col">
                                <h5 class="mb-0 text-info"><?php echo $stats['total_inscritos']; ?></h5>
                                <small class="text-muted">Inscritos</small>
                            </div>
                            <div class="col">
                                <h5 class="mb-0 text-primary"><?php echo $stats['total_en_curso']; ?></h5>
                                <small class="text-muted">En Curso</small>
                            </div>
                            <div class="col">
                                <h5 class="mb-0 text-success"><?php echo $stats['total_finalizados']; ?></h5>
                                <small class="text-muted">Finalizados</small>
                            </div>
                            <div class="col">
                                <h5 class="mb-0 text-danger"><?php echo $stats['total_abandonos']; ?></h5>
                                <small class="text-muted">Abandonos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de inscripciones -->
        <?php if (empty($inscripciones)): ?>
            <div class="alert alert-info">
                No hay inscripciones registradas para esta edición.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Alumno</th>
                            <th>Cédula</th>
                            <th>Estado</th>
                            <th>Fecha Inscripción</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inscripciones as $inscripcion): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($inscripcion['apellido'] . ', ' . 
                                                              $inscripcion['alumno_nombre']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($inscripcion['cedula']); ?></td>
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
                                            <?php foreach ($estados as $valor => $texto): ?>
                                                <?php if ($valor != $inscripcion['estado']): ?>
                                                    <li>
                                                        <form method="POST" 
                                                              action="?module=inscripciones&action=cambiar_estado">
                                                            <input type="hidden" 
                                                                   name="inscripcion_id" 
                                                                   value="<?php echo $inscripcion['id']; ?>">
                                                            <input type="hidden" 
                                                                   name="nuevo_estado" 
                                                                   value="<?php echo $valor; ?>">
                                                            <button type="submit" class="dropdown-item">
                                                                <?php echo $texto; ?>
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>