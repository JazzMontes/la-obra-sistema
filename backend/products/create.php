<?php
// Registra un nuevo productos.
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

if ($currentUser["role"] !== "admin") {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tienes permisos para realizar esta acción"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$name = trim($data["name"] ?? "");
$description = trim($data["description"] ?? "");
$categoryId = $data["category_id"] ?? null;
$type = trim($data["type"] ?? "");
$price = $data["price"] ?? null;
if ($name === "" || $description === "" || filter_var($categoryId, FILTER_VALIDATE_INT) === false || (int)$categoryId <= 0 || !in_array($type, ["prepared", "resale"], true) || !is_numeric($price) || (float)$price < 0) {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "Los datos del producto no son válidos"]);
    exit;
}
$categoryId = (int)$categoryId;
$price = (float)$price;
$stmt = $pdo->prepare("SELECT id,status FROM categories WHERE id=:id LIMIT 1");
$stmt->execute([":id" => $categoryId]);
$cat = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cat) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Categoría no encontrada"]);
    exit;
}
if ($cat["status"] !== "active") {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "La categoría está inactiva"]);
    exit;
}
$stmt = $pdo->prepare("INSERT INTO products(name,description,category_id,type,price,is_available,status) VALUES(:name,:description,:category_id,:type,:price,TRUE,'active')");
$stmt->execute([":name" => $name, ":description" => $description, ":category_id" => $categoryId, ":type" => $type, ":price" => $price]);
http_response_code(201);
echo json_encode(["success" => true, "message" => "Producto registrado correctamente", "product" => ["id" => (int)$pdo->lastInsertId(), "name" => $name, "description" => $description, "category_id" => $categoryId, "type" => $type, "price" => $price, "is_available" => true, "status" => "active"]]);
