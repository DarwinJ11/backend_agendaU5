<?php
// api/auth/perfil.php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

$usuario = verificarToken();

echo json_encode([
    "success" => true,
    "usuario" => [
        "id"                => $usuario['id'],
        "nombre_de_usuario" => htmlspecialchars($usuario['nombre_de_usuario']),
        "foto"              => $usuario['foto']
    ]
]);
