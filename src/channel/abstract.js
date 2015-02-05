/**
 * Abstract Channel class
 */

Klass = require("../klass.js")

module.exports = new Klass({


  /**
   * Internal state
   * 0: down
   * 1: connecting
   * 2: up!
   */
  state: 0,
  socket: false,


  /**
   * Open channel
   */
  open: function() { },


  /**
   * Close channel
   */
  close: function() { },


  /**
   * Send data to channel
   * @param  mixed data  Raw data
   */
  send: function(data) { },


  /**
   * Define message callback
   * @param  function callback  On message callback
   */
  onmessage: function(callback) { }


})
