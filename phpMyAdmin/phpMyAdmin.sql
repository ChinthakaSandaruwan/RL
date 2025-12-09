SET NAMES utf8mb4;
SET time_zone = "+00:00";
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';

CREATE DATABASE IF NOT EXISTS `rentallanka`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `rentallanka`;

CREATE TABLE IF NOT EXISTS `user_role` (
  `role_id` INT NOT NULL AUTO_INCREMENT,
  `role_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `uk_user_role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_status` (
  `status_id` INT NOT NULL AUTO_INCREMENT,
  `status_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `uk_user_status_name` (`status_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_status` (
  `status_id` INT NOT NULL AUTO_INCREMENT,
  `status_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `uk_payment_status_name` (`status_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_method` (
  `method_id` INT NOT NULL AUTO_INCREMENT,
  `method_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`method_id`),
  UNIQUE KEY `uk_payment_method_name` (`method_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `provinces` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name_en` varchar(45) NOT NULL,
  `name_si` varchar(45) DEFAULT NULL,
  `name_ta` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 ;

CREATE TABLE IF NOT EXISTS `districts` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `province_id` INT NOT NULL,
  `name_en` varchar(45) DEFAULT NULL,
  `name_si` varchar(45) DEFAULT NULL,
  `name_ta` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `provinces_id` (`province_id`),
  CONSTRAINT `fk_districts_provinces` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 ;

CREATE TABLE IF NOT EXISTS `cities` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `district_id` INT NOT NULL,
  `name_en` VARCHAR(45) DEFAULT NULL,
  `name_si` VARCHAR(45) DEFAULT NULL,
  `name_ta` VARCHAR(45) DEFAULT NULL,
  `sub_name_en` VARCHAR(45) DEFAULT NULL,
  `sub_name_si` VARCHAR(45) DEFAULT NULL,
  `sub_name_ta` VARCHAR(45) DEFAULT NULL,
  `postcode` VARCHAR(15) DEFAULT NULL,
  `latitude` DOUBLE DEFAULT NULL,
  `longitude` DOUBLE DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_cities_districts1_idx` (`district_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 ;

CREATE TABLE IF NOT EXISTS `property_type` (
  `type_id` INT NOT NULL AUTO_INCREMENT,
  `type_name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `uk_property_type_name` (`type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `listing_status` (
  `status_id` INT NOT NULL AUTO_INCREMENT,
  `status_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `uk_listing_status_name` (`status_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rent_status` (
  `status_id` INT NOT NULL AUTO_INCREMENT,
  `status_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `uk_rent_status_name` (`status_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notification_type` (
  `type_id` INT NOT NULL AUTO_INCREMENT,
  `type_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `uk_notification_type_name` (`type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `request_status` (
  `status_id` INT NOT NULL AUTO_INCREMENT,
  `status_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `uk_request_status_name` (`status_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `amenity` (
  `amenity_id` INT NOT NULL AUTO_INCREMENT,
  `amenity_name` VARCHAR(100) NOT NULL,
  `category` ENUM('property', 'room', 'both') NOT NULL DEFAULT 'both',
  PRIMARY KEY (`amenity_id`),
  UNIQUE KEY `uk_amenity_name` (`amenity_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vehicle_brand` (
  `brand_id` INT NOT NULL AUTO_INCREMENT,
  `brand_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`brand_id`),
  UNIQUE KEY `uk_vehicle_brand_name` (`brand_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `meal_type` (
  `type_id` INT NOT NULL AUTO_INCREMENT,
  `type_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `uk_meal_type_name` (`type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user` (
  `user_id` INT NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NULL,
  `nic` VARCHAR(20) NULL,
  `name` VARCHAR(100) NULL,
  `mobile_number` VARCHAR(10) NOT NULL,
  `profile_image` VARCHAR(255) NULL,
  `role_id` INT NOT NULL,
  `status_id` INT NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uk_users_phone` (`mobile_number`),
  UNIQUE KEY `uk_users_email` (`email`),
  UNIQUE KEY `uk_users_nic` (`nic`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_status` (`status_id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `user_role` (`role_id`),
  CONSTRAINT `fk_users_status` FOREIGN KEY (`status_id`) REFERENCES `user_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `otp_verification` (
  `otp_id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `otp_code` VARCHAR(6) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `is_verified` TINYINT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`otp_id`),
  KEY `idx_otp_user_id` (`user_id`),
  KEY `idx_otp_code` (`otp_code`),
  KEY `idx_otp_is_verified` (`is_verified`),
  CONSTRAINT `fk_otp_verifications_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `package_type` (
  `type_id` INT NOT NULL AUTO_INCREMENT,
  `type_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `uk_package_type_name` (`type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `package_status` (
  `status_id` INT NOT NULL AUTO_INCREMENT,
  `status_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `uk_package_status_name` (`status_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `package` (
  `package_id` INT NOT NULL AUTO_INCREMENT,
  `package_name` VARCHAR(150) NOT NULL,
  `package_type_id` INT NOT NULL,
  `duration_days` INT NULL,
  `max_properties` INT NULL DEFAULT 0,
  `max_rooms` INT NULL DEFAULT 0,
  `max_vehicles` INT NULL DEFAULT 0,
  `price` DECIMAL(10,2) NOT NULL,
  `description` TEXT NULL,
  `status_id` INT NOT NULL DEFAULT 1, -- Default active
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`package_id`),
  UNIQUE KEY `uk_packages_name` (`package_name`),
  KEY `idx_packages_type` (`package_type_id`),
  KEY `idx_packages_status` (`status_id`),
  CONSTRAINT `fk_packages_type` FOREIGN KEY (`package_type_id`) REFERENCES `package_type` (`type_id`),
  CONSTRAINT `fk_packages_status` FOREIGN KEY (`status_id`) REFERENCES `package_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `subscription_status` (
  `status_id` INT NOT NULL AUTO_INCREMENT,
  `status_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `uk_subscription_status_name` (`status_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bought_package` (
  `bought_package_id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `package_id` INT NOT NULL,
  `bought_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_date` DATETIME NULL,
  `remaining_properties` INT DEFAULT 0,
  `remaining_rooms` INT DEFAULT 0,
  `remaining_vehicles` INT DEFAULT 0,
  `status_id` INT NOT NULL DEFAULT 1, -- Default active (subscription_statuses)
  `payment_slip` VARCHAR(255) NULL,
  `payment_status_id` INT NOT NULL DEFAULT 1, -- Default pending (payment_statuses)
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`bought_package_id`),
  KEY `idx_bought_packages_user_id` (`user_id`),
  KEY `idx_bought_packages_package_id` (`package_id`),
  KEY `idx_bought_packages_status` (`status_id`),
  KEY `idx_bought_packages_payment_status` (`payment_status_id`),
  CONSTRAINT `fk_bought_packages_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bought_packages_package` FOREIGN KEY (`package_id`) REFERENCES `package` (`package_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bought_packages_status` FOREIGN KEY (`status_id`) REFERENCES `subscription_status` (`status_id`),
  CONSTRAINT `fk_bought_packages_payment_status` FOREIGN KEY (`payment_status_id`) REFERENCES `payment_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `property` (
  `property_id` INT NOT NULL AUTO_INCREMENT,
  `property_code` VARCHAR(255) NOT NULL,
  `owner_id` INT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `price_per_month` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `bedrooms` INT NULL DEFAULT 0,
  `bathrooms` INT NULL DEFAULT 0,
  `living_rooms` INT NULL DEFAULT 0,
  `garden` INT NULL DEFAULT 0,
  /* Amenities moved to property_amenity table */
  `sqft` DECIMAL(10,2) NULL,
  `property_type_id` INT NULL,
  `status_id` INT NOT NULL DEFAULT 4, -- Default pending (listing_statuses)
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_disable` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`property_id`),
  KEY `idx_properties_owner_id` (`owner_id`),
  KEY `idx_properties_status` (`status_id`),
  KEY `idx_properties_type` (`property_type_id`),
  KEY `idx_props_status_price` (`status_id`, `price_per_month`),
  FULLTEXT KEY `idx_ft_properties_title_desc` (`title`, `description`),
  CONSTRAINT `fk_properties_owner` FOREIGN KEY (`owner_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_properties_type` FOREIGN KEY (`property_type_id`) REFERENCES `property_type` (`type_id`),
  CONSTRAINT `fk_properties_status` FOREIGN KEY (`status_id`) REFERENCES `listing_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `property_amenity` (
  `property_id` INT NOT NULL,
  `amenity_id` INT NOT NULL,
  PRIMARY KEY (`property_id`, `amenity_id`),
  CONSTRAINT `fk_pa_property` FOREIGN KEY (`property_id`) REFERENCES `property` (`property_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pa_amenity` FOREIGN KEY (`amenity_id`) REFERENCES `amenity` (`amenity_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `property_image` (
  `image_id` INT NOT NULL AUTO_INCREMENT,
  `property_id` INT NOT NULL,
   `primary_image` TINYINT NOT NULL DEFAULT 0,
  `image_path` VARCHAR(255) NOT NULL,
  `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`image_id`),
  KEY `idx_property_images_property_id` (`property_id`),
  CONSTRAINT `fk_property_images_property` FOREIGN KEY (`property_id`) REFERENCES `property` (`property_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `property_location` (
  `location_id` INT NOT NULL AUTO_INCREMENT,
  `property_id` INT NOT NULL,
  `city_id` INT NULL,
  `address` VARCHAR(255) NULL,
  `google_map_link` VARCHAR(255) NULL,
  `postal_code` VARCHAR(10) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`location_id`),
  KEY `idx_locations_property_id` (`property_id`),
  KEY `idx_locations_city_id` (`city_id`),
  CONSTRAINT `fk_property_locations_property` FOREIGN KEY (`property_id`) REFERENCES `property` (`property_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_property_locations_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `room_type` (
  `type_id` INT NOT NULL AUTO_INCREMENT,
  `type_name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `uk_room_type_name` (`type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `room` (
  `room_id` INT NOT NULL AUTO_INCREMENT,
  `room_code` VARCHAR(255) NOT NULL,
  `owner_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `room_type_id` INT NULL,
  `beds` INT NOT NULL DEFAULT 1,
  `bathrooms` INT NOT NULL DEFAULT 1,
  `maximum_guests` INT NOT NULL DEFAULT 1,
  `price_per_day` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status_id` INT NOT NULL DEFAULT 4,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `update_disable` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`room_id`),
  KEY `idx_rooms_owner_id` (`owner_id`),
  KEY `idx_rooms_status` (`status_id`),
  KEY `idx_rooms_type` (`room_type_id`),
  FULLTEXT KEY `idx_ft_rooms_title_desc` (`title`, `description`),
  CONSTRAINT `fk_rooms_owner` FOREIGN KEY (`owner_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rooms_type` FOREIGN KEY (`room_type_id`) REFERENCES `room_type` (`type_id`),
  CONSTRAINT `fk_rooms_status` FOREIGN KEY (`status_id`) REFERENCES `listing_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `room_amenity` (
  `room_id` INT NOT NULL,
  `amenity_id` INT NOT NULL,
  PRIMARY KEY (`room_id`, `amenity_id`),
  CONSTRAINT `fk_ra_room` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ra_amenity` FOREIGN KEY (`amenity_id`) REFERENCES `amenity` (`amenity_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `room_image` (
  `image_id` INT NOT NULL AUTO_INCREMENT,
  `room_id` INT NOT NULL,
  `primary_image` TINYINT NOT NULL DEFAULT 0,
  `image_path` VARCHAR(255) NOT NULL,
  `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`image_id`),
  KEY `idx_room_images_room_id` (`room_id`),
  CONSTRAINT `fk_room_images_room` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `room_location` (
  `location_id` INT NOT NULL AUTO_INCREMENT,
  `room_id` INT NOT NULL,
  `city_id` INT NULL,
  `address` VARCHAR(255) NULL,
  `google_map_link` VARCHAR(255) NULL,
  `postal_code` VARCHAR(10) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`location_id`),
  KEY `idx_locations_room_id` (`room_id`),
  KEY `idx_locations_city_id` (`city_id`),
  CONSTRAINT `fk_room_locations_room` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_room_locations_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `setting` (
  `setting_id` INT NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `uk_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `footer_content` (
  `footer_id` INT NOT NULL AUTO_INCREMENT,
  `company_name` VARCHAR(100) NOT NULL,
  `about_text` TEXT NULL,
  `address` VARCHAR(255) NULL,
  `email` VARCHAR(100) NULL,
  `phone` VARCHAR(20) NULL,
  `facebook_link` VARCHAR(255) NULL,
  `twitter_link` VARCHAR(255) NULL,
  `google_link` VARCHAR(255) NULL,
  `instagram_link` VARCHAR(255) NULL,
  `linkedin_link` VARCHAR(255) NULL,
  `github_link` VARCHAR(255) NULL,
  `copyright_text` VARCHAR(255) NULL,
  `show_social_links` TINYINT(1) DEFAULT 1,
  `show_products` TINYINT(1) DEFAULT 1,
  `show_useful_links` TINYINT(1) DEFAULT 1,
  `show_contact` TINYINT(1) DEFAULT 1,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`footer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `property_wishlist` (
  `wishlist_id` INT NOT NULL AUTO_INCREMENT,
  `customer_id` INT NOT NULL,
  `property_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`wishlist_id`),
  UNIQUE KEY `uk_wishlist_unique` (`customer_id`, `property_id`),
  CONSTRAINT `fk_wishlist_customer` FOREIGN KEY (`customer_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_property` FOREIGN KEY (`property_id`) REFERENCES `property` (`property_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `room_wishlist` (
  `wishlist_id` INT NOT NULL AUTO_INCREMENT,
  `customer_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`wishlist_id`),
  UNIQUE KEY `uk_room_wishlist_unique` (`customer_id`, `room_id`),
  CONSTRAINT `fk_room_wishlist_customer` FOREIGN KEY (`customer_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_room_wishlist_room` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL, -- The all user types (admin, owner, customer)
  `action` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_user_logs_user` (`user_id`),
  CONSTRAINT `fk_user_logs_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notification` (
  `notification_id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type_id` INT NOT NULL DEFAULT 1, 
  `property_id` INT NULL,
  `is_read` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `read_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `idx_notifications_user_id` (`user_id`),
  KEY `idx_notifications_is_read` (`is_read`),
  KEY `idx_notifications_property_id` (`property_id`),
  KEY `idx_notifications_type` (`type_id`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notifications_property` FOREIGN KEY (`property_id`) REFERENCES `property` (`property_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_notifications_type` FOREIGN KEY (`type_id`) REFERENCES `notification_type` (`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS advertiser_request;
CREATE TABLE IF NOT EXISTS `advertiser_request` (
  `request_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `status_id` INT NOT NULL DEFAULT 1, -- Default pending (request_statuses)
  `reviewed_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`request_id`),
  KEY `idx_ar_user` (`user_id`),
  KEY `idx_ar_status` (`status_id`),
  CONSTRAINT `fk_advreq_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_advreq_status` FOREIGN KEY (`status_id`) REFERENCES `request_status`(`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `room_meal` (
  `room_id` INT NOT NULL,
  `meal_type_id` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`room_id`, `meal_type_id`),
  CONSTRAINT `fk_room_meals_room` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_room_meals_type` FOREIGN KEY (`meal_type_id`) REFERENCES `meal_type` (`type_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `room_rent` (
  `rent_id` INT NOT NULL AUTO_INCREMENT,
  `room_id` INT NOT NULL,
  `customer_id` INT NOT NULL,
  `checkin_date` DATETIME NOT NULL,
  `checkout_date` DATETIME NOT NULL,
  `guests` INT NOT NULL DEFAULT 1,
  `meal_id` INT NULL,
  `price_per_night` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status_id` INT NOT NULL DEFAULT 2, -- Default pending (rent_statuses)
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rent_id`),
  KEY `idx_room_rents_room_id` (`room_id`),
  KEY `idx_room_rents_customer_id` (`customer_id`),
  KEY `idx_room_rents_checkin` (`checkin_date`),
  KEY `idx_room_rents_status` (`status_id`),
  CONSTRAINT `fk_room_rents_room` FOREIGN KEY (`room_id`) REFERENCES `room` (`room_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_room_rents_customer` FOREIGN KEY (`customer_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_room_rents_status` FOREIGN KEY (`status_id`) REFERENCES `rent_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `property_rent` (
  `rent_id` INT NOT NULL AUTO_INCREMENT,
  `property_id` INT NOT NULL,
  `customer_id` INT NOT NULL,
  `price_per_month` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status_id` INT NOT NULL DEFAULT 2, -- Default pending
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rent_id`),
  KEY `idx_property_rents_property_id` (`property_id`),
  KEY `idx_property_rents_customer_id` (`customer_id`),
  KEY `idx_property_rents_status` (`status_id`),
  CONSTRAINT `fk_property_rents_property` FOREIGN KEY (`property_id`) REFERENCES `property` (`property_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_property_rents_customer` FOREIGN KEY (`customer_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_property_rents_status` FOREIGN KEY (`status_id`) REFERENCES `rent_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bank` (
  `bank_id` INT NOT NULL AUTO_INCREMENT,
  `bank_name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`bank_id`),
  UNIQUE KEY `uk_bank_name` (`bank_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_bank_account` (
  `account_id` INT NOT NULL AUTO_INCREMENT,
  `bank_id` INT NOT NULL,
  `branch` VARCHAR(100) NOT NULL,
  `account_number` VARCHAR(50) NOT NULL,
  `account_holder_name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_id`),
  KEY `idx_admin_bank_bank` (`bank_id`),
  CONSTRAINT `fk_admin_bank_bank` FOREIGN KEY (`bank_id`) REFERENCES `bank` (`bank_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vehicle_type` (
  `type_id` INT NOT NULL AUTO_INCREMENT,
  `type_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `uk_vehicle_type_name` (`type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fuel_type` (
  `type_id` INT NOT NULL AUTO_INCREMENT,
  `type_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `uk_fuel_type_name` (`type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `transmission_type` (
  `type_id` INT NOT NULL AUTO_INCREMENT,
  `type_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `uk_transmission_type_name` (`type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pricing_type` (
  `type_id` INT NOT NULL AUTO_INCREMENT,
  `type_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `uk_pricing_type_name` (`type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vehicle_color` (
  `color_id` INT NOT NULL AUTO_INCREMENT,
  `color_name` VARCHAR(50) NOT NULL,
  `hex_code` VARCHAR(7) NULL,
  PRIMARY KEY (`color_id`),
  UNIQUE KEY `uk_vehicle_color_name` (`color_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vehicle_model` (
  `model_id` INT NOT NULL AUTO_INCREMENT,
  `brand_id` INT NOT NULL,
  `model_name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`model_id`),
  UNIQUE KEY `uk_vehicle_model_name` (`model_name`),
  KEY `idx_vehicle_model_brand` (`brand_id`),
  CONSTRAINT `fk_vehicle_model_brand` FOREIGN KEY (`brand_id`) REFERENCES `vehicle_brand` (`brand_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vehicle` (
  `vehicle_id` INT NOT NULL AUTO_INCREMENT,
  `vehicle_code` VARCHAR(255) NOT NULL,
  `owner_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `model_id` INT NOT NULL,
  `year` INT NOT NULL,
  `vehicle_type_id` INT NOT NULL,
  `fuel_type_id` INT NOT NULL,
  `transmission_type_id` INT NOT NULL,
  `number_of_seats` INT NULL,
  `mileage` DECIMAL(8,2) NULL,
  `pricing_type_id` INT NOT NULL DEFAULT 1,
  `price_per_day` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `price_per_km` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `security_deposit` DECIMAL(10,2) NULL DEFAULT 0.00,
  `license_plate` VARCHAR(20) NULL,
  `color_id` INT NOT NULL,
  `is_driver_available` TINYINT(1) NOT NULL DEFAULT 0,
  `driver_cost_per_day` DECIMAL(10,2) NULL DEFAULT 0.00,
  `status_id` INT NOT NULL DEFAULT 4,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `update_disable` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`vehicle_id`),
  UNIQUE KEY `uk_vehicles_license_plate` (`license_plate`),
  KEY `idx_vehicles_owner_id` (`owner_id`),
  KEY `idx_vehicles_status` (`status_id`),
  KEY `idx_vehicles_type` (`vehicle_type_id`),
  KEY `idx_vehicles_fuel` (`fuel_type_id`),
  KEY `idx_vehicles_transmission` (`transmission_type_id`),
  FULLTEXT KEY `idx_ft_vehicles_title_desc` (`title`, `description`),
  CONSTRAINT `fk_vehicles_owner` FOREIGN KEY (`owner_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vehicles_model` FOREIGN KEY (`model_id`) REFERENCES `vehicle_model` (`model_id`),
  CONSTRAINT `fk_vehicles_type` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_type` (`type_id`),
  CONSTRAINT `fk_vehicles_fuel` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_type` (`type_id`),
  CONSTRAINT `fk_vehicles_transmission` FOREIGN KEY (`transmission_type_id`) REFERENCES `transmission_type` (`type_id`),
  CONSTRAINT `fk_vehicles_color` FOREIGN KEY (`color_id`) REFERENCES `vehicle_color` (`color_id`),
  CONSTRAINT `fk_vehicles_pricing` FOREIGN KEY (`pricing_type_id`) REFERENCES `pricing_type` (`type_id`),
  CONSTRAINT `fk_vehicles_status` FOREIGN KEY (`status_id`) REFERENCES `listing_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vehicle_location` (
  `location_id` INT NOT NULL AUTO_INCREMENT,
  `vehicle_id` INT NOT NULL,
  `province_id` INT NULL,
  `district_id` INT NULL,
  `city_id` INT NULL,
  `address` VARCHAR(255) NULL,
  `google_map_link` VARCHAR(255) NULL,
  `postal_code` VARCHAR(10) NULL,
  `pickup_instructions` TEXT NULL,
  PRIMARY KEY (`location_id`),
  KEY `idx_vehicle_locations_vehicle_id` (`vehicle_id`),
  KEY `idx_vehicle_locations_city` (`city_id`),
  KEY `idx_vehicle_locations_province_id` (`province_id`),
  KEY `idx_vehicle_locations_district_id` (`district_id`),
  CONSTRAINT `fk_vehicle_locations_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicle` (`vehicle_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vehicle_locations_province` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vehicle_locations_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vehicle_locations_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vehicle_image` (
  `image_id` INT NOT NULL AUTO_INCREMENT,
  `vehicle_id` INT NOT NULL,
   `primary_image` BOOLEAN NOT NULL DEFAULT 0,
  `image_path` VARCHAR(255) NOT NULL,
 
  `alt_text` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`image_id`),
  KEY `idx_vehicle_images_vehicle_id` (`vehicle_id`),
  KEY `idx_vehicle_images_primary` (`primary_image`),
  CONSTRAINT `fk_vehicle_images_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicle` (`vehicle_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vehicle_rent` (
  `rent_id` INT NOT NULL AUTO_INCREMENT,
  `vehicle_id` INT NOT NULL,
  `customer_id` INT NOT NULL,
  `pickup_date` DATETIME NOT NULL,
  `dropoff_date` DATETIME NOT NULL,
  `price_per_day` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status_id` INT NOT NULL DEFAULT 2, -- Default pending
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rent_id`),
  KEY `idx_vehicle_rents_vehicle_id` (`vehicle_id`),
  KEY `idx_vehicle_rents_customer_id` (`customer_id`),
  KEY `idx_vehicle_rents_pickup_date` (`pickup_date`),
  KEY `idx_vehicle_rents_status` (`status_id`),
  CONSTRAINT `fk_vehicle_rents_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicle` (`vehicle_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vehicle_rents_customer` FOREIGN KEY (`customer_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vehicle_rents_status` FOREIGN KEY (`status_id`) REFERENCES `rent_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 

CREATE TABLE IF NOT EXISTS `vehicle_wishlist` (
  `wishlist_id` INT NOT NULL AUTO_INCREMENT,
  `customer_id` INT NOT NULL,
  `vehicle_id` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`wishlist_id`),
  UNIQUE KEY `uk_vehicle_wishlist_unique` (`customer_id`, `vehicle_id`),
  KEY `idx_vehicle_wishlist_vehicle` (`vehicle_id`),
  CONSTRAINT `fk_vw_user` FOREIGN KEY (`customer_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vw_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicle` (`vehicle_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_type_change_request` (
  `request_id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `current_role_id` INT NOT NULL,
  `requested_role_id` INT NOT NULL,
  `reason` TEXT NULL,
  `status_id` INT NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` DATETIME NULL,
  `processed_by` INT NULL,
  PRIMARY KEY (`request_id`),
  KEY `idx_user_type_change_user` (`user_id`),
  KEY `idx_user_type_change_status` (`status_id`),
  CONSTRAINT `fk_user_type_change_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `review` (
  `review_id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `target_id` INT NOT NULL, -- ID of the property/room/vehicle
  `target_type` ENUM('property', 'room', 'vehicle') NOT NULL,
  `rating` DECIMAL(2,1) NOT NULL, -- e.g., 4.5
  `comment` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  KEY `idx_reviews_user` (`user_id`),
  KEY `idx_reviews_target` (`target_id`, `target_type`),
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `transaction` (
  `transaction_id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'LKR',
  `reference_id` VARCHAR(100) NULL, -- Gateway Ref or Bank Slip Ref
  `payment_method_id` INT NOT NULL,
  `status` ENUM('pending', 'success', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `related_type` ENUM('package', 'rent') NOT NULL,
  `related_id` INT NOT NULL, -- package_id or rent_id
  `proof_image` VARCHAR(255) NULL, -- For bank slips
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`transaction_id`),
  KEY `idx_transactions_user` (`user_id`),
  CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transactions_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method` (`method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat` (
  `chat_id` INT NOT NULL AUTO_INCREMENT,
  `user1_id` INT NOT NULL, -- Usually customer
  `user2_id` INT NOT NULL, -- Usually owner/admin
  `related_type` ENUM('property', 'room', 'vehicle', 'general') NULL,
  `related_id` INT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`chat_id`),
  UNIQUE KEY `uk_chat_users_topic` (`user1_id`, `user2_id`, `related_type`, `related_id`),
  CONSTRAINT `fk_chat_user1` FOREIGN KEY (`user1_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_user2` FOREIGN KEY (`user2_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_message` (
  `message_id` BIGINT NOT NULL AUTO_INCREMENT,
  `chat_id` INT NOT NULL,
  `sender_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`),
  KEY `idx_messages_chat` (`chat_id`),
  CONSTRAINT `fk_messages_chat` FOREIGN KEY (`chat_id`) REFERENCES `chat` (`chat_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `availability_block` (
  `block_id` INT NOT NULL AUTO_INCREMENT,
  `item_id` INT NOT NULL,
  `item_type` ENUM('property', 'room', 'vehicle') NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `reason` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`block_id`),
  KEY `idx_blocks_item` (`item_id`, `item_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `coupon` (
  `coupon_id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `discount_amount` DECIMAL(10,2) NULL,
  `discount_percentage` DECIMAL(5,2) NULL, -- e.g. 10.50 for 10.5%
  `min_spend` DECIMAL(10,2) NULL,
  `expiry_date` DATETIME NOT NULL,
  `usage_limit` INT NOT NULL DEFAULT 100,
  `usage_count` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`coupon_id`),
  UNIQUE KEY `uk_coupon_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_document` (
  `document_id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `document_type` ENUM('nic_front', 'nic_back', 'license_front', 'license_back', 'passport', 'business_reg') NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0, -- 0: Pending, 1: Verified, 2: Rejected
  `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`document_id`),
  KEY `idx_docs_user` (`user_id`),
  CONSTRAINT `fk_docs_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
