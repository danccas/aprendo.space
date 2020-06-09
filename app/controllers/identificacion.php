<?php
Route::library('formity2');

if(isset($_GET['out'])) {
  Identify::g()->close();
  #Route::redirect('.');
}

Identify::direccionar_logueado();

#if(Identify::verificacion_logeo()) {
#  header("Location: " . RAIZ_WEB);
#  exit();
#}

$db = Doris::init('aprendo');

$form = Formity::getInstance('login');
$form->setUniqueId('login');
$form->buttons = ['Ingresar'];
$form->addField('usuario', 'input:text')->setIcon('user');
$form->addField('clave', 'input:password')->setIcon('lock');

if($form->byRequest()) {
  if($form->isValid($error)) {
    $data = $form->getData();
#    $rp = Identify::filter('empresa_id', EMPRESA_ID);
    $rp = Identify::login($db, $data['usuario'], $data['clave'], $error);
    if(!empty($rp)) {
      Route::redirect(Route::g()->attr('web'));
    } else {
      $form->setError($error);
    }
  }
}
Route::data('clear', true);
Route::view('identificacion');
