if(typeof document.librarys === 'undefined') {
  document.librarys = {};
}
function callAllFunctions(name) {
  if(typeof document.librarys[name] !== 'undefined') {
//    console.log('LLAMANDO A WAITINGS', document.librarys[name].waiting);
    document.librarys[name].waiting.forEach(function(x){
      x();
    });
  }
}
function requireJS(url, name) {
//  console.log('requireJS', typeof url, url, name);
  if(typeof name === 'function') {
    if(typeof document.librarys[url] === 'undefined') {
      document.librarys[url] = { status: -1, url: {}, waiting: [] };
      document.librarys[url].waiting.push(name);
    } else if(document.librarys[url].status == 2) {
      name();
    } else {
      document.librarys[url].waiting.push(name);
    }
    return;
  }
  name = name || url;
  if(typeof document.librarys[name] !== 'undefined') {
    if(document.librarys[name].status !== -1) {
//      console.log('YA EXISTE', name, document.librarys[name]);
      return;
    }
  } else {
    document.librarys[name] = {status: null, url:{}, waiting: [] };
  }
  var defer = true;
  if(typeof url === 'object') {
    defer = false;
    url.forEach(function(x){
      document.librarys[name].url[x] = 0;
    });
  } else {
    document.librarys[name].url[url] = 0;
  }
//console.log("URLSSSS", name, url, document.librarys[name].url);
  document.librarys[name].status = 1;
  for(var ind in document.librarys[name].url) {
    if(document.librarys[name].url.hasOwnProperty(ind)) {
      var turl = ind;;
      var script = document.createElement("script");
      script.onload = function() {
//        console.log("FIN", this.url);
        document.librarys[name].url[this.url] = 2;
        for(var index in document.librarys[name].url) { 
          if(document.librarys[name].url.hasOwnProperty(index)) {
            if(document.librarys[name].url[index] !== 2) {
              return;
            }
          }
        }
        document.librarys[name].status = 2;
        callAllFunctions(name);
      };
      script.src = ind;
      script.url = ind;
      if(defer) {
        script.defer = true;
      } else {
        script.async = false;
      }
      document.head.appendChild(script);
      document.librarys[name].url[ind] = 1;
//      console.log("IMPORTANTDO", defer, ind);
    }
  }
}
