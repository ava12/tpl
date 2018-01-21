#!/bin/bash
if [[ -n $1 ]]
then
  if [[ -a "cases/$1.tpl" ]]; then
    php testcase.php "cases/$1.tpl"
  else
    echo Test not found
  fi
else
  for name in cases/*.tpl; do
    php testcase.php "$name"
    if [[ $? != 0 ]]; then break; fi
  done
fi
echo