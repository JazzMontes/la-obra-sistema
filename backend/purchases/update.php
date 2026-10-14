<?php
// Actualiza los datos de compras.
require_once __DIR__.'/../config/service_helpers.php';
method('PATCH');
$u=user(true);

// Recibir datos de la solicitud
$d=body();$id=positiveId($d['id']??null);try{$pdo->beginTransaction();$old=purchase($id,true);$merged=['purchase_date'=>$d['purchase_date']??$old['purchase_date'],'supplier'=>array_key_exists('supplier',$d)?$d['supplier']:$old['supplier'],'payment_method_id'=>$d['payment_method_id']??$old['payment_method_id'],'items'=>$d['items']??array_map(fn($i)=>['input_id'=>$i['input_id'],'quantity'=>$i['quantity'],'unit_cost'=>$i['unit_cost']],$old['items'])];if(!count(array_intersect(array_keys($d),['purchase_date','supplier','payment_method_id','items'])))out(422,'No hay cambios para actualizar');[$date,$supplier,$pm,$items,$total]=validPurchase($merged);$s=$pdo->prepare('UPDATE purchases SET purchase_date=?,supplier=?,payment_method_id=?,total=?,updated_at=NOW() WHERE id=?');$s->execute([$date,$supplier,$pm,$total,$id]);$pdo->prepare('DELETE FROM purchase_items WHERE purchase_id=?')->execute([$id]);insertItems($id,$items);$new=purchase($id);$pdo->prepare('INSERT INTO purchase_history(purchase_id,changed_by,previous_data,new_data,change_reason) VALUES(?,?,?,?,?)')->execute([$id,$u['id'],auditJson($old),auditJson($new),isset($d['change_reason'])?trim((string)$d['change_reason']):null]);$pdo->commit();out(200,'Compra actualizada correctamente',['purchase'=>$new]);}catch(Throwable $e){failure($e);}
