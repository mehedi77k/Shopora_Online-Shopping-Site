SHOPORA SUPPORT REPLY HTTP 404 FIX
==================================

Problem
-------
The Support reply form was posting to /online_shop/api/support_action.php.
If that new endpoint was missing from the localhost copy, Apache returned HTTP 404.

Fix
---
The forms now post back to pages that already exist in every Shopora installation:

Admin / Super Admin reply + status:
  /online_shop/admin/support_view.php?id=...

Customer reply:
  /online_shop/support_view.php?id=...

Both pages already contain AJAX-aware JSON response handling, so reply/status updates
work without a page reload and no separate support_action endpoint is required.

Files changed
-------------
- online_shop/admin/support_view.php
- online_shop/support_view.php

No database migration is required.

After replacing the files, use Ctrl+F5 in the browser once.
