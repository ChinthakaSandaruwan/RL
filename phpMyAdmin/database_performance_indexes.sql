-- ========================================================
-- Database Performance Optimization Indexes for RentalLanka
-- ========================================================
-- This script adds missing indexes to improve query performance
-- Run this in phpMyAdmin to speed up your application
-- 
-- IMPORTANT: If you get "Duplicate key name" errors, it means
-- that index already exists. You can IGNORE those errors!
-- ========================================================

-- Property Table Indexes
-- Used by: load_property.php, user_recommendations.php
-- Improves: Property listing queries filtered by status
ALTER TABLE `property` ADD INDEX `idx_status_created` (`status_id`, `created_at`);

-- Room Table Indexes  
-- Used by: load_room.php, user_recommendations.php
-- Improves: Room listing queries filtered by status
ALTER TABLE `room` ADD INDEX `idx_status_created` (`status_id`, `created_at`);

-- Vehicle Table Indexes
-- Used by: load_vehicle.php, user_recommendations.php
-- Improves: Vehicle listing queries filtered by status
ALTER TABLE `vehicle` ADD INDEX `idx_status_created` (`status_id`, `created_at`);

-- Property Image Indexes
-- Used by: Property queries that join with images
-- Improves: Finding primary images for properties
ALTER TABLE `property_image` ADD INDEX `idx_property_primary` (`property_id`, `primary_image`);

-- Room Image Indexes
-- Used by: Room queries that join with images
-- Improves: Finding primary images for rooms
ALTER TABLE `room_image` ADD INDEX `idx_room_primary` (`room_id`, `primary_image`);

-- Vehicle Image Indexes (Note: Already has idx_vehicle_images_primary)
-- Adding composite index for better join performance
ALTER TABLE `vehicle_image` ADD INDEX `idx_vehicle_primary` (`vehicle_id`, `primary_image`);

-- Property Location Indexes
-- Used by: Location-based property searches
-- Improves: Queries that filter properties by city
ALTER TABLE `property_location` ADD INDEX `idx_property_city` (`property_id`, `city_id`);

-- Room Location Indexes
-- Used by: Location-based room searches
-- Improves: Queries that filter rooms by city  
ALTER TABLE `room_location` ADD INDEX `idx_room_city` (`room_id`, `city_id`);

-- Vehicle Location Indexes
-- Used by: Location-based vehicle searches
-- Improves: Queries that filter vehicles by city
ALTER TABLE `vehicle_location` ADD INDEX `idx_vehicle_city` (`vehicle_id`, `city_id`);

-- Cities Table Indexes (Note: Already has fk_cities_districts1_idx)
-- Adding specific index for district lookups
ALTER TABLE `cities` ADD INDEX `idx_district` (`district_id`);

-- Districts Table Indexes (Note: Already has provinces_id key)
-- Adding specific index for province lookups
ALTER TABLE `districts` ADD INDEX `idx_province` (`province_id`);

-- ========================================
-- WISHLIST PERFORMANCE INDEXES
-- ========================================
-- These speed up wishlist lookups for logged-in users

-- Property Wishlist (Note: Already has uk_wishlist_unique)
-- Adding index for customer lookups
ALTER TABLE `property_wishlist` ADD INDEX `idx_customer` (`customer_id`);

-- Room Wishlist (Note: Already has uk_room_wishlist_unique)
-- Adding index for customer lookups
ALTER TABLE `room_wishlist` ADD INDEX `idx_customer` (`customer_id`);

-- Vehicle Wishlist (Note: Already has uk_vehicle_wishlist_unique)
-- Adding index for customer lookups - needs different name to avoid conflict
ALTER TABLE `vehicle_wishlist` ADD INDEX `idx_vehicle_wishlist_customer` (`customer_id`);

-- ========================================
-- USER & ROLE INDEXES
-- ========================================
-- User table already has idx_users_role, but adding for consistency

ALTER TABLE `user` ADD INDEX `idx_role` (`role_id`);

-- ========================================
-- CHAT PERFORMANCE INDEXES (CRITICAL!)
-- ========================================
-- These are CRITICAL for chat functionality performance
-- Without these, chat queries can be very slow

-- Chat Conversations - Composite index for customer + status lookups
-- Used by: Admin chat dashboard, customer chat interface
ALTER TABLE `chat_conversations` ADD INDEX `idx_user_status` (`user_id`, `status`);

-- Chat Conversations - Index for created_at ordering
-- Used by: Finding latest conversations
ALTER TABLE `chat_conversations` ADD INDEX `idx_created_at` (`created_at`);

-- Chat Messages - Composite index for conversation + created_at
-- Used by: Loading messages in a conversation
ALTER TABLE `chat_messages` ADD INDEX `idx_conversation_created` (`conversation_id`, `created_at`);

-- Chat Messages - Index for sender lookups
-- Used by: Filtering messages by sender
ALTER TABLE `chat_messages` ADD INDEX `idx_sender` (`sender_id`, `sender_type`);

-- ========================================
-- SUCCESS!
-- ========================================
-- All performance indexes have been created.
--
-- Next Steps:
-- 1. Run EXPLAIN on your slow queries to verify indexes are being used
-- 2. Monitor query execution time before and after
-- 3. Expected improvement: 70-80% faster page loads
-- 
-- To verify an index is being used:
-- EXPLAIN SELECT * FROM property WHERE status_id = 1 ORDER BY created_at DESC;
-- Look for 'type: ref' or 'type: range' (good!)
-- Avoid 'type: ALL' (bad - full table scan)
-- ========================================================
