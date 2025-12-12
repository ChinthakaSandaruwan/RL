-- ========================================================
-- VERIFY CHAT PERFORMANCE INDEXES
-- ========================================================
-- Run this script in phpMyAdmin to check if chat indexes exist
-- If any are missing, they will be created automatically
-- ========================================================

-- Check and create chat_conversations indexes
SELECT 
    COUNT(*) as index_exists,
    'idx_user_status on chat_conversations' as index_name
FROM information_schema.STATISTICS 
WHERE table_schema = DATABASE()
  AND table_name = 'chat_conversations'
  AND index_name = 'idx_user_status';

ALTER TABLE `chat_conversations` ADD INDEX IF NOT EXISTS `idx_user_status` (`user_id`, `status`);

SELECT 
    COUNT(*) as index_exists,
    'idx_created_at on chat_conversations' as index_name
FROM information_schema.STATISTICS 
WHERE table_schema = DATABASE()
  AND table_name = 'chat_conversations'
  AND index_name = 'idx_created_at';

ALTER TABLE `chat_conversations` ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`);

-- Check and create chat_messages indexes
SELECT 
    COUNT(*) as index_exists,
    'idx_conversation_created on chat_messages' as index_name
FROM information_schema.STATISTICS 
WHERE table_schema = DATABASE()
  AND table_name = 'chat_messages'
  AND index_name = 'idx_conversation_created';

ALTER TABLE `chat_messages` ADD INDEX IF NOT EXISTS `idx_conversation_created` (`conversation_id`, `created_at`);

SELECT 
    COUNT(*) as index_exists,
    'idx_sender on chat_messages' as index_name
FROM information_schema.STATISTICS 
WHERE table_schema = DATABASE()
  AND table_name = 'chat_messages'
  AND index_name = 'idx_sender';

ALTER TABLE `chat_messages` ADD INDEX IF NOT EXISTS `idx_sender` (`sender_id`, `sender_type`);

-- ========================================================
-- FINAL VERIFICATION
-- ========================================================
SELECT 'Chat Conversations Indexes:' as info;
SHOW INDEX FROM chat_conversations;

SELECT 'Chat Messages Indexes:' as info;
SHOW INDEX FROM chat_messages;

-- ========================================================
-- SUCCESS!
-- ========================================================
-- All critical chat indexes should now be in place
-- ========================================================
