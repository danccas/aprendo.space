var ElementsAdd = function(params) {
  var cantidad = 0;
  var metodos = {
    init: function() {
      if(!empty(params.clone)) {
        var box = $(params.clone);
        box.removeAttr('id');
        box.removeClass('hide');
        params.code = box[0].outerHTML;//outerHTML();
        box.remove();
      }
      if(!empty(params.join)) {
        $(params.contain).find(params.join).each(function() {
          var html = $(this)[0].outerHTML;//outerHTML();
          $(this).remove();
          metodos._add(null, html, true);
        });
      }
      if(!empty(params.plus)) {
        $(params.plus).on("click", function(){
          metodos.add();
        });
      }
    },
    corregir_index: function() {
      var i = 1;
      $(params.contain).children().each(function() {
        $(this).find('.indice>.key').text(i);
        i++;
      });
    },
    add: function(x) {
      metodos._add(x, params.code);
    },
    _add: function(x, contnt, z) {
      if(cantidad < params.max) {
        var temp = $(contnt);
        $(params.contain).append(temp);
        var eliminar = $("<div>");
        eliminar.addClass('eliminar');
        eliminar.attr('style','position: absolute;bottom: 0;left: 0;right: 0;font-size: 10px;color: red;line-height: 25px;text-align: center;cursor: pointer;');
        eliminar.html("QUITAR");
        eliminar.on("click", function() {
          if(cantidad > params.min) {
            typeof params.onremove === 'function' && params.onremove(temp);
            cantidad--;
            temp.remove();
            metodos.corregir_index();
          }
        });
        temp.find('.indice>.key').text(cantidad + 1);
        if(temp.find('.indice>.eliminar').length <= 0) {
          temp.find('.indice').append(eliminar);
        }
        typeof params.onadd === 'function' && params.onadd(temp, cantidad, x);
        cantidad++;
        if(typeof z === 'undefined') {
          metodos.corregir_index();
        }
      } else {
        typeof params.onlimit === 'function' && params.onlimit();
      }
    },
  };
  return metodos;
};
