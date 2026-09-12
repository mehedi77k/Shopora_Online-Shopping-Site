@echo off
cd /d "%~dp0.."
echo Starting Shopora real-time WebSocket server...
echo Keep this window open while using the website.
echo.
php realtime\websocket_server.php
pause
