<?php
// api/config/database.php

function getConexion() {
    $host = "localhost";
    $dbname = "name_db";
    $user = "user";
    $password = "password";
    $charset = "utf8mb4";

    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

    try {
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]);

        return $pdo;

    } catch (PDOException $e) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Error de conexión a la base de datos"
            // "error" => $e->getMessage() // (activar solo en desarrollo)
        ]);

        exit;
    }
}