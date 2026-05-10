<?php
// api/contactos/crear.php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

$usuario = verificarToken();
$pdo     = getConexion();

// Soportar JSON y multipart/form-data
$datos = json_decode(file_get_contents('php://input'), true);
if (!$datos) {
    $datos = $_POST;
}

$nombre    = isset($datos['nombre'])    ? trim($datos['nombre'])    : '';
$apellido  = isset($datos['apellido'])  ? trim($datos['apellido'])  : null;
$telefono  = isset($datos['telefono'])  ? trim($datos['telefono'])  : '';
$email     = isset($datos['email'])     ? trim($datos['email'])     : null;
$direccion = isset($datos['direccion']) ? trim($datos['direccion']) : null;
$notas     = isset($datos['notas'])     ? trim($datos['notas'])     : null;

// Validaciones
if (empty($nombre)) {
    echo json_encode(["success" => false, "message" => "El nombre es obligatorio"]);
    exit;
}
if (empty($telefono)) {
    echo json_encode(["success" => false, "message" => "El teléfono es obligatorio"]);
    exit;
}
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "El email no es válido"]);
    exit;
}

// Foto
$fotoUrl = null;
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $fotoUrl = subirImagen($_FILES['foto'], 'contactos');
}

$stmt = $pdo->prepare(
    "INSERT INTO contactos (usuario_id, nombre, apellido, telefono, email, direccion, notas, foto)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->execute([
    $usuario['id'], $nombre, $apellido, $telefono, $email, $direccion, $notas, $fotoUrl
]);

echo json_encode([
    "success" => true,
    "message" => "Contacto creado correctamente",
    "id"      => $pdo->lastInsertId()
]);

function subirImagen($archivo, $carpeta) {
    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $extensionesPermitidas)) {
        return null;
    }
    $nombreArchivo = uniqid() . '_' . time() . '.' . $extension;
    $rutaDestino   = __DIR__ . '/../uploads/' . $carpeta . '/' . $nombreArchivo;
    if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        $protocolo = (!empty($_SERVER['HTTPS'])) ? 'https' : 'http';
        $host      = $_SERVER['HTTP_HOST'];
        $rutaBase  = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
        return $protocolo . '://' . $host . $rutaBase . '/uploads/' . $carpeta . '/' . $nombreArchivo;
    }
    return null;
}
