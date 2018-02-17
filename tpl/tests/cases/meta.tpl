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
