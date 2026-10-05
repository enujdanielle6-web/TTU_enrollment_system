-- ============================================================================
-- LMS Phase 13: notification read state
-- Remembers which LMS notifications (announcements, assignments, quizzes and
-- student submissions) each user has opened, so the bell and tab badges only
-- count what is new.
--
-- Additive and safe to run more than once. Run it once on every existing
-- database (local XAMPP and InfinityFree) in phpMyAdmin > SQL.
-- New installs from 01_schema.sql / schema.sql already include this table.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `lms_notification_reads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `item_type` varchar(20) NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
  `read_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lms_notifread_user_item` (`user_id`,`item_type`,`item_id`),
  CONSTRAINT `fk_lms_notifread_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
