var (foo, bar, baz)

bar: @foo
foo: 2
baz: foo
assert(2, $bar)
assert(2, baz)
foo: 3
assert(3, $bar)
assert(2, baz)
assert(@foo, bar)

foo: @4
assert(3, $bar)
assert(2, baz)

var (a, b, c)

a: [[2, @3]]
b: a
c: @a
a[1]: 4
a[2]: 5
assert(2, b[1])
assert(5, $b[2])
assert(4, c[1])
assert(5, $c[2])
