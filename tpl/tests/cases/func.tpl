var (a, b, c)

define func: pure function() {
  'hello'
}

assert('hello', func())

b: function() {
  'hello'
}

assert('hello', b())

define func1: function(a, @b) {
  b: {'/' b}
  a b
}

a: '+'
b: '-'
c: @a

assert('+/-', func1(a, b))
assert('-', b)

assert('+/-', func1(a, @b))
assert('/-', b)

assert('#/*', func1(.b: '*', '#'))

assert('+/+', func1(a, @c))
assert('/+', a)

var func2
func2: function (out) { a :: out}
a: ''
func2 :: 'hello '
func2 :: 'world'
assert('hello world', a)

assert('hello', function () {
  'hello'
  exit
  ' world'
}())

assert('bye', function () {
  'hi'
  return 'bye'
}())

a: [:.who: 'world', .say: function () {
  this.said: {
    'hello '
    this.who
  }
}:]
a.say()
assert('hello world', a.said)

a: [:.anyName: function () {
  'hi '
  this.who
}, .who: 'all':]
assert('hi all', {'' a})

a: '*'
assert('*/*', function (a) {
  arg.a: {a '/'}
  arg.a
  a
}(a))
assert('*', a)

=(a, '+++')
assert('+++', a)
