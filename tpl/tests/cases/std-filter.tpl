var func1: @function(text) {
  replace(text, [: .<: '&lt;', .>: '&gt;', .&: '&amp;':])
}

var func2: function(text) {
 '"' replace(text, [: .'"': '&quot;' :]) '"'
}

var f1: filter(func1)
var f2: filter(func2)

f1 :: '<a'
f1 :: '&b>'
f1 :: f1.raw('<b>')
f1 :: f2.raw('<hr>')
f1 :: f1.raw('</b>')
assert('&lt;a&amp;b&gt;<b>&lt;hr&gt;</b>', f1.content())

f1.clear()
assert('', f1.content())
assert('&lt;"&gt;', f1.func('<">'))
func1: func2
assert('"<&quot;>"', f1.func('<">'))

f1.func: {}
f1 :: '<">'
assert('', f1.content())
f1.clear()

f1.func: function(text) { text }
f1 :: '<">'
assert('<">', f1.content())

assert('"&quot;a&b&quot;"', f2.func('"a&b"'))
func2: {}
assert('"&quot;a&b&quot;"', f2.func('"a&b"'))
f2.func: 'hello'
assert('hello', f2.func('"a&b"'))

f1.func: function(text) { '"' replace(text, [: .'"': '""' :]) '"'}
f1.clear()
f1 :: 'foo'
f1 :: 'bar'
assert('"foobar"', f1.content())
f1 :: 'baz'
assert('"foobarbaz"', f1.content())

f1.clear()
f1 :: [: 'f', 'oo', f1.raw('bar'), (: 'baz', 'var' :) :]
assert('"foo"bar"baz"', f1.content())