/**
 * Basic OOP class
 */

module.exports = function(protos, parent) {

  var k = function() {

    /// Minimalist events manager
    this._events = { }

    this.on = function(name, callback) {
      if (!this._events[name]) this._events[name] = [ ]
      this._events[name].push(callback)
    }

    this.off = function(name, callback) {
      if (!this._events[name]) return
      if (callback) {
        var i = this._events[name].indexOf(callback)
        if (i > -1) this._events[name].splice(i, 1)
      } else {
        this._events[name] = [ ]
      }
    }

    this.trigger = function(name) {
      if (!this._events[name]) return
      var args = Array.prototype.slice.call(arguments, 1)
      this._events[name].forEach(function(callback) {
        if (callback) callback.apply(null, args)
      })
    }

    if (this.__construct)
      this.__construct.apply(this, arguments)

  }

  /// Basics
  parent = parent || { }
  protos.static = protos
  protos.parent = parent.prototype
  k.prototype   = protos
  k.parent      = parent

  /// Inheritance
  k.extends     = function(protos) {
    for (var i in this.prototype)
      if (protos[i] === undefined)
        protos[i] = this.prototype[i]
    return new Klass(protos, this)
  }

  return k

}
