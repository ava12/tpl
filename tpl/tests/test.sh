#!/bin/bash
if [[ -n $1 ]]
then
  if [[ -a "cases/$1.tpl" ]]; then
    php ../../tpl.php "cases/$1.tpl"
  else
    echo Test not found
  fi
else
  php ../../tpl.php "cases/*.tpl"
fi
echo
