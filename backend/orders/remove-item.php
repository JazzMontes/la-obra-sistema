<?php
// Quita elementos de pedidos.
header("Content-Type: application/json");
require_once __DIR__ . "/../config/cors.php";
require_once __DIR__ . "/../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {
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

$data=json_decode(file_get_contents("php://input"),true);$orderId=$data["order_id"]??null;$productId=$data["product_id"]??null;
if(filter_var($orderId,FILTER_VALIDATE_INT)===false||(int)$orderId<=0||filter_var($productId,FILTER_VALIDATE_INT)===false||(int)$productId<=0){http_response_code(422);echo json_encode(["success"=>false,"message"=>"Datos inválidos"]);exit;}$orderId=(int)$orderId;$productId=(int)$productId;
$stmt=$pdo->prepare("SELECT order_status FROM orders WHERE id=:id");$stmt->execute([":id"=>$orderId]);$o=$stmt->fetch(PDO::FETCH_ASSOC);if(!$o){http_response_code(404);echo json_encode(["success"=>false,"message"=>"Pedido no encontrado"]);exit;}if($o["order_status"]!=="open"){http_response_code(409);echo json_encode(["success"=>false,"message"=>"El pedido ya no puede modificarse"]);exit;}
$stmt=$pdo->prepare("SELECT id FROM order_items WHERE order_id=:o AND product_id=:p");$stmt->execute([":o"=>$orderId,":p"=>$productId]);if(!$stmt->fetch()){http_response_code(404);echo json_encode(["success"=>false,"message"=>"El producto no está en el pedido"]);exit;}
try{$pdo->beginTransaction();$stmt=$pdo->prepare("DELETE FROM order_items WHERE order_id=:o AND product_id=:p");$stmt->execute([":o"=>$orderId,":p"=>$productId]);$stmt=$pdo->prepare("UPDATE orders SET total=(SELECT COALESCE(SUM(subtotal),0) FROM order_items WHERE order_id=:oid1) WHERE id=:oid2");$stmt->execute([":oid1"=>$orderId,":oid2"=>$orderId]);$pdo->commit();echo json_encode(["success"=>true,"message"=>"Producto eliminado del pedido"]);}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();http_response_code(500);echo json_encode(["success"=>false,"message"=>"No fue posible eliminar el producto"]);}
