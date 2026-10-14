<?php
require_once __DIR__.'/../config/service_helpers.php';
method('POST');
$u=user(false);
$d=body();$amount=money($d['amount']??null);$reason=trim((string)($d['reason']??''));if($reason===''||mb_strlen($reason)>255)out(422,'Motivo inválido');try{$s=$pdo->prepare('INSERT INTO expenses(amount,reason,user_id,status) VALUES(?,?,?,\'active\')');$s->execute([$amount,$reason,$u['id']]);out(201,'Gasto registrado correctamente',['expense'=>expense((int)$pdo->lastInsertId())]);}catch(Throwable $e){failure($e);}
