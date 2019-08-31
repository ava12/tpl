assert(false, isset())
assert(false, isset({}))
assert(true, isset(false))
assert(true, isset(0))
assert(true, isset(''))
assert(true, isset([::]))

assert('null', typeof())
assert('null', typeof({}))
assert('bool', typeof(true))
assert('number', typeof(123))
assert('number', typeof(-12.34e56))
assert('string', typeof(''))
assert('list', typeof([::]))
assert('function', typeof(typeof))
assert('function', typeof(function(){}))

assert({}, data())

var (l: [: {}, true, false, 0, 123, '', 'foo', (::), (: 1, 2 :) :], i, f, g)
foreach (l, i) do assert(i, data(i))
g: function () {1}
f: function (a: 5) {
  if ~(a) then [: g, 2 :]
  else f(-(a, 1))
}
assert([: g, 2 :], data(f))

assert({}, scalar())
assert(false, scalar(false))
assert('', scalar(''))
assert(1, scalar(f))

