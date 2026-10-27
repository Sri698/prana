@echo off
echo ========================================================
echo  PRANA HOMEO CARE - 1-CLICK SYNC & BUILD APK
echo  Syncs changes from case.html -> Android Bundle -> APK
echo ========================================================
echo.

cd /d "%~dp0"
python sync_from_case.py --apk

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================================
    echo  SUCCESS! APK generated: PranaHomeoCare_Case_Portal.apk
    echo ========================================================
) else (
    echo.
    echo [ERROR] Build failed. Please check console errors above.
)

pause
