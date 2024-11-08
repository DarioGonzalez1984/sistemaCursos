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
$error = '';

try {
    // Obtener datos del curso
    $stmt = $conn->prepare("SELECT * FROM cursos WHERE id = ?");
    $stmt->execute([$id]);
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$curso) {
        $_SESSION['error'] = 'Curso no encontrado';
        header('Location: ?module=cursos');
        exit;
    }

    // Verificar si tiene ediciones (para mostrar advertencia)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM ediciones WHERE curso_id = ?");
    $stmt->execute([$id]);
    $tieneEdiciones = $stmt->fetchColumn() > 0;

    // Procesar el formulario
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $curso = [
            'nombre' => cleanInput($_POST['nombre']),
            'detalle' => cleanInput($_POST['detalle'])
        ];

        // Validaciones
        $errors = [];
        if (empty($curso['nombre'])) {
            $errors[] = 'El nombre del curso es requerido';
        } elseif (strlen($curso['nombre']) > 255) {
            $errors[] = 'El nombre no puede exceder los 255 caracteres';
        }

        // Verificar si existe otro curso con el mismo nombre
        if (empty($errors)) {
            $stmt = $conn->prepare("
                SELECT id 
                FROM cursos 
                WHERE nombre = ? AND id != ?
            ");
            $stmt->execute([$curso['nombre'], $id]);
            if ($stmt->fetch()) {
                $errors[] = 'Ya existe otro curso con ese nombre';
            }
        }

        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("
                    UPDATE cursos 
                    SET nombre = ?, detalle = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $curso['nombre'],
                    $curso['detalle'],
                    $id
                ]);

                $_SESSION['message'] = 'Curso actualizado exitosamente';
                header('Location: ?module=cursos');
                exit;
            } catch(PDOException $e) {
                $error = 'Error al actualizar el curso';
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
            <h5 class="mb-0">Editar Curso</h5>
            <a href="?module=cursos" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($tieneEdiciones): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle-fill me-2"></i>
                Este curso tiene ediciones asociadas. Los cambios afectarán a todas las ediciones.
            </div>
        <?php endif; ?>

        <form method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre del Curso *</label>
                <input type="text" 
                       class="form-control" 
                       id="nombre" 
                       name="nombre" 
                       value="<?php echo htmlspecialchars($curso['nombre']); ?>"
                       required>
                <div class="invalid-feedback">
                    Por favor ingrese el nombre del curso
                </div>
            </div>

            <div class="mb-3">
                <label for="detalle" class="form-label">Detalle</label>
                <textarea class="form-control" 
                          id="detalle" 
                          name="detalle" 
                          rows="4"><?php echo htmlspecialchars($curso['detalle']); ?></textarea>
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