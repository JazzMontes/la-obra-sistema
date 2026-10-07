<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../config/cors.php";
require_once __DIR__ . "/../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
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
if (!in_array($currentUser["role"], ["admin", "employee"], true)) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tienes permisos para realizar esta acción"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$orderId = $data["order_id"] ?? null; $productId = $data["product_id"] ?? null; $quantity = $data["quantity"] ?? null;
if (filter_var($orderId,FILTER_VALIDATE_INT)===false || (int)$orderId<=0 || filter_var($productId,FILTER_VALIDATE_INT)===false || (int)$productId<=0 || filter_var($quantity,FILTER_VALIDATE_INT)===false || (int)$quantity<=0) { http_response_code(422); echo json_encode(["success"=>false,"message"=>"Los datos del producto no son válidos"]); exit; }
$orderId=(int)$orderId; $productId=(int)$productId; $quantity=(int)$quantity;
$stmt=$pdo->prepare("SELECT id,order_status FROM orders WHERE id=:id LIMIT 1"); $stmt->execute([":id"=>$orderId]); $order=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$order){http_response_code(404);echo json_encode(["success"=>false,"message"=>"Pedido no encontrado"]);exit;}
if($order["order_status"]!=="open"){http_response_code(409);echo json_encode(["success"=>false,"message"=>"El pedido ya no puede modificarse"]);exit;}
$stmt=$pdo->prepare("SELECT id,price,status,is_available FROM products WHERE id=:id LIMIT 1"); $stmt->execute([":id"=>$productId]); $product=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$product){http_response_code(404);echo json_encode(["success"=>false,"message"=>"Producto no encontrado"]);exit;}
if($product["status"]!=="active" || !(bool)$product["is_available"]){http_response_code(422);echo json_encode(["success"=>false,"message"=>"El producto no está disponible"]);exit;}
$price=(float)$product["price"];
try{$pdo->beginTransaction();
$stmt=$pdo->prepare("SELECT id FROM order_items WHERE order_id=:order_id AND product_id=:product_id LIMIT 1");$stmt->execute([":order_id"=>$orderId,":product_id"=>$productId]);
if($stmt->fetch()){ $pdo->rollBack(); http_response_code(409); echo json_encode(["success"=>false,"message"=>"El producto ya está en el pedido; modifica su cantidad"]); exit; }
$subtotal=$price*$quantity;
$stmt=$pdo->prepare("INSERT INTO order_items(order_id,product_id,quantity,unit_price,subtotal) VALUES(:order_id,:product_id,:quantity,:unit_price,:subtotal)");$stmt->execute([":order_id"=>$orderId,":product_id"=>$productId,":quantity"=>$quantity,":unit_price"=>$price,":subtotal"=>$subtotal]);
$stmt=$pdo->prepare("UPDATE orders SET total=(SELECT COALESCE(SUM(subtotal),0) FROM order_items WHERE order_id=:oid1) WHERE id=:oid2");$stmt->execute([":oid1"=>$orderId,":oid2"=>$orderId]);
$pdo->commit(); http_response_code(201); echo json_encode(["success"=>true,"message"=>"Producto agregado al pedido","item"=>["product_id"=>$productId,"quantity"=>$quantity,"unit_price"=>$price,"subtotal"=>$subtotal]]);
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();http_response_code(500);echo json_encode(["success"=>false,"message"=>"No fue posible agregar el producto"]);}
