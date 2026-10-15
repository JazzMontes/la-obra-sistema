<?php

$host = "localhost";
$dbname = "la_obra_db";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {// Establece la conexión con la base de datos de configuración.

    http_response_code(500);

// Enviar respuesta
    echo json_encode([
        "success" => false,
        "message" => "Error de conexión con la base de datos"
    ]);

    exit;
}