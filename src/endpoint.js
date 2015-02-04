var klass = require("./klass.js")

module.exports = new klass({


  __construct: function(uri, options) {

    options = options || { }

  },

  onPeer: function(callback) {

    callback(null)

  }


})
