<?php
// --- ranges_overlap: adyacencia NO es solape ---
check('adjacency end==start no solapa', ranges_overlap('09:00:00','12:00:00','12:00:00','15:00:00') === false);
check('adjacency start==end no solapa', ranges_overlap('12:00:00','15:00:00','09:00:00','12:00:00') === false);
// --- contención total ---
check('contención total solapa', ranges_overlap('09:00:00','18:00:00','10:00:00','11:00:00') === true);
check('contenido dentro solapa',  ranges_overlap('10:00:00','11:00:00','09:00:00','18:00:00') === true);
// --- solapes parciales en ambos sentidos ---
check('parcial por la derecha', ranges_overlap('09:00:00','13:00:00','12:00:00','15:00:00') === true);
check('parcial por la izquierda', ranges_overlap('12:00:00','15:00:00','09:00:00','13:00:00') === true);
check('idénticos solapan', ranges_overlap('09:00:00','13:00:00','09:00:00','13:00:00') === true);
check('disjuntos no solapan', ranges_overlap('09:00:00','10:00:00','11:00:00','12:00:00') === false);

// --- valid_time_order ---
check('fin>inicio válido', valid_time_order('09:00:00','17:00:00') === true);
check('fin==inicio inválido', valid_time_order('09:00:00','09:00:00') === false);
check('fin<inicio inválido', valid_time_order('17:00:00','09:00:00') === false);

// --- can_transition: matriz completa por rol ---
foreach (['responsable','administrador'] as $r) {
    check("[$r] programado->confirmado", can_transition('programado','confirmado',$r) === true);
    check("[$r] confirmado->ausente",    can_transition('confirmado','ausente',$r) === true);
    check("[$r] ausente->cubierto NO bare", can_transition('ausente','cubierto',$r) === false);
    check("[$r] programado->ausente NO",  can_transition('programado','ausente',$r) === false);
    check("[$r] confirmado->programado === admin?", can_transition('confirmado','programado',$r) === ($r === 'administrador'));
}
check('admin ausente->programado', can_transition('ausente','programado','administrador') === true);
check('admin cubierto->programado', can_transition('cubierto','programado','administrador') === true);
check('admin programado->programado NO (mismo)', can_transition('programado','programado','administrador') === false);
check('responsable ausente->programado NO', can_transition('ausente','programado','responsable') === false);
check('empleado programado->confirmado NO', can_transition('programado','confirmado','empleado') === false);
check('empleado ausente->programado NO', can_transition('ausente','programado','empleado') === false);
