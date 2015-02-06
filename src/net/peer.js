/**
 * RTC Peer
 */

Klass = require("../klass.js")
WRTC = require("../channel/webrtc.js")

module.exports = new Klass({


  rtc: false,
  point: false,
  uuid: false,


  __construct: function(point) {

    console.log('peer++')
    this.point = point

    this.point.signal.onmessage(this.receive.bind(this))
    this.rtc = new WRTC(this.point.signal, this.point.uuid)
    this.rtc.start()
    this.rtc.offer()

  },


  receive: function(sdp) {

    if (sdp.desc.type == 'offer') {
      this.rtc.stop()
      this.rtc = new WRTC(this.point.signal, this.point.uuid)
      this.rtc.start()
    }
    this.uuid = sdp.uuid
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
