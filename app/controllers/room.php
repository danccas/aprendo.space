<?php

Identify::direccionar_no_logueado();

Route::library('formity2');
Route::libraryOwn('socket');
Route::libraryOwn('sesion');

$db = Doris::init('aprendo');

Route::any(':codigo', array('codigo' => '[\w\-\_]{8}'), function($route) use($db) {
  $sesion = $db->get("
    SELECT
      S.*,
      U.usuario,
      U.nombres as usuario_nombres,
    (SELECT COUNT(*) as cantidad
      FROM usuario_seguidor
      WHERE usuario_id = S.usuario_id) as seguidores,
    (SELECT COUNT(*)
      FROM usuario_seguidor
      WHERE usuario_id = S.usuario_id AND seguidor_id = " . Identify::g()->id . ") as seguidor_yo
    FROM sesion S
      JOIN usuario U ON U.id = S.usuario_id
    WHERE S.codigo = :codigo", true, array(
    'codigo'  => $route->current_route['codigo'],
  ));
  if(empty($sesion)) {
    _404();
  }
  if($sesion['usuario_id'] == Identify::g()->id) {
    //Route::redirect('/sesiones/info/' . $sesion['codigo']);
  }
  $sesion = sesion_to_human($sesion);

  if(in_array($sesion['estado'], [SESION_PENDIENTE, SESION_ATRASADO, SESION_INICIADO])) {
    $db->insert('sesion_espectador', array(
      'sesion_id'   => $sesion['id'],
      'usuario_id'  => Identify::g()->id,
      'fecha_desde' => Doris::time(),
    ));
  }
  Route::createLink('markFavorite', function() use($db, $sesion) {
    $type = $_POST['type'];
    $existe = $db->get("
      SELECT *
      FROM usuario_seguidor
      WHERE usuario_id = " . $sesion['usuario_id'] . " AND seguidor_id = " . Identify::g()->id, true);
    if($type == 'button') {
      if(!empty($existe) ) {
        $db->delete('usuario_seguidor', 'usuario_id = ' . $sesion['usuario_id'] .
        ' AND seguidor_id = ' . Identify::g()->id);
      } else {
        $db->insert('usuario_seguidor', array(
          'usuario_id'  => $sesion['usuario_id'],
          'seguidor_id' => Identify::g()->id,
          'fecha'        => Doris::time(),
        ));
      }
    } else {
      $existe = empty($existe);
    }
    $rp = $db->get("
      SELECT COUNT(*) as cantidad
      FROM usuario_seguidor
      WHERE usuario_id = " . $sesion['usuario_id'], true);
    Route::responseJSON(200, array(
     'message' => [
        'count' => $rp['cantidad'],
        'me'    => empty($existe),
      ]
    ));
  });
  Route::createLink('getInfo', function() use($db) {
    $cid = (int) $_POST['cid'];
    $pregunta = $db->get("
        SELECT
          P.*,
          CP.id as cid,
          D.nombre as dificultad,
          D.color as dificultad_color
        FROM cuestionario_pregunta CP
        JOIN pregunta P ON P.id = CP.pregunta_id
        JOIN dificultad D ON D.id = P.dificultad_id
        WHERE CP.id = " . $cid, true);
    if(empty($pregunta)) {
      Route::responseJSON(404, 'sin-información');
    }
    if(!empty($pregunta['imagen'])) {
      $pregunta['imagen'] = Route::g()->attr('dir_image_public') . $pregunta['imagen'];
    }
    $pregunta['opciones'] = $db->get("SELECT * FROM opcion WHERE pregunta_id = " . $pregunta['id'] . " ORDER BY orden ASC");
    Route::responseJSON(200, [
	    'code'    => 200,
	    'message' => $pregunta,
    ]);
  });
  Route::createLink('registerMark', function() use($db, $sesion) {
    $cid       = (int) $_POST['cid'];
    $opcion_id = (int) $_POST['oid'];
    $existe = $db->get("
      SELECT
        O.correcto,
        P.texto
      FROM opcion O
        JOIN pregunta P ON P.id = O.pregunta_id
      WHERE O.id = " . $opcion_id, true);
    if(empty($existe)) {
      _404();
    }
    $db->insert('sesion_respuesta', array(
      '*usuario_id'      => Identify::g()->id,
      '*sesion_id'       => $sesion['id'],
      '*cid'             => $cid,
      'opcion_id'        => $opcion_id,
    ));
    $puntaje = $db->get("
      SELECT
        COALESCE(SUM(CASE WHEN O2.correcto = 1 THEN CP2.puntaje ELSE 0 END), 0) as puntaje
      FROM sesion_respuesta SR2
        JOIN cuestionario_pregunta CP2 ON CP2.id = SR2.cid
        JOIN opcion O2 ON O2.id = SR2.opcion_id
      WHERE sesion_id = {$sesion['id']} AND usuario_id = " . Identify::g()->id, true);
    $puntaje = !empty($puntaje)  ? $puntaje['puntaje'] : 0;
    SocketSend($sesion['id'], TYPE_VIEWER, Identify::g()->id, array(
      'nick'    => Identify::g()->user,
      'mark'    => array(
        'cid'       => $cid,
        'opcion_id' => $opcion_id,
        'text'      => $existe['texto'],
        'puntaje'   => $puntaje,
        'status'    => !empty($existe['correcto']),
      ),
    ));
    Route::responseJSON(200,[
	    'message' => 
	    array(
      'puntaje' => $puntaje,
    )]);
  });
  Route::addMeta('title', $sesion['nombre'] . ' de ' . $sesion['usuario'] . ' | #AprendoSpace');
  Route::theme('room', $sesion);
});
