-- Sri Lanka Rental System - Data Seeding
-- Extracted from phpMyAdmin.sql

SET NAMES utf8mb4;
SET time_zone = "+00:00";
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';

-- 1. Lookup Tables Data

-- User Roles
INSERT INTO `user_role` (`role_id`, `role_name`) VALUES 
(1, 'super_admin'), (2, 'admin'), (3, 'owner'), (4, 'customer')
ON DUPLICATE KEY UPDATE `role_name`=`role_name`;

-- User Statuses
INSERT INTO `user_status` (`status_id`, `status_name`) VALUES
(1, 'active'), (2, 'inactive'), (3, 'banned')
ON DUPLICATE KEY UPDATE `status_name`=`status_name`;

-- Package Types
INSERT INTO `package_type` (`type_id`, `type_name`) VALUES
(1, 'monthly'), (2, 'yearly'), (3, 'property_based'), (4, 'room_based'), (5, 'vehicle_based')
ON DUPLICATE KEY UPDATE `type_name`=`type_name`;

-- Package Statuses
INSERT INTO `package_status` (`status_id`, `status_name`) VALUES
(1, 'active'), (2, 'inactive')
ON DUPLICATE KEY UPDATE `status_name`=`status_name`;

-- Subscription Statuses
INSERT INTO `subscription_status` (`status_id`, `status_name`) VALUES
(1, 'active'), (2, 'expired')
ON DUPLICATE KEY UPDATE `status_name`=`status_name`;

-- Payment Statuses
INSERT INTO `payment_status` (`status_id`, `status_name`) VALUES
(1, 'pending'), (2, 'paid'), (3, 'failed'), (4, 'success')
ON DUPLICATE KEY UPDATE `status_name`=`status_name`;

-- Payment Methods
INSERT INTO `payment_method` (`method_id`, `method_name`) VALUES
(1, 'card'), (2, 'bank'), (3, 'mobile'), (4, 'cash')
ON DUPLICATE KEY UPDATE `method_name`=`method_name`;

-- Property Types
INSERT INTO `property_type` (`type_id`, `type_name`) VALUES
(1, 'Office Space(Per Sqrft)'), (2, 'Parking Property'), (3, 'Selling Property'), (4, 'Rental Property'), 
(5, 'Ware House'), (6, 'Anex(Space Office)'), (7, 'apartment'), (8, 'house'), (9, 'villa'), 
(10, 'duplex'), (11, 'studio'), (12, 'penthouse'), (13, 'bungalow'), (14, 'townhouse'), 
(15, 'farmhouse'), (16, 'office'), (17, 'shop'), (18, 'warehouse'), (19, 'land'), 
(20, 'commercial_building'), (21, 'industrial'), (22, 'hotel'), (23, 'guesthouse'), 
(24, 'resort'), (25, 'other')
ON DUPLICATE KEY UPDATE `type_name`=`type_name`;

-- Listing Statuses
INSERT INTO `listing_status` (`status_id`, `status_name`) VALUES
(1, 'available'), (2, 'rented'), (3, 'unavailable'), (4, 'pending'), (5, 'maintenance')
ON DUPLICATE KEY UPDATE `status_name`=`status_name`;

-- Room Types
INSERT INTO `room_type` (`type_id`, `type_name`) VALUES
(1, 'Anex Room'), (2, 'Daily Room'), (3, 'Hotel Room'), (4, 'Boarding Room'), (5, 'Single Room'), 
(6, 'Double Room'), (7, 'Twin Room'), (8, 'Suite'), (9, 'Deluxe'), (10, 'Family'), 
(11, 'Studio'), (12, 'Dorm'), (13, 'Apartment'), (14, 'Villa'), (15, 'Penthouse'), 
(16, 'Shared'), (17, 'Conference'), (18, 'Meeting'), (19, 'Other')
ON DUPLICATE KEY UPDATE `type_name`=`type_name`;

-- Vehicle Types
INSERT INTO `vehicle_type` (`type_id`, `type_name`) VALUES
(1, 'car'), (2, 'motorcycle'), (3, 'van'), (4, 'suv'), (5, 'pickup'), 
(6, 'coupe'), (7, 'sedan'), (8, 'hatchback'), (9, 'wagon'), (10, 'other')
ON DUPLICATE KEY UPDATE `type_name`=`type_name`;

-- Fuel Types
INSERT INTO `fuel_type` (`type_id`, `type_name`) VALUES
(1, 'petrol'), (2, 'diesel'), (3, 'electric'), (4, 'hybrid'), (5, 'other')
ON DUPLICATE KEY UPDATE `type_name`=`type_name`;

-- Transmission Types
INSERT INTO `transmission_type` (`type_id`, `type_name`) VALUES
(1, 'manual'), (2, 'automatic'), (3, 'cvt'), (4, 'semi-automatic'), (5, 'other')
ON DUPLICATE KEY UPDATE `type_name`=`type_name`;

-- Pricing Types
INSERT INTO `pricing_type` (`type_id`, `type_name`) VALUES
(1, 'per_day'), (2, 'per_km')
ON DUPLICATE KEY UPDATE `type_name`=`type_name`;

-- Rent Statuses
INSERT INTO `rent_status` (`status_id`, `status_name`) VALUES
(1, 'booked'), (2, 'pending'), (3, 'cancelled')
ON DUPLICATE KEY UPDATE `status_name`=`status_name`;

-- Notification Types
INSERT INTO `notification_type` (`type_id`, `type_name`) VALUES
(1, 'system'), (2, 'other')
ON DUPLICATE KEY UPDATE `type_name`=`type_name`;

-- Request Statuses
INSERT INTO `request_status` (`status_id`, `status_name`) VALUES
(1, 'pending'), (2, 'approved'), (3, 'rejected')
ON DUPLICATE KEY UPDATE `status_name`=`status_name`;


-- 2. Core Data

-- Packages
INSERT INTO `package` (`package_name`, `package_type_id`, `duration_days`, `max_properties`, `max_rooms`, `max_vehicles`, `price`, `description`, `status_id`) VALUES
  ('Property Monthly 10', 1, 30, 10, 0, 0, 1999.00, 'List up to 10 properties for 30 days.', 1),
  ('Property Yearly 120', 2, 365, 120, 0, 0, 19999.00, 'List up to 120 properties for 1 year.', 1),
  ('Room Monthly 10', 1, 30, 0, 10, 0, 1499.00, 'List up to 10 rooms for 30 days.', 1),
  ('Room Yearly 120', 2, 365, 0, 120, 0, 14999.00, 'List up to 120 rooms for 1 year.', 1),
  ('Vehicle Monthly 5', 1, 30, 0, 0, 5, 999.00, 'List up to 5 vehicles for 30 days.', 1),
  ('Vehicle Yearly 60', 2, 365, 0, 0, 60, 9999.00, 'List up to 60 vehicles for 1 year.', 1)
ON DUPLICATE KEY UPDATE
  `package_type_id`=VALUES(`package_type_id`),
  `duration_days`=VALUES(`duration_days`),
  `max_properties`=VALUES(`max_properties`),
  `max_rooms`=VALUES(`max_rooms`),
  `max_vehicles`=VALUES(`max_vehicles`),
  `price`=VALUES(`price`),
  `description`=VALUES(`description`),
  `status_id`=VALUES(`status_id`);

-- Users
INSERT INTO `user` (`user_id`, `email`, `name`, `mobile_number`, `role_id`, `status_id`, `created_at`) VALUES
(1, 'super_admin@rentallanka.com', 'superadmin', '0713018395', 1, 1, '2025-11-04 12:00:00'),
(2, 'admin@rentallanka.com', 'admin', '0710476945', 2, 1, '2025-11-04 12:00:00'),
(3, 'owner@rentallanka.com', 'owner1', '0713018095', 3, 1, '2025-11-04 12:00:00'),
(4, 'customer@rentallanka.com', 'customer1', '0713018096', 4, 1, '2025-11-04 12:00:00');

-- Settings
INSERT INTO `setting` (`setting_key`, `setting_value`) VALUES
  ('footer_company_name', 'Rentallanka'),
  ('footer_about', 'Find properties and rooms for rent across Sri Lanka.'),
  ('footer_address', 'Colombo, Sri Lanka'),
  ('footer_email', 'info@rentallanka.lk'),
  ('footer_phone', '071 234 5678'),
  ('footer_social_facebook', ''),
  ('footer_social_twitter', ''),
  ('footer_social_google', ''),
  ('footer_social_instagram', ''),
  ('footer_social_linkedin', ''),
  ('footer_social_github', ''),
  ('footer_products_links', 'Properties|/public/includes/all_properties.php\nRooms|/public/includes/all_rooms.php'),
  ('footer_useful_links', 'Pricing|#\nSettings|#\nOrders|#\nHelp|#'),
  ('footer_copyright_text', CONCAT('  ', YEAR(CURRENT_DATE), ' Copyright: ')),
  ('footer_show_social', '1'),
  ('footer_show_products', '1'),
  ('footer_show_useful_links', '1'),
  ('footer_show_contact', '1')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
