<?php
// Consulta el detalle de compras.
require_once __DIR__.'/../config/service_helpers.php';
method('GET');
$u=user(true);
$id=positiveId($_GET['id']??null);out(200,'Compra consultada',['purchase'=>purchase($id)]);
