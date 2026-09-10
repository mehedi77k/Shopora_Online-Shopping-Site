-- Shopora dual-currency migration for an EXISTING online_shop database.
-- Safe for existing customer/order data. Run once after database_migration_super_admin.sql if needed.
-- BDT remains the base currency. EUR is a secondary/reference currency.
-- Legacy orders are backfilled using the migration-time EUR rate because no historical rate existed before this feature.

USE online_shop;

CREATE TABLE IF NOT EXISTS currency_rates (
    rate_id INT AUTO_INCREMENT PRIMARY KEY,
    currency_code CHAR(3) NOT NULL UNIQUE,
    currency_name VARCHAR(50) NOT NULL,
    currency_symbol VARCHAR(10) NOT NULL,
    rate_to_bdt DECIMAL(12,6) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_currency_updated_by FOREIGN KEY (updated_by)
        REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Initial reference rate verified on 2026-09-10. Change it anytime from Super Admin -> Currency Settings.
INSERT INTO currency_rates (currency_code, currency_name, currency_symbol, rate_to_bdt, is_active, updated_by)
SELECT 'EUR','Euro','€',143.361000,1,NULL
WHERE NOT EXISTS (SELECT 1 FROM currency_rates WHERE currency_code='EUR');

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='base_currency') = 0,
    "ALTER TABLE orders ADD COLUMN base_currency CHAR(3) NOT NULL DEFAULT 'BDT' AFTER total_amount",
    "SELECT 1"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='eur_exchange_rate') = 0,
    "ALTER TABLE orders ADD COLUMN eur_exchange_rate DECIMAL(12,6) NULL AFTER base_currency",
    "SELECT 1"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='total_eur') = 0,
    "ALTER TABLE orders ADD COLUMN total_eur DECIMAL(12,2) NULL AFTER eur_exchange_rate",
    "SELECT 1"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @eur_rate := (SELECT rate_to_bdt FROM currency_rates WHERE currency_code='EUR' AND is_active=1 LIMIT 1);

UPDATE orders
SET base_currency = 'BDT',
    eur_exchange_rate = COALESCE(NULLIF(eur_exchange_rate,0), @eur_rate),
    total_eur = COALESCE(total_eur, ROUND(total_amount / NULLIF(COALESCE(NULLIF(eur_exchange_rate,0), @eur_rate),0), 2))
WHERE eur_exchange_rate IS NULL OR eur_exchange_rate = 0 OR total_eur IS NULL;
