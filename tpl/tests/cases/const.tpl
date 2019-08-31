define foo: 1
var bar

bar: foo
assert(1, bar)

bar: (:foo:)
bar[1]::2
assert((:'12':), bar)

bar: {foo 3}
assert('13', bar)

define baz: function () {
  var f: function() {}
}

