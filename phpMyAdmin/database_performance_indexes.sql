-- Database Performance Optimization Indexes
-- Run these queries in phpMyAdmin to improve database query performance
-- These indexes will speed up the property, room, and vehicle load queries

-- 1. Property table indexes
ALTER TABLE `property` 
ADD INDEX `idx_status_created` (`status_id`, `created_at` DESC);

-- 2. Room table indexes
ALTER TABLE `room` 
ADD INDEX `idx_status_created` (`status_id`, `created_at` DESC);

-- 3. Vehicle table indexes
ALTER TABLE `vehicle` 
ADD INDEX `idx_status_created` (`status_id`, `created_at` DESC);

-- 4. Property location indexes
ALTER TABLE `property_location` 
ADD INDEX `idx_property_city` (`property_id`, `city_id`);

-- 5. Room location indexes
ALTER TABLE `room_location` 
ADD INDEX `idx_room_city` (`room_id`, `city_id`);

-- 6. Vehicle location indexes
ALTER TABLE `vehicle_location` 
ADD INDEX `idx_vehicle_city` (`vehicle_id`, `city_id`);

-- 7. Cities table indexes
ALTER TABLE `cities` 
ADD INDEX `idx_district` (`district_id`);

-- 8. Districts table indexes
ALTER TABLE `districts` 
ADD INDEX `idx_province` (`province_id`);

-- 9. Property image indexes
ALTER TABLE `property_image` 
ADD INDEX `idx_property_primary` (`property_id`, `primary_image`);

-- 10. Room image indexes
ALTER TABLE `room_image` 
ADD INDEX `idx_room_primary` (`room_id`, `primary_image`);

-- 11. Vehicle image indexes
ALTER TABLE `vehicle_image` 
ADD INDEX `idx_vehicle_primary` (`vehicle_id`, `primary_image`);

-- 12. Wishlist indexes for faster lookups
ALTER TABLE `property_wishlist` 
ADD INDEX `idx_customer` (`customer_id`);

ALTER TABLE `room_wishlist` 
ADD INDEX `idx_customer` (`customer_id`);

ALTER TABLE `vehicle_wishlist` 
ADD INDEX `idx_customer` (`customer_id`);

-- 13. User table index
ALTER TABLE `user` 
ADD INDEX `idx_role` (`role_id`);

-- Note: These are recommended indexes based on the current query patterns.
-- Run EXPLAIN on your queries after adding these indexes to verify performance improvements.
-- Monitor query execution time before and after applying these indexes.
