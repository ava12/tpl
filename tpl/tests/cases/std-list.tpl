assert(0, count())
assert(0, count([::]))
assert(1, count([:,:]))
assert(3, count([: 10, , 20 :]))

var foo: [: .a: 2, .b: 7, .c: 1, .d: 8 :]

assert([::], slice())
assert(foo, slice(foo))
assert([::], slice(foo, 5, 1))
assert([::], slice(foo, -4, 1))
assert([::], slice(foo, 2, 0))
assert([::], slice(foo, 2, -1))
assert([: .c: 1, .d: 8 :], slice(foo, 3))
assert([: .c: 1, .d: 8 :], slice(foo, -1))
assert([: .b: 7, .c: 1 :], slice(foo, -2, 2))
assert(foo, slice(foo, -5, 10))
assert([: .c: 1, .d: 8 :], slice(.start: 3, .count: 2, .l: foo))
assert([: .c: 1, .d: 8 :], slice(foo, .count: 2, .start: 3))
assert([: .d: 8 :], slice(foo, 0, 10))
assert([: .a: 2, .b: 7 :], slice(foo, -6, 5))

assert([::], splice())
assert(foo, splice(foo))
assert(foo, splice(foo, 2))
assert([: .a: 2, .c: 1, .d: 8 :], splice(foo, 2, 1))
assert([: .a: 2, .e: 'e', .c: 1, .d: 8 :], splice(foo, 2, .insert: [: .e: 'e' :]))
assert([: .e: 'e', .c: 1, .d: 8 :], splice(foo, -5, 4, [: .e: 'e' :]))
assert([: .a: 2, .b: 7, .e: 'e' :], splice(foo, 3, 4, [: .e: 'e' :]))

foo: [: 3, .b: 1, .c: 4, 1 :]

assert({}, key())
assert({}, key(foo))
assert({}, key(foo, {}))
assert({}, key(foo, 5))
assert({}, key(foo, -4))
assert({}, key(foo, 1))
assert('b', key(foo, 2))
assert('c', key(foo, 3))
assert({}, key(foo, 4))
assert('b', key(foo, -2))
assert('c', key(foo, -1))

assert({}, index())
assert({}, index(foo))
assert({}, index(foo, {}))
assert({}, index(foo, 'a'))
assert({}, index(foo, 'B'))
assert({}, index(foo, 'bb'))
assert(2, index(foo, 'b'))
assert(3, index(foo, 'c'))

assert([::], keys())
assert([::], keys([::]))
assert([: , 'b', 'c', , :], keys(foo))

assert([::], values())
assert([::], values([::]))
assert([: 3, 1, 4, 1 :], values(foo))

var bar: [: 'a', 'b', 'a' :]
assert([::], combine())
assert([::], combine([::], [::]))
assert([: .a: 4, .b: 1, 1 :], combine(bar, foo))
assert([: .'3': 'a', .'1': {}, .'4': 'a' :], combine(foo, bar))

assert([::], sort())
assert([::], sort([::]))
assert([: 1, 1, 2, 3, 4, 5, 6, 9 :], sort([: 3, 1, 4, 1, 5, 9, 2, 6 :]))

foo: [: 2, 7, [:1, 1:], .b: 8, 2, .a: 8, [:1, 2:], .c: 8 :]
bar: [: .a: 8, .b: 8, .c: 8, 7, 2, 2, [:1, 1:], [:1, 2:] :]
var func: pure function(v1, v2, k1, k2, i1, i2) {
  var result
  result: -(v2, v1)
  if result then return result

  result: -(ord(k1), ord(k2))
  if result then return result

  -(i1, i2)
}
assert(bar, sort(foo, func))
