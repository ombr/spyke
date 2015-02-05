/**
 * Endpoint class
 */

Klass = require("../klass.js")
Peer = require("./peer.js")
WS = require("../channel/websocket.js")
WRTC = require("../channel/webrtc.js")

module.exports = new Klass({


  signal: false,
  peer: false,


  __construct: function(uri) {

    console.log('endpoint++')
    var bits = uri.split(':', 2)
    switch (bits[0]) {

    case 'ws':
      this.signal = new WS(uri)
      break

    default:
      console.warn("Protocol not supported (yet)")
      return

    }

    this.signal.onready(this.offer.bind(this))
    this.signal.open()

  },


  offer: function() {

    this.peer = new Peer(this.signal)
    this.peer.onready(this.trigger.bind(this, 'peer'))

  },


  onpeer: function(callback) { return this.on('peer', callback) }


})
