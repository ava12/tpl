@echo off
set XDEBUG_CONFIG="idekey=ava12.tpl"
if "%1" == "" goto batch

if not exist cases\%1.bnf goto no-test

php testcase.php cases\%1.bnf
goto end

:no-test
echo Test not found
goto end

:batch
dir > nul
echo Testing:
for %%i in (cases\*.bnf) do if not errorlevel 1 php testcase.php %%i
echo.

:end
