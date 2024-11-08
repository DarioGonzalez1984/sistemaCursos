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
$error = '';

try {
    // Obtener datos de la inscripción
    $stmt = $conn->prepare("
        SELECT i.*, 
               a.nombre as alumno_nombre, a.apellido,
               c.nombre as curso_nombre,
               e.fecha_inicio as edicion_inicio, e.fecha_fin as edicion_fin
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
        $_SESSION['error'] = 'Solo se pueden editar inscripciones en estado inicial';
        header('Location: ?module=inscripciones');
        exit;
    }

    // Obtener ediciones disponibles
    $stmt = $conn->query("
        SELECT e.id, e.fecha_inicio, e.fecha_fin, c.nombre as curso_nombre
        FROM ediciones e
        JOIN cursos c ON e.curso_id = c.id
        WHERE e.fecha_fin >= CURDATE() OR e.id = " . $inscripcion['edicion_id'] . "
        ORDER BY e.fecha_inicio, c.nombre
    ");
    $ediciones = $stmt->fetchAll();

    // Procesar el formulario
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nueva_edicion_id = cleanInput($_POST['edicion_id']);
        $nueva_fecha = cleanInput($_POST['fecha_inscripcion']);

        // Validaciones
        $errors = [];

        if (empty($nueva_fecha)) {
            $errors[] = 'La fecha de inscripción es requerida';
        }

        // Verificar que la nueva fecha no sea posterior al inicio de la edición
        $stmt = $conn->prepare("
            SELECT fecha_inicio 
            FROM ediciones 
            WHERE id = ?
        ");
        $stmt->execute([$nueva_edicion_id]);
        $edicion = $stmt->fetch();
        
        if (strtotime($nueva_fecha) > strtotime($edicion['fecha_inicio'])) {
            $errors[] = 'La fecha de inscripción no puede ser posterior a la fecha de inicio del curso';
        }

        // Verificar que no exista otra inscripción para el mismo alumno en la nueva edición
        if ($nueva_edicion_id != $inscripcion['edicion_id']) {
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM inscripciones 
                WHERE alumno_id = ? AND edicion_id = ? AND id != ?
            ");
            $stmt->execute([$inscripcion['alumno_id'], $nueva_edicion_id, $id]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'El alumno ya está inscrito en esta edición del curso';
            }
        }

        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("
                    UPDATE inscripciones 
                    SET edicion_id = ?, fecha_inscripcion = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $nueva_edicion_id,
                    $nueva_fecha,
                    $id
                ]);

                $_SESSION['message'] = 'Inscripción actualizada exitosamente';
                header('Location: ?module=inscripciones');
                exit;
            } catch(PDOException $e) {
                $error = 'Error al actualizar la inscripción';
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }

} catch(PDOException $e) {
    $error = 'Error al procesar la solicitud';
}
?>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Editar Inscripción</h5>
            <a href="?module=inscripciones" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="alert alert-info">
            <strong>Alumno:</strong> <?php echo htmlspecialchars($inscripcion['apellido'] . ', ' . $inscripcion['alumno_nombre']); ?>
        </div>

        <form method="POST" class="needs-validation" novalidate>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="edicion_id" class="form-label">Edición del Curso *</label>
                    <select class="form-select" id="edicion_id" name="edicion_id" required>
                        <?php foreach ($ediciones as $edicion): ?>
                            <option value="<?php echo $edicion['id']; ?>"
                                    <?php echo ($inscripcion['edicion_id'] == $edicion['id']) ? 'selected' : ''; ?>>
                                <?php 
                                echo htmlspecialchars($edicion['curso_nombre'] . ' (' . 
                                     date('d/m/Y', strtotime($edicion['fecha_inicio'])) . ' al ' .
                                     date('d/m/Y', strtotime($edicion['fecha_fin'])) . ')'); 
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="fecha_inscripcion" class="form-label">Fecha de Inscripción *</label>
                    <input type="date" 
                           class="form-control" 
                           id="fecha_inscripcion" 
                           name="fecha_inscripcion" 
                           value="<?php echo $inscripcion['fecha_inscripcion']; ?>"
                           required>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Guardar Cambios
                </button>
            </div>
        </form>
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