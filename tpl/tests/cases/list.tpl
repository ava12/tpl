var a: 3

assert(3, a[1])
assert(3, a[0])
assert(3, a[0, 1])
assert({}, a[2])
assert({}, a[-1])

a[3, 2]: 4
assert([[3, {}, [[{}, 4]]]], a)
