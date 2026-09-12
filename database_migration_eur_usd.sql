-- Shopora currency migration: BDT + EUR -> EUR + USD
-- Run this ONCE on an existing Shopora database that still uses BDT as base currency.
-- Existing BDT product/order/payment values are converted to EUR.
-- Historical BDT->EUR order rates are used when available; otherwise the current legacy EUR rate is used.
-- USD did not exist in old orders, so old orders receive the migration-time USD reference rate.
-- Initial USD rate: ECB reference rate for 2026-09-11, 1 EUR = 1.1592 USD.

USE online_shop;
START TRANSACTION;

SET @legacy_bdt_per_eur := COALESCE(
    (SELECT rate_to_bdt FROM currency_rates WHERE currency_code='EUR' AND is_active=1 LIMIT 1),
    143.360996
);
SET @usd_per_eur := 1.159200;

-- Convert current catalog prices from BDT to EUR.
UPDATE products
SET price = ROUND(price / NULLIF(@legacy_bdt_per_eur, 0), 2);

-- Convert historical order-item and payment amounts while the old per-order BDT/EUR rate is still available.
UPDATE order_items oi
JOIN orders o ON o.order_id = oi.order_id
SET oi.unit_price = ROUND(oi.unit_price / NULLIF(COALESCE(NULLIF(o.eur_exchange_rate,0), @legacy_bdt_per_eur),0), 2),
    oi.subtotal = ROUND(oi.subtotal / NULLIF(COALESCE(NULLIF(o.eur_exchange_rate,0), @legacy_bdt_per_eur),0), 2);

UPDATE payments p
JOIN orders o ON o.order_id = p.order_id
SET p.amount = ROUND(p.amount / NULLIF(COALESCE(NULLIF(o.eur_exchange_rate,0), @legacy_bdt_per_eur),0), 2);

-- Add the new USD snapshot columns.
ALTER TABLE orders
    ADD COLUMN usd_exchange_rate DECIMAL(12,6) DEFAULT NULL AFTER base_currency,
    ADD COLUMN total_usd DECIMAL(12,2) DEFAULT NULL AFTER usd_exchange_rate;

-- Convert the order's primary amount from BDT to EUR.
UPDATE orders
SET total_amount = ROUND(
        COALESCE(
            total_eur,
            total_amount / NULLIF(COALESCE(NULLIF(eur_exchange_rate,0), @legacy_bdt_per_eur),0)
        ),
        2
    ),
    base_currency = 'EUR';

-- Backfill a USD reference snapshot for pre-migration orders.
UPDATE orders
SET usd_exchange_rate = @usd_per_eur,
    total_usd = ROUND(total_amount * @usd_per_eur, 2);

-- Remove old BDT/EUR-specific order columns and set the new default base currency.
ALTER TABLE orders
    DROP COLUMN eur_exchange_rate,
    DROP COLUMN total_eur,
    MODIFY base_currency CHAR(3) NOT NULL DEFAULT 'EUR';

-- Re-purpose the exchange-rate table for currencies quoted per 1 EUR.
ALTER TABLE currency_rates
    CHANGE COLUMN rate_to_bdt rate_per_eur DECIMAL(12,6) NOT NULL;

DELETE FROM currency_rates WHERE currency_code='USD';
UPDATE currency_rates
SET currency_code='USD',
    currency_name='US Dollar',
    currency_symbol='$',
    rate_per_eur=@usd_per_eur,
    is_active=1,
    updated_at=CURRENT_TIMESTAMP
WHERE currency_code='EUR';

-- If the old EUR row did not exist, create USD now.
INSERT INTO currency_rates (currency_code,currency_name,currency_symbol,rate_per_eur,is_active,updated_by)
SELECT 'USD','US Dollar','$',@usd_per_eur,1,NULL
WHERE NOT EXISTS (SELECT 1 FROM currency_rates WHERE currency_code='USD');

COMMIT;
