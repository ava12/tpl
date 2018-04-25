var (re, str)

re: regexp('/')

str: 'c:\util'
assert('/\//u', re.pattern)
assert(0, re.count(str))
assert({}, re.first(str))
assert({}, re.last(str))
assert([::], re.match(str))
assert(str, re.replace(str, ':'))
assert([: str :], re.split(str))

str: '/dev/null'
assert(2, re.count(str))
assert([: .match: '/', .pos: 1, .sub: (::) :], re.first(str))
assert([: .match: '/', .pos: 5, .sub: (::) :], re.last(str))
assert([:
  [: .match: '/', .pos: 1, .sub: (::) :],
  [: .match: '/', .pos: 5, .sub: (::) :],
:], re.match(str))

assert(':dev:null', re.replace(str, ':'))
assert(':dev:null', re.replace(str, ':', 2))
assert(':dev/null', re.replace(str, ':', 1))
assert('/dev/null', re.replace(str, ':', 0))

assert([: '', 'dev', 'null' :], re.split(str))
assert([: '', 'dev', 'null' :], re.split(str, 3))
assert([: '', 'dev/null' :], re.split(str, 2))
assert([: str :], re.split(str, 1))
assert([: '', 'dev', 'null' :], re.split(str, 0))

re: regexp('(,) | (и) ')
str: 'жизнь, вселенная и всё остальное'
assert(2 ,re.count(str))
assert([: .match: ', ', .pos: 6, .sub: (: [: .match: ',', .pos: 6 :] :) :], re.first(str))
assert([: .match: ' и ', .pos: 17, .sub: (: {}, [: .match: 'и', .pos: 18 :] :) :], re.last(str))
assert([:
  [: .match: ', ', .pos: 6, .sub: (: [: .match: ',', .pos: 6 :] :) :],
  [: .match: ' и ', .pos: 17, .sub: (: {}, [: .match: 'и', .pos: 18 :] :) :],
:], re.match(str))

assert('жизнь/вселенная/всё остальное', re.replace(str, '/'))
assert([: 'жизнь', 'вселенная', 'всё остальное' :], re.split(str))
