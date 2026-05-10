<?php
// api/contactos/index.php

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

$stmt = $pdo->prepare(
    "SELECT id, nombre, apellido, telefono, email, direccion, notas, foto, fecha_creacion
     FROM contactos
     WHERE usuario_id = ?
     ORDER BY nombre ASC"
);
$stmt->execute([$usuario['id']]);
$contactos = $stmt->fetchAll();

// Sanitizar salida
$resultado = [];
foreach ($contactos as $c) {
    $resultado[] = [
        "id"             => $c['id'],
        "nombre"         => htmlspecialchars($c['nombre']),
        "apellido"       => htmlspecialchars($c['apellido'] ?? ''),
        "telefono"       => htmlspecialchars($c['telefono']),
        "email"          => htmlspecialchars($c['email'] ?? ''),
        "direccion"      => htmlspecialchars($c['direccion'] ?? ''),
        "notas"          => htmlspecialchars($c['notas'] ?? ''),
        "foto"           => $c['foto'],
        "fecha_creacion" => $c['fecha_creacion']
    ];
}

echo json_encode([
    "success"   => true,
    "contactos" => $resultado
]);
