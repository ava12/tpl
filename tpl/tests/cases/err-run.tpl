\\:: 202
var foo
foo: function() { '*' foo}
foo()

\\:: 203
var func
func: function () { [:func:] }
'' func

\\:: 204
define foo: '123'
foo:: '456'

\\:: 204
define foo: [:1,2:]
foo[2]: 3

\\:: 204
define foo: [:1,2:]
foo:: 3

\\:: 204
define foo: [:1,2:]
var bar: @foo[2]
bar: 3
