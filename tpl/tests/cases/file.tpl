define NL: chr(10)
define CR: chr(13)

prepareFs([:
  .ro: (: 'r', [:
    .eols: (:
      .cr: {'hello' CR 'world'},
      .crlf: {'hello' CR NL 'world'},
      .lf: {'hello' NL 'world'},
    :),
    .parent: (:
      .child: [:
        .file: ''
      :]
    :),
    .void: '',
    .тест: (:
      .тест: "тест"
    :),
    .'.hidden': '',
    .wrong-enc: bytes(0xc0, 0x81),
  :]:),

  .ac: (: 'rac', [:
    .'log.txt': {'123' CR NL}
  :]:),

  .cwn: (: 'rcwn', [:
    .sub: (::),
    .'foo.txt': '',
  :]:),

  .wd: (: 'rwd', [:
    .sub: (::),
    .'foo.txt': '',
    .'bar.txt': '',
  :]:),
:])

define err: [:
  .err: 1,
  .root: 2,
  .path: 3,
  .name: 4,
  .type: 5,
  .perm: 6,
  .exists: 7,
  .encoding: 8,
:]

define assertPerm: function(perm, fd) {
  define chars: [:
    .r: 'read',
    .c: 'create',
    .a: 'append',
    .w: 'write',
    .n: 'rename',
    .d: 'delete',
  :]

  if ~~(fd.perm()) then error({'некорректный тип (' typeof(fd.perm()) ')'})

  if ==(perm, '*') then perm: 'rcawnd'

  var (flags: [::], name, flag, i)
  for i: 1 .. length(perm) do {
    name: chars[substr(perm, i, 1)]
    if name then flags[name]: true
  }
  foreach (fd.perm, flag, name) do {
    if ^^(flag, flags[name]) then error({'флаг ' name ': ' +(flag)})
  }
}

var (f, ff, d, dd)

f: file('ro:void')
assert(0, f.error())
assertPerm('r', f)
assert('', f.content)
f.load()
assert('', f.content)

f: file('ro:тест/тест')
assert(0, f.error())
assertPerm('r', f)
assert('', f.content)
f.load()
assert('тест', f.content)

f: file('ro:wrong-enc')
assert(0, f.error())
f.load()
assert(err.encoding, f.error())

f: file('wrong:wrong')
assert(err.root, f.error())

f: file('ro:wrong')
assert(err.path, f.error())

f: file('ro:.hidden')
assert(err.name, f.error())

f: file('ro:eols')
assert(err.type, f.error())

f: file('ac:log.txt')
assert(0, f.error())
assertPerm('ra', f)
assert(true, f.exists())

f: file('ac:wrong.txt')
assert(0, f.error())
assertPerm('*', f)
assert(false, f.exists())

f: file('cwn:foo.txt')
assert(0, f.error())
assertPerm('rawn', f)

f: file('wd:foo.txt')
assert(0, f.error())
assertPerm('rawd', f)


d: dir('ro:')
assert(0, d.error)
assert('ro:', d.path)

d: dir('/eols/')
assert(err.name, d.error)

d: dir('/eols')
assert(0, d.error)
assert('ro:eols/', d.path)

dd: d.dirs('*')
assert(0, count(dd))

ff: d.files('*')
assert(true, >=(count(ff), 3))
foreach (ff, f) do {
  f.load()
  if <>(f.content, {'hello' NL 'world'}) then {
    error({'файл ' f.name() ' содержит "' f.content '"'})
  }
}

d: dir('ro:parent')
dd: dir.parent()
assert(0, dd.error)
assert('ro:', dd.path)

dd: d.dir('child')
assert(0, dd.error)
assert('ro:parent/child/', dd.path)
assertPerm('r', dd)

f: dd.file('file')
assert(0, f.error())
assertPerm('r', f)
assert('ro:parent/child/', f.path())
assert('file', f.name())

d: dir('ro:')

dd: d.dirs()
assert([: 'eols', 'parent', 'тест' :], keys(dd))

dd: d.dirs('*')
assert([: 'eols', 'parent', 'тест' :], keys(dd))

dd: d.dirs('e*')
assert([: 'eols' :], keys(dd))

ff: d.files()
assert([: 'void', 'wrong-enc' :], keys(ff))

ff: d.files('*')
assert([: 'void', 'wrong-enc' :], keys(ff))

ff: d.files('.*')
assert([::], ff)

d: d.dir('тест')
ff: d.files('*')
assert([: 'тест' :], keys(ff))


d: dir('ac:')

ff: d.files('*.txt')
assert([: 'log.txt' :], keys(ff))

ff: d.files('?.txt')
assert([::], ff)

d: dir('ro:parent')
dd: d.dirs()
assert([: 'child' :], keys(dd))


f: file('ac:log.txt')
f.load()
assert(4, length(f.content))
assert({'123' NL}, f.content)
assert({'123' NL}, f[1])
assert(4, length(f.content))
f.content :: 'абв'
assert(7, length(f.content))
assert({'123' NL 'абв'}, f.content)

f.content: 'foo'
assert('foo', f.content)

f :: 'bar'
assert('foobar', f.content)
assert('foobar', {'' f})

f.append()
assert(0, f.error())
f.load()
assert({'123' NL 'foobar'}, f.content)
f.save()
assert(err.perm, f.error())
f.append()

f: file('ac:log.txt')
f.rename('foo.txt')
assert(err.perm, f.error())

f: file('ac:log.txt')
f.move('wrong:wrong')
assert(err.perm, f.error())

f: file('ac:log.txt')
f.delete()
assert(err.perm, f.error())


f: file('cwn:wrong')
assert(0, f.error())
assertPerm('*', f)

f.rename('right')
assert(0, f.error())
assert('right', f.name())
assert(false, file('cwn:wrong').exists())

f :: 'hi'
assert('hi', f.content)
f.save()
assert(0, f.error())

f.content: ''
f.load()
assert(0, f.error())
assert('hi', f.content)

d: dir(f.path()).dir('sub')
assert(0, d.error)
assert('cwn:sub/', {'' d})
f.move(d)
assert(0, f.error())
assert('cwn:sub/', f.path())
assert('right', f.name())

f.move('cwn:', 'left')
assert(0, f.error())
assert('cwn:', f.path())
assert('left', f.name())
assert(false, file('cwn:sub/right').exists())

f: file('cwn:foo.txt')
f.rename('bar.txt')
assert(0, f.error())
f.move(d)
assert(err.perm, f.error())

f: file({f.path() f.name()})
assert(0, f.error())
f.delete()
assert(err.perm, f.error())

f: file('cwn:baz.txt')
f.rename('bar.txt')
assert(err.exists, f.error())


f: file('wd:wrong')
assert(err.path, f.error())

f: file('wd:foo.txt')
assertPerm('rawd', f)

