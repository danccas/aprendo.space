"use strict";
process.title = 'socketAprendo';
var webSocketsServerPort = 8081;
///usr/local/lib/node_modules/websocket
var webSocketServer = require('/usr/local/lib/node_modules/websocket').server;

var http = require('https');
var fs = require('fs');
var rooms = {};

var TYPE_PRESENTER = 'dx';
var TYPE_VIEWER  = 'ax';
var TYPE_DRAWER   = 'vx';

var actualizar_cantidad = function(id) {
  var a = 0;
  var d = 0;
  var p = 0;
  for(var index in rooms[id][TYPE_PRESENTER]) {
    if(rooms[id][TYPE_PRESENTER][index].conexion !== false) {
      d++;
    }
  }
  rooms[id]['datos'][TYPE_PRESENTER] = d;
  for(var index in rooms[id][TYPE_VIEWER]) {
    if(rooms[id][TYPE_VIEWER][index].conexion !== false) {
      a++;
    }
  }
  rooms[id]['datos'][TYPE_VIEWER]  = a;
  for(var index in rooms[id][TYPE_DRAWER]) {
    if(rooms[id][TYPE_DRAWER][index].conexion !== false) {
      p++;
    }
  }
  rooms[id]['datos'][TYPE_DRAWER] = p;
};
var obtener_reporte = function (id) {
  var temp = JSON.parse(JSON.stringify(rooms[id].datos));
  temp.listado = {};
  for (var index in rooms[id][TYPE_VIEWER]) {
    temp.listado[index] = {
      id:        index,
      conectado: (rooms[id][TYPE_VIEWER][index].conexion !== false),
      materiales: rooms[id][TYPE_VIEWER][index].materiales,
    };
  }
  return temp;
}
var obtener_reporte_cantidad = function (id) {
  return rooms[id]['datos'];
}
function report_screen_material(room_id, cid) {
  var rp = {
    total: 0,
  };
  for (var index in rooms[room_id][TYPE_VIEWER]) {
    if(typeof rooms[room_id][TYPE_VIEWER][index].materiales[cid] !== 'undefined') {
      if(typeof rp[rooms[room_id][TYPE_VIEWER][index].materiales[cid]] !== 'undefined') {
        rp[rooms[room_id][TYPE_VIEWER][index].materiales[cid]]++;
      } else {
        rp[rooms[room_id][TYPE_VIEWER][index].materiales[cid]] = 1;
      }
      rp.total++;
    }
  }
  return rp;
}
function sendBroadcast(room_id, data) {
  for (var index in rooms[room_id][TYPE_VIEWER]) {
    if(rooms[room_id][TYPE_VIEWER][index].conexion !== false) {
      rooms[room_id][TYPE_VIEWER][index].conexion.sendUTF(JSON.stringify(data));
    }
  }
  for (var index in rooms[room_id][TYPE_PRESENTER]) {
    if(rooms[room_id][TYPE_PRESENTER][index].conexion !== false) {
      rooms[room_id][TYPE_PRESENTER][index].conexion.sendUTF(JSON.stringify(data));
    }
  }
  for (var index in rooms[room_id][TYPE_DRAWER]) {
    rooms[room_id][TYPE_DRAWER][index].sendUTF(JSON.stringify(data));
  }
}
var procesar_mensaje = function(data, connection, first, identidad) {
  if(typeof identidad.room_id === 'undefined') {
    //return;
  }
  if(typeof rooms[identidad.room_id] === 'undefined') {
    //return;
  }
  if(typeof rooms[identidad.room_id] === 'undefined') {
    rooms[identidad.room_id] = {
      datos: {
        fecha_desde: null,
        fecha_hasta: null,
        streaming: false,
        video: true,
      },
      screen: null,
    };
    rooms[identidad.room_id][TYPE_PRESENTER] = {};
    rooms[identidad.room_id][TYPE_VIEWER] = {};
    rooms[identidad.room_id][TYPE_DRAWER] = {};
  }
  if(identidad.tipo === TYPE_DRAWER) {
    if(first) {
      if(typeof rooms[identidad.room_id][TYPE_DRAWER][identidad.socketId] === 'undefined') {
        rooms[identidad.room_id][TYPE_DRAWER][identidad.socketId] = connection;
      } else {
        connection.sendUTF(JSON.stringify({
          acceso_bloqueado: true,
          mensaje: 'Se ha encontrado otra conexión existente DOCENTE',
        }));
        return;
      }
      identidad.registrado = true;
    }
    return obtener_reporte(identidad.room_id);

  } else if(identidad.tipo === TYPE_PRESENTER) {
    if(first) {
      if(typeof rooms[identidad.room_id][TYPE_PRESENTER][identidad.socketId] === 'undefined') {
        rooms[identidad.room_id][TYPE_PRESENTER][identidad.socketId] = {
          materiales: {},
          conexion: connection, 
        };
      } else {
        if(rooms[identidad.room_id][TYPE_PRESENTER][identidad.socketId].conexion === false) {
          rooms[identidad.room_id][TYPE_PRESENTER][identidad.socketId].conexion = connection;
        } else {
          connection.sendUTF(JSON.stringify({
            acceso_bloqueado: true,
            mensaje: 'Se ha encontrado otra conexión existente DOCENTE',
          }));
          return;
        }
      }
      identidad.registrado = true;
      actualizar_cantidad(identidad.room_id);
    }
    if(typeof data.info !== 'undefined') {
      rooms[identidad.room_id].datos.fecha_desde = data.info.fecha_desde;
      rooms[identidad.room_id].datos.fecha_hasta = data.info.fecha_hasta;
      rooms[identidad.room_id].datos.streaming   = data.info.streaming;
      rooms[identidad.room_id].datos.video       = data.info.video;
    }
    if(typeof data.screen !== 'undefined') {
      if(rooms[identidad.room_id].datos.streaming) {
        console.log('Enviar Pantalla a todos');
        rooms[identidad.room_id].screen = data.screen;
        sendBroadcast(identidad.room_id, {
          action: 'screen',
          screen: rooms[identidad.room_id].screen
        });
      } else {
        return {
          action: 'deny',
          message: 'Debe iniciar la sesión antes de poder enviar un material',
        };
      }
    }
    if(typeof data.action !== 'undefined') {
      if(data.action == 'video') {
        rooms[identidad.room_id].datos.video = data.video;
        var temp = JSON.parse(JSON.stringify(rooms[identidad.room_id].datos));
        temp.action = 'video';
        sendBroadcast(identidad.room_id, temp);
        return false;
      } else if(data.action == 'iniciar') {
        if(!rooms[identidad.room_id].datos.streaming) {
          rooms[identidad.room_id].datos.fecha_desde = parseInt((new Date).getTime() / 1000);
          rooms[identidad.room_id].datos.fecha_hasta = null;
          rooms[identidad.room_id].datos.streaming = true;
          var temp = JSON.parse(JSON.stringify(rooms[identidad.room_id].datos));
          temp.action = 'iniciar';
          sendBroadcast(identidad.room_id, temp);
          return false;
        } else {
          return {
            action: 'deny',
            message: 'Acción no permitida',
          };
        }
      } else if(data.action == 'detener') {
        if(rooms[identidad.room_id].datos.streaming) {
          rooms[identidad.room_id].datos.streaming = false;
          rooms[identidad.room_id].datos.fecha_hasta = parseInt((new Date).getTime() / 1000);
          var temp = JSON.parse(JSON.stringify(rooms[identidad.room_id].datos));
          temp.action = 'detener';
          sendBroadcast(identidad.room_id, temp);
          return false;
        } else {  
          return {
            action: 'deny',
            message: 'Acción no permitida',
          };
        }
      }
    }
    return obtener_reporte(identidad.room_id);

  } else if(identidad.tipo === TYPE_VIEWER) {
    if(first) {
      if(typeof rooms[identidad.room_id][TYPE_VIEWER][identidad.socketId] === 'undefined') {
        rooms[identidad.room_id][TYPE_VIEWER][identidad.socketId] = {
          materiales: {},
          conexion: connection,
        };
      } else {
        if(rooms[identidad.room_id][TYPE_VIEWER][identidad.socketId].conexion === false) {
          rooms[identidad.room_id][TYPE_VIEWER][identidad.socketId].conexion = connection;
        } else {
          connection.sendUTF(JSON.stringify({
            acceso_bloqueado: true,
            mensaje: 'Se ha encontrado otra conexión existente ALUMNO',
          }));
          return;
        }
      }
      identidad.registrado = true;
      actualizar_cantidad(identidad.room_id);
      //var reporte = obtener_reporte(identidad.room_id);
      var reporte = {
        action: 'login',
        nick: identidad.nick,
        amounts: obtener_reporte_cantidad(identidad.room_id)
      };
      for (var index in rooms[identidad.room_id][TYPE_PRESENTER]) {
        if(rooms[identidad.room_id][TYPE_PRESENTER][index].conexion !== false) {
          rooms[identidad.room_id][TYPE_PRESENTER][index].conexion.sendUTF(JSON.stringify(reporte));
        }
      }
      for (var index in rooms[identidad.room_id][TYPE_DRAWER]) {
        rooms[identidad.room_id][TYPE_DRAWER][index].sendUTF(JSON.stringify(reporte));
      }
    } else if(typeof data.mark !== 'undefined') {
      if(true) {
        rooms[identidad.room_id][TYPE_VIEWER][identidad.socketId].materiales[data.mark.cid] = data.mark.opcion_id;
        //var reporte = obtener_reporte(identidad.room_id);
        var reporte = {
          action: 'mark',
          nick: data.nick,
          uid: identidad.socketId,
          cid: data.mark.cid,
          oid: data.mark.opcion_id,
          text: data.mark.text,
          puntaje: data.mark.puntaje,
          status: data.mark.status,
          report: report_screen_material(identidad.room_id, data.mark.cid),
        };
        for (var index in rooms[identidad.room_id][TYPE_PRESENTER]) {
          if(rooms[identidad.room_id][TYPE_PRESENTER][index].conexion !== false) {
            rooms[identidad.room_id][TYPE_PRESENTER][index].conexion.sendUTF(JSON.stringify(reporte));
          }
        }
      }
    }
    return false;
  }
  return {
    mensaje: 'ERROR',
    data: data
  };
};
var server = http.createServer({
  cert: fs.readFileSync('/etc/nginx/rules/ssl/aprendo.space.pem'),
  key: fs.readFileSync('/etc/nginx/rules/ssl/aprendo.space.key')
},function(req, res) {
  console.log(res.socket.remoteAddress);
  if(res.socket.remoteAddress == '127.0.0.1' || res.socket.remoteAddress == '::ffff:172.17.0.3') {
    if(req.method == 'POST') {
      var body = "";
      var out = null;
      req.on('data', function (chunk) {
        body += chunk;
      });
      req.on('end', function () {
        var xhr = false;
        try {
          xhr = JSON.parse(body);
        } catch(err) {
          xhr = false;
          console.log('JSON RECIBIDO NO VALIDO');
          console.log(err);
        }
        if(xhr !== false) {
          console.log('REQUEST', xhr);
          if(typeof xhr.identidad !== 'undefined') {
            out = procesar_mensaje(xhr.data, false, false, xhr.identidad);
            if(typeof out === 'object' && out !== false) {
              if(typeof rooms[xhr.identidad.room_id][xhr.identidad.tipo][xhr.identidad.socketId] !== 'undefined') {
                if(rooms[xhr.identidad.room_id][xhr.identidad.tipo][xhr.identidad.socketId].conexion) {
                  rooms[xhr.identidad.room_id][xhr.identidad.tipo][xhr.identidad.socketId].conexion.sendUTF(JSON.stringify(out));
                } else {
                  console.log('Conexión destino está inactiva');
                }
              } else {
                console.log('Conexión destino no registrada');
              }
            }
          }
        }
      });
      res.writeHead(200);
      res.write(JSON.stringify(out));
      res.end();
    }
  }
  res.writeHead(404);
  res.end();
});
server.listen(webSocketsServerPort, function() {
  console.log((new Date()) + " Server is listening on port " + webSocketsServerPort);
});
var wsServer = new webSocketServer({
  httpServer: server
});
wsServer.on('request', function(request) {
  console.log((new Date()) + ' Connection from origin ' + request.origin + '.');
  var connection = request.accept(null, request.origin);
  var identidad  = false;
  var first      = true;
  console.log((new Date()) + ' Connection accepted.');
  /* Enviamos la hora actual */
  connection.sendUTF(JSON.stringify({
    tipo: 'unix',
    time: Math.floor(new Date() / 1000)
  }));
  connection.on('message', function(message) {
    if (message.type !== 'utf8') {
      return;
    }
    console.log('RECEIVE DATA: ' + message.utf8Data);
    var data = null;
    try {
      data = JSON.parse(message.utf8Data);
    } catch(err) {
      data = null;
      console.log(err);
    }
    if(data == null) {
      return;
    }
    if(first) {
      identidad = {
        id: data.id,
        socketId: data.id,
        tipo: data.tipo,
        room_id: data.room_id,
        registrado: false,
        nick: data.nick,
      };
      if(typeof data.socketId !== 'undefined') {
        identidad.socketId = data.socketId;
      }
    } else {
      if(typeof identidad.tipo !== 'undefined') {
        console.log('ERROR#1');
      /*ERROR ¨*/
      }
      if(typeof identidad.room_id !== 'undefined') {
        console.log('ERROR#2');
      /*ERROR ¨*/
      }
    }
    var out = procesar_mensaje(data, connection, first, identidad);
    first = false;
    if(typeof out === 'object' && out !== false) {
      connection.sendUTF(JSON.stringify(out));
    }
  });
  connection.on('close', function(connection) {
    console.log((new Date()) + " Peer " + connection.remoteAddress + " disconnected.");
    if(identidad !== false && identidad.registrado) {
      if(identidad.tipo == TYPE_VIEWER) {
        delete rooms[identidad.room_id][TYPE_VIEWER][identidad.socketId].conexion;
        rooms[identidad.room_id][TYPE_VIEWER][identidad.socketId].conexion = false;
        actualizar_cantidad(identidad.room_id);
        //var reporte = obtener_reporte(identidad.room_id);
        var reporte = {
          action: 'logout',
          nick: identidad.nick,
          amounts: obtener_reporte_cantidad(identidad.room_id)
        };
        for (var index in rooms[identidad.room_id][TYPE_PRESENTER]) {
          if(rooms[identidad.room_id][TYPE_PRESENTER][index].conexion !== false) {
            rooms[identidad.room_id][TYPE_PRESENTER][index].conexion.sendUTF(JSON.stringify(reporte));
          }
        }
        for (var index in rooms[identidad.room_id][TYPE_DRAWER]) {
          rooms[identidad.room_id][TYPE_DRAWER][index].sendUTF(JSON.stringify(reporte));
        }

      } else if(identidad.tipo == TYPE_PRESENTER) {
        delete rooms[identidad.room_id][TYPE_PRESENTER][identidad.socketId].conexion;
        rooms[identidad.room_id][TYPE_PRESENTER][identidad.socketId].conexion = false;
        actualizar_cantidad(identidad.room_id);

      } else if(identidad.tipo == TYPE_DRAWER) {
        delete rooms[identidad.room_id][TYPE_DRAWER][identidad.socketId];
      }
    }
  });
});
