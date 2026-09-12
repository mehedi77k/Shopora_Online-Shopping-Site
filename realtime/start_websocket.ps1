Set-Location (Join-Path $PSScriptRoot '..')
Write-Host 'Starting Shopora real-time WebSocket server...'
Write-Host 'Keep this window open while using the website.'
php realtime/websocket_server.php
