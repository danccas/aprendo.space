<?php

require_once(__DIR__ . '/../conf.php');

Route::library('sesion');
Route::library('socket');

$db = Doris::init('aprendo');


$matar = $db->get("
  SELECT S.*
  FROM sesion S
  WHERE S.fecha_desde IS NOT NULL AND S.fecha_hasta IS NULL
    AND TIMESTAMPDIFF(HOUR, S.fecha_desde, NOW()) >= 6");

foreach($matar as $sesion) {
  $db->update('sesion', array(
    'fecha_hasta' => Doris::time(),
  ), 'id = ' . $sesion['id']);
  SocketSend($sesion['id'], TYPE_PRESENTER, Identify::g()->id, array(
    'action'  => 'detener',
  ));
}
