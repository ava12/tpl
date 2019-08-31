var foo
\{
  foo: 'hello'
}\
assert('hello', foo)

var bar: 123
\{
  var bar: 456
}\
assert(123, bar)

var baz: 123
\{
  var v: 456
  \{ var v: \{ }\ 789 }\
  baz: v
}\
assert(456, baz)

\{
  macros:: [: .foo: 'bar' :]
}\
assert('\bar\', %\\\foo\\\%)

\{
  macros([: .foo: 'baz' :])
}\
assert('\baz\', %\\\foo\\\%)

\{ include('inc:var_foobar.inc') }\
assert(1, foobar)
\{ require('inc:var_foobar.inc') }\
\{ include('inc:foobar+1.inc') }\
assert(2, foobar)
\{ include('inc:foobar+1.inc') }\
assert(3, foobar)
\{ require('inc:foobar+1.inc') }\
assert(3, foobar)

\{ newvar('mfoo', 123) }\
assert(123, mfoo)
assert(0, isconst(@mfoo))

\{ newdef('mbar', 321) }\
assert(321, mbar)
assert(1, isconst(@mbar))

\{ metavar('mbaz', 12) }\
\{
  assert(12, mbaz)
  assert(0, isconst(@mbaz))
}\

\{ metadef('mvar', 21) }\
\{
  assert(21, mvar)
  assert(1, isconst(@mvar))
}\

