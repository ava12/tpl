#!/bin/bash
if [[ -n $1 ]]
then
  if [[ -a cases/$1.bnf ]]; then
    php testcase.php cases/$1.bnf
  else
    echo Test not found
  fi
else
  echo Testing:
  for name in cases/*.bnf; do
    php testcase.php "$name"
    if [[ $? != 0 ]]; then break; fi
  done
fi
