<?php
// Consulta el listado de compras.
require_once __DIR__.'/../config/service_helpers.php';
method('GET');
$u=user(true);
$where=[];$params=[];foreach(['date_from'=>'>=','date_to'=>'<='] as $k=>$op){if(isset($_GET[$k])&&$_GET[$k]!==''){$where[]='p.purchase_date '.$op.' ?';$params[]=dateval($_GET[$k]);}}$sql='SELECT p.*,u.name AS user_name,pm.name AS payment_method FROM purchases p JOIN users u ON u.id=p.user_id JOIN payment_methods pm ON pm.id=p.payment_method_id'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY p.purchase_date DESC,p.id DESC';out(200,'Compras consultadas',['purchases'=>rows($sql,$params)]);
