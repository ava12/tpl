var (i, k, v)
var a: 0

for i: 1..4 do a: +(a, i)
assert(10, a)
assert(4, i)

a: 0
i: 0
for i: 1..-4 do a: +(a, i)
assert(0, a)
assert(0, i)

for i: 1..-4,-1 do a: +(a, i)
assert(-9, a)
assert(-4, i)

a: 0
for i: 1.1..1.31,0.1 do a: +(a, 1)
assert(3, a)
assert(1.3, i)

define const: 4
var l: [: 3, .a: @1, @const, .A: 1 :]
var (vv: [::], kk: [::], ii: [::])

assert(true, isref(l[2]))
assert(true, isconst(l[3]))

foreach (l, v, k, i) do {
  vv :: v
  kk :: k
  ii :: i
  v: 0
}
assert([:3, 1, 4, 1:], vv)
assert([:'a', 'A':], kk)
assert([:1, 2, 3, 4:], ii)
assert([:3, .a: @1, @4, .A: 1:], l)

k: 0
foreach (function(){[: 3, 1, 4 :]}, i) do k: +(k, i)
assert(8, k)