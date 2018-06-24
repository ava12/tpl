@echo off
dir > nul
set XDEBUG_CONFIG="idekey=ava12.tpl"
if not "%1" == "" goto chosen

:all

for %%c in (cases\*.tpl) do if not errorlevel 1 php testcase.php -eCP866 -fCP1251 %%c
goto end

:chosen
php testcase.php -eCP866 -fCP1251 cases\%1.tpl

:end
echo.