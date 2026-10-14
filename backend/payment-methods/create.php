<?php
// Registra un nuevo métodos de pago.
header("Content-Type: application/json");
require_once __DIR__ . "/../config/cors.php";
require_once __DIR__ . "/../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

// Enviar respuesta
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

// Validar autenticación
$headers = getallheaders();
$authorization = $headers["Authorization"] ?? "";
if (!preg_match('/Bearer\s+(\S+)/', $authorization, $matches)) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Token de autenticación requerido"]);
    exit;
}
$tokenHash = hash("sha256", $matches[1]);

// Consultar o preparar datos
$stmt = $pdo->prepare("SELECT u.id, u.status, u.role, s.expires_at FROM sessions s INNER JOIN users u ON s.user_id=u.id WHERE s.token_hash=:token_hash LIMIT 1");
$stmt->execute([":token_hash" => $tokenHash]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Sesión inválida"]);
    exit;
}
if (strtotime($currentUser["expires_at"]) <= time()) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "La sesión ha expirado"]);
    exit;
}
if ($currentUser["status"] !== "active") {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "La cuenta no tiene acceso al sistema"]);
    exit;
}
if (!in_array($currentUser["role"], ["admin"], true)) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tienes permisos para realizar esta acción"]);
    exit;
}

$data=json_decode(file_get_contents("php://input"),true);$name=trim($data["name"]??"");if($name===""){http_response_code(422);echo json_encode(["success"=>false,"message"=>"El nombre es obligatorio"]);exit;}$stmt=$pdo->prepare("SELECT id FROM payment_methods WHERE name=:name LIMIT 1");$stmt->execute([":name"=>$name]);if($stmt->fetch()){http_response_code(409);echo json_encode(["success"=>false,"message"=>"El método de pago ya existe"]);exit;}$stmt=$pdo->prepare("INSERT INTO payment_methods(name,status) VALUES(:name,'active')");$stmt->execute([":name"=>$name]);http_response_code(201);echo json_encode(["success"=>true,"message"=>"Método de pago registrado correctamente","payment_method"=>["id"=>(int)$pdo->lastInsertId(),"name"=>$name,"status"=>"active"]]);
