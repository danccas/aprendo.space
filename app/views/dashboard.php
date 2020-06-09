<div class="container">
  <h1 class="has-text-white">#Principal</h1>
  <div class="card">
    <div class="card-content">

<?php if(empty($sesiones)) { ?>
      <div class="has-text-centered">
        <i>No se ha encontrado sesiones públicas, cree su propia sesión haciendo click <a href="/sesiones">Aquí</a></i>
      </div>
<?php } else { ?>
      <div class="columns is-multiline">
<?php foreach($sesiones as $s) { ?>
        <div class="column is-3">
          <div class="card" style="max-width:400px;">
            <a href="/room/<?= $s['codigo'] ?>" target="_blank">
            <div class="card-content box-sesion">
              <div class="box-sesion-image">
                <div class="box-sesion-user"><?= $s['usuario'] ?></div>
                <div class="box-sesion-time" data-duration="<?= strtotime($s['fecha_desde']) ?>"></div>
              </div>
              <div class="content">
                <h5><?= $s['area'] ?></h5>
                <h1><?= $s['nombre'] ?></h1>
              </div>
            </div>
            </a>
          </div>
        </div>
<?php } ?>
      </div>
<?php } ?>
    </div>
  </div>
</div>
