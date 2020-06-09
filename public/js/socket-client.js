$(function () {
  "use strict";

  if(typeof IDENTIDAD === 'undefined' || empty(IDENTIDAD)){
     return false;
  }
  window.WebSocket = window.WebSocket || window.MozWebSocket;
  if (!window.WebSocket) {
    content.html($('<p>',
      { text:'Sorry, but your browser doesn\'t support WebSocket.'}
    ));
    return;
  }
  var INTENTOS_SOCKET = 0;
  var connection = false;
  $(".socket_signal").show(); 

  function enviar(cx, msg) {
    var json = JSON.stringify(msg);
    cx.send(json);
  }

  conectarSocket();

  function conectarSocket() {
    INTENTOS_SOCKET++;
    var EVENTO_ERROR = 0;
    var SERVER = 'wss://aprendo.space:8081';
    connection = new WebSocket(SERVER);
    connection.onopen = function () {
      INTENTOS_SOCKET = 0;
      enviar(connection, IDENTIDAD);
      $(".socket_signal").removeClass("socket_medium");
      $(".socket_signal").removeClass("socket_slow");
      $(".socket_signal").addClass("socket_fast");
    };
    connection.onerror = function (error) {
      $(".socket_signal").removeClass("socket_medium");
      $(".socket_signal").removeClass("socket_fast");
      $(".socket_signal").addClass("socket_slow");
      EVENTO_ERROR = 1;
      if(INTENTOS_SOCKET==5){
        popyAlert('Error en la conexión, no se ha podido establecer conectar al servidor.');
      }
      connection.close();
      connection = false;
      setTimeout(conectarSocket,1000);
    };
    connection.onclose = function (error) {
      $(".socket_signal").removeClass("socket_medium");
      $(".socket_signal").removeClass("socket_fast");
      $(".socket_signal").addClass("socket_slow");
      if(EVENTO_ERROR==0){
        if(INTENTOS_SOCKET==5){
          popyAlert('Error en la conexión, no se ha podido establecer conectar al servidor.');
        }
        connection.close();
        connection = false;
        setTimeout(conectarSocket,1000);
      
      }
    };
    connection.onmessage = function (message) {
      try {
        console.log('RECEIVE', message.data);
        var json = JSON.parse(message.data);
        var fecha_actual = Date.now().toString().substr(0,10);
        var c = 'unix';
        if(!empty(json.acceso_bloqueado)) {
          $(".struct_web_body").remove();
          popyAlert(json.mensaje);
          window.location = RAIZ_WEB + 'finalizar';
          return;
        } else {
          $(document).trigger('socket-message', [json]);
        }
      } catch (e) {
        console.log('Invalid JSON: ', message.data, e);
        return;
      }
    };
    $(document).off('socket-send').on('socket-send', function(event, data) {
      if(connection !== false) {
        enviar(connection, data);
      } else {
        console.log('Sin conexion para enviar');
      }
    });
  }
});
function mostrar_detalles_de_evaluacion(x) {
  var listado = $(".listado_alumnos");
  listado.find(".titular>span").text(x.alumnos);
  $.each(x.listado, function(s, y) {
    var card = listado.find(".carta[data-alumno='" + y.id + "']");
    if(!empty(y.conectado)) {
      card.removeClass('registered').addClass('connected');
    } else {
      card.removeClass('connected').addClass('registered');
    }
    if(!empty(y.preguntas)) {
      var porcentaje = parseInt(count(y.preguntas) * 100 / parseInt(x.cantidad_preguntas));
      card.find('.barra').animate({width: porcentaje + '%'}, 1000);
      if(porcentaje === 100) {
        if(!card.find(".preguntas span[data-id='completo']").is(':visible')) {
          card.find(".preguntas span").slideUp();
          card.find(".preguntas span[data-id='completo']").slideDown();
        }
      } else {
        $.each(y.preguntas, function(k, v) {
          var preg = card.find(".preguntas span[data-id='" + k + "']");
          if(!empty(v)) {
            preg.slideDown();
          } else {
            preg.slideUp();
          }
        });
      }
    }
  });
}
