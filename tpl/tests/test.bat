@echo off
dir > nul
set XDEBUG_CONFIG="idekey=ava12.tpl"
if "%1" == "" goto all

php testcase.php -eCP866 -fCP1251 cases\%1.tpl
goto end

:all
php ..\..\tpl.php cases\*.tpl

:end
echo.
