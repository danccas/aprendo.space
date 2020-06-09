<?php

define('SESION_PENDIENTE', 1);
define('SESION_ATRASADO', 2);
define('SESION_INICIADO', 3);
define('SESION_CANCELADO', 4);
define('SESION_FINALIZADO', 5);


function sesion_to_human($s) {
  $s['codigo_streaming'] = md5($s['codigo']);
  $x = strtotime($s['fecha_programada']);
  if(!empty($s['fecha_hasta'])) {
    $s['estado'] = SESION_FINALIZADO;
    $s['estado_txt']  = 'Finalizado';
    $s['estado_html'] = '<span class="tag is-success">Finalizado</span>';
  } elseif(!empty($s['fecha_desde'])) {
    $s['estado'] = SESION_INICIADO;
    $s['estado_txt']  = 'Iniciado';
    $s['estado_html'] = '<span class="tag is-danger">Iniciado</span>';
  } elseif($x < time()) {
    $s['estado'] = SESION_ATRASADO;
    $s['estado_txt']  = 'Atrasado';
    $s['estado_html'] = '<span class="tag is-warning">Atrasado</span>';
  } else {
    $s['estado'] = SESION_PENDIENTE;
    $s['estado_txt']  = 'Pendiente';
    $s['estado_html'] = '<span class="tag is-warning">Pendiente</span>';
  }
  return $s;
}
function subir_imagen_pregunta($imagen, &$error = null) {
  if ($imagen['error'] != UPLOAD_ERR_OK) {
    $error = 'Error al subir el archivo';
    return null;
  } else {
    $info = getimagesize($imagen['tmp_name']);
    if ($info === FALSE) {
      $error = 'El formato del archivo es inválido.';
      return null;
    }
    $file = time() . uniqid() . '.jpg';
    if(move_uploaded_file($imagen['tmp_name'], DIR_IMAGES_PRIVATE . $file)) {
      return $file;
    } else {
      $error = 'Surgió un problema inesperado al subir la imagen';
      return null;
    }
  }
  return null;
}
