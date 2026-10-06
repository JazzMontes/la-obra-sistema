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
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Receta no encontrada"]);
    exit;
}
try {
    $pdo->beginTransaction();
    $check = $pdo->prepare("SELECT status FROM inputs WHERE id=:id");
    foreach ($inputs as $item) {
        $iid = $item["input_id"] ?? null;
        $q = $item["quantity"] ?? null;
        if (filter_var($iid, FILTER_VALIDATE_INT) === false || (int)$iid <= 0 || !is_numeric($q) || (float)$q <= 0) {
            throw new InvalidArgumentException("Datos de insumo inválidos");
        }
        $check->execute([":id" => (int)$iid]);
        $i = $check->fetch(PDO::FETCH_ASSOC);
        if (!$i) {
            throw new RuntimeException("Insumo no encontrado: " . $iid);
        }
        if ($i["status"] !== "active") {
            throw new InvalidArgumentException("El insumo " . $iid . " está inactivo");
        }
    }
    $pdo->prepare("DELETE FROM recipes WHERE product_id=:id")->execute([":id" => $productId]);
    $insert = $pdo->prepare("INSERT INTO recipes(product_id,input_id,quantity) VALUES(:product_id,:input_id,:quantity)");
    foreach ($inputs as $item) {
        $insert->execute([":product_id" => $productId, ":input_id" => (int)$item["input_id"], ":quantity" => (float)$item["quantity"]]);
    }
    $pdo->commit();
    echo json_encode(["success" => true, "message" => "Receta actualizada correctamente"]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code($e instanceof RuntimeException ? 404 : 422);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
