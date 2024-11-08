<?php
if (!defined('DB_HOST')) {
    exit('Acceso directo al script no permitido.');
}

$curso = [
    'nombre' => '',
    'detalle' => ''
];
$error = '';

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

    // Verificar si ya existe un curso con el mismo nombre
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT id FROM cursos WHERE nombre = ?");
            $stmt->execute([$curso['nombre']]);
            if ($stmt->fetch()) {
                $errors[] = 'Ya existe un curso con ese nombre';
            }
        } catch(PDOException $e) {
            $errors[] = 'Error al verificar el nombre del curso';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO cursos (nombre, detalle)
                VALUES (?, ?)
            ");
            $stmt->execute([
                $curso['nombre'],
                $curso['detalle']
            ]);

            $_SESSION['message'] = 'Curso creado exitosamente';
            header('Location: ?module=cursos');
            exit;
        } catch(PDOException $e) {
            $error = 'Error al crear el curso';
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Nuevo Curso</h5>
            <a href="?module=cursos" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
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
                    <i class="bi bi-save"></i> Guardar
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