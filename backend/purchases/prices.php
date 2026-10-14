<?php
require_once __DIR__.'/../config/service_helpers.php';
method('GET');
$u=user(true);
$id=positiveId($_GET['input_id']??null);if(!record('SELECT id FROM inputs WHERE id=?',[$id]))out(404,'Insumo no encontrado');$r=rows('SELECT pi.input_id,i.name AS input_name,i.unit,p.id AS purchase_id,p.purchase_date,pi.quantity,pi.unit_cost FROM purchase_items pi JOIN purchases p ON p.id=pi.purchase_id JOIN inputs i ON i.id=pi.input_id WHERE pi.input_id=? ORDER BY p.purchase_date DESC,p.id DESC',[$id]);out(200,'Historial de precios consultado',['prices'=>$r]);
