<?php
// Actualiza los datos de gastos y salidas.
require_once __DIR__.'/../config/service_helpers.php';
method('PATCH');
$u=user(false);

// Recibir datos de la solicitud
$d=body();$id=positiveId($d['id']??null);$why=trim((string)($d['change_reason']??''));if($why===''||mb_strlen($why)>255)out(422,'El motivo de corrección es obligatorio');if(!array_key_exists('amount',$d)&&!array_key_exists('reason',$d))out(422,'Debes enviar monto o motivo del gasto');try{$pdo->beginTransaction();$old=expense($id,true);expenseAccess($old,$u);expenseEditable($old);$amount=array_key_exists('amount',$d)?money($d['amount']):$old['amount'];$reason=array_key_exists('reason',$d)?trim((string)$d['reason']):$old['reason'];if($reason===''||mb_strlen($reason)>255)out(422,'Motivo del gasto inválido');$pdo->prepare('UPDATE expenses SET amount=?,reason=?,updated_at=NOW() WHERE id=?')->execute([$amount,$reason,$id]);$new=expense($id);$pdo->prepare('INSERT INTO expense_history(expense_id,action,previous_data,new_data,change_reason,changed_by) VALUES(?,\'updated\',?,?,?,?)')->execute([$id,auditJson($old),auditJson($new),$why,$u['id']]);$pdo->commit();out(200,'Gasto actualizado correctamente',['expense'=>$new]);}catch(Throwable $e){failure($e);}
