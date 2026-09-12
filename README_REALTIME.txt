SHOPORA REAL-TIME / WEBSOCKET SETUP
===================================

The website continues to work normally if the WebSocket server is not running.
Real-time updates require the lightweight PHP WebSocket process included here.
No Composer or Node.js package is required.

LOCAL LARAGON / XAMPP
---------------------
1. Start Apache and MySQL normally.
2. Make sure the project folder is named online_shop under your web root.
3. Start the WebSocket server using ONE of these methods:

   Option A: Double-click
      realtime\start_websocket.bat

   Option B: Terminal / PowerShell
      cd C:\laragon\www\online_shop
      php realtime\websocket_server.php

4. Keep that terminal window open.
5. Open the website normally, for example:
      http://localhost/online_shop/

DEFAULT PORTS
-------------
Browser WebSocket: 8081/TCP
Internal notifier : 8082/UDP on 127.0.0.1 only

If port 8081 is already in use, change REALTIME_WS_PORT in:
   config\realtime.php

If port 8082 is already in use, change REALTIME_NOTIFY_PORT in the same file.

HOW IT WORKS
------------
- PHP database actions send a small event to localhost UDP 8082.
- The standalone server broadcasts only an event name and identifiers.
- Private support-message text, user profile data, order details and passwords
  are NOT broadcast through WebSocket.
- Each browser fetches its own authorized state through the normal PHP session.
- Support threads update without a manual refresh.
- Support unread counters and cart counters synchronize across open tabs.
- Relevant list/detail pages refresh automatically when stored data changes.

PRODUCTION / HTTPS
------------------
For an HTTPS production site, put the WebSocket server behind a TLS-capable
reverse proxy (for example Nginx/Apache proxying WSS) and expose it securely.
The bundled direct socket is intended for the current local/LAN PHP setup.
