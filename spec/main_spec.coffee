Spyke = require "../src/spyke.js"

describe 'endpoint', ()->
  it 'can play ping pong', (done)->
    caller = new Spyke.endpoint('s3://spyke/luc')
    caller.onPeer (peer)->
      peer.connect (socket)->
        socket.send('PING')
      peer.onConnect callback

    callee = new Spyke.endpoint('s3://spyke/luc')
    callee.onPeer (peer)->
      peer.onConnect callback

    callback = (socket)->
      socket.onMessage (message)->
        console.log message
        socket.send('PONG') if message == 'PING'
        done() if message == 'PONG'
