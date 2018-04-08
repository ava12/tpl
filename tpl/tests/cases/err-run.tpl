\\:: 200
error('test')

\\:: 202
var foo
foo: function() { '*' foo}
foo()

\\:: 203
var func
func: function () { (:func:) }
'' func

\\:: 204
define foo: '123'
foo:: '456'

\\:: 204
define foo: (:1,2:)
foo[2]: 3

\\:: 204
define foo: (:1,2:)
foo:: 3

\\:: 204
define foo: (:1,2:)
var bar: @foo[2]
bar: 3

\\:: 205 / 0
/(10, 0)

\\:: 205 div 0
div(10, 0)

\\:: 205 mod 0
mod(10, 0)

\\:: 205 ** neg, float
**(-2, 1.5)
