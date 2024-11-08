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
$success = '';

try {
    // Obtener lista de cursos para el select
    $stmt = $conn->query("SELECT id, nombre FROM cursos ORDER BY nombre");
    $cursos = $stmt->fetchAll();

    // Obtener datos de la edición
    $stmt = $conn->prepare("
        SELECT e.*, c.nombre as curso_nombre 
        FROM ediciones e 
        JOIN cursos c ON e.curso_id = c.id 
        WHERE e.id = ?");
    $stmt->execute([$id]);
    $edicion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$edicion) {
        $_SESSION['error'] = 'Edición no encontrada';
        header('Location: ?module=ediciones');
        exit;
    }

    // Verificar si tiene inscripciones (para posibles restricciones)
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM inscripciones WHERE edicion_id = ?");
    $stmt->execute([$id]);
    $tieneInscripciones = $stmt->fetchColumn() > 0;

    // Procesar el formulario si se envió
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $edicion = [
            'curso_id' => cleanInput($_POST['curso_id']),
            'fecha_inicio' => cleanInput($_POST['fecha_inicio']),
            'fecha_fin' => cleanInput($_POST['fecha_fin'])
        ];

        // Validaciones
        $errors = [];
        if (empty($edicion['curso_id'])) $errors[] = 'El curso es requerido';
        if (empty($edicion['fecha_inicio'])) $errors[] = 'La fecha de inicio es requerida';
        if (empty($edicion['fecha_fin'])) $errors[] = 'La fecha de fin es requerida';

        // Validar que fecha_fin sea posterior a fecha_inicio
        if (!empty($edicion['fecha_inicio']) && !empty($edicion['fecha_fin'])) {
            $inicio = new DateTime($edicion['fecha_inicio']);
            $fin = new DateTime($edicion['fecha_fin']);
            if ($fin <= $inicio) {
                $errors[] = 'La fecha de fin debe ser posterior a la fecha de inicio';
            }
        }

        // Si tiene inscripciones, validar que las nuevas fechas no afecten a las inscripciones existentes
        if ($tieneInscripciones) {
            $stmt = $conn->prepare("
                SELECT MIN(fecha_inscripcion) as primera_inscripcion, 
                       MAX(CASE WHEN estado = 'finalizado' THEN fecha_fin END) as ultima_finalizacion
                FROM inscripciones 
                WHERE edicion_id = ?");
            $stmt->execute([$id]);
            $fechas = $stmt->fetch();

            if ($fechas['primera_inscripcion'] && new DateTime($edicion['fecha_inicio']) > new DateTime($fechas['primera_inscripcion'])) {
                $errors[] = 'No puede modificar la fecha de inicio a una fecha posterior a la primera inscripción';
            }
            if ($fechas['ultima_finalizacion'] && new DateTime($edicion['fecha_fin']) < new DateTime($fechas['ultima_finalizacion'])) {
                $errors[] = 'No puede modificar la fecha de fin a una fecha anterior a la última finalización';
            }
        }

        // Verificar solapamiento de fechas
        if (empty($errors)) {
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM ediciones 
                WHERE curso_id = ? 
                AND id != ?
                AND (
                    (fecha_inicio BETWEEN ? AND ?) OR
                    (fecha_fin BETWEEN ? AND ?) OR
                    (fecha_inicio <= ? AND fecha_fin >= ?)
                )
            ");
            $stmt->execute([
                $edicion['curso_id'],
                $id,
                $edicion['fecha_inicio'],
                $edicion['fecha_fin'],
                $edicion['fecha_inicio'],
                $edicion['fecha_fin'],
                $edicion['fecha_fin'],
                $edicion['fecha_inicio']
            ]);

            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Ya existe una edición del curso en ese rango de fechas';
            }
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("
                UPDATE ediciones 
                SET curso_id = ?, 
                    fecha_inicio = ?, 
                    fecha_fin = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $edicion['curso_id'],
                $edicion['fecha_inicio'],
                $edicion['fecha_fin'],
                $id
            ]);

            $_SESSION['message'] = 'Edición actualizada exitosamente';
            header('Location: ?module=ediciones');
            exit;
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
            <h5 class="mb-0">Editar Edición</h5>
            <a href="?module=ediciones" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($tieneInscripciones): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Esta edición tiene inscripciones asociadas. Algunas modificaciones pueden estar restringidas.
            </div>
        <?php endif; ?>

        <form method="POST" class="needs-validation" novalidate>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="curso_id" class="form-label">Curso *</label>
                    <select class="form-select" 
                            id="curso_id" 
                            name="curso_id" 
                            <?php echo $tieneInscripciones ? 'disabled' : ''; ?> 
                            required>
                        <option value="">Seleccione un curso...</option>
                        <?php foreach ($cursos as $curso): ?>
                            <option value="<?php echo $curso['id']; ?>"
                                    <?php echo ($edicion['curso_id'] == $curso['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($curso['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($tieneInscripciones): ?>
                        <input type="hidden" name="curso_id" value="<?php echo $edicion['curso_id']; ?>">
                    <?php endif; ?>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                    <input type="date" 
                           class="form-control" 
                           id="fecha_inicio" 
                           name="fecha_inicio" 
                           value="<?php echo $edicion['fecha_inicio']; ?>"
                           required>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="fecha_fin" class="form-label">Fecha de Fin *</label>
                    <input type="date" 
                           class="form-control" 
                           id="fecha_fin" 
                           name="fecha_fin" 
                           value="<?php echo $edicion['fecha_fin']; ?>"
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

            // Validación adicional de fechas
            const inicio = new Date(document.getElementById('fecha_inicio').value)
            const fin = new Date(document.getElementById('fecha_fin').value)
            
            if (fin <= inicio) {
                event.preventDefault()
                alert('La fecha de fin debe ser posterior a la fecha de inicio')
            }
            
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>