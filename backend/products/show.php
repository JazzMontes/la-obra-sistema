<?php
// Consulta el detalle de productos.
header("Content-Type: application/json");
require_once __DIR__ . "/../config/cors.php";
require_once __DIR__ . "/../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
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

if (!in_array($currentUser["role"], ["admin", "employee"], true)) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tienes permisos para realizar esta acción"]);
    exit;
}

$id = $_GET["id"] ?? null;
if (filter_var($id, FILTER_VALIDATE_INT) === false || (int)$id <= 0) {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "El id del producto no es válido"]);
    exit;
}
$id = (int)$id;
$stmt = $pdo->prepare("SELECT p.id,p.name,p.description,p.type,p.price,p.is_available,p.status,c.id AS category_id,c.name AS category_name FROM products p INNER JOIN categories c ON p.category_id=c.id WHERE p.id=:id LIMIT 1");
$stmt->execute([":id" => $id]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$r) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Producto no encontrado"]);
    exit;
}
$product = ["id" => (int)$r["id"], "name" => $r["name"], "description" => $r["description"], "category" => ["id" => (int)$r["category_id"], "name" => $r["category_name"]], "type" => $r["type"], "price" => (float)$r["price"], "is_available" => (bool)$r["is_available"], "status" => $r["status"]];
echo json_encode(["success" => true, "product" => $product]);
