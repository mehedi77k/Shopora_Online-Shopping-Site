-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 14, 2026 at 01:28 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `online_shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `cart_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`cart_id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 2, '2026-09-10 15:19:46', '2026-09-10 15:19:46'),
(2, 1, '2026-09-10 15:20:58', '2026-09-10 15:20:58'),
(3, 3, '2026-09-10 15:21:18', '2026-09-10 15:21:18');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_item_id` int NOT NULL,
  `cart_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `image`, `created_at`) VALUES
(1, 'Electronics', 'Electronic devices and accessories', NULL, '2026-09-10 15:18:22'),
(2, 'Fashion', 'Clothing and fashion products', NULL, '2026-09-10 15:18:22'),
(3, 'Mobile Phones', 'Smartphones and mobile accessories', NULL, '2026-09-10 15:18:22'),
(4, 'Computers', 'Computers and computer accessories', NULL, '2026-09-10 15:18:22'),
(5, 'Home & Living', 'Home and living products', NULL, '2026-09-10 15:18:22');

-- --------------------------------------------------------

--
-- Table structure for table `contact_conversations`
--

CREATE TABLE `contact_conversations` (
  `conversation_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `requester_name` varchar(100) NOT NULL,
  `requester_email` varchar(150) NOT NULL,
  `subject` varchar(180) NOT NULL,
  `status` enum('Open','Answered','Closed') NOT NULL DEFAULT 'Open',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_message_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `contact_conversations`
--

INSERT INTO `contact_conversations` (`conversation_id`, `user_id`, `requester_name`, `requester_email`, `subject`, `status`, `created_at`, `updated_at`, `last_message_at`) VALUES
(1, NULL, 'MEHEDI  HASAN', 'admin@gmail.com', 'ngdiongin', 'Closed', '2026-09-12 15:26:50', '2026-09-12 16:06:42', '2026-09-12 16:05:36');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `message_id` bigint NOT NULL,
  `conversation_id` int NOT NULL,
  `sender_user_id` int DEFAULT NULL,
  `sender_role` enum('guest','customer','admin','super_admin') NOT NULL DEFAULT 'guest',
  `message_type` enum('requester','staff') NOT NULL DEFAULT 'requester',
  `sender_name` varchar(100) NOT NULL,
  `sender_email` varchar(150) NOT NULL,
  `message_text` text NOT NULL,
  `seen_by_requester` tinyint(1) NOT NULL DEFAULT '0',
  `seen_by_staff` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`message_id`, `conversation_id`, `sender_user_id`, `sender_role`, `message_type`, `sender_name`, `sender_email`, `message_text`, `seen_by_requester`, `seen_by_staff`, `created_at`) VALUES
(1, 1, NULL, 'guest', 'requester', 'MEHEDI  HASAN', 'admin@gmail.com', 'lkjihgufgdxf', 1, 1, '2026-09-12 15:26:50'),
(2, 1, 1, 'super_admin', 'staff', 'Mehedi Hasan', 'mehedi241-50-001@diu.edu.bd', 'xcfghjkm', 0, 1, '2026-09-12 16:05:36');

-- --------------------------------------------------------

--
-- Table structure for table `currency_rates`
--

CREATE TABLE `currency_rates` (
  `rate_id` int NOT NULL,
  `currency_code` char(3) NOT NULL,
  `currency_name` varchar(50) NOT NULL,
  `currency_symbol` varchar(10) NOT NULL,
  `rate_per_eur` decimal(12,6) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_by` int DEFAULT NULL,
  `source_name` varchar(100) DEFAULT NULL,
  `source_url` varchar(255) DEFAULT NULL,
  `source_date` date DEFAULT NULL,
  `last_checked_at` datetime DEFAULT NULL,
  `auto_update` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `currency_rates`
--

INSERT INTO `currency_rates` (`rate_id`, `currency_code`, `currency_name`, `currency_symbol`, `rate_per_eur`, `is_active`, `updated_by`, `source_name`, `source_url`, `source_date`, `last_checked_at`, `auto_update`, `updated_at`) VALUES
(1, 'USD', 'US Dollar', '$', '1.161800', 1, NULL, 'Frankfurter', 'https://api.frankfurter.dev/v2/rate/eur/usd', '2026-09-13', '2026-09-12 18:06:34', 1, '2026-09-12 18:06:34');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int NOT NULL,
  `user_id` int NOT NULL,
  `order_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `total_amount` decimal(10,2) NOT NULL,
  `base_currency` char(3) NOT NULL DEFAULT 'EUR',
  `usd_exchange_rate` decimal(12,6) DEFAULT NULL,
  `total_usd` decimal(12,2) DEFAULT NULL,
  `shipping_address` text NOT NULL,
  `payment_method` enum('Cash on Delivery','Card','Mobile Banking') NOT NULL DEFAULT 'Cash on Delivery',
  `order_status` enum('Pending','Processing','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int NOT NULL,
  `order_id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int NOT NULL,
  `order_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash on Delivery','Card','Mobile Banking') NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_status` enum('Pending','Paid','Failed','Refunded') NOT NULL DEFAULT 'Pending',
  `payment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `product_name` varchar(150) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `image` varchar(255) DEFAULT NULL,
  `status` enum('available','unavailable') NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `description`, `price`, `stock`, `image`, `status`, `created_at`) VALUES
(1, 1, 'Wireless Headphone', 'Bluetooth wireless headphone', '17.44', 20, NULL, 'available', '2026-09-10 15:18:22'),
(2, 1, 'Smart Watch', 'Digital smart watch', '24.41', 15, NULL, 'available', '2026-09-10 15:18:22'),
(3, 3, 'Samsung Smartphone', 'Android smartphone', '244.14', 10, NULL, 'available', '2026-09-10 15:18:22'),
(4, 4, 'Laptop', 'Laptop for office and study', '453.40', 8, NULL, 'available', '2026-09-10 15:18:22'),
(5, 2, 'T-Shirt', 'Cotton casual t-shirt', '5.23', 40, NULL, 'available', '2026-09-10 15:18:22');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int NOT NULL,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `rating` tinyint NOT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `profile_number` varchar(50) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `address` text,
  `blood_group` varchar(5) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `gender` varchar(30) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','customer') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `profile_number`, `profile_image`, `address`, `blood_group`, `joining_date`, `gender`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'Mehedi Hasan', 'mehedi241-50-001@diu.edu.bd', '01766359263', NULL, NULL, NULL, NULL, '2026-09-10', NULL, '$2y$10$Rdj5b8sLYxLCO/tMUpAoVuh276vr008bW1TVN3AFw7lM87iVQvxAe', 'super_admin', 'active', '2026-09-10 15:19:13'),
(2, 'Dipto Biswas', 'admin@gmail.com', '01944055947', NULL, NULL, NULL, NULL, '2026-09-10', NULL, '$2y$10$yFHvNV9t1Y2I8p2JIoEiOuLyubAMfpX7b3c5ve5pCzL.yWIQ4zBbO', 'customer', 'active', '2026-09-10 15:19:46'),
(3, 'Dipjol', 'user@gmail.com', '01944055947', NULL, NULL, NULL, NULL, '2026-09-10', NULL, '$2y$10$MXiI/0HgsS28/GaoycYsz.s2LbBf2Q2t6fPm4e94OqOORNuEC1QDS', 'admin', 'active', '2026-09-10 15:20:36');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_logs`
--

CREATE TABLE `user_activity_logs` (
  `activity_id` bigint NOT NULL,
  `user_id` int DEFAULT NULL,
  `actor_user_id` int DEFAULT NULL,
  `activity_type` varchar(64) NOT NULL,
  `description` varchar(255) NOT NULL,
  `metadata_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_activity_logs`
--

INSERT INTO `user_activity_logs` (`activity_id`, `user_id`, `actor_user_id`, `activity_type`, `description`, `metadata_json`, `created_at`) VALUES
(1, 3, 3, 'logout', 'Signed out of the account.', NULL, '2026-09-12 14:25:41'),
(2, 1, 1, 'login', 'Signed in successfully.', '{\"role\": \"super_admin\"}', '2026-09-12 14:25:59'),
(3, 1, 1, 'logout', 'Signed out of the account.', NULL, '2026-09-12 14:36:01'),
(4, 1, 1, 'login', 'Signed in successfully.', '{\"role\": \"super_admin\"}', '2026-09-12 14:36:18'),
(5, 1, 1, 'logout', 'Signed out of the account.', NULL, '2026-09-12 14:36:43'),
(6, 1, 1, 'login', 'Signed in successfully.', '{\"role\": \"super_admin\"}', '2026-09-12 14:36:55'),
(7, 1, 1, 'login', 'Signed in successfully.', '{\"role\": \"super_admin\"}', '2026-09-12 14:50:29'),
(8, 2, 2, 'login', 'Signed in successfully.', '{\"role\": \"customer\"}', '2026-09-12 14:51:09'),
(9, 3, 3, 'login', 'Signed in successfully.', '{\"role\": \"admin\"}', '2026-09-12 14:52:13'),
(10, 1, 1, 'product_deleted', 'Deleted product #6: Table Lamp', '{\"product_id\": 6}', '2026-09-12 15:11:15'),
(11, 1, 1, 'logout', 'Signed out of the account.', NULL, '2026-09-12 15:11:35'),
(12, 2, 2, 'login', 'Signed in successfully.', '{\"role\": \"customer\"}', '2026-09-12 15:11:43'),
(13, 2, 2, 'logout', 'Signed out of the account.', NULL, '2026-09-12 15:12:09'),
(14, 3, 3, 'login', 'Signed in successfully.', '{\"role\": \"admin\"}', '2026-09-12 15:12:17'),
(15, 3, 3, 'logout', 'Signed out of the account.', NULL, '2026-09-12 15:19:58'),
(16, 1, 1, 'login', 'Signed in successfully.', '{\"role\": \"super_admin\"}', '2026-09-12 15:44:01'),
(17, 1, 1, 'logout', 'Signed out of the account.', NULL, '2026-09-12 15:52:28'),
(18, 1, 1, 'login', 'Signed in successfully.', '{\"role\": \"super_admin\"}', '2026-09-12 15:52:44'),
(19, NULL, 1, 'support_staff_reply', 'Support staff replied to conversation #1', '{\"conversation_id\": 1}', '2026-09-12 16:05:36'),
(20, NULL, 1, 'support_status_changed', 'Support conversation #1 status changed to Closed', '{\"status\": \"Closed\", \"conversation_id\": 1}', '2026-09-12 16:06:42'),
(21, 1, 1, 'logout', 'Signed out of the account.', NULL, '2026-09-12 16:16:46');

-- --------------------------------------------------------

--
-- Table structure for table `user_mobile_numbers`
--

CREATE TABLE `user_mobile_numbers` (
  `mobile_id` int NOT NULL,
  `user_id` int NOT NULL,
  `label` varchar(40) NOT NULL DEFAULT 'Mobile',
  `mobile_number` varchar(30) NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `fk_cart_user` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD UNIQUE KEY `unique_cart_product` (`cart_id`,`product_id`),
  ADD KEY `fk_cartitem_product` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `contact_conversations`
--
ALTER TABLE `contact_conversations`
  ADD PRIMARY KEY (`conversation_id`),
  ADD KEY `idx_contact_user` (`user_id`),
  ADD KEY `idx_contact_email` (`requester_email`),
  ADD KEY `idx_contact_status_last` (`status`,`last_message_at`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `fk_contact_message_user` (`sender_user_id`),
  ADD KEY `idx_contact_message_thread` (`conversation_id`,`created_at`),
  ADD KEY `idx_contact_message_staff_unread` (`message_type`,`seen_by_staff`),
  ADD KEY `idx_contact_message_requester_unread` (`message_type`,`seen_by_requester`);

--
-- Indexes for table `currency_rates`
--
ALTER TABLE `currency_rates`
  ADD PRIMARY KEY (`rate_id`),
  ADD UNIQUE KEY `currency_code` (`currency_code`),
  ADD KEY `fk_currency_updated_by` (`updated_by`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `fk_order_user` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `fk_orderitem_order` (`order_id`),
  ADD KEY `fk_orderitem_product` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `fk_payment_order` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `fk_product_category` (`category_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `unique_user_product_review` (`user_id`,`product_id`),
  ADD KEY `fk_review_product` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `idx_activity_user_time` (`user_id`,`created_at`),
  ADD KEY `idx_activity_actor_time` (`actor_user_id`,`created_at`),
  ADD KEY `idx_activity_type_time` (`activity_type`,`created_at`);

--
-- Indexes for table `user_mobile_numbers`
--
ALTER TABLE `user_mobile_numbers`
  ADD PRIMARY KEY (`mobile_id`),
  ADD KEY `idx_user_mobile_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contact_conversations`
--
ALTER TABLE `contact_conversations`
  MODIFY `conversation_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `message_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `currency_rates`
--
ALTER TABLE `currency_rates`
  MODIFY `rate_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  MODIFY `activity_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `user_mobile_numbers`
--
ALTER TABLE `user_mobile_numbers`
  MODIFY `mobile_id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cartitem_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`cart_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cartitem_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `contact_conversations`
--
ALTER TABLE `contact_conversations`
  ADD CONSTRAINT `fk_contact_conversation_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD CONSTRAINT `fk_contact_message_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `contact_conversations` (`conversation_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_contact_message_user` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `currency_rates`
--
ALTER TABLE `currency_rates`
  ADD CONSTRAINT `fk_currency_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_orderitem_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orderitem_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  ADD CONSTRAINT `fk_activity_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `user_mobile_numbers`
--
ALTER TABLE `user_mobile_numbers`
  ADD CONSTRAINT `fk_user_mobile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
