define foo: 1
var bar: [: .cr: @foo, .r: @2, .cv: foo, .v: 2 :]

assert(false, isconst(foo))
assert(false, isconst(#foo))
assert(true, isconst(@foo))

assert(false, isconst(bar))
assert(false, isconst(#bar))
assert(false, isconst(@bar))

assert(true, isconst(@bar.cr))
assert(false, isconst(@bar.r))
assert(false, isconst(@bar.cv))
assert(false, isconst(@bar.v))

assert(false, isref(bar))
assert(true, isref(bar.cr))
assert(true, isref(bar.r))
assert(false, isref(bar.cv))
assert(false, isref(bar.v))

bar: @foo
assert(true, isref(bar))
assert(true, isconst(bar))
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
