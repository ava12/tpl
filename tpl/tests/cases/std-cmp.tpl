assert(true, ==())
assert(true, ==({}))
assert(true, ==({}, {}))
assert(true, ==(true, 1))
assert(true, ==(false, 0))
assert(false, ==(true, -1))
assert(true, ==(1, '1'))
assert(true, ==(1.0, '1'))
assert(false, ==(1, '1.0'))
assert(true, ==(true, '1'))
assert(true, ==(false, ''))
assert(true, ==({}, ''))

assert(false, <>())
assert(false, <>({}))
assert(false, <>({}, {}))
assert(false, <>(true, 1))
assert(false, <>(1, '1'))

assert(true, >=())
assert(true, >=(1, 1))
assert(true, >=(2, 1))
assert(false, >=(1, 2))
assert(true, >=(1.0, 1))

assert(false, <())

assert(false, >())
assert(false, >(1, 1))
assert(true, >(2, 1))
assert(false, >(1, 2))
assert(false, >(1.0, 1))

assert(true, <=())