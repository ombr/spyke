/**
 * WebSocket class
 */

Channel = require("./abstract.js")
//WebSocket = require("websocket").client;

module.exports = Channel.extends({


  host: false,
  room: false,


  __construct: function(uri) {

    console.log('websocket++', uri)
    var bits = uri.split('#')
    this.host = bits[0]
    this.room = bits[1]

  },


  open: function() {

    console.log('websocket.open')
    this.state = 1
    this.socket = new WebSocket(this.host)
    this.socket.binaryType = 'arraybuffer'
    this.socket.onopen     = this.opened.bind(this)
    this.socket.onmessage  = this.receive.bind(this)
    this.socket.onclose    = this.closed.bind(this)

  },


  opened: function() {

    console.log('websocket.opened')
    this.state = 2
    this.trigger('ready', this)

  },


  close: function() {

    console.log('websocket.close')
    this.socket.close()

  },


  closed: function() {

    console.log('websocket.closed')
    this.socket = null
    this.state = 0

  },


  send: function(data) {

    console.log('websocket.send', data)
    var str = this.room + ':' + JSON.stringify(data)
    console.log('str', str)
    this.socket.send(str)

  },


  receive: function(buffer) {

    console.log('websocket.receive', buffer.data)
    var i = buffer.data.indexOf(':')
    if (buffer.data.substr(0, i) !== this.room) return
    this.trigger('message', JSON.parse(buffer.data.substr(i + 1)))

  },


  onready: function(callback) { return this.on('ready', callback) },
  onmessage: function(callback) { return this.on('message', callback) }


})
