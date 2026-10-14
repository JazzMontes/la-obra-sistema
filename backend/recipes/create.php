<?php
// Registra un nuevo recetas.
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
$productId = $data["product_id"] ?? null;
$inputs = $data["inputs"] ?? null;
if (filter_var($productId, FILTER_VALIDATE_INT) === false || (int)$productId <= 0 || !is_array($inputs) || count($inputs) === 0) {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "Producto e insumos válidos son obligatorios"]);
    exit;
}
$productId = (int)$productId;
$stmt = $pdo->prepare("SELECT type FROM products WHERE id=:id");
$stmt->execute([":id" => $productId]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Producto no encontrado"]);
    exit;
}
if ($p["type"] !== "prepared") {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "Solo los productos preparados pueden tener receta"]);
    exit;
}
$stmt = $pdo->prepare("SELECT id FROM recipes WHERE product_id=:id LIMIT 1");
$stmt->execute([":id" => $productId]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(["success" => false, "message" => "El producto ya tiene una receta registrada"]);
    exit;
}
try {
    $pdo->beginTransaction();
    $insert = $pdo->prepare("INSERT INTO recipes(product_id,input_id,quantity) VALUES(:product_id,:input_id,:quantity)");
    $check = $pdo->prepare("SELECT status FROM inputs WHERE id=:id");
    foreach ($inputs as $item) {
        $inputId = $item["input_id"] ?? null;
        $qty = $item["quantity"] ?? null;
        if (filter_var($inputId, FILTER_VALIDATE_INT) === false || (int)$inputId <= 0 || !is_numeric($qty) || (float)$qty <= 0) {
            throw new InvalidArgumentException("Datos de insumo inválidos");
        }
        $inputId = (int)$inputId;
        $check->execute([":id" => $inputId]);
        $i = $check->fetch(PDO::FETCH_ASSOC);
        if (!$i) {
            throw new RuntimeException("Insumo no encontrado: " . $inputId);
        }
        if ($i["status"] !== "active") {
            throw new InvalidArgumentException("El insumo " . $inputId . " está inactivo");
        }
        $insert->execute([":product_id" => $productId, ":input_id" => $inputId, ":quantity" => (float)$qty]);
    }
    $pdo->commit();
    http_response_code(201);
    echo json_encode(["success" => true, "message" => "Receta registrada correctamente"]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code($e instanceof RuntimeException ? 404 : 422);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
