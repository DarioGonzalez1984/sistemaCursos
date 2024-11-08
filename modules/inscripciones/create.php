<?php
if (!defined('DB_HOST')) {
    exit('Acceso directo al script no permitido.');
}

$inscripcion = [
    'alumno_id' => '',
    'edicion_id' => '',
    'fecha_inscripcion' => date('Y-m-d')
];
$error = '';
$edicion_preseleccionada = isset($_GET['edicion_id']) ? cleanInput($_GET['edicion_id']) : '';

try {
    // Obtener lista de alumnos
    $stmt = $conn->query("
        SELECT id, cedula, nombre, apellido 
        FROM alumnos 
        ORDER BY apellido, nombre
    ");
    $alumnos = $stmt->fetchAll();

    // Obtener ediciones disponibles (que no hayan finalizado)
    $stmt = $conn->query("
        SELECT e.id, e.fecha_inicio, e.fecha_fin, c.nombre as curso_nombre
        FROM ediciones e
        JOIN cursos c ON e.curso_id = c.id
        WHERE e.fecha_fin >= CURDATE()
        ORDER BY e.fecha_inicio, c.nombre
    ");
    $ediciones = $stmt->fetchAll();

    // Procesar el formulario
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $inscripcion = [
            'alumno_id' => cleanInput($_POST['alumno_id']),
            'edicion_id' => cleanInput($_POST['edicion_id']),
            'fecha_inscripcion' => cleanInput($_POST['fecha_inscripcion'])
        ];

        // Validaciones
        $errors = [];
        if (empty($inscripcion['alumno_id'])) $errors[] = 'El alumno es requerido';
        if (empty($inscripcion['edicion_id'])) $errors[] = 'La edición es requerida';
        if (empty($inscripcion['fecha_inscripcion'])) $errors[] = 'La fecha de inscripción es requerida';

        // Verificar si el alumno ya está inscrito en esta edición
        if (empty($errors)) {
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM inscripciones 
                WHERE alumno_id = ? AND edicion_id = ?
            ");
            $stmt->execute([$inscripcion['alumno_id'], $inscripcion['edicion_id']]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'El alumno ya está inscrito en esta edición del curso';
            }
        }

        // Verificar que la fecha de inscripción no sea posterior a la fecha de inicio de la edición
        if (empty($errors)) {
            $stmt = $conn->prepare("
                SELECT fecha_inicio 
                FROM ediciones 
                WHERE id = ?
            ");
            $stmt->execute([$inscripcion['edicion_id']]);
            $edicion = $stmt->fetch();
            
            if (strtotime($inscripcion['fecha_inscripcion']) > strtotime($edicion['fecha_inicio'])) {
                $errors[] = 'La fecha de inscripción no puede ser posterior a la fecha de inicio del curso';
            }
        }

        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO inscripciones (
                        alumno_id, edicion_id, fecha_inscripcion, estado
                    ) VALUES (?, ?, ?, 'inscrito')
                ");
                $stmt->execute([
                    $inscripcion['alumno_id'],
                    $inscripcion['edicion_id'],
                    $inscripcion['fecha_inscripcion']
                ]);

                $_SESSION['message'] = 'Inscripción creada exitosamente';
                header('Location: ?module=inscripciones');
                exit;
            } catch(PDOException $e) {
                $error = 'Error al crear la inscripción';
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }

} catch(PDOException $e) {
    $error = 'Error al cargar los datos necesarios';
}
?>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Nueva Inscripción</h5>
            <a href="?module=inscripciones" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($ediciones)): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                No hay ediciones disponibles para inscripción
            </div>
        <?php else: ?>
            <form method="POST" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="alumno_id" class="form-label">Alumno *</label>
                        <select class="form-select" id="alumno_id" name="alumno_id" required>
                            <option value="">Seleccione un alumno...</option>
                            <?php foreach ($alumnos as $alumno): ?>
                                <option value="<?php echo $alumno['id']; ?>"
                                        <?php echo ($inscripcion['alumno_id'] == $alumno['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($alumno['apellido'] . ', ' . $alumno['nombre'] . 
                                                              ' (CI: ' . $alumno['cedula'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">
                            Por favor seleccione un alumno
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="edicion_id" class="form-label">Edición del Curso *</label>
                        <select class="form-select" id="edicion_id" name="edicion_id" required>
                            <option value="">Seleccione una edición...</option>
                            <?php foreach ($ediciones as $edicion): ?>
                                <option value="<?php echo $edicion['id']; ?>"
                                        <?php echo ($edicion_preseleccionada == $edicion['id']) ? 'selected' : ''; ?>>
                                    <?php 
                                    echo htmlspecialchars($edicion['curso_nombre'] . ' (' . 
                                         date('d/m/Y', strtotime($edicion['fecha_inicio'])) . ' al ' .
                                         date('d/m/Y', strtotime($edicion['fecha_fin'])) . ')'); 
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">
                            Por favor seleccione una edición
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="fecha_inscripcion" class="form-label">Fecha de Inscripción *</label>
                        <input type="date" 
                               class="form-control" 
                               id="fecha_inscripcion" 
                               name="fecha_inscripcion" 
                               value="<?php echo $inscripcion['fecha_inscripcion']; ?>"
                               required>
                        <div class="invalid-feedback">
                            Por favor seleccione la fecha de inscripción
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>