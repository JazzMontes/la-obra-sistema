<?php
// Consulta el detalle de recetas.
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

if ($currentUser["role"] !== "admin") {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tienes permisos para realizar esta acción"]);
    exit;
}

$productId = $_GET["product_id"] ?? null;
if (filter_var($productId, FILTER_VALIDATE_INT) === false || (int)$productId <= 0) {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "El id del producto no es válido"]);
    exit;
}
$productId = (int)$productId;
$stmt = $pdo->prepare("SELECT id,name,type FROM products WHERE id=:id");
$stmt->execute([":id" => $productId]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Producto no encontrado"]);
    exit;
}
if ($p["type"] !== "prepared") {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "El producto no es de tipo prepared"]);
    exit;
}
$stmt = $pdo->prepare("SELECT i.id,i.name,r.quantity,i.unit FROM recipes r INNER JOIN inputs i ON r.input_id=i.id WHERE r.product_id=:id ORDER BY i.name ASC");
$stmt->execute([":id" => $productId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$rows) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "El producto no tiene receta registrada"]);
    exit;
}
foreach ($rows as &$r) {
    $r["id"] = (int)$r["id"];
    $r["quantity"] = (float)$r["quantity"];
}
unset($r);
echo json_encode(["success" => true, "recipe" => ["product" => ["id" => (int)$p["id"], "name" => $p["name"]], "inputs" => $rows]]);
