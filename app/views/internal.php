<!DOCTYPE html>
<html lang="es-PE">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1,user-scalable=yes"/>
  <title>#YoAprendo</title>
  <?= Route::renderMeta() ?>
  <meta property="og:image" content="https://aprendo.space/img/logo_social.png">
  <link href="/css/bulma.css" rel="stylesheet" type="text/css">
  <link href="/css/style.css" rel="stylesheet" type="text/css">
  <script type="text/javascript" src="/js/requirejs.js"></script>
  <script src="/js/jquery.min.js"></script>
  <?= Route::renderAssets() ?>
</head>
<body>
  <header>
    <?php include(Route::g()->attr('views') . 'internal.header.php'); ?>
  </header>
  <main>
<?php if(Route::hasTitle() || Route::hasDescription() || !empty(Route::data('submenu'))) { ?>
    <div class="container">
      <h1 class="has-text-white"><?= Route::getTitle() ?></h1>
      <?php if(Route::hasDescription()) { ?><p class="has-text-white"><?= Route::getDescription(); ?></p><?php } ?>
      <div class="mini-nav"> <?= Route::renderNav() ?> </div>
<?php if(isset($VISTA_HTML)) { echo $VISTA_HTML; } else { include(Route::g()->attr('views') . $VISTA . '.php'); } ?>
    </div>
<?php } else { ?>
<?php if(isset($VISTA_HTML)) { echo $VISTA_HTML; } else { include(Route::g()->attr('views') . $VISTA . '.php'); } ?>
<?php } ?>
  </main>
<?php include(Route::g()->attr('views') . 'internal.footer.php'); ?> 
<script src="/js/popy.js"></script>
<script src="/js/main.js"></script>
</body>
</html>

