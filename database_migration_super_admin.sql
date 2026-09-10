-- Upgrade an EXISTING Shopora database without deleting current data.
-- This adds the super_admin role and promotes the oldest existing Admin to Super Admin.

USE online_shop;

ALTER TABLE users
    MODIFY COLUMN role ENUM('super_admin','admin','customer') NOT NULL DEFAULT 'customer';

SET @has_super_admin := (SELECT COUNT(*) FROM users WHERE role = 'super_admin');
SET @first_admin_id := (SELECT user_id FROM users WHERE role = 'admin' ORDER BY user_id ASC LIMIT 1);

UPDATE users
SET role = 'super_admin'
WHERE user_id = @first_admin_id
  AND @has_super_admin = 0
  AND @first_admin_id IS NOT NULL;
