<?php
// Desactiva insumos.
header("Content-Type: application/json");
require_once __DIR__ . "/../config/cors.php";
require_once __DIR__ . "/../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "PATCH") {
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

if ($currentUser["role"] !== "admin") {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tienes permisos para realizar esta acción"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = $data["id"] ?? null;
if (filter_var($id, FILTER_VALIDATE_INT) === false || (int)$id <= 0) {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "El id no es válido"]);
    exit;
}
$id = (int)$id;
$stmt = $pdo->prepare("SELECT status FROM inputs WHERE id=:id");
$stmt->execute([":id" => $id]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$r) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Insumo no encontrado"]);
    exit;
}
if ($r["status"] !== "active") {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "El insumo ya está inactivo"]);
    exit;
}
$stmt = $pdo->prepare("UPDATE inputs SET status='inactive' WHERE id=:id");
$stmt->execute([":id" => $id]);
echo json_encode(["success" => true, "message" => "Insumo desactivado correctamente"]);
