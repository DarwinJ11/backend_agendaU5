<?php
// api/auth/editar.php

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

// Leer datos (puede venir como JSON o multipart)
$datos = json_decode(file_get_contents('php://input'), true);
if (!$datos) {
    $datos = $_POST;
}

$nombre_de_usuario = isset($datos['nombre_de_usuario']) ? trim($datos['nombre_de_usuario']) : '';
$password_nuevo    = isset($datos['password']) ? $datos['password'] : '';

if (empty($nombre_de_usuario)) {
    echo json_encode([
        "success" => false,
        "message" => "El nombre de usuario es obligatorio"
    ]);
    exit;
}

// Verificar que el nombre no esté tomado por otro usuario
$stmt = $pdo->prepare(
    "SELECT id FROM usuarios WHERE nombre_de_usuario = ? AND id != ? LIMIT 1"
);
$stmt->execute([$nombre_de_usuario, $usuario['id']]);
if ($stmt->fetch()) {
    echo json_encode([
        "success" => false,
        "message" => "El nombre de usuario ya está en uso"
    ]);
    exit;
}

// Manejar foto
$fotoUrl = $usuario['foto'];
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $nueva = subirImagen($_FILES['foto'], 'usuarios');
    if ($nueva) {
        $fotoUrl = $nueva;
    }
}

// Actualizar datos
if (!empty($password_nuevo)) {
    if (strlen($password_nuevo) < 6) {
        echo json_encode([
            "success" => false,
            "message" => "La contraseña debe tener al menos 6 caracteres"
        ]);
        exit;
    }
    $hash = password_hash($password_nuevo, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare(
        "UPDATE usuarios SET nombre_de_usuario = ?, password = ?, foto = ? WHERE id = ?"
    );
    $stmt->execute([$nombre_de_usuario, $hash, $fotoUrl, $usuario['id']]);
} else {
    $stmt = $pdo->prepare(
        "UPDATE usuarios SET nombre_de_usuario = ?, foto = ? WHERE id = ?"
    );
    $stmt->execute([$nombre_de_usuario, $fotoUrl, $usuario['id']]);
}

// Devolver datos actualizados
$stmt = $pdo->prepare("SELECT id, nombre_de_usuario, foto FROM usuarios WHERE id = ?");
$stmt->execute([$usuario['id']]);
$actualizado = $stmt->fetch();

echo json_encode([
    "success" => true,
    "message" => "Perfil actualizado correctamente",
    "usuario" => [
        "id"                => $actualizado['id'],
        "nombre_de_usuario" => htmlspecialchars($actualizado['nombre_de_usuario']),
        "foto"              => $actualizado['foto']
    ]
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
