<?php
// Registra pagos de ventas.
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
if (!in_array($currentUser["role"], ["admin", "employee"], true)) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "No tienes permisos para realizar esta acción"]);
    exit;
}

$data=json_decode(file_get_contents("php://input"),true);$orderId=$data["order_id"]??null;$methodId=$data["payment_method_id"]??null;if(filter_var($orderId,FILTER_VALIDATE_INT)===false||(int)$orderId<=0||filter_var($methodId,FILTER_VALIDATE_INT)===false||(int)$methodId<=0){http_response_code(422);echo json_encode(["success"=>false,"message"=>"Datos de pago inválidos"]);exit;}$orderId=(int)$orderId;$methodId=(int)$methodId;
$stmt=$pdo->prepare("SELECT id,total,order_status,payment_status FROM orders WHERE id=:id");$stmt->execute([":id"=>$orderId]);$o=$stmt->fetch(PDO::FETCH_ASSOC);if(!$o){http_response_code(404);echo json_encode(["success"=>false,"message"=>"Pedido no encontrado"]);exit;}if($o["order_status"]!=="confirmed"){http_response_code(409);echo json_encode(["success"=>false,"message"=>"El pedido debe estar confirmado antes de registrar el pago"]);exit;}if($o["payment_status"]==="paid"){http_response_code(409);echo json_encode(["success"=>false,"message"=>"El pedido ya está pagado"]);exit;}
$stmt=$pdo->prepare("SELECT id,name,status FROM payment_methods WHERE id=:id");$stmt->execute([":id"=>$methodId]);$m=$stmt->fetch(PDO::FETCH_ASSOC);if(!$m){http_response_code(404);echo json_encode(["success"=>false,"message"=>"Método de pago no encontrado"]);exit;}if($m["status"]!=="active"){http_response_code(409);echo json_encode(["success"=>false,"message"=>"El método de pago está inactivo"]);exit;}
$total=(float)$o["total"];$cash=null;$change=null;if(mb_strtolower(trim($m["name"]),'UTF-8')==='efectivo'){$cash=$data["cash_received"]??null;if(!is_numeric($cash)||(float)$cash<$total){http_response_code(422);echo json_encode(["success"=>false,"message"=>"El efectivo recibido debe ser igual o mayor al total"]);exit;}$cash=(float)$cash;$change=$cash-$total;}
try{$pdo->beginTransaction();$stmt=$pdo->prepare("INSERT INTO payments(order_id,payment_method_id,amount,cash_received,change_amount) VALUES(:o,:m,:a,:c,:ch)");$stmt->execute([":o"=>$orderId,":m"=>$methodId,":a"=>$total,":c"=>$cash,":ch"=>$change]);$stmt=$pdo->prepare("UPDATE orders SET payment_status='paid' WHERE id=:id");$stmt->execute([":id"=>$orderId]);$pdo->commit();echo json_encode(["success"=>true,"message"=>"Pago registrado correctamente","payment"=>["order_id"=>$orderId,"payment_method"=>$m["name"],"amount"=>$total,"cash_received"=>$cash,"change_amount"=>$change]]);}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();http_response_code(500);echo json_encode(["success"=>false,"message"=>"No fue posible registrar el pago"]);}
