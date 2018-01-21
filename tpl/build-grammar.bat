@echo off
php ..\parsebnf\parsebnf.php -P doc\tpl.bnf > src\parser\grammar.php
