<?php
// api/contactos/eliminar.php

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

$id = isset($datos['id']) ? intval($datos['id']) : 0;

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "ID de contacto inválido"]);
    exit;
}

// Verificar que pertenece al usuario antes de eliminar
$stmt = $pdo->prepare("SELECT id FROM contactos WHERE id = ? AND usuario_id = ? LIMIT 1");
$stmt->execute([$id, $usuario['id']]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Contacto no encontrado"]);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM contactos WHERE id = ? AND usuario_id = ?");
$stmt->execute([$id, $usuario['id']]);

echo json_encode([
    "success" => true,
    "message" => "Contacto eliminado correctamente"
]);
