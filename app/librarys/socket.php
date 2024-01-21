<?php
define('TYPE_PRESENTER', 'dx');
define('TYPE_VIEWER', 'ax');
define('TYPE_DRAWER', 'vx');

Route::libraryOwn('curly');

function SocketSend($room_id, $type_id, $socket_id, $data) {
  $URL = "https://172.17.0.5:8081/";
  $data = array(
    'identidad' => array(
      'room_id'  => $room_id,
      'tipo'     => $type_id,
      'id'       => $socket_id . uniqid(),
      'socketId' => $socket_id,
    ),
    'data' => $data,
  );
  return curly(CURLY_POST, $URL, null, json_encode($data));
}
