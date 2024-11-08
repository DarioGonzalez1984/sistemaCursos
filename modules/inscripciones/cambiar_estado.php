<?php
if (!defined('DB_HOST')) {
    exit('Acceso directo al script no permitido.');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['inscripcion_id']) && isset($_POST['nuevo_estado'])) {
    $inscripcion_id = cleanInput($_POST['inscripcion_id']);
    $nuevo_estado = cleanInput($_POST['nuevo_estado']);
    
    try {
        // Verificar que la inscripción existe
        $stmt = $conn->prepare("SELECT estado FROM inscripciones WHERE id = ?");
        $stmt->execute([$inscripcion_id]);
        $inscripcion = $stmt->fetch();

        if (!$inscripcion) {
            $_SESSION['error'] = 'Inscripción no encontrada';
            header('Location: ?module=inscripciones');
            exit;
        }

        // Validar el nuevo estado
        $estados_validos = ['inscrito', 'en_curso', 'finalizado', 'abandonado'];
        if (!in_array($nuevo_estado, $estados_validos)) {
            $_SESSION['error'] = 'Estado no válido';
            header('Location: ?module=inscripciones');
            exit;
        }

        // Actualizar el estado y las fechas según corresponda
        $stmt = $conn->prepare("
            UPDATE inscripciones 
            SET estado = ?,
                fecha_inicio = CASE 
                    WHEN ? = 'en_curso' THEN CURRENT_DATE
                    ELSE fecha_inicio 
                END,
                fecha_fin = CASE 
                    WHEN ? IN ('finalizado', 'abandonado') THEN CURRENT_DATE
                    ELSE fecha_fin 
                END
            WHERE id = ?
        ");
        
        $stmt->execute([
            $nuevo_estado,
            $nuevo_estado,
            $nuevo_estado,
            $inscripcion_id
        ]);

        $_SESSION['message'] = 'Estado actualizado exitosamente';
        
    } catch(PDOException $e) {
        $_SESSION['error'] = 'Error al actualizar el estado';
    }
}

header('Location: ?module=inscripciones');
exit;
?>