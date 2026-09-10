-- SHOPORA IMAGE MANAGEMENT MIGRATION
-- Run this ONCE on an existing dual-currency/Super Admin database.
-- Existing users, admins, products, orders and prices are preserved.

USE online_shop;

SET @category_image_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'categories'
      AND COLUMN_NAME = 'image'
);

SET @category_image_sql = IF(
    @category_image_exists = 0,
    'ALTER TABLE categories ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER description',
    'SELECT ''categories.image already exists'' AS migration_status'
);

PREPARE shopora_category_image_stmt FROM @category_image_sql;
EXECUTE shopora_category_image_stmt;
DEALLOCATE PREPARE shopora_category_image_stmt;
