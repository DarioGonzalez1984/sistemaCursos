<?php
if (!defined('DB_HOST')) {
    exit('Acceso directo al script no permitido.');
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'ID de alumno no especificado';
    header('Location: ?module=alumnos');
    exit;
}

$id = cleanInput($_GET['id']);
$error = '';
$success = '';

try {
    // Obtener datos del alumno
    $stmt = $conn->prepare("SELECT * FROM alumnos WHERE id = ?");
    $stmt->execute([$id]);
    $alumno = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$alumno) {
        $_SESSION['error'] = 'Alumno no encontrado';
        header('Location: ?module=alumnos');
        exit;
    }

    // Procesar el formulario si se envió
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Validar y limpiar datos
        $alumno = [
            'cedula' => cleanInput($_POST['cedula']),
            'nombre' => cleanInput($_POST['nombre']),
            'apellido' => cleanInput($_POST['apellido']),
            'fecha_nacimiento' => cleanInput($_POST['fecha_nacimiento']),
            'telefono' => cleanInput($_POST['telefono']),
            'direccion' => cleanInput($_POST['direccion']),
            'genero' => cleanInput($_POST['genero'])
        ];

        // Validaciones
        $errors = [];
        if (empty($alumno['cedula'])) $errors[] = 'La cédula es requerida';
        if (empty($alumno['nombre'])) $errors[] = 'El nombre es requerido';
        if (empty($alumno['apellido'])) $errors[] = 'El apellido es requerido';
        if (empty($alumno['fecha_nacimiento'])) $errors[] = 'La fecha de nacimiento es requerida';

        // Verificar si la cédula ya existe (excluyendo el alumno actual)
        $stmt = $conn->prepare("SELECT id FROM alumnos WHERE cedula = ? AND id != ?");
        $stmt->execute([$alumno['cedula'], $id]);
        if ($stmt->fetch()) {
            $errors[] = 'Ya existe un alumno con esa cédula';
        }

        if (empty($errors)) {
            // Actualizar alumno
            $stmt = $conn->prepare("
                UPDATE alumnos 
                SET cedula = ?, 
                    nombre = ?, 
                    apellido = ?, 
                    fecha_nacimiento = ?, 
                    telefono = ?, 
                    direccion = ?, 
                    genero = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $alumno['cedula'],
                $alumno['nombre'],
                $alumno['apellido'],
                $alumno['fecha_nacimiento'],
                $alumno['telefono'],
                $alumno['direccion'],
                $alumno['genero'],
                $id
            ]);

            $_SESSION['message'] = 'Alumno actualizado exitosamente';
            header('Location: ?module=alumnos');
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
            <h5 class="mb-0">Editar Alumno</h5>
            <a href="?module=alumnos" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="needs-validation" novalidate>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="cedula" class="form-label">Cédula *</label>
                    <input type="text" 
                           class="form-control" 
                           id="cedula" 
                           name="cedula" 
                           value="<?php echo htmlspecialchars($alumno['cedula']); ?>" 
                           required>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="nombre" class="form-label">Nombre *</label>
                    <input type="text" 
                           class="form-control" 
                           id="nombre" 
                           name="nombre" 
                           value="<?php echo htmlspecialchars($alumno['nombre']); ?>" 
                           required>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="apellido" class="form-label">Apellido *</label>
                    <input type="text" 
                           class="form-control" 
                           id="apellido" 
                           name="apellido" 
                           value="<?php echo htmlspecialchars($alumno['apellido']); ?>" 
                           required>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento *</label>
                    <input type="date" 
                           class="form-control" 
                           id="fecha_nacimiento" 
                           name="fecha_nacimiento" 
                           value="<?php echo $alumno['fecha_nacimiento']; ?>" 
                           required>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="telefono" class="form-label">Teléfono</label>
                    <input type="tel" 
                           class="form-control" 
                           id="telefono" 
                           name="telefono" 
                           value="<?php echo htmlspecialchars($alumno['telefono']); ?>">
                </div>

                <div class="col-md-6 mb-3">
                    <label for="genero" class="form-label">Género</label>
                    <select class="form-select" id="genero" name="genero">
                        <option value="">Seleccione...</option>
                        <option value="M" <?php echo $alumno['genero'] == 'M' ? 'selected' : ''; ?>>Masculino</option>
                        <option value="F" <?php echo $alumno['genero'] == 'F' ? 'selected' : ''; ?>>Femenino</option>
                        <option value="Otro" <?php echo $alumno['genero'] == 'Otro' ? 'selected' : ''; ?>>Otro</option>
                    </select>
                </div>

                <div class="col-12 mb-3">
                    <label for="direccion" class="form-label">Dirección</label>
                    <textarea class="form-control" 
                              id="direccion" 
                              name="direccion" 
                              rows="3"><?php echo htmlspecialchars($alumno['direccion']); ?></textarea>
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