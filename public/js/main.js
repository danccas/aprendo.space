function dragElement(elmnt) {
  var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
  if (document.getElementById(elmnt.id + "header")) {
    /* if present, the header is where you move the DIV from:*/
    document.getElementById(elmnt.id + "header").onmousedown = dragMouseDown;
  } else {
    /* otherwise, move the DIV from anywhere inside the DIV:*/
    elmnt.onmousedown = dragMouseDown;
  }

  function dragMouseDown(e) {
    e = e || window.event;
    e.preventDefault();
    // get the mouse cursor position at startup:
    pos3 = e.clientX;
    pos4 = e.clientY;
    document.onmouseup = closeDragElement;
    // call a function whenever the cursor moves:
    document.onmousemove = elementDrag;
  }

  function elementDrag(e) {
    e = e || window.event;
    e.preventDefault();
    // calculate the new cursor position:
    pos1 = pos3 - e.clientX;
    pos2 = pos4 - e.clientY;
    pos3 = e.clientX;
    pos4 = e.clientY;
    // set the element's new position:
    elmnt.style.top = (elmnt.offsetTop - pos2) + "px";
    elmnt.style.left = (elmnt.offsetLeft - pos1) + "px";
  }

  function closeDragElement() {
    /* stop moving when mouse button is released:*/
    document.onmouseup = null;
    document.onmousemove = null;
  }
}
window.Curly = function(params, popy) {
  var instance = null;
  var params2 = Object.assign({}, params);
  params2.beforeSend = function(re) {
    re.setRequestHeader("X_POPY", '9435');
    if(typeof params.beforeSend !== 'undefined') {
      params.beforeSend(re);
    }
  };
  params2.error = function(a,b,c) {
    if(a.status == 203) {
      popyConfirm('Su sesión ha caducado, ¿Desea iniciar nuevamente?', function() {
        var e = Popy({
          url: RAIZ_WEB,
          width: 500,
          height: 500,
          onSubmit: function() {
            e.close(true);
            Curly(params);
          },
          onClose: function() {
            e.close(true);
          }
        }).init(true);
      });
    } else if(a.status == 401) {
      if(!empty(popy)) {
        popy.close();
      }
      popyAlert('Usted no tiene provilegios para esta acción');

    } else if(a.status == 404 || a.status == 500) {
      //Enviar data al administrador
      if(!empty(popy)) {
        popy.close();
      }
      console.log('ERROR',a.status, a);
      popyAlert('Ha ocurrido un error');
    } else {
      typeof params.error === 'function' && params.error(a,b,c);
    }
  };
  return $.ajax(params2); 
};
function empty(mixed_var) {
  var undef, key, i, len;
  var emptyValues = [undefined,undef, null, false, 0, '', '0'];
  for (i = 0, len = emptyValues.length; i < len; i++) {
    if (mixed_var === emptyValues[i]) {
      return true;
    }
  }
  if (typeof mixed_var === 'object') {
    for (key in mixed_var) {
      if (mixed_var.hasOwnProperty(key)) {
        return false;
      }
    }
    return true;
  }
  return false;
}
function array_map(callback, listado) {
  for(var index in listado) {
    if(listado.hasOwnProperty(index)) {
      listado[index] = callback(listado[index]);
    }
  }
  return listado;
}
function array_merge(obj1, obj2) {
  var obj3 = {};
  for (var attrname in obj1) { if(obj1.hasOwnProperty(attrname)) {obj3[attrname] = obj1[attrname];}}
  for (var attrname in obj2) { if(obj2.hasOwnProperty(attrname)) {obj3[attrname] = obj2[attrname];}}
  return obj3;
}
$(document).ready(function() {
  $("[data-target='nav-mobile']").on('click', function() {
    $("#nav-mobile").removeClass('hide').addClass('show');
    if(!$('.sidenav-overlay').length) {
      $('body').append($('<div>').addClass('sidenav-overlay').on('click', function() {
        $("#nav-mobile").removeClass('show').addClass('hide');
        $(this).remove();
      }));
    }
  });
  $(document).on('click', '[data-is-ajax]', function(e) {
    e.preventDefault();
    console.log("CLICK ajax");
    var box = $(this).closest('[data-content-tablefy]');
    Curly({
      url: $(this).attr('href'),
      type: 'GET',
      success: function(data) {
        box.html(data);
      },
    });
  });
  $(document).on('click', 'a[data-popy]', function(e) {
    var a = $(this);
    if(typeof a.attr('data-confirm') == 'undefined') {
    	e.preventDefault();
    	popyLink($(this).attr('href'));
    	return false;
    }
  });
  $(document).on('click', "[data-confirm]", function(e) {
    var a = $(this);
    if(!empty(a.attr('data-permission'))) {
      console.log("EJECUTANDO");
      a.removeAttr('data-permission');
      if(!empty(a.attr('data-submit')) || a.attr('data-submit') === '') {
        a.closest('form').trigger('submit');
        return true;
      } else if(typeof a.attr('data-popy') === 'undefined') {
        //console.log('IR X LOCATION');
        window.location = a.attr('href');
        return;
      } else {
        e.preventDefault();
        var url = a.attr('href');
        popyLink(url);
        return false;
      }
    } else {
      e.preventDefault();
      var texto = a.attr('data-confirm') || 'Â¿Esta seguro de realizar esta acciÃ³n?';
      popyConfirm(texto, function() {
        var fc = a.attr('data-on-confirm');
        if(!empty(fc)) {
          typeof window[fc] === 'function' && window[fc].call(a);
        }
        a.attr('data-permission', 1);
        a.trigger('click');
      });
      return false;
    }
  });
});

setInterval(function() {
  
  $("[data-duration]").each(function() {
    var duration = (Date.now() / 1000) - parseInt($(this).attr('data-duration'));
    var seconds = parseInt(duration%60)
        , minutes = parseInt((duration/60)%60)
        , hours = parseInt((duration/(60*60))%24)
        , days  = parseInt(duration/(60*60*24));  
    hours = (hours < 10) ? "0" + hours : hours;
    minutes = (minutes < 10) ? "0" + minutes : minutes;
    seconds = (seconds < 10) ? "0" + seconds : seconds;
    $(this).text(hours + ":" + minutes + ":" + seconds);
  });
}, 500);
