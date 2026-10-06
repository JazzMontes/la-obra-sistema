<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/cors.php";
require_once __DIR__ . "/../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "PATCH") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

$headers = getallheaders();
$authorization = $headers["Authorization"] ?? "";
if (!preg_match('/Bearer\s+(\S+)/', $authorization, $matches)) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Token de autenticación requerido"]);
    exit;
}
$tokenHash = hash("sha256", $matches[1]);
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
if (!is_array($data)) {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "Datos inválidos"]);
    exit;
}
$id = $data["id"] ?? null;
if (filter_var($id, FILTER_VALIDATE_INT) === false || (int)$id <= 0) {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "El id no es válido"]);
    exit;
}
$id = (int)$id;
$stmt = $pdo->prepare("SELECT * FROM products WHERE id=:id");
$stmt->execute([":id" => $id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Producto no encontrado"]);
    exit;
}
$fields = [];
$params = [":id" => $id];
if (array_key_exists("name", $data)) {
    $v = trim($data["name"] ?? "");
    if ($v === "") {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "Nombre inválido"]);
        exit;
    }
    $fields[] = "name=:name";
    $params[":name"] = $v;
}
if (array_key_exists("description", $data)) {
    $v = trim($data["description"] ?? "");
    if ($v === "") {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "Descripción inválida"]);
        exit;
    }
    $fields[] = "description=:description";
    $params[":description"] = $v;
}
if (array_key_exists("category_id", $data)) {
    $v = $data["category_id"];
    if (filter_var($v, FILTER_VALIDATE_INT) === false || (int)$v <= 0) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "Categoría inválida"]);
        exit;
    }
    $v = (int)$v;
    $s = $pdo->prepare("SELECT status FROM categories WHERE id=:id");
    $s->execute([":id" => $v]);
    $c = $s->fetch(PDO::FETCH_ASSOC);
    if (!$c) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Categoría no encontrada"]);
        exit;
    }
    if ($c["status"] !== "active") {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "La categoría está inactiva"]);
        exit;
    }
    $fields[] = "category_id=:category_id";
    $params[":category_id"] = $v;
}
if (array_key_exists("type", $data)) {
    $v = trim($data["type"] ?? "");
    if (!in_array($v, ["prepared", "resale"], true)) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "Tipo inválido"]);
        exit;
    }
    $fields[] = "type=:type";
    $params[":type"] = $v;
}
if (array_key_exists("price", $data)) {
    $v = $data["price"];
    if (!is_numeric($v) || (float)$v < 0) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "Precio inválido"]);
        exit;
    }
    $fields[] = "price=:price";
    $params[":price"] = (float)$v;
}
if (array_key_exists("is_available", $data)) {
    $v = filter_var($data["is_available"], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($v === null) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "Disponibilidad inválida"]);
        exit;
    }
    $fields[] = "is_available=:is_available";
    $params[":is_available"] = $v ? 1 : 0;
}
if (!$fields) {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "No se enviaron campos para actualizar"]);
    exit;
}
$stmt = $pdo->prepare("UPDATE products SET " . implode(",", $fields) . " WHERE id=:id");
$stmt->execute($params);
echo json_encode(["success" => true, "message" => "Producto actualizado correctamente"]);
