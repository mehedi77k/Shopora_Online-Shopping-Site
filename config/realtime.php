<?php
// Real-time WebSocket settings. The WebSocket server is started separately
// with: php realtime/websocket_server.php
if (!defined('REALTIME_ENABLED')) define('REALTIME_ENABLED', true);
if (!defined('REALTIME_WS_HOST')) define('REALTIME_WS_HOST', '0.0.0.0');
if (!defined('REALTIME_WS_PORT')) define('REALTIME_WS_PORT', 8081);
if (!defined('REALTIME_NOTIFY_HOST')) define('REALTIME_NOTIFY_HOST', '127.0.0.1');
if (!defined('REALTIME_NOTIFY_PORT')) define('REALTIME_NOTIFY_PORT', 8082);
