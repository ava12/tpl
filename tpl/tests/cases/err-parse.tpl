\\:: 100
'�'

\\:: 101
var

\\:: 102
`

\\:: 103
"string

\\:: 104
var :

\\:: 104
(: :]

\\:: 104
[: :)

\\:: 104
{ .))

\\:: 104
((. }

\\:: 105
var foo: 1
var foo

\\:: 105
\{ newvar('foo', 1) }\
var foo: 2

\\:: 105
\{ newdef('foo', 1) }\
var foo: 2

\\:: 105
\{ metavar('foo', 1)}\
\{ metavar('foo', 2)}\

\\:: 105
\{ metadef('foo', 1)}\
\{ metadef('foo', 2)}\

\\:: 106
foo

\\:: 106
\{
  var foo
  \{ foo: 123 }\
}\

\\:: 107
var foo
pure function () { foo }

\\:: 108
pure function (@ a) {}

\\:: 109
\{ include('inc:loop-a.inc') }\

\\:: 110
%foo\bar\baz%

\\:: 110
\{
  macros:: (: .foo: 'bar' :)
  %\foo\%
}\
