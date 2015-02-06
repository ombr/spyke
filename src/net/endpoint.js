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
  uuid: false,


  __construct: function(uri) {

    console.log('endpoint++')
    this.uuid = this.gen()
    console.log('endpoint.uuid', this.uuid)

    var bits = uri.split(':', 2)
    switch (bits[0]) {

    case 'ws':   this.signal = new WS(uri);   break
    case 'wrtc': this.signal = new WRTC(uri); break

    default:
      console.warn("Protocol not supported (yet)")
      return

    }

    this.signal.onready(this.offer.bind(this))
    this.signal.open()

  },


  offer: function() {

    this.peer = new Peer(this)
    this.peer.onready(this.trigger.bind(this, 'peer'))

  },


  gen: function() {

    var
    r, v, mask = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'

    return mask.replace(/[xy]/g, function(c) {
      r = Math.random() * 16 | 0,
      v = c == 'x' ? r : (r & 0x3 | 0x8)
      return v.toString(16)
    })

  },


  onpeer: function(callback) { return this.on('peer', callback) }


})
