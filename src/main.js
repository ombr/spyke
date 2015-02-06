Spyke = require("./spyke.js")

var
peers = { },
channel = new Spyke.endpoint("ws://localhost:12345/#luc")

channel.onpeer(function(peer) {
  if (peers[peer.uuid]) return
  peer.onconnect(function(socket) {
    peers[peer.uuid] = peer
    // channel.offer()
    socket.onmessage(function(msg) {
      if (msg == 'ping') socket.send('pong')
      if (msg == 'pong') document.querySelector('h1').innerHTML = ':)';
    })
    socket.send('ping')
  })
  peer.connect()
})
