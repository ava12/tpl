define foo: 1
var bar
bar: [:foo:]
bar[1]::2
assert([:'12':], bar)
