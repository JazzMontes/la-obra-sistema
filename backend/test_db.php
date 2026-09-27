<?php

require_once __DIR__ . '/config/database.php';

echo json_encode([
    "success" => true,
    "message" => "Conexión correcta con la base de datos"
]);