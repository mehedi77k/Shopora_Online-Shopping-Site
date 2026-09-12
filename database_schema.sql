-- Shopora / Online Shopping Management System
-- Database: online_shop
-- Import this only if you need to recreate the database structure.

CREATE DATABASE IF NOT EXISTS online_shop
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_0900_ai_ci;
USE online_shop;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) DEFAULT NULL,
    profile_number VARCHAR(50) DEFAULT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    address TEXT NULL,
    blood_group VARCHAR(5) DEFAULT NULL,
    joining_date DATE DEFAULT NULL,
    gender VARCHAR(30) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin','admin','customer') NOT NULL DEFAULT 'customer',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS currency_rates (
    rate_id INT AUTO_INCREMENT PRIMARY KEY,
    currency_code CHAR(3) NOT NULL UNIQUE,
    currency_name VARCHAR(50) NOT NULL,
    currency_symbol VARCHAR(10) NOT NULL,
    rate_per_eur DECIMAL(12,6) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_by INT NULL,
    source_name VARCHAR(100) DEFAULT NULL,
    source_url VARCHAR(255) DEFAULT NULL,
    source_date DATE DEFAULT NULL,
    last_checked_at DATETIME DEFAULT NULL,
    auto_update TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_currency_updated_by FOREIGN KEY (updated_by)
        REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_mobile_numbers (
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

CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NULL,
    product_name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image VARCHAR(255),
    status ENUM('available','unavailable') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_category FOREIGN KEY (category_id)
        REFERENCES categories(category_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS carts (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cart_items (
    cart_item_id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    CONSTRAINT fk_cartitem_cart FOREIGN KEY (cart_id)
        REFERENCES carts(cart_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_cartitem_product FOREIGN KEY (product_id)
        REFERENCES products(product_id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_cart_product (cart_id, product_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL,
    base_currency CHAR(3) NOT NULL DEFAULT 'EUR',
    usd_exchange_rate DECIMAL(12,6) DEFAULT NULL,
    total_usd DECIMAL(12,2) DEFAULT NULL,
    shipping_address TEXT NOT NULL,
    payment_method ENUM('Cash on Delivery','Card','Mobile Banking') NOT NULL DEFAULT 'Cash on Delivery',
    order_status ENUM('Pending','Processing','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
    CONSTRAINT fk_order_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_orderitem_order FOREIGN KEY (order_id)
        REFERENCES orders(order_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_orderitem_product FOREIGN KEY (product_id)
        REFERENCES products(product_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash on Delivery','Card','Mobile Banking') NOT NULL,
    transaction_id VARCHAR(100),
    payment_status ENUM('Pending','Paid','Failed','Refunded') NOT NULL DEFAULT 'Pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_order FOREIGN KEY (order_id)
        REFERENCES orders(order_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating TINYINT NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_review_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_review_product FOREIGN KEY (product_id)
        REFERENCES products(product_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT check_rating CHECK (rating BETWEEN 1 AND 5),
    UNIQUE KEY unique_user_product_review (user_id, product_id)
) ENGINE=InnoDB;



-- Support Center: threaded contact conversations and replies
CREATE TABLE IF NOT EXISTS contact_conversations (
    conversation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    requester_name VARCHAR(100) NOT NULL,
    requester_email VARCHAR(150) NOT NULL,
    subject VARCHAR(180) NOT NULL,
    status ENUM('Open','Answered','Closed') NOT NULL DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_contact_conversation_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_contact_user (user_id),
    INDEX idx_contact_email (requester_email),
    INDEX idx_contact_status_last (status, last_message_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contact_messages (
    message_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_user_id INT NULL,
    sender_role ENUM('guest','customer','admin','super_admin') NOT NULL DEFAULT 'guest',
    message_type ENUM('requester','staff') NOT NULL DEFAULT 'requester',
    sender_name VARCHAR(100) NOT NULL,
    sender_email VARCHAR(150) NOT NULL,
    message_text TEXT NOT NULL,
    seen_by_requester TINYINT(1) NOT NULL DEFAULT 0,
    seen_by_staff TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_contact_message_conversation FOREIGN KEY (conversation_id)
        REFERENCES contact_conversations(conversation_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_contact_message_user FOREIGN KEY (sender_user_id)
        REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_contact_message_thread (conversation_id, created_at),
    INDEX idx_contact_message_staff_unread (message_type, seen_by_staff),
    INDEX idx_contact_message_requester_unread (message_type, seen_by_requester)
) ENGINE=InnoDB;

-- Meaningful account/activity audit trail used by Admin/Super Admin history lookup
CREATE TABLE IF NOT EXISTS user_activity_logs (
    activity_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    actor_user_id INT NULL,
    activity_type VARCHAR(64) NOT NULL,
    description VARCHAR(255) NOT NULL,
    metadata_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_activity_actor FOREIGN KEY (actor_user_id)
        REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_activity_user_time (user_id, created_at),
    INDEX idx_activity_actor_time (actor_user_id, created_at),
    INDEX idx_activity_type_time (activity_type, created_at)
) ENGINE=InnoDB;

-- Initial USD reference rate from the ECB reference rate published for 2026-09-11.
-- EUR is the base currency; 1 EUR = 1.1592 USD.
-- This fallback value is replaced automatically when Shopora reaches the live EUR/USD source.
INSERT INTO currency_rates (currency_code, currency_name, currency_symbol, rate_per_eur, is_active, updated_by)
VALUES ('USD', 'US Dollar', '$', 1.159200, 1, NULL)
ON DUPLICATE KEY UPDATE currency_code = VALUES(currency_code);

INSERT IGNORE INTO categories (category_name, description) VALUES
('Electronics','Electronic devices and accessories'),
('Fashion','Clothing and fashion products'),
('Mobile Phones','Smartphones and mobile accessories'),
('Computers','Computers and computer accessories'),
('Home & Living','Home and living products');
