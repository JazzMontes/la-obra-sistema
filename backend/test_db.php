<?php
// Comprueba la conexión a la base de datos de .

require_once __DIR__ . "/../config/cors.php";
require_once __DIR__ . '/config/database.php';

// Enviar respuesta
echo json_encode([
    "success" => true,
    "message" => "Conexión correcta con la base de datos"
]);