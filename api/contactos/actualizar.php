<?php
// api/contactos/actualizar.php

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

$datos = json_decode(file_get_contents('php://input'), true);
if (!$datos) {
    $datos = $_POST;
}

$id        = isset($datos['id'])        ? intval($datos['id'])       : 0;
$nombre    = isset($datos['nombre'])    ? trim($datos['nombre'])    : '';
$apellido  = isset($datos['apellido'])  ? trim($datos['apellido'])  : null;
$telefono  = isset($datos['telefono'])  ? trim($datos['telefono'])  : '';
$email     = isset($datos['email'])     ? trim($datos['email'])     : null;
$direccion = isset($datos['direccion']) ? trim($datos['direccion']) : null;
$notas     = isset($datos['notas'])     ? trim($datos['notas'])     : null;

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "ID de contacto inválido"]);
    exit;
}
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

// Verificar que el contacto pertenece al usuario
$stmt = $pdo->prepare("SELECT id, foto FROM contactos WHERE id = ? AND usuario_id = ? LIMIT 1");
$stmt->execute([$id, $usuario['id']]);
$contacto = $stmt->fetch();

if (!$contacto) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Contacto no encontrado"]);
    exit;
}

// Foto
$fotoUrl = $contacto['foto'];
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $nueva = subirImagen($_FILES['foto'], 'contactos');
    if ($nueva) {
        $fotoUrl = $nueva;
    }
}

$stmt = $pdo->prepare(
    "UPDATE contactos
     SET nombre = ?, apellido = ?, telefono = ?, email = ?, direccion = ?, notas = ?, foto = ?
     WHERE id = ? AND usuario_id = ?"
);
$stmt->execute([
    $nombre, $apellido, $telefono, $email, $direccion, $notas, $fotoUrl,
    $id, $usuario['id']
]);

echo json_encode([
    "success" => true,
    "message" => "Contacto actualizado correctamente"
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
