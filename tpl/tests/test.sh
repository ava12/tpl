#!/bin/bash
if [[ -n $1 ]]
then
  if [[ -a "cases/$1.tpl" ]]; then
    php ../../tpl.php "$1"
  else
    echo Test not found
  fi
else
  php ../../tpl.php
fi
echo
