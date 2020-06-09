<!DOCTYPE html>
<html lang="es-PE">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1,user-scalable=yes"/>
  <title>#YoAprendo</title>
  <meta property="og:image" content="https://aprendo.space/img/logo_social.png">
  <link href="/css/bulma.css" rel="stylesheet" type="text/css">
  <link href="/css/style.css" rel="stylesheet" type="text/css">
</head>
<body>
<div class="content-vertical-center">
<div class="container">
  <div class="login-home">
    <h1 class="has-text-white">#YoAprendo</h1>
    <div class="login-box">
      <img class="box-buo" src="/img/buo_negro.png" />
      <p>Somos una plataforma en la que podrás aprender, además de interactuar con el presentador en tiempo real.</p>
<div style="color:#000;">
<?= Formity::getInstance('login')->render(); ?>
</div>
<br />
<div class="has-text-centered">
Si no tiene una cuenta, hágalo fácilmente con un <b><a href="/registro">Click Aquí para Registrarse</a></b>
</div>
    </div>
  </div>
</div>
</div>
<script src="/js/jquery.min.js"></script>
</body>
</html>
