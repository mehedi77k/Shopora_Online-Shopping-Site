-- Shopora Support Center + User Activity History migration
-- Safe for an existing online_shop database. Existing users/orders/products are preserved.
USE online_shop;

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
