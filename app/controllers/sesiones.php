<?php

Identify::direccionar_no_logueado();

Route::library('formity2');
Route::libraryOwn('socket');
Route::libraryOwn('sesion');

$db = Doris::init('aprendo');

$next = $db->get("SELECT COUNT(*) as cantidad FROM sesion WHERE usuario_id = " . Identify::g()->id, true);

$ls = $db->get("SELECT * FROM area ORDER BY nombre ASC");
$ls = result_parse_to_options($ls, 'id', 'nombre');

$time_default = time() + 60*60;
$form = Formity::getInstance('sesion');
$form->setTitle('Sesión');
$form->addField('nombre', 'input:text')->setValue('Sesión #' . ($next['cantidad'] + 1));
$form->addField('fecha_programada', 'input:date')->setValue(date('Y-m-d', $time_default));
$form->addField('hora_programada', 'input:time')->setValue(date('H:i', $time_default));
$form->addField('area_id:Área', 'select')->setOptions($ls);

$ls = $db->get("SELECT * FROM dificultad ORDER BY nombre ASC");
$ls = result_parse_to_options($ls, 'id', 'nombre');
$form->addField('privacidad', 'select')->setOptions(array(
  1 => 'Público',
  2 => 'Privado',
))->setValue(1);

$formo = Formity::getInstance('opcion');
$formo->addField('texto', 'input:text');
$formo->addField('correcto', 'boolean');

$form = Formity::getInstance('pregunta');
$form->setTitle('Pregunta');
$form->addField('texto', 'textarea');
$form->addField('tipo', 'input:text');
$form->addField('dificultad', 'select')->setOptions($ls)->setValue(1);
$form->addField('opciones', $formo, '1-5:3');

Route::any('', function() use($db) {
  $table = Tablefy::getInstance('sesions');
  $table->setTitle('Relación de sesions');
  $table->setHeader(array('NOMBRE', 'ÁREA', 'PRIVACIDAD','FECHA PROGRAMADA','ESTADO'));
  $table->setData(function($e) use($db) {
    return $db->pagination("
      SELECT
        F.*,
        A.nombre as area
      FROM sesion F
      LEFT JOIN area A ON A.id = F.area_id
      WHERE F.usuario_id = " . Identify::g()->id . "
      ORDER BY id ASC", $e);
  }, function($n) {
    $n = sesion_to_human($n);
    return array(
      $n['nombre'],
      $n['area'],
      $n['privacidad'] == 1  ? '<span class="tag">Público</span>' : '<span class="tag">Privado</span>',
      fecha($n['fecha_programada'], true),
      $n['estado_html'],
      'tb_options' => in_array($n['estado'], array(SESION_PENDIENTE, SESION_ATRASADO)) ? array('editar','studio') : array('studio'),
    );
  });
  $table->setOption('studio', '/sesiones/info/:codigo');
  $table->setOption('editar&', function($n) use($db) {
    $n = sesion_to_human($n);
    $form = Formity::getInstance('sesion');
    if(!in_array($n['estado'], [SESION_PENDIENTE, SESION_ATRASADO])) {
      $form->removeField('fecha_programada');
      $form->removeField('hora_programada');
    }
    if($form->byRequest()) {
      if($form->isValid()) {
        $data = $form->getData();
        if(!empty($data['fecha_programada'])) {
          $data['fecha_programada'] = $data['fecha_programada'] . ' ' . $data['hora_programada'];
          unset($data['hora_programada']);
        }
        $db->update('sesion', $data, 'id = ' . $n['id']);
        Route::go2Back();
      }
    } else {
      $form->setPreData($n);
    }
    Route::setTitle('#Cuestionarios');
    Route::setDescription('Se muestra la relación de sesions registrados.');
    Route::render($form);
  });
  $table->prepare();
  Route::setTitle('#MisSesiones');
  Route::setDescription('Se muestra la relación de sesiones pendientes');
  Route::nav('Nuevo&', function() use($db) {
    $form = Formity::getInstance('sesion');
    if($form->byRequest()) {
      if($form->isValid()) {
        $data = $form->getData();
        $data['fecha_programada'] = $data['fecha_programada'] . ' ' . $data['hora_programada'];
        unset($data['hora_programada']);
        $data['usuario_id'] = Identify::g()->id;
        do {
          $data['codigo'] = get_token(8);
          $existe = $db->get("SELECT * FROM sesion WHERE codigo = :codigo", false, array(
            'codigo' => $data['codigo'],
          ));
        } while(!empty($existe));
        $form_id = $db->insert('sesion', $data);
        Route::refresh();
//        Route::go2Back();
      }
    }
    Route::render($form);
  });
  Route::render($table);
});
Route::any('info/:codigo', array('codigo' => '[\w\-\_]{8}'), function($ce) use($db) {
  $route = $this->current_route;
  $sesion = $db->get("SELECT * FROM sesion WHERE usuario_id = :usuario AND codigo = :codigo", true, array(
    'usuario' => Identify::g()->id,
    'codigo'  => $route['codigo'],
  ));
  if(empty($sesion) && $sesion['usuario_id'] != Identify::g()->id) {
    _404();
  }
  $sesion = sesion_to_human($sesion);
  Route::createLink('cuestionarios&', '/cuestionarios');
  Route::createLink('toggleVideo', function() use($db, $sesion) {
    $nv = $sesion['streaming'] != 1;
    $db->update('sesion', array(
      'streaming' => $nv ? 1 : 0,
    ), 'id = ' . $sesion['id']);
    SocketSend($sesion['id'], TYPE_PRESENTER, Identify::g()->id, array(
      'action' => 'video',
      'video'  => $nv,
    ));
    Route::responseJSON(201, array(
      'old'       => $sesion['streaming'],
      'streaming' => $nv,
      'text'      => !$nv ? 'Iniciar Streaming' : 'Detener Streaming',
    ));
  });
  Route::createLink('insertar', function() use($db, $sesion) {
    $cuestionario_id = $_POST['cuestionario_id'];
    $cuestionario = $db->get("SELECT * FROM cuestionario WHERE id = :id AND usuario_id = :usuario", true, array(
      'id' => $cuestionario_id,
      'usuario' => Identify::g()->id,
    ));
    if(empty($cuestionario)) {
      Route::responseJSON(404, 'sin-cuestionario');
    }
    $registrado = $db->get("SELECT * FROM sesion_cuestionario WHERE sesion_id = :sesion AND cuestionario_id = :cuestionario", true, array(
      'sesion'       => $sesion['id'],
      'cuestionario' => $cuestionario_id,
    ));
    if(empty($registrado)) {
      $db->insert('sesion_cuestionario', array(
        'sesion_id'       => $sesion['id'],
        'cuestionario_id' => $cuestionario_id,
      ));
      Route::responseJSON(201, 'OK');
    } else {
      Route::responseJSON(304, 'YaExiste');
    }
  });
  Route::createLink('tabs', function() use($db, $sesion) {
     $cuestionario_id = $_POST['cid'];
     $registrado = $db->get("SELECT * FROM sesion_cuestionario WHERE sesion_id = :sesion AND cuestionario_id = :cuestionario", true, array(
      'sesion'       => $sesion['id'],
      'cuestionario' => $cuestionario_id,
    ));
    if(!empty($registrado)) {
      $preguntas = $db->get("
        SELECT
          P.*,
          CP.orden,
          CP.id as cid,
          D.nombre as dificultad,
          D.color as dificultad_color,
          (SELECT COUNT(*) FROM sesion_material WHERE cid = CP.id) as cantidad_mostrado,
          (SELECT COUNT(*) FROM sesion_respuesta R WHERE R.cid = CP.id) as respuestas
        FROM cuestionario_pregunta CP
        JOIN pregunta P ON P.id = CP.pregunta_id
        JOIN dificultad D ON D.id = P.dificultad_id
        WHERE CP.cuestionario_id = " . $cuestionario_id . "
        ORDER BY CP.orden ASC");
      if(!empty($preguntas)) {
        $preguntas = array_map(function($n) use($db, $sesion) {
          $n['opciones'] = $db->get("
            SELECT O.*,
              (SELECT COUNT(*) FROM sesion_respuesta R WHERE R.opcion_id = O.id AND R.sesion_id = " . $sesion['id'] . ") as respuestas
            FROM opcion O
            WHERE O.pregunta_id = " . $n['id'] . "
            ORDER BY O.orden ASC");
          return $n;
        }, $preguntas);
      }
      Route::view('sesion.cuestionario', array(
        'preguntas' => $preguntas,
      ));
    } else {
      Route::response(404);
    }
  });
  Route::createLink('changeStatus', function() use($db, $sesion) {
    if(in_array($sesion['estado'], [SESION_PENDIENTE, SESION_ATRASADO])) { /* Iniciar */
      $db->update('sesion', array(
        'fecha_desde' => Doris::time(),
      ), 'id = ' . $sesion['id']);
      SocketSend($sesion['id'], TYPE_PRESENTER, Identify::g()->id, array( 
        'action'  => 'iniciar',
      ));
      Route::responseJSON(201, array(
        'action' => 'iniciar',
        'next'   => array(
          'class' => 'button is-darger',
          'text'  => 'Detener',
        )
      ));
    } elseif($sesion['estado'] == SESION_INICIADO) {
      $db->update('sesion', array(
        'fecha_hasta' => Doris::time(),
      ), 'id = ' . $sesion['id']);
      SocketSend($sesion['id'], TYPE_PRESENTER, Identify::g()->id, array(
        'action'  => 'detener',
      ));
      Route::responseJSON(201, array(
        'action' => 'detener',
        'next'   => array(
          'class' => 'button',
          'text'  => fecha($sesion['fecha_desde']),
        )
      ));
    } else {
      Route::responseJSON(304);
    }
  });
  Route::createLink('insertScreen', function() use($db, $sesion) {
    if($sesion['estado'] != SESION_INICIADO) {
      Route::responseJSON(406, 'Debe iniciar la sesión para poder enviar un material');
    }
    $cid = is_numeric($_POST['cid']) ? $_POST['cid'] : null;
    $current = $db->get("
      SELECT *
      FROM sesion_material
      WHERE sesion_id = {$sesion['id']} AND fecha_hasta IS NULL", true); 
    if(!empty($current)) {
      $db->update('sesion_material', array(
        'fecha_hasta' => Doris::time(),
      ),  'id = ' . $current['id']);
    }
    if(empty($cid) || (!empty($current) && $cid == $current['cid'])) {
      SocketSend($sesion['id'], TYPE_PRESENTER, Identify::g()->id, array(
        'screen'    => null,
      ));
      Route::responseJSON(304, 'no-screen');
    } else {
      $db->insert('sesion_material', array(
        'sesion_id'   => $sesion['id'],
        'cid'         => $cid,
        'fecha_desde' => Doris::time(),
      ));
      SocketSend($sesion['id'], TYPE_PRESENTER, Identify::g()->id, array(
        'screen' => array(
          'id'   => $cid
        ),
      ));
      Route::responseJSON(201, 'Ok');
    }
  });
  Route::createLink('getCuadrilla', function() use($db, $sesion) {
    $sesion['respuestas'] = $db->get("
      SELECT
        U.id as usuario_id,
        U.nombres as usuario_nombres,
        U.usuario,
        CP.id as cid,
        P.id as pregunta_id,
        P.texto as pregunta_texto,
        COALESCE((
          SELECT
            SUM(CASE WHEN O2.correcto = 1 THEN CP2.puntaje ELSE 0 END)
          FROM sesion_respuesta SR2
            JOIN cuestionario_pregunta CP2 ON CP2.id = SR2.cid
            JOIN opcion O2 ON O2.id = SR2.opcion_id
          WHERE sesion_id = SR.sesion_id AND usuario_id = U.id
        ), 0) as puntaje,
        CP.puntaje as pregunta_puntaje,
        SR.opcion_id,
        O.orden,
        O.correcto
      FROM (
        SELECT *
        FROM sesion_espectador
        WHERE sesion_id = {$sesion['id']}
        GROUP BY usuario_id
      ) SE
        JOIN usuario U ON U.id = SE.usuario_id
        LEFT JOIN sesion_respuesta SR ON SR.sesion_id = SE.sesion_id AND SR.usuario_id = SE.usuario_id
        LEFT JOIN cuestionario_pregunta CP ON CP.id = SR.cid
        LEFT JOIN pregunta P ON P.id = CP.pregunta_id
        LEFT JOIN opcion O ON O.id = SR.opcion_id
      ORDER BY U.nombres ASC, SR.id ASC");
    if(!empty($sesion['respuestas'])) {
      $ccids = array_filter($sesion['respuestas'], function($n) { return !empty($n['cid']); });
      $sesion['cids'] = !empty($ccids) ? array_group_by($ccids, array(
        array('key' => 'cid', 'only' => ['cid','pregunta_id','pregunta_texto','pregunta_puntaje']),
      )) : array();
      $sesion['respuestas'] = array_group_by($sesion['respuestas'], array(
        array('key' => 'usuario_id', 'only' => ['usuario_id','usuario_nombres','usuario', 'puntaje']),
        array('key' => 'cid', 'only' => ['cid','pregunta_id','pregunta_texto','pregunta_puntaje','opcion_id','orden','correcto']),
      ));
    }
    Route::view('sesion.cuadrilla', $sesion);
  });
  if($sesion['estado'] != SESION_FINALIZADO) {
    $sesion['cuestionarios'] = $db->get("
      SELECT
        C.*,
        (SELECT COUNT(*) FROM cuestionario_pregunta WHERE cuestionario_id = C.id) as preguntas_cantidad
      FROM sesion_cuestionario SC
      JOIN cuestionario C ON C.id = SC.cuestionario_id
      WHERE SC.sesion_id = {$sesion['id']}
      ORDER BY C.nombre ASC");
  } else {
    $preguntas = $db->get("
      SELECT
        P.*,
        D.nombre as dificultad,
        D.color as dificultad_color,
        (SELECT COUNT(*) FROM sesion_material WHERE cid = SM.cid) as cantidad_mostrado,
        (SELECT COUNT(*) FROM sesion_respuesta R WHERE cid = SM.cid) as respuestas
      FROM sesion_material SM
      JOIN cuestionario_pregunta CP ON CP.id = SM.cid
      JOIN pregunta P ON P.id = CP.pregunta_id
      JOIN dificultad D ON D.id = P.dificultad_id
      WHERE SM.sesion_id = " . $sesion['id'] . "
      GROUP BY CP.id
      ORDER BY SM.fecha_desde ASC");
    if(!empty($preguntas)) {
      $preguntas = array_map(function($n) use($db, $sesion) {
        $n['opciones'] = $db->get("
          SELECT O.*,
            (SELECT COUNT(*) FROM sesion_respuesta R WHERE R.opcion_id = O.id AND R.sesion_id = " . $sesion['id'] . ") as respuestas
          FROM opcion O
          WHERE O.pregunta_id = " . $n['id'] . "
          ORDER BY O.orden ASC");
        return $n;
      }, $preguntas);
      $sesion['preguntas'] = $preguntas;
      unset($preguntas);
    }
  }
  SocketSend($sesion['id'], TYPE_PRESENTER, Identify::g()->id, array(
    'info' => array(
      'fecha_desde' => strtotime($sesion['fecha_desde']),
      'fecha_hasta' => strtotime($sesion['fecha_hasta']),
      'streaming'   => $sesion['estado'] == SESION_INICIADO,
      'video'       => $sesion['video'] == 1,
    ),
  ));
  Route::theme('sesion', $sesion);
});
