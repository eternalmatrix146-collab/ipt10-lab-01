
@echo off
rem ------------------------------------------------------------------
rem  Midnight Market — one command to run everything.
rem  Double-click this file, or run  .\start  in a terminal.
rem  Starts MySQL (XAMPP), prepares the database, opens the browser
rem  and runs the server. Press Ctrl+C to stop the server.
rem ------------------------------------------------------------------
cd /d "%~dp0"

rem Use the first PHP that has the MySQL driver: PATH first (usually the newest), then XAMPP.
set "PHP="
for %%P in ("php" "C:\xampp\php\php.exe" "D:\Program Files\Xampp\php\php.exe") do (
    if not defined PHP (
        "%%~P" -r "exit(extension_loaded('pdo_mysql') ? 0 : 1);" >nul 2>&1 && set "PHP=%%~P"
    )
)
if not defined PHP (
    echo   No PHP with the pdo_mysql extension was found. Install XAMPP or enable pdo_mysql in php.ini.
    pause
    exit /b 1
)
echo   Using PHP: %PHP%

if not exist ".env" (
    copy ".env.example" ".env" >nul
    echo   Created .env from .env.example
)

"%PHP%" phantom start %*
if errorlevel 1 pause
