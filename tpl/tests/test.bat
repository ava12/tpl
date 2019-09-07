@echo off
dir > nul
set XDEBUG_CONFIG="idekey=ava12.tpl"
rem php ..\..\tpl.php %1 %2 %3 %4 %5 %6 %7 %8 %9
php ..\..\tpl.php %*
echo.
