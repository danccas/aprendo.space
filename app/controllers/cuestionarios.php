<?php

Identify::direccionar_no_logueado();

Route::library('formity2');
Route::library('chartjs');
Route::libraryOwn('sesion');

$db = Doris::init('aprendo');

$form = Formity::getInstance('cuestionario');
$form->setTitle('Formulario');
$form->addField('nombre', 'input:text');
//$form->addField('tipo', 'input:text');

$formo = Formity::getInstance('opcion');
$formo->addField('texto', 'input:text');
$formo->addField('correcto', 'boolean');

$form = Formity::getInstance('pregunta');
$form->setTitle('Pregunta');
$form->addField('puntaje', 'input:number')->setMin(0)->setMax(100)->setStep(0.05)->setValue(5);
$form->addField('texto?:Pregunta', 'textarea');
$form->addField('imagen?:Imagen', 'input:file');
$ls = $db->get("SELECT * FROM dificultad ORDER BY nombre ASC");
$ls = result_parse_to_options($ls, 'id', 'nombre');
$form->addField('dificultad_id:Dificultad', 'select')->setOptions($ls)->setValue(2);
$form->addField('opciones', $formo, '1-5:3');

Route::any('', function() use($db) {
  $table = Tablefy::getInstance('cuestionarios');
  $table->setTitle('Relación de cuestionarios');
  $table->setHeader(array('CUESTIONARIO','PREGUNTAS'));
  $table->setData(function($e) use($db) {
    return $db->pagination("
      SELECT
        F.*,
        (SELECT COUNT(*) FROM cuestionario_pregunta WHERE cuestionario_id = F.id) as preguntas
      FROM cuestionario F
      WHERE F.usuario_id = " . Identify::g()->id . "
      ORDER BY id ASC", $e);
  }, function($n) {
    return array(
      $n['nombre'],
      $n['preguntas'],
    );
  });
  Route::requestByPopy(function() use($table) {
    $table->setOption('Insertar&', function($n) {
      Route::Action('insertar_cuestionario(_instance, ' . json_encode($n) . ');');
    });
  });
  $table->setOption('editar&', function($n) use($db) {
    $form = Formity::getInstance('cuestionario');
    if($form->byRequest()) {
      if($form->isValid()) {
        $data = $form->getData();
        $db->update('cuestionario', $data, 'id = ' . $n['id']);
        Route::refresh();
      }
    } else {
      $form->setPreData($n);
    }
    Route::setTitle('#Cuestionarios');
    Route::setDescription('Se muestra la relación de cuestionarios registrados.');
    Route::render($form);
  });
  $table->setOption('preguntas&', function($n) use($db) {
    $table = Tablefy::getInstance('preguntas');
    $table->setTitle('Relación de preguntas de: ' . $n['nombre']);
    $table->setHeader(array('PREGUNTA'));
    $table->setData(function($e) use($db, $n) {
      return $db->pagination("
        SELECT
          P.*,
          FP.orden,
          FP.puntaje
        FROM cuestionario_pregunta FP
        JOIN pregunta P ON P.id = FP.pregunta_id
        WHERE FP.cuestionario_id = {$n['id']}
        ORDER BY orden ASC");
    }, function($n) {
      return array(
        $n['texto'],
      );
    });
    $table->setOption('editar&', function($p) use($db, $n) {
      $form = Formity::getInstance('pregunta');
      if($form->byRequest()) {
        if($form->isValid()) {
          $data = $form->getData();
          $opciones = $data['opciones'];
          unset($data['opciones']);
          $puntaje = $data['puntaje'];
          unset($data['puntaje']);
          if(empty($data['texto']) && empty($data['imagen'])) {
            $form->setError('Se debe ingresar un texto o imagen.');
          } else {
            if(!empty($data['imagen'])) {
              $data['imagen'] = subir_imagen_pregunta($data['imagen'], $error);
              if(!empty($error)) {
                $form->setError($error);
                goto sineditar;
              }
            }
            $db->transaction();
            $db->update('pregunta', $data, 'id = ' . $p['id']);
            $db->update('cuestionario_pregunta', array(
              'puntaje' => $puntaje,
            ), 'cuestionario_id = ' . $n['id'] . ' AND pregunta_id = ' . $p['id']);
            foreach($opciones as $k => $o) {

              $db->insert('opcion', array(
                '*pregunta_id' => $p['id'],
                '*orden'       => $k + 1,
                'texto'       => $o['texto'],
                'correcto'    => $o['correcto'],
              ));
            }
            $db->commit();
            Route::go2Back();
            sineditar:
          }
        }
      } else {
        $p['opciones'] = $db->get("SELECT * FROM opcion WHERE pregunta_id = " . $p['id'] . " ORDER BY orden ASC");
        if(!empty($p['imagen'])) {
          $p['imagen'] = Route::g()->attr('dir_image_public') . $p['imagen'];
        }
        $form->setPreData($p);
      }
      Route::render($form);
    });
    $table->setOption('eliminar', function($n) use($db) {
      $db->transaction();
      $db->delete('pregunta', 'id = ' . $n['id']);
      $db->delete('cuestionario_pregunta', 'pregunta_id = ' . $n['id']);
      $db->commit();
      Route::go2Back();
    });
    $table->prepare();
    Route::nav('Nueva Pregunta&', function() use($db, $n) {
      $form = Formity::getInstance('pregunta');
      if($form->byRequest()) {
        if($form->isValid()) {
          $data = $form->getData();
          $opciones = $data['opciones'];
          unset($data['opciones']);
          $puntaje = $data['puntaje'];
          unset($data['puntaje']);
          $db->transaction();
          $pregunta_id = $db->insert('pregunta', $data);
           foreach($opciones as $k => $o) {
            $db->insert('opcion', array(
              '*pregunta_id' => $pregunta_id,
              '*orden'       => $k + 1,
              'texto'       => $o['texto'],
              'correcto'    => $o['correcto'],
            ));
          }
          $db->insert('cuestionario_pregunta', array(
            'cuestionario_id' => $n['id'],
            'pregunta_id'     => $pregunta_id,
            'puntaje'         => $puntaje,
	    'orden'           => 1,
          ));
          $db->commit();
          Route::go2Back();
        }
      }
      Route::render($form);
    });
    Route::setTitle('Preguntas');
    Route::render($table);
  });
  $table->prepare();
  Route::setTitle('#Cuestionarios');
  Route::setDescription('Se muestra la relación de cuestionarios registrados.');
  Route::nav('Nuevo&', function() use($db) {
    $form = Formity::getInstance('cuestionario');
    if($form->byRequest()) {
      if($form->isValid()) {
        $data = $form->getData();
        $data['usuario_id'] = Identify::g()->id;
        $form_id = $db->insert('cuestionario', $data);
        Route::Refresh();
      }
    }
    Route::render($form);
  });
  Route::render($table);
});
