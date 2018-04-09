\\ базовая проверка синтаксических конструкций
\*
проверяется способность парсера и машины распарсить и хоть как-то выполнить
корректную программу
*\

true
false

0
123
-123
0xab12

0.1
1.2
-1.2
0.1e12
1.2e13
-1.3e14
0.1e-12
1.2e-13
-1.3e-14

'''hello '' "% world'''
"""hello "" '% world """
%%%hello %% '" \\ world %%%

var ~!$^&*-_=+|/?;<>

{}
((..))
{1 2 3}

[::]
(::)
[:,,:]
[:, 1:]
[:2,:]
[:1,,2:]
[:,,1,,2,,:]
[:1, .foo: 2, .'bar': 3, ."baz": 4, .%var%: 5,:]
[:{'hello ' 1}, ord('a'), @chr(123), #chr(123):]
(::).(1, 2)

pure function () {
  arg[1]
  arg.foo
  arg['bar']
  arg[{1 2 3}]
  arg[1, 'foo', {1 2 3}]
  return this
}

function (a, @b, c: 1, @d: 2) {
  a
  b
  c
  d
  exit
}

var foo
var bar: 123
var (foo, baz: 456)
define foo: 789

bar: bar(foo).baz[1]
bar:: {foo bar baz}
bar: @foo
baz: #bar

if foo then bar: baz
if foo then bar
else {foo bar baz}

do {
  if foo then break
  if bar then continue
  while baz
  until baz
}

for bar: 1 .. 10 do "*"
for bar: 10..1,-2 do {
  "+"
}

var (i, j, k)
foreach (foo) do i
foreach (foo, i) do {i}
foreach (foo, , j) do {j}
foreach (foo, i, j) do {i j}
foreach (foo, , , k) do {k}
foreach (foo, i, , k) do {i k}
foreach (foo, , j, k) do {j k}
foreach (foo, i, j, k) do {i j k}

case foo(bar)[baz] {
  when 1 then bar
  when ({foo bar}, baz[1]) then bar: baz
}

case foo(bar)[baz] {
  when 1 then bar
  when ({foo bar}, baz[1]) then bar: baz
}
else {1 2 3}

and (foo)
and (foo, bar)
or (bar)
or (bar, baz)

pure function (a) { 'hello ' a }('world')
