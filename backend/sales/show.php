<?php
// Consulta el detalle de ventas.
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

$id=$_GET["id"]??null;if(filter_var($id,FILTER_VALIDATE_INT)===false||(int)$id<=0){http_response_code(422);echo json_encode(["success"=>false,"message"=>"El id no es válido"]);exit;}$id=(int)$id;
$stmt=$pdo->prepare("SELECT o.*,u.name AS user_name,p.id AS payment_id,p.amount,p.cash_received,p.change_amount,p.paid_at,pm.id AS payment_method_id,pm.name AS payment_method FROM orders o INNER JOIN users u ON u.id=o.user_id LEFT JOIN payments p ON p.order_id=o.id LEFT JOIN payment_methods pm ON pm.id=p.payment_method_id WHERE o.id=:id");$stmt->execute([":id"=>$id]);$o=$stmt->fetch(PDO::FETCH_ASSOC);if(!$o){http_response_code(404);echo json_encode(["success"=>false,"message"=>"Venta o pedido no encontrado"]);exit;}if($currentUser["role"]==="employee" && ((int)$o["user_id"]!==(int)$currentUser["id"] || date("Y-m-d",strtotime($o["created_at"]))!==date("Y-m-d"))){http_response_code(403);echo json_encode(["success"=>false,"message"=>"Solo puedes consultar tus pedidos del día"]);exit;}$stmt=$pdo->prepare("SELECT oi.product_id,p.name,oi.quantity,oi.unit_price,oi.subtotal FROM order_items oi INNER JOIN products p ON p.id=oi.product_id WHERE oi.order_id=:id ORDER BY oi.id");$stmt->execute([":id"=>$id]);$items=$stmt->fetchAll(PDO::FETCH_ASSOC);foreach($items as &$i){$i["product_id"]=(int)$i["product_id"];$i["quantity"]=(int)$i["quantity"];$i["unit_price"]=(float)$i["unit_price"];$i["subtotal"]=(float)$i["subtotal"];}unset($i);
echo json_encode(["success"=>true,"sale"=>["id"=>(int)$o["id"],"user"=>["id"=>(int)$o["user_id"],"name"=>$o["user_name"]],"order_status"=>$o["order_status"],"payment_status"=>$o["payment_status"],"delivery_status"=>$o["delivery_status"],"total"=>(float)$o["total"],"created_at"=>$o["created_at"],"confirmed_at"=>$o["confirmed_at"],"delivered_at"=>$o["delivered_at"],"cancelled_at"=>$o["cancelled_at"],"items"=>$items,"payment"=>$o["payment_id"]===null?null:["id"=>(int)$o["payment_id"],"payment_method_id"=>(int)$o["payment_method_id"],"payment_method"=>$o["payment_method"],"amount"=>(float)$o["amount"],"cash_received"=>$o["cash_received"]===null?null:(float)$o["cash_received"],"change_amount"=>$o["change_amount"]===null?null:(float)$o["change_amount"],"paid_at"=>$o["paid_at"]]]]);
