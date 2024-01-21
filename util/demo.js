const Srf = require('drachtio-srf');
const srf = new Srf();

srf.connect({host: '127.0.0.1', port: 9022, secret: 'cymru'});

srf.on('connect', (err, hp) => {
  if (err) return console.log(`Error connecting: ${err}`);
  console.log(`connected to server listening on ${hp}`);

  setInterval(optionsPing, 10000);
});

function optionsPing() {
  srf.request('sip:tighthead.drachtio.org', {
    method: 'OPTIONS',
    headers: {
      'Subject': 'OPTIONS Ping'
    }
  }, (err, req) => {
    if (err) return console.log(`Error sending OPTIONS: ${err}`);
    req.on('response', (res) => {
      console.log(`Response to OPTIONS ping: ${res.status}`);
    });
  });
}
