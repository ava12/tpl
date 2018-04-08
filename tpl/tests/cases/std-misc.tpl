var (foo, bar)
assert(false, isconst(foo))
foo: const([: 1, 2 :])
assert(true, isconst(foo))
assert(true, isconst(foo[2]))

assert(false, isref(bar))
bar: @foo
assert(true, isref(bar))
assert(false, isconst(bar))
assert(false, isref(#bar))
assert(true, isref(@foo))

var func: function (baz) {
  this.baz: baz
  +(baz, 1)
}

var baz: [: .foo: 1, .bar: 2, .baz: 3 :]

assert(5, call(func, 4, baz))
assert(3, baz.baz)
assert(6, call(func, 5, @baz))
assert(5, baz.baz)
