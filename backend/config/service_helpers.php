<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
function out($code, $message, $extra = [])
{
    http_response_code($code);
    echo json_encode(array_merge(['success' => $code < 400, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}
function method($m)
{
    if ($_SERVER['REQUEST_METHOD'] !== $m) out(405, 'Método no permitido');
}
function body()
{
    $x = json_decode(file_get_contents('php://input'), true);
    if (!is_array($x)) out(422, 'JSON inválido');
    return $x;
}
function positiveId($v)
{
    if (!is_scalar($v) || filter_var($v, FILTER_VALIDATE_INT) === false || (int)$v <= 0) out(422, 'ID inválido');
    return (int)$v;
}
function money($v, $positive = true)
{
    if (!is_scalar($v) || !is_numeric($v) || !is_finite((float)$v) || ($positive ? (float)$v <= 0 : (float)$v < 0) || round((float)$v, 2) != (float)$v) out(422, 'Monto o costo inválido');
    return number_format((float)$v, 2, '.', '');
}
function dateval($v)
{
    if (!is_string($v) || !($d = DateTime::createFromFormat('!Y-m-d', $v)) || $d->format('Y-m-d') !== $v) out(422, 'Fecha inválida');
    return $v;
}
function user($admin = false)
{
    global $pdo;
    $h = function_exists('getallheaders') ? getallheaders() : [];
    $a = '';
    foreach ($h as $k => $v) if (strtolower($k) === 'authorization') $a = $v;
    if (!$a && isset($_SERVER['HTTP_AUTHORIZATION'])) $a = $_SERVER['HTTP_AUTHORIZATION'];
    if (!preg_match('/^Bearer\s+(\S+)$/i', $a, $m)) out(401, 'Token de autenticación requerido');
    $s = $pdo->prepare('SELECT u.id,u.role,u.status,s.expires_at FROM sessions s JOIN users u ON u.id=s.user_id WHERE s.token_hash=? LIMIT 1');
    $s->execute([hash('sha256', $m[1])]);
    $u = $s->fetch(PDO::FETCH_ASSOC);
    if (!$u || strtotime($u['expires_at']) <= time()) out(401, 'Sesión inválida o expirada');
    if ($u['status'] !== 'active' || !in_array($u['role'], ['admin', 'employee'], true)) out(403, 'Cuenta sin acceso');
    if ($admin && $u['role'] !== 'admin') out(403, 'Se requiere rol administrador');
    return $u;
}
function record($sql, $params = [])
{
    global $pdo;
    $s = $pdo->prepare($sql);
    $s->execute($params);
    return $s->fetch(PDO::FETCH_ASSOC);
}
function rows($sql, $params = [])
{
    global $pdo;
    $s = $pdo->prepare($sql);
    $s->execute($params);
    return $s->fetchAll(PDO::FETCH_ASSOC);
}
function auditJson($v)
{
    return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
}
function purchase($id, $lock = false)
{
    $p = record('SELECT p.*,u.name AS user_name,pm.name AS payment_method FROM purchases p JOIN users u ON u.id=p.user_id JOIN payment_methods pm ON pm.id=p.payment_method_id WHERE p.id=?' . ($lock ? ' FOR UPDATE' : ''), [$id]);
    if (!$p) out(404, 'Compra no encontrada');
    $p['items'] = rows('SELECT pi.id,pi.input_id,i.name AS input_name,i.unit,pi.quantity,pi.unit_cost,pi.subtotal FROM purchase_items pi JOIN inputs i ON i.id=pi.input_id WHERE pi.purchase_id=? ORDER BY pi.id', [$id]);
    return $p;
}
function expense($id, $lock = false)
{
    $e = record('SELECT e.*,u.name AS user_name FROM expenses e JOIN users u ON u.id=e.user_id WHERE e.id=?' . ($lock ? ' FOR UPDATE' : ''), [$id]);
    if (!$e) out(404, 'Gasto no encontrado');
    return $e;
}
function expenseAccess($e, $u)
{
    if ($u['role'] !== 'admin' && (int)$e['user_id'] !== (int)$u['id']) out(403, 'No puedes consultar o modificar gastos de otra persona');
}
function expenseEditable($e)
{
    if ($e['status'] !== 'active') out(409, 'El gasto ya está cancelado');/* PENDIENTE: validar corte abierto al implementar cortes de caja. */
}
function validItems($items)
{
    global $pdo;
    if (!is_array($items) || !count($items)) out(422, 'Debes enviar al menos un insumo');
    $clean = [];
    $seen = [];
    $total = 0;
    foreach ($items as $item) {
        if (!is_array($item)) out(422, 'Insumo inválido');
        $id = positiveId($item['input_id'] ?? null);
        if (isset($seen[$id])) out(422, 'No repitas el mismo insumo en una compra');
        $seen[$id] = true;
        $input = record('SELECT id FROM inputs WHERE id=? AND status=\'active\' ', [$id]);
        if (!$input) out(404, 'Insumo no encontrado o inactivo');
        $q = $item['quantity'] ?? null;
        if (!is_scalar($q) || !is_numeric($q) || (float)$q <= 0 || round((float)$q, 3) != (float)$q) out(422, 'Cantidad inválida');
        $cost = money($item['unit_cost'] ?? null, false);
        $sub = round((float)$q * (float)$cost, 2);
        $total += $sub;
        $clean[] = ['input_id' => $id, 'quantity' => number_format((float)$q, 3, '.', ''), 'unit_cost' => $cost, 'subtotal' => number_format($sub, 2, '.', '')];
    }
    if ($total > 9999999999.99) out(422, 'Total excede el límite');
    return [$clean, number_format($total, 2, '.', '')];
}
function validPurchase($d)
{
    $date = dateval($d['purchase_date'] ?? null);
    $supplier = $d['supplier'] ?? null;
    if ($supplier !== null && (!is_string($supplier) || mb_strlen($supplier) > 150)) out(422, 'Proveedor inválido');
    $pm = positiveId($d['payment_method_id'] ?? null);
    if (!record('SELECT id FROM payment_methods WHERE id=? AND status=\'active\' ', [$pm])) out(404, 'Método de pago inexistente o inactivo');
    [$items, $total] = validItems($d['items'] ?? null);
    return [$date, $supplier, $pm, $items, $total];
}
function insertItems($id, $items)
{
    global $pdo;
    $s = $pdo->prepare('INSERT INTO purchase_items(purchase_id,input_id,quantity,unit_cost,subtotal) VALUES(?,?,?,?,?)');
    foreach ($items as $i) $s->execute([$id, $i['input_id'], $i['quantity'], $i['unit_cost'], $i['subtotal']]);
}
function failure($e)
{
    global $pdo;
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage());
    out(500, 'No se pudo completar la operación');
}
