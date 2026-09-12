-- Shopora: automatic EUR/USD rate + extended user profiles
-- Run ONCE on an existing online_shop database after taking a backup.
USE online_shop;

ALTER TABLE users
    MODIFY COLUMN phone VARCHAR(30) DEFAULT NULL,
    ADD COLUMN profile_number VARCHAR(50) DEFAULT NULL AFTER phone,
    ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL AFTER profile_number,
    ADD COLUMN address TEXT NULL AFTER profile_image,
    ADD COLUMN blood_group VARCHAR(5) DEFAULT NULL AFTER address,
    ADD COLUMN joining_date DATE DEFAULT NULL AFTER blood_group,
    ADD COLUMN gender VARCHAR(30) DEFAULT NULL AFTER joining_date;

UPDATE users SET joining_date = DATE(created_at) WHERE joining_date IS NULL;

CREATE TABLE user_mobile_numbers (
    mobile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    label VARCHAR(40) NOT NULL DEFAULT 'Mobile',
    mobile_number VARCHAR(30) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_mobile_user (user_id),
    CONSTRAINT fk_user_mobile_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

ALTER TABLE currency_rates
    ADD COLUMN source_name VARCHAR(100) DEFAULT NULL AFTER updated_by,
    ADD COLUMN source_url VARCHAR(255) DEFAULT NULL AFTER source_name,
    ADD COLUMN source_date DATE DEFAULT NULL AFTER source_url,
    ADD COLUMN last_checked_at DATETIME DEFAULT NULL AFTER source_date,
    ADD COLUMN auto_update TINYINT(1) NOT NULL DEFAULT 1 AFTER last_checked_at;

UPDATE currency_rates
SET auto_update = 1,
    source_name = COALESCE(source_name, 'Frankfurter'),
    source_url = COALESCE(source_url, 'https://api.frankfurter.dev/v2/rate/eur/usd'),
    last_checked_at = NULL
WHERE currency_code = 'USD';
