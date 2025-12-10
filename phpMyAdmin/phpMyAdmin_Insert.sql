-- Sri Lanka Rental System - Data Seeding
-- Extracted from phpMyAdmin.sql

SET NAMES utf8mb4;
SET time_zone = "+00:00";
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';


-- 1. Location Data (Provinces & Districts)

INSERT IGNORE INTO `provinces` (`id`, `name_en`, `name_si`, `name_ta`) VALUES
(1, 'Western', 'බස්නාහිර', 'மேல்'),
(2, 'Central', 'මධ්‍යම', 'மத்திய'),
(3, 'Southern', 'දකුණු', 'தென்'),
(4, 'North Western', 'වයඹ', 'வட மேல்'),
(5, 'Sabaragamuwa', 'සබරගමුව', 'சபரகமுவ'),
(6, 'Eastern', 'නැගෙනහිර', 'கிழக்கு'),
(7, 'Uva', 'ඌව', 'ஊவா'),
(8, 'North Central', 'උතුරු මැද', 'வட மத்திய'),
(9, 'Northern', 'උතුරු', 'வட');

INSERT IGNORE INTO `districts` (`id`, `province_id`, `name_en`, `name_si`, `name_ta`) VALUES
(1, 6, 'Ampara', 'අම්පාර', 'அம்பாறை'),
(2, 8, 'Anuradhapura', 'අනුරාධපුරය', 'அனுராதபுரம்'),
(3, 7, 'Badulla', 'බදුල්ල', 'பதுளை'),
(4, 6, 'Batticaloa', 'මඩකලපුව', 'மட்டக்களப்பு'),
(5, 1, 'Colombo', 'කොළඹ', 'கொழும்பு'),
(6, 3, 'Galle', 'ගාල්ල', 'காலி'),
(7, 1, 'Gampaha', 'ගම්පහ', 'கம்பஹா'),
(8, 3, 'Hambantota', 'හම්බන්තොට', 'அம்பாந்தோட்டை'),
(9, 9, 'Jaffna', 'යාපනය', 'யாழ்ப்பாணம்'),
(10, 1, 'Kalutara', 'කළුතර', 'களுத்துறை'),
(11, 2, 'Kandy', 'මහනුවර', 'கண்டி'),
(12, 5, 'Kegalle', 'කෑගල්ල', 'கேகாலை'),
(13, 9, 'Kilinochchi', 'කිලිනොච්චිය', 'கிளிநொச்சி'),
(14, 4, 'Kurunegala', 'කුරුණෑගල', 'குருணாகல்'),
(15, 9, 'Mannar', 'මන්නාරම', 'மன்னார்'),
(16, 2, 'Matale', 'මාතලේ',  'மாத்தளை'),
(17, 3, 'Matara', 'මාතර', 'மாத்தறை'),
(18, 7, 'Monaragala', 'මොණරාගල', 'மொணராகலை'),
(19, 9, 'Mullaitivu', 'මුලතිව්',  'முல்லைத்தீவு'),
(20, 2, 'Nuwara Eliya', 'නුවර එළිය', 'நுவரேலியா'),
(21, 8, 'Polonnaruwa', 'පොළොන්නරුව', 'பொலன்னறுவை'),
(22, 4, 'Puttalam', 'පුත්තලම', 'புத்தளம்'),
(23, 5, 'Ratnapura', 'රත්නපුර', 'இரத்தினபுரி'),
(24, 6, 'Trincomalee', 'ත්‍රිකුණාමලය', 'திருகோணமலை'),
(25, 9, 'Vavuniya', 'වව්නියාව', 'வவுனியா');

-- 2. User & System Lookup Data

-- User Roles
INSERT INTO `user_role` (`role_id`, `role_name`) VALUES 
(1, 'super_admin'), (2, 'admin'), (3, 'owner'), (4, 'customer')
ON DUPLICATE KEY UPDATE `role_name`=`role_name`;

-- User Statuses
INSERT INTO `user_status` (`status_id`, `status_name`) VALUES
(1, 'active'), (2, 'inactive'), (3, 'banned')
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

-- Users
INSERT INTO `user` (`user_id`, `email`, `name`, `mobile_number`, `role_id`, `status_id`, `created_at`) VALUES
(1, 'super_admin@rentallanka.com', 'superadmin', '0710476945', 1, 1, '2025-11-04 12:00:00'),
(2, 'admin@rentallanka.com', 'admin', '0713018095', 2, 1, '2025-11-04 12:00:00'),
(3, 'owner@rentallanka.com', 'owner1', '0718186333', 3, 1, '2025-11-04 12:00:00'),
(4, 'customer@rentallanka.com', 'customer1', '0711111111', 4, 1, '2025-11-04 12:00:00');

-- Sample Bought Package for Testing (Owner has purchased a package with quotas)
-- This allows owner1 to test the system with available package quotas
INSERT INTO `bought_package` (`bought_package_id`, `user_id`, `package_id`, `bought_date`, `expires_date`, `remaining_properties`, `remaining_rooms`, `remaining_vehicles`, `status_id`, `payment_status_id`) VALUES
(1, 3, 1, '2025-12-01 10:00:00', '2026-01-01 10:00:00', 10, 10, 5, 1, 2)
ON DUPLICATE KEY UPDATE 
  `remaining_properties`=VALUES(`remaining_properties`),
  `remaining_rooms`=VALUES(`remaining_rooms`),
  `remaining_vehicles`=VALUES(`remaining_vehicles`);


-- Vehicle Brands
INSERT IGNORE INTO `vehicle_brand` (`brand_id`, `brand_name`) VALUES 
(1, 'Toyota'), (2, 'Honda'), (3, 'Nissan'), (4, 'Suzuki'), (5, 'Mitsubishi'), 
(6, 'Kia'), (7, 'Hyundai'), (8, 'BMW'), (9, 'Mercedes-Benz'), (10, 'Audi')
ON DUPLICATE KEY UPDATE `brand_name`=`brand_name`;

-- Vehicle Colors
INSERT IGNORE INTO `vehicle_color` (`color_id`, `color_name`, `hex_code`) VALUES 
(1, 'White', '#FFFFFF'), (2, 'Black', '#000000'), (3, 'Silver', '#C0C0C0'), (4, 'Gray', '#808080'), 
(5, 'Red', '#FF0000'), (6, 'Blue', '#0000FF'), (7, 'Brown', '#A52A2A'), (8, 'Green', '#008000')
ON DUPLICATE KEY UPDATE `color_name`=`color_name`;

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

-- Properties
INSERT INTO `property` (`property_id`, `property_code`, `owner_id`, `title`, `description`, `price_per_month`, `bedrooms`, `bathrooms`, `living_rooms`, `property_type_id`, `status_id`, `sqft`) VALUES
(1, 'PROP001', 3, 'Luxury Villa in Colombo', 'Beautiful 3 bedroom villa with pool and garden in the heart of Colombo.', 150000.00, 3, 2, 1, 9, 1, 2500.00),
(2, 'PROP002', 3, 'Cozy Apartment in Kandy', 'Modern 2 bedroom apartment with amazing view of the lake.', 85000.00, 2, 1, 1, 7, 1, 1200.00),
(3, 'PROP003', 3, 'Beach Bungalow in Galle', 'Relaxing beachfront bungalow perfect for vacations.', 200000.00, 4, 3, 2, 13, 1, 3000.00)
ON DUPLICATE KEY UPDATE `title`=`title`;

-- Property Location
INSERT INTO `property_location` (`property_id`, `province_id`, `district_id`, `city_id`, `address`, `postal_code`) VALUES
(1, NULL, NULL, NULL, '123 Lotus Road', '00700'), -- Colombo 7 (Approximation)
(2, NULL, NULL, NULL, '45/B Lake View', '20000'), -- Kandy
(3, NULL, NULL, NULL, '10 Beach Road', '80000')  -- Galle
ON DUPLICATE KEY UPDATE `address`=`address`;

-- Property Amenities
INSERT INTO `property_amenity` (`property_id`, `amenity_id`) VALUES
(1, 1), (1, 2), (1, 5), (1, 6), (1, 7), -- Villa: AC, WiFi, Pool, Gym, Parking
(2, 1), (2, 2), (2, 7),             -- Apt: AC, WiFi, Parking
(3, 1), (3, 2), (3, 4), (3, 5)      -- Bungalow: AC, WiFi, Kitchen, Pool
ON DUPLICATE KEY UPDATE `property_id`=`property_id`;

-- Property Images
INSERT INTO `property_image` (`property_id`, `primary_image`, `image_path`) VALUES
(1, 1, 'public/assets/images/placeholder-property.jpg'),
(2, 1, 'public/assets/images/placeholder-property.jpg'),
(3, 1, 'public/assets/images/placeholder-property.jpg')
ON DUPLICATE KEY UPDATE `image_path`=`image_path`;

-- Rooms
INSERT INTO `room` (`room_id`, `room_code`, `owner_id`, `title`, `description`, `room_type_id`, `beds`, `bathrooms`, `maximum_guests`, `price_per_day`, `status_id`, `update_disable`) VALUES
(1, 'ROOM001', 3, 'Ocean View Double Room', 'Spacious double room with sea view.', 6, 1, 1, 2, 5000.00, 1, 0),
(2, 'ROOM002', 3, 'Budget Single Room', 'Clean and affordable single room for backpackers.', 5, 1, 1, 1, 2500.00, 1, 0)
ON DUPLICATE KEY UPDATE `title`=`title`;

-- Room Location
INSERT INTO `room_location` (`room_id`, `province_id`, `district_id`, `city_id`, `address`, `postal_code`) VALUES
(1, NULL, NULL, NULL, 'No 5, Unawatuna', '80600'),
(2, NULL, NULL, NULL, 'No 12, Ella Town', '90090')
ON DUPLICATE KEY UPDATE `address`=`address`;

-- Room Amenities
INSERT INTO `room_amenity` (`room_id`, `amenity_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 8), -- Double Room: AC, WiFi, TV, Hot Water
(2, 2), (2, 8)                  -- Single Room: WiFi, Hot Water
ON DUPLICATE KEY UPDATE `room_id`=`room_id`;

-- Room Meals
INSERT INTO `room_meal` (`room_id`, `meal_type_id`, `price`) VALUES
(1, 1, 500.00), (1, 2, 800.00), -- Room 1: Breakfast, Lunch
(2, 1, 300.00)                -- Room 2: Breakfast
ON DUPLICATE KEY UPDATE `price`=`price`;

-- Vehicles
INSERT INTO `vehicle` (`vehicle_id`, `vehicle_code`, `owner_id`, `title`, `description`, `model_id`, `vehicle_type_id`, `fuel_type_id`, `transmission_type_id`, `pricing_type_id`, `price_per_day`, `price_per_km`, `status_id`, `color_id`, `license_plate`, `year`, `number_of_seats`) VALUES
(1, 'VEH001', 3, 'Toyota Prius for Rent', 'Well maintained hybrid car for city run.', 2, 1, 4, 2, 1, 8000.00, 0.00, 1, 1, 'CAB-1234', 2020, 5),
(2, 'VEH002', 3, 'Suzuki Alto Budget Car', 'Low fuel consumption, best for long trips.', 10, 1, 1, 1, 2, 0.00, 60.00, 1, 3, 'BCC-5678', 2019, 4)
ON DUPLICATE KEY UPDATE `title`=`title`;

-- Vehicle Location
INSERT INTO `vehicle_location` (`vehicle_id`, `city_id`, `address`, `postal_code`) VALUES
(1, NULL, 'Nugegoda junction', '10250'),
(2, NULL, 'Gampaha Town', '11000')
ON DUPLICATE KEY UPDATE `address`=`address`;

