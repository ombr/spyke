/**
 * WebRTC class
 */

Channel = require("./abstract.js")

/// Minimalist adapter
var
RTCPeerConnection     = window.webkitRTCPeerConnection || window.mozRTCPeerConnection,
RTCIceCandidate       = window.RTCIceCandidate         || window.mozRTCIceCandidate,
RTCSessionDescription = window.RTCSessionDescription   || window.mozRTCSessionDescription

module.exports = Channel.extends({


  signal: false,
  sdp: false,
  rtc: false,
  uuid: false,


  __construct: function(signal, uuid) {

    console.log('webrtc++', signal)
    this.signal = signal
    this.uuid = uuid

  },


  start: function() {

    console.log('webrtc.start')
    this.state = 1
    this.sdp = { desc: false, candidates: [ ] }

    var config = { iceServers: [ { url: 'stun:23.21.150.121' } ] }
    this.rtc = new RTCPeerConnection(config)
    this.rtc.onicecandidate = this.candidate.bind(this)
    this.rtc.ondatachannel  = this.data.bind(this)
    // this.rtc.onsignalingstatechange = this.signaling.bind(this)

  },


  stop: function() {

    console.log('webrtc.stop')
    this.rtc.close()
    this.state = 0

  },


  open: function() {

    console.log('webrtc.open')
    this.socket = this.rtc.createDataChannel('signal', { reliable: true })
    this.socket.binaryType = 'arraybuffer'
    this.socket.onopen     = this.opened.bind(this)
    this.socket.onmessage  = this.receive.bind(this)
    this.socket.onclose    = this.closed.bind(this)

  },


  data: function(e) {

    console.log('webrtc.data')
    this.socket = e.channel
    this.socket.binaryType = 'arraybuffer'
    this.socket.onopen     = this.opened.bind(this)
    this.socket.onmessage  = this.receive.bind(this)
    this.socket.onclose    = this.closed.bind(this)

  },


  opened: function() {

    console.log('webrtc.opened')
    this.state = 2
    this.trigger('ready', this)

  },


  close: function() {

    console.log('webrtc.close')
    this.socket.close()

  },


  closed: function() {

    console.log('webrtc.closed')
    this.socket = null
    this.state = 0

  },


  send: function(data) {

    console.log('webrtc.send', data)
    var str = JSON.stringify(data)
    this.socket.send(str)

  },


  receive: function(buffer) {

    console.log('webrtc.receive', buffer.data)
    this.trigger('message', JSON.parse(buffer.data))

  },


  offer: function() {

    if (this.sdp.desc) return
    console.log('webrtc.offer')
    this.open()
    this.rtc.createOffer(this.local.bind(this), function(e) { console.log('error', e) })

  },


  answer: function() {

    if (this.sdp.desc) return
    console.log('webrtc.answer')
    this.rtc.createAnswer(this.local.bind(this), function(e) { console.log('error', e) })

  },


  local: function(desc) {

    console.log('webrtc.local', desc)
    this.rtc.setLocalDescription(desc)
    this.sdp.desc = desc
    this.submit()

  },


  remote: function(sdp) {

    console.log('webrtc.remote', sdp.desc)
    this.rtc.setRemoteDescription(new RTCSessionDescription(sdp.desc))
    for (var i in sdp.candidates) {
      console.log('webrtc.remoteCandidate', sdp.candidates[i])
      this.rtc.addIceCandidate(new RTCIceCandidate(sdp.candidates[i]))
    }

  },


  candidate: function(e) {

    console.log('webrtc.candidate', e)
    if (e.candidate/* && this.sdp.candidates.length < 3*/)
      this.sdp.candidates.push(e.candidate)
    else this.sdp.ready = true
    this.submit()

  },


  submit: function() {

    if (this.sdp.desc && this.sdp.ready && !this.sdp.sent) {
      this.sdp.uuid = this.uuid
      this.sdp.sent = true
      this.signal.send(this.sdp)
    }

  },


  onready: function(callback) { return this.on('ready', callback) },
  onmessage: function(callback) { console.log('onmessage event'); return this.on('message', callback) }


})
