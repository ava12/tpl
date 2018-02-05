assert(162, 0xa2)
assert(179, 0xB3)
assert(-0.031, -3.1e-2)

assert('"hi"all"', """hi""all""")
assert("'hi'all'", '''hi''all''')
assert("%hi%all%", %%%hi%%all%%%)

assert({'hello' chr(10) chr(10) 'world'}, 'hello

world')

assert(0, or(0))
assert(2, or(2))
assert(2, or(0, 2))
assert(3, or(3, 4))
assert(0, and(0))
assert(2, and(2))
assert(3, and(1, 2, 3))
assert(0, and(1, 0, 2))

assert([:{}:], [:,:])
assert([:{}, {}:], [:,,:])
assert([:{}, 1, {}, {}, 2, {}:], [:,1,,,2,,:])
assert([:.foo: 1, .bar: 2, .baz: 3:], [:.foo: 1, .'bar': 2, .{'b' chr(97) 'z'}: 3:])
