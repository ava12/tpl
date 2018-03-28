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
