Spyke = require("./spyke.js")

var channel = new Spyke.endpoint("ws://localhost:12345/#luc")
channel.onpeer(function(peer) {
  peer.onconnect(function(socket) {
    socket.onmessage(function(msg) {
      if (msg == 'ping') socket.send('pong')
      if (msg == 'pong') document.querySelector('h1').innerHTML = ':)';
    })
    socket.send('ping')
  })
  peer.connect()
})
