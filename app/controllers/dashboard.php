<?php

Identify::direccionar_no_logueado();

Route::library('dashboard');
Route::library('chartjs');

$db = Doris::init('aprendo');

$sesiones = $db->get("
  SELECT
    S.*,
    U.usuario,
    A.nombre as area
  FROM sesion S
    JOIN usuario U ON U.id = S.usuario_id
    LEFT JOIN area A ON A.id = S.area_id
  WHERE S.privacidad = 1 AND S.fecha_desde IS NOT NULL AND S.fecha_hasta IS NULL
  ORDER BY S.fecha_desde DESC
  LIMIT 10");


Route::theme('dashboard', array(
  'sesiones' => $sesiones,
));
