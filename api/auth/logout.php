<?php
// api/auth/logout.php

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

// Limpiar token
$stmt = $pdo->prepare(
    "UPDATE usuarios SET token = NULL, token_expiracion = NULL WHERE id = ?"
);
$stmt->execute([$usuario['id']]);

echo json_encode([
    "success" => true,
    "message" => "Sesión cerrada correctamente"
]);
