@echo off
title Balaton Szallasok Plugin Deploy
color 0A
chcp 65001 >nul

echo.
echo ========================================
echo   BALATON SZALLASOK PLUGIN DEPLOY
echo ========================================
echo.

set /p COMMIT_MSG="Commit uzenet (Enter = 'Update'): "
if "%COMMIT_MSG%"=="" set COMMIT_MSG=Update

echo.
echo ========================================
echo   Git add...
echo ========================================
git add .

echo.
echo ========================================
echo   Git commit: %COMMIT_MSG%
echo ========================================
git commit -m "%COMMIT_MSG%"

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [INFO] Nincs uj valtozas, nincs mit commitolni.
    echo.
    pause
    exit /b 0
)

echo.
echo ========================================
echo   Git push...
echo ========================================
git push

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ========================================
    echo   [HIBA] Push sikertelen!
    echo   Ellenorizd a fenti hibauzeneteket.
    echo ========================================
    echo.
    pause
    exit /b 1
)

echo.
echo ========================================
echo   DEPLOY SIKERES!
echo ========================================
echo.
echo [+] GitHub repo:
echo     https://github.com/aktivbalaton/balaton-szallasok-plugin
echo.
echo [+] A szerver automatikusan frissul
echo     a webhook segitsegevel.
echo.
echo [+] Plugin ellenorzese:
echo     https://aktivbalaton.hu/balatoni-szallasok/
echo.
echo ========================================
echo.
pause
