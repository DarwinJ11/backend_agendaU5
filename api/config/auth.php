<?php
// api/config/auth.php

require_once __DIR__ . '/database.php';

function verificarToken() {
    $headers = getallheaders();

    if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Token requerido"
        ]);
        exit;
    }

    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : $headers['authorization'];
    $token = str_replace('Bearer ', '', $authHeader);
    $token = trim($token);

    if (empty($token)) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Token inválido"
        ]);
        exit;
    }

    $pdo = getConexion();

    $stmt = $pdo->prepare(
        "SELECT id, nombre_de_usuario, foto, token_expiracion
         FROM usuarios
         WHERE token = ?
         LIMIT 1"
    );
    $stmt->execute([$token]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Token inválido o expirado"
        ]);
        exit;
    }

    // Verificar expiración
    if (new DateTime() > new DateTime($usuario['token_expiracion'])) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Token expirado"
        ]);
        exit;
    }

    return $usuario;
}
