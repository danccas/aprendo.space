<style>
.table-data th, .table-data td {
  vertical-align: middle;
}
.table-data td > .material-icons {
  font-size: 20px;
}
.columna_nombres {
  width: 200px;
}
.columna_nombres span {
  font-size: 11px;
  color: #878787;
}
.columna_indice {
  width: 50px;
  text-align: center!important;
}
.columna_acciones {
  width: 100px;
  text-align: center!important;
}
.columna_puntaje {
  width: 100px;
  text-align: center!important;
}
.columna_pregunta {
  width: 100px;
}
.columna_correcto {
  text-align: center!important;
}
.is-touch {
  padding: 7px;
  cursor: pointer;
}
.is-touch:hover {
  background: #e6e6e6;
  border-radius: 20px;
}
#toggleVideo {
  margin-right: 10px;
}
#toggleVideo[data-streaming="1"] {
  background: #ff3c3c;
  color: #fff;
  border: 0;
}
#toggleVideo[data-streaming="0"] {
  background: #01c400;
  color: #fff;
  border: 0;
}
</style>
<div class="container">
<h1 class="has-text-white">#<?= toHashtag($nombre) ?></h1>
<div class="columns is-multiline">
  <div class="column is-3">
    <div class="card" style="min-height: 170px">
      <div class="card-header">
        <p class="card-header-title">Estado</p>
        <span class="card-header-icon">
          <?php if(!empty($fecha_hasta)) { ?>
          <span class="realtime"><?= $duracion ?></span>
          <?php } elseif(!empty($fecha_desde)) { ?>
          <span class="realtime recording" data-duration="<?= strtotime($fecha_desde) ?>"></span>
          <?php } else { ?>
          <span class="realtime" style="display:none;"></span>
          <?php } ?>
        </span>
      </div>
      <div class="card-content has-text-centered">
        <div class="has-text-centered">
<?php if(!empty($fecha_hasta)) { ?>
          <span class="button is-alert"><?= fecha($fecha_desde, true) ?></span>
<?php } elseif(!empty($fecha_desde)) { ?>
          <button id="btnRecord" class="button is-danger">Detener</button>
<?php } else { ?>
          <button id="btnRecord" class="button is-success">Iniciar</button>
<?php } ?>
        </div>
        <div class="viewers"></div>
      </div>
    </div>
  </div>
  <div class="column is-9">
    <div class="card" style="min-height: 170px">
      <div class="card-content">
        <ul id="userLog" style="overflow-y: hidden;max-height: 125px;"></ul>
      </div>
    </div>
  </div>
<?php if(empty($fecha_hasta)) { ?>
  <div class="column is-12">
    <div class="card">
      <div class="card-header">
        <p class="card-header-title">Relación de cuestionarios</p>
        <span href="#" class="card-header-icon" aria-label="more options">
          <a id="toggleVideo" class="button is-small" data-streaming="<?= $streaming ?>"><?= $streaming == 1 ? 'Detener Streaming' : 'Iniciar Streaming' ?></a>
          <a <?= Route::href('cuestionarios') ?> class="button is-small naranja">Cuestionarios</a>
        </span>
      </div>
      <div class="card-content">
        <div class="code" style="width: 500px;margin: 0 auto;margin-bottom: 20px;">
          https://aprendo.space/room/<?= $codigo ?>
        </div>
        <div class="tabs is-small">
          <ul id="tabsCuestionarios">
<?php foreach($cuestionarios as $c) { ?>
            <li data-cid="<?= $c['id'] ?>"><a><?= $c['nombre'] ?> (<?= $c['preguntas_cantidad'] ?>)</a></li>
<?php } ?>
          </ul>
        </div>
        <div id="pageCuestionarios" class="container section">
        </div>
      </div>
    </div>
  </div> 
<?php } else { ?>
  <div class="column is-12">
    <div class="card">
      <div class="card-header">
        <p class="card-header-title">Relación de Preguntas Enviadas</p>
      </div>
      <div class="card-content">
      <?php include(Route::g()->attr('views') . 'sesion.cuestionario.php'); ?>
      </div>
    </div>
  </div>
<?php } ?>
  <div class="column is-12">
    <div class="card">
      <div class="card-header">
        <p class="card-header-title">Relación de Espéctadores</p>
      </div>
      <div class="card-content" id="receiveCuadrilla">

      </div>
    </div>
  </div>
</div>
</div>

<script>
function getCuadrilla() {
  Curly({
    url: '<?= Route::link('getCuadrilla') ?>',
    type: 'GET',
    complete: function(xhr) {
      if(xhr.status == 200) {
        $("#receiveCuadrilla").html(xhr.responseText);
      }
    },
  });
}
$(document).ready(function() {
<?php if($estado == SESION_FINALIZADO) { ?>
getCuadrilla();
<?php } else { ?>
getCuadrilla();
setInterval(getCuadrilla, 15000);
<?php } ?>
});
</script>
<?php if($estado != SESION_FINALIZADO) { ?>
<style>
.resize-drag {
  display: none;
  position: fixed;
  bottom: 0;
  right: 100px;
  width: 600px;
  height: 300px;
  z-index:999;
  background: rgb(1, 54, 125);
  border-radius: 7px;
  overflow: hidden;
}
#StreamingVideo {
  padding: 10px;
  width: 100%;
  height: 100%;
}
</style>
<div class="resize-drag">
  <div id="StreamingVideo"></div>
</div>
<script src="https://meet.jit.si/external_api.js"></script>
<script src="/js/resize.js"></script>
<script>
var api = null;
var domain = 'meet.jit.si';
var options = {
  roomName: "<?= $codigo_streaming ?>",
  userInfo: {
    displayName: '@<?= Identify::g()->user ?>',
  },
  width: '100%',
  height: '100%',
  parentNode: document.getElementById('StreamingVideo'),
  configOverwrite: {
    enableNoAudioDetection: false,
    enableNoisyMicDetection: false,
  },
  interfaceConfigOverwrite: {
    MOBILE_APP_PROMO: false,
    LANG_DETECTION: true,
    filmStripOnly: false,
    SHOW_JITSI_WATERMARK: false,
    SHOW_WATERMARK_FOR_GUESTS: false,
    DEFAULT_BACKGROUND: '#1A73E8',
    GENERATE_ROOMNAMES_ON_WELCOME_PAGE: false,
    SHOW_CHROME_EXTENSION_BANNER: false,
    SHOW_PROMOTIONAL_CLOSE_PAGE: false,
    DEFAULT_REMOTE_DISPLAY_NAME: 'Espectador',
    TOOLBAR_BUTTONS: [
        'microphone', 'camera', 'desktop', 'fullscreen', 'videobackgroundblur','stats','settings',
        'fodeviceselection', 'mute-everyone', 'tileview', 'raisehand','chat',
    ],
    SETTINGS_SECTIONS: [ 'devices', 'language', 'moderator' ],
  },
}
</script>
<script>
$(document).ready(function() {
  dragElement(document.getElementsByClassName('resize-drag')[0]);
});

function iniciar_streaming() {
  api = new JitsiMeetExternalAPI(domain, options);
  api.executeCommand('subject', 'En linea');
  api.addEventListener('readyToClose',  function() {
    detener_streaming();
  });
  $(".resize-drag").slideDown();
}
function detener_streaming() {
  api.dispose();
  $(".resize-drag").slideUp();
}
function insertar_cuestionario(popy, cu) {
  Curly({
    url: '<?= Route::link('insertar') ?>',
    type: 'POST',
    data: { cuestionario_id: cu.id },
    success: function(a, b, c) {
      if(c.status === 201)  {
        $("#tabsCuestionarios").append($('<li>').attr('data-cid', cu.id).html($('<a>').text(cu.nombre + ' (' + cu.preguntas + ')')));
      } else if(c.status === 304) {
      }
    },
  });
  popy.parametros.father.close();
}
(function() {
  var tabs  = $("#tabsCuestionarios");
  var boxes = $("#pageCuestionarios");
  var actualizar = function(cid, cb) {
    var box = boxes.find("[data-cid='" + cid + "']");
    Curly({
      url: '<?= Route::link('tabs') ?>',
      type: 'POST',
      data: { cid: cid },
      success: function(x) {
        box.html(x);
        typeof cb === 'function' && cb();
      }
    });
  };
  tabs.on('click', 'li', function() {
    var cid = $(this).attr('data-cid');
    tabs.children().removeClass('is-active');
    tabs.find("[data-cid='" + cid + "']").addClass('is-active');
    if(!boxes.find("[data-cid='" + cid + "']").length) {
      boxes.append($("<div>").attr('data-cid', cid).hide());
      actualizar(cid, function() {
        boxes.children().filter(function() { return $(this).attr('data-cid') != cid; }).slideUp();
        boxes.find("[data-cid='" + cid + "']").slideDown();
      });
    } else {
      boxes.children().filter(function() { return $(this).attr('data-cid') != cid; }).slideUp();
      boxes.find("[data-cid='" + cid + "']").slideDown();
    }
  });
})();
</script>
<script>
var IDENTIDAD = {
  tipo: 'dx',
  id: <?= Identify::g()->id ?>,
  room_id: <?= $id ?>,
};
</script>
<script src="/js/socket-client.js"></script>
<script>
<?php if($estado == SESION_INICIADO && $streaming) { ?>
$(document).ready(function() {
  iniciar_streaming();
});
<?php } ?>
function write_log(txt, color) {
  var now = new Date();
  now = now.getHours() + ':' + now.getMinutes();
  var tt = $("<li>").text(now + ' => ' + txt).css({ color: color });
  $("#userLog").prepend(tt);
}
function agregar_marcacion(x) {
  var tabla = $(".table-data");
  if(!tabla.find("thead [data-cid='" + x.cid + "']").length) {
    tabla.find('thead tr').append($('<th>')
      .addClass('columna_pregunta')
      .attr('data-cid', x.cid).text(x.text));
    tabla.find('tbody tr').append($('<td>')
      .addClass('columna_pregunta')
      .attr('data-cid', x.cid));
  }
  tabla.find("tbody tr[data-user='" + x.uid + "'] td.columna_puntaje")
    .text(x.puntaje);
  tabla.find("tbody tr[data-user='" + x.uid + "'] td[data-cid='" + x.cid + "']")
    .addClass('columna_correcto')
    .html(x.status ? '<i class="material-icons" style="color: #00d700;">check</i>' : '<i class="material-icons" style="color: #f80000;">clear</i>');
}
$(document).on('socket-message', function(event, data) {
  if(data.action == 'deny') {
    popyAlert(data.message);
  } else if(data.action == 'iniciar') {
    write_log('Se ha dado inicio a la sala', 'green');
    $(".realtime").addClass('recording').attr('data-duration', Date.now() / 1000).slideDown();
  } else if(data.action == 'detener') {
    write_log('Se ha detenido la sala', 'red');
    $(".realtime").removeClass('recording').removeAttr('data-duration');
    setTimeout(function() { location.reload(); }, 1000);
  } else if(data.action == 'login') {
    write_log('El usuario @' + data.nick + ' se ha unido de la sala.', 'green');
  } else if(data.action == 'logout') {
    write_log('El usuario @' + data.nick + ' se ha retirado de la sala.', 'red');
  } else if(data.action == 'screen') {
    if(data.screen !== null) {
      write_log('Se ha compatido un material con los presentes.', '#ff8d00');
    } else {
      write_log('Se ha retirado el material a los presentes.', '#903013');
    }
    if(typeof data.screen !== 'undefined' && data.screen !== null) {
      var sec  = $(".box-pregunta[data-cid='" + data.screen.id + "']").find(".tag-sec");
      sec.slideUp(800, function() {
        var next = parseInt(sec.attr('data-check')) + 1;
        sec.html('<i class="material-icons">check</i>' + next).slideDown();
        sec.attr('data-check', next);
      });
    }
  } else if(data.action == 'mark') {
    write_log('El usuario @' + data.nick + ' ha respondido ' + (data.status ? 'correctamente.' : 'incorrectamente.'), '#1a73e8');
    agregar_marcacion(data);
    if(data.report.total > 0) {
      $(".box-pregunta[data-cid=" + data.cid + "]").find('.opcion>span').slideUp(500, function() {
        $(this).attr('title','Respuestas: 0').text('0%');
        for(var i in data.report) {
          if(data.report.hasOwnProperty(i) && i !== 'total') {
            var porce = data.report[i] * 100 / data.report.total;
            $(".box-pregunta[data-cid=" + data.cid + "]").find('.opcion[data-id=' + i + ']>span').attr('title', 'Respuestas: ' + data.report[i]).text(porce + '%');
          }
        }
        $(".box-pregunta[data-cid=" + data.cid + "]").find('.opcion>span').slideDown();
      });
    }
  }
  if(typeof data.amounts !== 'undefined') {
    $(".viewers").slideUp();
    $(".viewers").text(data.amounts.ax + ' espectador(es)').slideDown();
  }
});
$("#btnRecord").on('click', function() {
  var rz = this;
  if(typeof rz.bloqueado !== 'undefined' && rz.bloqueado) {
    console.log('bloqueado', rz.bloqueado);
    return;
  }
  rz.bloqueado = true;
  Curly({
    url: '<?= Route::link('changeStatus') ?>',
    type: 'POST',
    dataType: 'json',
    success: function(xhr) {
      if(xhr.code === 201) {
        $(rz).attr('class', xhr.message.next.class);
        $(rz).text(xhr.message.next.text);
      }
    },
    complete: function() {
      rz.bloqueado = false;
    },
  });
});
$("#toggleVideo").on('click', function() {
  var rz = this;
  if(typeof rz.bloqueado !== 'undefined' && rz.bloqueado) {
    console.log('bloqueado', rz.bloqueado);
    return;
  }
  rz.bloqueado = true;
  Curly({
    url: '<?= Route::link('toggleVideo') ?>',
    type: 'POST',
    dataType: 'json',
    complete: function(xhr) {
      console.log('request', xhr, rz);
      if(xhr.status === 201) {
        $(rz).attr('data-streaming', xhr.responseJSON.message.streaming ? 1 : 0).text(xhr.responseJSON.message.text);
        if(xhr.responseJSON.message.streaming) {
          iniciar_streaming(); 
        } else {
          detener_streaming();
        }
      }
      setTimeout(function() {
        rz.bloqueado = false;
      }, 2000);
    },
  });
});
$("#pageCuestionarios").on('click', '.box-pregunta', function() {
  var rz = this;
  if(typeof rz.bloqueado !== 'undefined' && rz.bloqueado) {
    console.log('bloqueado', rz.bloqueado);
    return;
  }
  rz.bloqueado = true;
  Curly({
    url: '<?= Route::link('insertScreen') ?>',
    type: 'POST',
    dataType: 'json',
    data: { cid: $(rz).attr('data-cid') },
    complete: function(xhr) {
      if(xhr.status == 406) {
        popyAlert(xhr.responseJSON.message);
      } else if(xhr.status == 304) {
        $(rz).closest('.columns').find('.box-pregunta').removeClass('in-screen');
      } else if(xhr.status == 201) {
        $(rz).closest('.columns').find('.box-pregunta').removeClass('in-screen');
        $(rz).addClass('in-screen');
      }
      rz.bloqueado = false;
    },
  });
});
</script>
<?php } ?>
