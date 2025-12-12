-- Sri Lanka Rental System - Data Seeding
-- Extracted from phpMyAdmin.sql

SET NAMES utf8mb4;
SET time_zone = "+00:00";
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';


-- 1. Location Data (Provinces & Districts)



-- 2. User & System Lookup Data

-- User Roles
INSERT INTO `user_role` (`role_id`, `role_name`) VALUES 
(1, 'Super Admin'), (2, 'Admin'), (3, 'Owner'), (4, 'Customer')
ON DUPLICATE KEY UPDATE `role_name`=`role_name`;

-- User Statuses
INSERT INTO `user_status` (`status_id`, `status_name`) VALUES
(1, 'Active'), (2, 'Inactive'), (3, 'Banned')
ON DUPLICATE KEY UPDATE `status_name`=`status_name`;

-- Package Types (Duration + Category Combinations)
INSERT INTO `package_type` (`type_id`, `type_name`) VALUES
(1, 'Monthly - Property'),
(2, 'Monthly - Room'),
(3, 'Monthly - Vehicle'),
(4, 'Monthly - Property & Room'),
(5, 'Monthly - Property & Vehicle'),
(6, 'Monthly - Room & Vehicle'),
(7, 'Monthly - All (Property, Room & Vehicle)'),
(8, 'Yearly - Property'),
(9, 'Yearly - Room'),
(10, 'Yearly - Vehicle'),
(11, 'Yearly - Property & Room'),
(12, 'Yearly - Property & Vehicle'),
(13, 'Yearly - Room & Vehicle'),
(14, 'Yearly - All (Property, Room & Vehicle)')
ON DUPLICATE KEY UPDATE `type_name`=`type_name`;

-- Package Statuses
INSERT INTO `package_status` (`status_id`, `status_name`) VALUES
(1, 'Active'), (2, 'Inactive')
ON DUPLICATE KEY UPDATE `status_name`=`status_name`;

-- Subscription Statuses
INSERT INTO `subscription_status` (`status_id`, `status_name`) VALUES
(1, 'Active'), (2, 'Expired')
ON DUPLICATE KEY UPDATE `status_name`=`status_name`;

-- Payment Statuses
INSERT INTO `payment_status` (`status_id`, `status_name`) VALUES
(1, 'Pending'), (2, 'Paid'), (3, 'Failed'), (4, 'Success')
ON DUPLICATE KEY UPDATE `status_name`=`status_name`;

-- Payment Methods
INSERT INTO `payment_method` (`method_id`, `method_name`) VALUES
(1, 'Card'), (2, 'Bank'), (3, 'Mobile'), (4, 'Cash')
ON DUPLICATE KEY UPDATE `method_name`=`method_name`;

-- Property Types
INSERT INTO `property_type` (`type_id`, `type_name`) VALUES
(1, 'Office Space(Per Sqrft)'), (2, 'Parking Property'), (3, 'Selling Property'), (4, 'Rental Property'), 
(5, 'Ware House'), (6, 'Anex(Space Office)'), (7, 'Apartment'), (8, 'House'), (9, 'Villa'), 
(10, 'Duplex'), (11, 'Studio'), (12, 'Penthouse'), (13, 'Bungalow'), (14, 'Townhouse'), 
(15, 'Farmhouse'), (16, 'Office'), (17, 'Shop'), (18, 'Warehouse'), (19, 'Land'), 
(20, 'Commercial Building'), (21, 'Industrial'), (22, 'Hotel'), (23, 'Guesthouse'), 
(24, 'Resort'), (25, 'Other')
ON DUPLICATE KEY UPDATE `type_name`=`type_name`;

-- Listing Statuses
INSERT INTO `listing_status` (`status_id`, `status_name`) VALUES
(1, 'Available'), (2, 'Rented'), (3, 'Unavailable'), (4, 'Pending'), (5, 'Maintenance')
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
(1, 'Car'), (2, 'Motorcycle'), (3, 'Van'), (4, 'SUV'), (5, 'Pickup'), 
(6, 'Coupe'), (7, 'Sedan'), (8, 'Hatchback'), (9, 'Wagon'), (10, 'Other')
ON DUPLICATE KEY UPDATE `type_name`=`type_name`;

-- Fuel Types
INSERT INTO `fuel_type` (`type_id`, `type_name`) VALUES
(1, 'Petrol'), (2, 'Diesel'), (3, 'Electric'), (4, 'Hybrid'), (5, 'Other')
ON DUPLICATE KEY UPDATE `type_name`=`type_name`;

-- Transmission Types
INSERT INTO `transmission_type` (`type_id`, `type_name`) VALUES
(1, 'Manual'), (2, 'Automatic'), (3, 'CVT'), (4, 'Semi-Automatic'), (5, 'Other')
ON DUPLICATE KEY UPDATE `type_name`=`type_name`;

-- Pricing Types
INSERT INTO `pricing_type` (`type_id`, `type_name`) VALUES
(1, 'Per Day'), (2, 'Per Km')
ON DUPLICATE KEY UPDATE `type_name`=`type_name`;

-- Rent Statuses
INSERT INTO `rent_status` (`status_id`, `status_name`) VALUES
(1, 'Booked'), (2, 'Pending'), (3, 'Cancelled')
ON DUPLICATE KEY UPDATE `status_name`=`status_name`;

-- Notification Types
INSERT INTO `notification_type` (`type_id`, `type_name`) VALUES
(1, 'System'), (2, 'Other')
ON DUPLICATE KEY UPDATE `type_name`=`type_name`;

-- Request Statuses
INSERT INTO `request_status` (`status_id`, `status_name`) VALUES
(1, 'Pending'), (2, 'Approved'), (3, 'Rejected')
ON DUPLICATE KEY UPDATE `status_name`=`status_name`;


-- 2. Core Data

-- Packages (Sample Data)
INSERT INTO `package` (`package_name`, `package_type_id`, `duration_days`, `max_properties`, `max_rooms`, `max_vehicles`, `price`, `description`, `status_id`) VALUES
  ('Basic Monthly Property', 1, 30, 5, 0, 0, 2999.00, 'List up to 5 properties for 1 month', 1),
  ('Basic Monthly Room', 2, 30, 0, 10, 0, 1999.00, 'List up to 10 rooms for 1 month', 1),
  ('Basic Monthly Vehicle', 3, 30, 0, 0, 5, 1499.00, 'List up to 5 vehicles for 1 month', 1),
  ('Combo Monthly - Property & Room', 4, 30, 5, 10, 0, 4499.00, 'List 5 properties and 10 rooms for 1 month', 1),
  ('All-In-One Monthly', 7, 30, 5, 10, 5, 5999.00, 'Complete package: 5 properties, 10 rooms, 5 vehicles for 1 month', 1),
  ('Pro Yearly Property', 8, 365, 50, 0, 0, 29999.00, 'List up to 50 properties for 1 year', 1),
  ('Pro Yearly Room', 9, 365, 0, 100, 0, 19999.00, 'List up to 100 rooms for 1 year', 1),
  ('Pro Yearly Vehicle', 10, 365, 0, 0, 50, 14999.00, 'List up to 50 vehicles for 1 year', 1),
  ('Premium Yearly - All Categories', 14, 365, 50, 100, 50, 59999.00, 'Ultimate package: 50 properties, 100 rooms, 50 vehicles for 1 year', 1)
ON DUPLICATE KEY UPDATE
  `package_type_id`=VALUES(`package_type_id`),
  `duration_days`=VALUES(`duration_days`),
  `max_properties`=VALUES(`max_properties`),
  `max_rooms`=VALUES(`max_rooms`),
  `max_vehicles`=VALUES(`max_vehicles`),
  `price`=VALUES(`price`),
  `description`=VALUES(`description`),
  `status_id`=VALUES(`status_id`);


-- Vehicle Brands
INSERT IGNORE INTO `vehicle_brand` (`brand_id`, `brand_name`) VALUES 
(1, 'Toyota'), (2, 'Honda'), (3, 'Nissan'), (4, 'Suzuki'), (5, 'Mitsubishi'), 
(6, 'Kia'), (7, 'Hyundai'), (8, 'BMW'), (9, 'Mercedes-Benz'), (10, 'Audi')
ON DUPLICATE KEY UPDATE `brand_name`=`brand_name`;



-- Vehicle Models (Sample)
INSERT IGNORE INTO `vehicle_model` (`model_id`, `brand_id`, `model_name`) VALUES
(1, 1, 'Corolla'), (2, 1, 'Prius'), (3, 1, 'Land Cruiser'), (4, 1, 'Premio'),
(5, 2, 'Civic'), (6, 2, 'Vezel'), (7, 2, 'Fit'),
(8, 3, 'X-Trail'), (9, 3, 'Leaf'),
(10, 4, 'Alto'), (11, 4, 'WagonR')
ON DUPLICATE KEY UPDATE `model_name`=`model_name`;

-- Banks
INSERT IGNORE INTO `admin_bank` (`bank_id`, `bank_name`) VALUES
(1, 'Bank of Ceylon'), (2, 'Peoples Bank'), (3, 'Commercial Bank'), (4, 'Hatton National Bank'),
(5, 'Sampath Bank'), (6, 'Seylan Bank'), (7, 'Nations Trust Bank')
ON DUPLICATE KEY UPDATE `bank_name`=`bank_name`;

-- Amenities
INSERT IGNORE INTO `amenity` (`amenity_id`, `amenity_name`, `category`) VALUES
(1, 'Air Conditioning', 'both'), (2, 'WiFi', 'both'), (3, 'TV', 'both'), (4, 'Kitchen', 'property'),
(5, 'Swimming Pool', 'property'), (6, 'Gym', 'property'), (7, 'Parking', 'property'),
(8, 'Hot Water', 'both'), (9, 'Washing Machine', 'both'), (10, 'Fridge', 'both')
ON DUPLICATE KEY UPDATE `amenity_name`=`amenity_name`;

-- Meal Types
INSERT IGNORE INTO `meal_type` (`type_id`, `type_name`) VALUES
(1, 'Breakfast'), (2, 'Lunch'), (3, 'Dinner'), (4, 'Full Board')
ON DUPLICATE KEY UPDATE `type_name`=`type_name`;

-- Footer Content
INSERT IGNORE INTO `footer_content` (`footer_id`, `company_name`, `about_text`, `address`, `email`, `phone`, `copyright_text`) VALUES
(1, 'Rentallanka', 'Find properties and rooms for rent across Sri Lanka.', 'Colombo, Sri Lanka', 'info@rentallanka.lk', '071 234 5678', 'Copyright 2025')
ON DUPLICATE KEY UPDATE `company_name`=`company_name`;


-- 4. Sample Entity Data

-- Admin Bank Account
INSERT INTO `admin_bank_account` (`account_id`, `bank_id`, `branch`, `account_number`, `account_holder_name`) VALUES
(1, 3, 'Colombo Main', '1000123456', 'Rental Lanka PVT LTD')
ON DUPLICATE KEY UPDATE `account_number`=`account_number`;

