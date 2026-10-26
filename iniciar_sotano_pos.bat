@echo off
title SOTANO POS

cd /d "C:\Users\adder\Documents\SOTANO_POS"

start "" http://127.0.0.1:8000

"C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan serve --host=127.0.0.1 --port=8000

pause