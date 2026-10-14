<?php
// Registra un nuevo compras.
require_once __DIR__.'/../config/service_helpers.php';
method('POST');
$u=user(true);

// Recibir datos de la solicitud
$d=body();[$date,$supplier,$pm,$items,$total]=validPurchase($d);try{$pdo->beginTransaction();$s=$pdo->prepare('INSERT INTO purchases(purchase_date,supplier,payment_method_id,user_id,total) VALUES(?,?,?,?,?)');$s->execute([$date,$supplier,$pm,$u['id'],$total]);$id=(int)$pdo->lastInsertId();insertItems($id,$items);$pdo->commit();out(201,'Compra registrada correctamente',['purchase'=>purchase($id)]);}catch(Throwable $e){failure($e);}
