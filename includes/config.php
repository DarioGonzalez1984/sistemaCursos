<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_cursos');

try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
    exit;
}

// Función para verificar si el usuario está logueado
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /index.php');
        exit;
    }
}

// Función para limpiar datos de entrada
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para hashear passwords
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Función para verificar passwords
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Función para crear un nuevo usuario
function createUser($conn, $email, $password, $nombre) {
    try {
        $hash = hashPassword($password);
        $stmt = $conn->prepare("INSERT INTO usuarios (email, password, nombre) VALUES (?, ?, ?)");
        return $stmt->execute([$email, $hash, $nombre]);
    } catch(PDOException $e) {
        return false;
    }
}

// Función para verificar credenciales
function verifyCredentials($conn, $email, $password) {
    try {
        $stmt = $conn->prepare("SELECT id, password, nombre FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && verifyPassword($password, $user['password'])) {
            return $user;
        }
        return false;
    } catch(PDOException $e) {
        return false;
    }
}
?>