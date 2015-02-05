/**
 * RTC Peer
 */

Klass = require("../klass.js")
WRTC = require("../channel/webrtc.js")

module.exports = new Klass({


  rtc: false,
  connected: false,


  __construct: function(signal) {

    console.log('peer++')
    signal.onmessage(this.receive.bind(this, signal))
    this.rtc = new WRTC(signal)
    this.rtc.start()
    this.rtc.offer()

  },


  receive: function(signal, sdp) {

    if (sdp.desc.type == 'offer') {
      this.rtc.stop()
      this.rtc = new WRTC(signal)
      this.rtc.start()
    }
    this.rtc.remote(sdp)
    this.trigger('ready', this)

  },


  connect: function() {

    console.log('peer.connect')
    this.rtc.onready(this.trigger.bind(this, 'connect'))
    this.rtc.answer()

  },


  onready: function(callback) { return this.on('ready', callback) },
  onconnect: function(callback) { return this.on('connect', callback) }


})
