<?php
// api/auth/registrar.php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

// Leer datos JSON o form-data
$datos = json_decode(file_get_contents('php://input'), true);
if (!$datos) {
    $datos = $_POST;
}

$nombre_de_usuario = isset($datos['nombre_de_usuario']) ? trim($datos['nombre_de_usuario']) : '';
$password          = isset($datos['password']) ? $datos['password'] : '';

// Validaciones
if (empty($nombre_de_usuario) || empty($password)) {
    echo json_encode([
        "success" => false,
        "message" => "El nombre de usuario y la contraseña son obligatorios"
    ]);
    exit;
}

if (strlen($nombre_de_usuario) < 3 || strlen($nombre_de_usuario) > 50) {
    echo json_encode([
        "success" => false,
        "message" => "El nombre de usuario debe tener entre 3 y 50 caracteres"
    ]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode([
        "success" => false,
        "message" => "La contraseña debe tener al menos 6 caracteres"
    ]);
    exit;
}

$pdo = getConexion();

// Verificar si ya existe el usuario
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE nombre_de_usuario = ? LIMIT 1");
$stmt->execute([$nombre_de_usuario]);
if ($stmt->fetch()) {
    echo json_encode([
        "success" => false,
        "message" => "El nombre de usuario ya está en uso"
    ]);
    exit;
}

// Hash de contraseña
$passwordHash = password_hash($password, PASSWORD_BCRYPT);

// Manejar foto si se envía
$fotoUrl = null;
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $fotoUrl = subirImagen($_FILES['foto'], 'usuarios');
}

// Insertar usuario
$stmt = $pdo->prepare(
    "INSERT INTO usuarios (nombre_de_usuario, password, foto)
     VALUES (?, ?, ?)"
);
$stmt->execute([$nombre_de_usuario, $passwordHash, $fotoUrl]);

echo json_encode([
    "success" => true,
    "message" => "Usuario registrado correctamente"
]);

// Función auxiliar para subir imagen
function subirImagen($archivo, $carpeta) {
    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $extensionesPermitidas)) {
        return null;
    }

    $nombreArchivo = uniqid() . '_' . time() . '.' . $extension;
    $rutaDestino   = __DIR__ . '/../uploads/' . $carpeta . '/' . $nombreArchivo;

    if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        // Construir URL pública
        $protocolo  = (!empty($_SERVER['HTTPS'])) ? 'https' : 'http';
        $host       = $_SERVER['HTTP_HOST'];
        $rutaBase   = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
        return $protocolo . '://' . $host . $rutaBase . '/uploads/' . $carpeta . '/' . $nombreArchivo;
    }
    return null;
}
