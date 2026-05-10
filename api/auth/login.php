<?php
// api/auth/login.php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

$datos = json_decode(file_get_contents('php://input'), true);
if (!$datos) {
    $datos = $_POST;
}

$nombre_de_usuario = isset($datos['nombre_de_usuario']) ? trim($datos['nombre_de_usuario']) : '';
$password          = isset($datos['password']) ? $datos['password'] : '';

if (empty($nombre_de_usuario) || empty($password)) {
    echo json_encode([
        "success" => false,
        "message" => "El nombre de usuario y la contraseña son obligatorios"
    ]);
    exit;
}

$pdo = getConexion();

// Buscar usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre_de_usuario = ? LIMIT 1");
$stmt->execute([$nombre_de_usuario]);
$usuario = $stmt->fetch();

if (!$usuario || !password_verify($password, $usuario['password'])) {
    echo json_encode([
        "success" => false,
        "message" => "Credenciales incorrectas"
    ]);
    exit;
}

// Generar token
$token           = bin2hex(random_bytes(32));
$expiracion      = date('Y-m-d H:i:s', strtotime('+8 hours'));

// Guardar token en base de datos
$stmt = $pdo->prepare(
    "UPDATE usuarios SET token = ?, token_expiracion = ? WHERE id = ?"
);
$stmt->execute([$token, $expiracion, $usuario['id']]);

echo json_encode([
    "success" => true,
    "token"   => $token,
    "usuario" => [
        "id"                => $usuario['id'],
        "nombre_de_usuario" => htmlspecialchars($usuario['nombre_de_usuario']),
        "foto"              => $usuario['foto']
    ]
]);
