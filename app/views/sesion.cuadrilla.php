<table class="table is-bordered is-striped table-data">
  <thead>
    <tr>
      <th class="columna_indice">#</th>
      <th class="columna_nombres">Nombres</th>
      <th class="columna_puntaje">Puntaje</th>
<?php foreach($cids as $p) { ?>
      <th class="columna_pregunta" data-cid="<?= $p['cid'] ?>"><?= reducir_texto($p['pregunta_texto'], 50) ?></th>
<?php } ?>
    </tr>
  </thead>
  <tbody>
<?php $i = 0; foreach($respuestas as $r) { $i++; ?>
    <tr data-user="<?= $r['usuario_id'] ?>">
      <td class="columna_indice"><?= $i ?></td>
      <td class="columna_nombres"><?= $r['usuario_nombres'] ?> <span>- @<?= $r['usuario'] ?></span></td>
      <td class="columna_puntaje"><?= $r['puntaje'] ?></td>
<?php foreach($cids as $p) { if(isset($r['children'][$p['cid']])) { if($r['children'][$p['cid']]['correcto']) { ?>
      <td data-cid="<?= $p['cid'] ?>" class="columna_pregunta columna_correcto"><i class="material-icons" style="color: #00d700;">check</i></td>
<?php } else { ?>
      <td data-cid="<?= $p['cid'] ?>" class="columna_pregunta columna_correcto"><i class="material-icons" style="color: #f80000;">clear</i></td>
<?php } } else { ?>
      <td data-cid="<?= $p['cid'] ?>" class="columna_pregunta columna_correcto"></td>
<?php } } ?>
    </tr>
<?php } ?>
  </tbody>
</table>
