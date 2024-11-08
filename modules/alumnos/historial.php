<?php
if (!defined('DB_HOST')) {
    exit('Acceso directo al script no permitido.');
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'ID de alumno no especificado';
    header('Location: ?module=alumnos');
    exit;
}

try {
    // Obtener datos del alumno
    $stmt = $conn->prepare("
        SELECT * FROM alumnos WHERE id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $alumno = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$alumno) {
        $_SESSION['error'] = 'Alumno no encontrado';
        header('Location: ?module=alumnos');
        exit;
    }

    // Obtener historial de inscripciones
    $stmt = $conn->prepare("
        SELECT i.*, c.nombre as curso_nombre, 
               e.fecha_inicio as edicion_inicio, 
               e.fecha_fin as edicion_fin
        FROM inscripciones i
        JOIN ediciones e ON i.edicion_id = e.id
        JOIN cursos c ON e.curso_id = c.id
        WHERE i.alumno_id = ?
        ORDER BY i.fecha_inscripcion DESC
    ");
    $stmt->execute([$_GET['id']]);
    $inscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error = 'Error al cargar el historial';
}
?>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Historial del Alumno</h5>
            <a href="?module=alumnos" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-6">
                <h6>Información del Alumno</h6>
                <table class="table table-borderless">
                    <tr>
                        <th width="150">Cédula:</th>
                        <td><?php echo htmlspecialchars($alumno['cedula']); ?></td>
                    </tr>
                    <tr>
                        <th>Nombre completo:</th>
                        <td><?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido']); ?></td>
                    </tr>
                    <tr>
                        <th>Teléfono:</th>
                        <td><?php echo htmlspecialchars($alumno['telefono']); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <h6>Historial de Inscripciones</h6>
        <?php if (empty($inscripciones)): ?>
            <div class="alert alert-info">
                Este alumno aún no tiene inscripciones registradas.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th>Edición</th>
                            <th>Estado</th>
                            <th>Fecha Inscripción</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inscripciones as $inscripcion): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($inscripcion['curso_nombre']); ?></td>
                                <td>
                                    <?php 
                                    echo date('d/m/Y', strtotime($inscripcion['edicion_inicio'])) . 
                                         ' - ' . 
                                         date('d/m/Y', strtotime($inscripcion['edicion_fin']));
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $estados = [
                                        'inscrito' => 'info',
                                        'en_curso' => 'primary',
                                        'finalizado' => 'success',
                                        'abandonado' => 'danger'
                                    ];
                                    $badgeClass = $estados[$inscripcion['estado']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $badgeClass; ?>">
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
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>