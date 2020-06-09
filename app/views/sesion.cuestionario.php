<?php if(empty($preguntas)) { ?>
  <div class="has-text-centered">No se ha encontrado preguntas en este cuestionario.</div>
<?php } else { ?>
<div class="columns is-multiline">
<?php foreach($preguntas as $k => $p) { ?>
  <div class="column is-2" style="max-width:400px;">
    <div class="card box-pregunta content-vertical-center" data-cid="<?= $p['cid'] ?>">
      <span class="tag-ini" style="background:#2d2d2d;"><?= $k + 1 ?></span>
      <span class="tag-sec" data-check="<?= $p['cantidad_mostrado'] ?>" title="Cantidad Mostradas en pantalla">
        <?php if(!empty($p['cantidad_mostrado'])) { ?>
          <i class="material-icons">check</i><?= $p['cantidad_mostrado'] ?>
        <?php } ?>
      </span>
      <div>
<?php if(!empty($p['imagen'])) { ?>
  <div class="has-text-centered">
    <img src="<?= Route::g()->attr('dir_image_public') . $p['imagen'] ?>" style="max-height: 140px;max-width: 100%;" />
  </div>
<?php } else { ?>
        <h4 title="<?= $p['texto'] ?>"><?= reducir_texto($p['texto'], 30) ?></h4>
<?php } ?>
<?php foreach($p['opciones'] as $t) { ?>
        <span class="tag opcion" data-id="<?= $t['id'] ?>" title="<?= $t['texto'] ?>">
          <span title="Repuestas: <?= $t['respuestas'] ?>"><?= empty($p['respuestas']) ? 0 : number_format($t['respuestas'] * 100 / $p['respuestas']) ?>%</span>
          <?= reducir_texto($t['texto'], 10) ?>
        </span>
<?php } ?>
      </div>
      <span class="tag-end" style="background:<?= $p['dificultad_color'] ?>"><?= $p['dificultad'] ?></span>
    </div>
  </div>
<?php } ?>
</div>
<?php } ?>
