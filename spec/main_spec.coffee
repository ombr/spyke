Spyke = require "../src/spyke.js"

describe 'endpoint', ()->
  it 'Can play ping/pong', (done)->
    #caller = new Spyke.endpoint('s3://spyke/luc')
    caller = new Spyke.endpoint('ws://localhost:12345/#luc')
    caller.onpeer (peer)->
      peer.onconnect callback
      peer.onconnect (socket)->
        socket.send('PING')
      peer.connect()

    #callee = new Spyke.endpoint('s3://spyke/luc')
    callee = new Spyke.endpoint('ws://localhost:12345/#luc')
    callee.onpeer (peer)->
      peer.onconnect callback

    callback = (socket)->
      socket.onmessage (message)->
        console.log message
        socket.send('PONG') if message == 'PING'
        done() if message == 'PONG'
