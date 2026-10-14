<?php
require_once __DIR__.'/../config/service_helpers.php';
method('PATCH');
$u=user(false);
$d=body();$id=positiveId($d['id']??null);$why=trim((string)($d['cancellation_reason']??''));if($why===''||mb_strlen($why)>255)out(422,'Motivo de cancelación obligatorio');try{$pdo->beginTransaction();$old=expense($id,true);expenseAccess($old,$u);expenseEditable($old);$pdo->prepare('UPDATE expenses SET status=\'cancelled\',cancelled_by=?,cancelled_at=NOW(),cancellation_reason=?,updated_at=NOW() WHERE id=?')->execute([$u['id'],$why,$id]);$new=expense($id);$pdo->prepare('INSERT INTO expense_history(expense_id,action,previous_data,new_data,change_reason,changed_by) VALUES(?,\'cancelled\',?,?,?,?)')->execute([$id,auditJson($old),auditJson($new),$why,$u['id']]);$pdo->commit();out(200,'Gasto cancelado correctamente',['expense'=>$new]);}catch(Throwable $e){failure($e);}
