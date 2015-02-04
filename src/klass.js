module.exports = function(protos, parent) {

  var
  k = function() {
    if (this.__construct)
      this.__construct.apply(this, arguments)
  }

  parent = parent || { }
  protos.static = protos
  protos.parent = parent.prototype
  k.prototype   = protos
  k.parent      = parent
  k.extends     = function(protos) {
    for (var i in this.prototype)
      if (protos[i] === undef)
        protos[i] = this.prototype[i]
    return new klass(protos, this)
  }

  return k

}
