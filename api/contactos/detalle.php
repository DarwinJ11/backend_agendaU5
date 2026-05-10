<?php
// api/contactos/detalle.php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

$usuario = verificarToken();
$pdo     = getConexion();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "ID de contacto inválido"]);
    exit;
}

// Solo puede ver sus propios contactos
$stmt = $pdo->prepare(
    "SELECT id, nombre, apellido, telefono, email, direccion, notas, foto, fecha_creacion
     FROM contactos
     WHERE id = ? AND usuario_id = ?
     LIMIT 1"
);
$stmt->execute([$id, $usuario['id']]);
$contacto = $stmt->fetch();

if (!$contacto) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Contacto no encontrado"]);
    exit;
}

echo json_encode([
    "success"  => true,
    "contacto" => [
        "id"             => $contacto['id'],
        "nombre"         => htmlspecialchars($contacto['nombre']),
        "apellido"       => htmlspecialchars($contacto['apellido'] ?? ''),
        "telefono"       => htmlspecialchars($contacto['telefono']),
        "email"          => htmlspecialchars($contacto['email'] ?? ''),
        "direccion"      => htmlspecialchars($contacto['direccion'] ?? ''),
        "notas"          => htmlspecialchars($contacto['notas'] ?? ''),
        "foto"           => $contacto['foto'],
        "fecha_creacion" => $contacto['fecha_creacion']
    ]
]);
