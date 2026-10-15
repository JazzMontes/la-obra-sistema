<?php
// Consulta los registros activos de pedidos.
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

$stmt=$pdo->query("SELECT o.id,o.total,o.order_status,o.payment_status,o.delivery_status,o.created_at,o.confirmed_at,u.name AS user_name FROM orders o INNER JOIN users u ON u.id=o.user_id WHERE o.order_status='confirmed' AND (o.payment_status='pending' OR o.delivery_status='pending') ORDER BY o.created_at ASC");$orders=$stmt->fetchAll(PDO::FETCH_ASSOC);
$itemStmt=$pdo->prepare("SELECT oi.product_id,p.name,oi.quantity,oi.unit_price,oi.subtotal FROM order_items oi INNER JOIN products p ON p.id=oi.product_id WHERE oi.order_id=:id ORDER BY oi.id");foreach($orders as &$o){$o["id"]=(int)$o["id"];$o["total"]=(float)$o["total"];$itemStmt->execute([":id"=>$o["id"]]);$o["items"]=$itemStmt->fetchAll(PDO::FETCH_ASSOC);foreach($o["items"] as &$i){$i["product_id"]=(int)$i["product_id"];$i["quantity"]=(int)$i["quantity"];$i["unit_price"]=(float)$i["unit_price"];$i["subtotal"]=(float)$i["subtotal"];}unset($i);}unset($o);echo json_encode(["success"=>true,"orders"=>$orders]);
