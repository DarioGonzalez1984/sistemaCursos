<?php
if (!defined('DB_HOST')) {
    exit('Acceso directo al script no permitido.');
}

$edicion = [
    'curso_id' => '',
    'fecha_inicio' => '',
    'fecha_fin' => ''
];
$error = '';
$isEdit = false;

// Obtener lista de cursos para el select
try {
    $stmt = $conn->query("SELECT id, nombre FROM cursos ORDER BY nombre");
    $cursos = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Error al cargar los cursos';
    $cursos = [];
}

// Si es edición, cargar datos de la edición
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $isEdit = true;
    try {
        $stmt = $conn->prepare("SELECT * FROM ediciones WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $edicion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$edicion) {
            $_SESSION['error'] = 'Edición no encontrada';
            header('Location: ?module=ediciones');
            exit;
        }
    } catch(PDOException $e) {
        $error = 'Error al cargar datos de la edición';
    }
}

// Procesar el formulario
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

    // Validar que no haya solapamiento de fechas para el mismo curso
    if (empty($errors)) {
        try {
            $params = [
                $edicion['curso_id'],
                $edicion['fecha_inicio'],
                $edicion['fecha_fin']
            ];
            
            $sql = "
                SELECT COUNT(*) 
                FROM ediciones 
                WHERE curso_id = ? 
                AND (
                    (fecha_inicio BETWEEN ? AND ?) OR
                    (fecha_fin BETWEEN ? AND ?) OR
                    (fecha_inicio <= ? AND fecha_fin >= ?)
                )
            ";
            
            if ($isEdit) {
                $sql .= " AND id != ?";
                $params[] = $_GET['id'];
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->execute(array_merge($params, [
                $edicion['fecha_inicio'],
                $edicion['fecha_fin'],
                $edicion['fecha_fin'],
                $edicion['fecha_inicio']
            ]));
            
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Ya existe una edición del curso en ese rango de fechas';
            }
        } catch(PDOException $e) {
            $errors[] = 'Error al validar las fechas';
        }
    }

    if (empty($errors)) {
        try {
            if ($isEdit) {
                $stmt = $conn->prepare("
                    UPDATE ediciones 
                    SET curso_id = ?, fecha_inicio = ?, fecha_fin = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $edicion['curso_id'],
                    $edicion['fecha_inicio'],
                    $edicion['fecha_fin'],
                    $_GET['id']
                ]);
                $_SESSION['message'] = 'Edición actualizada con éxito';
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO ediciones (curso_id, fecha_inicio, fecha_fin)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    $edicion['curso_id'],
                    $edicion['fecha_inicio'],
                    $edicion['fecha_fin']
                ]);
                $_SESSION['message'] = 'Edición creada con éxito';
            }
            header('Location: ?module=ediciones');
            exit;
        } catch(PDOException $e) {
            $error = 'Error al guardar la edición';
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?php echo $isEdit ? 'Editar' : 'Nueva' ?> Edición</h5>
            <a href="?module=ediciones" class="btn btn-secondary">
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
                <div class="col-md-12 mb-3">
                    <label for="curso_id" class="form-label">Curso *</label>
                    <select class="form-select" id="curso_id" name="curso_id" required>
                        <option value="">Seleccione un curso...</option>
                        <?php foreach ($cursos as $curso): ?>
                            <option value="<?php echo $curso['id']; ?>"
                                    <?php echo ($edicion['curso_id'] == $curso['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($curso['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">
                        Por favor seleccione un curso
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                    <input type="date" 
                           class="form-control" 
                           id="fecha_inicio" 
                           name="fecha_inicio" 
                           value="<?php echo $edicion['fecha_inicio']; ?>"
                           required>
                    <div class="invalid-feedback">
                        Por favor seleccione la fecha de inicio
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="fecha_fin" class="form-label">Fecha de Fin *</label>
                    <input type="date" 
                           class="form-control" 
                           id="fecha_fin" 
                           name="fecha_fin" 
                           value="<?php echo $edicion['fecha_fin']; ?>"
                           required>
                    <div class="invalid-feedback">
                        Por favor seleccione la fecha de fin
                    </div>
                </div>
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
// Validación del lado del cliente
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
            const fechaInicio = new Date(document.getElementById('fecha_inicio').value)
            const fechaFin = new Date(document.getElementById('fecha_fin').value)
            
            if (fechaFin <= fechaInicio) {
                event.preventDefault()
                alert('La fecha de fin debe ser posterior a la fecha de inicio')
            }
            
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>