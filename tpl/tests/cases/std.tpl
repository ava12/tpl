assert({}, ord(''))
assert(68, ord('D'))
assert(1041, ord('Б'))
assert(68, ord('DБ'))

assert({}, chr())
assert('hi', chr(0x68, 0x69))
assert('АБ', chr(1040, 1041))

assert({}, length())
assert(0, length(''))
assert(2, length('DБ'))

assert('foo', substr('foo', 1))
assert('r', substr('bar', 0))
assert('в', substr('абв', 0))
assert('', substr('foo', 4, 1))
assert('', substr('foo', 1, 0))
assert('', substr('foo', 1, -1))
assert('a', substr('bar', -1, 1))

assert({}, trim())
assert('', trim(''))
assert('', trim(chr(9)))
assert('', trim(chr(10)))
assert('', trim(chr(12)))
assert('', trim(chr(13)))
assert('', trim(chr(32)))
assert('', trim(chr(160)))
assert('foo', trim({chr(9, 10, 12, 13, 32, 160) 'foo' chr(9, 10, 12, 13, 32, 160)}))
assert('foo bar', trim('  foo bar'))
assert('foo bar', trim('foo bar  '))

assert('foo', replace('foo', 'f'))
assert('foo', replace('foo', [:'f':]))
assert('baa', replace('foo', [: .o: 'a', 'c', .f: 'b', .{''}: 'd' :]))
assert('baz', replace('foo', [: .o: 'z', .fo: 'ba' :]))
assert('HI', replace('hi', [: .h: 'H', .i: 'I' :]))
assert('ПрИвЕт', replace('привет', [: .е: "Е", .и: "И", .п: "П" :]))

assert(true, not())
assert(true, not(false))
assert(false, not(true))

assert(false, &&())
assert(false, &&(false, false))
assert(false, &&(true, false))
assert(true, &&(true, true))

assert(false, ||())
assert(false, ||(false, false))
assert(true, ||(true, false))
assert(true, ||(true, true))

assert(false, ^^())
assert(false, ^^(false, false))
assert(true, ^^(true, false))
assert(false, ^^(true, true))
