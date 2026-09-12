Shopora - Login Access + About Cleanup Update

Changes
-------
1. Removed the old delivery-strip UI/message.
2. Removed the Technology card from About.
3. Removed the PHP/MySQL implementation sentence from About.
4. Shop is login-protected.
5. Categories are login-protected. Guest users are sent to sign-in instead of seeing the dropdown.
6. Contact is login-protected.
7. Direct URL access to shop.php and contact.php is blocked for guests by require_login().
8. Home and About remain public.

Database
--------
No SQL migration is required for this update.
