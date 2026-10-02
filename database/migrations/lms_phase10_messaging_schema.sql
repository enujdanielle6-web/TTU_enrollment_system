-- LMS Phase 10 Migration: Direct Messaging & Class Forum Threads
-- Implements multi-turn direct messaging and course discussions between faculty and students

CREATE TABLE IF NOT EXISTS `lms_threads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_course_id` int(10) unsigned DEFAULT NULL,
  `subject` varchar(255) NOT NULL DEFAULT 'Academic Discussion',
  `created_by` int(10) unsigned NOT NULL,
  `last_message_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lms_threads_course` (`lms_course_id`),
  KEY `idx_lms_threads_created_by` (`created_by`),
  KEY `idx_lms_threads_last_message` (`last_message_at`),
  CONSTRAINT `lms_threads_course_fk` FOREIGN KEY (`lms_course_id`) REFERENCES `lms_courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lms_threads_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_thread_participants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `last_read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_thread_user_unique` (`thread_id`, `user_id`),
  KEY `idx_participants_user` (`user_id`),
  CONSTRAINT `lms_participants_thread_fk` FOREIGN KEY (`thread_id`) REFERENCES `lms_threads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lms_participants_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` int(10) unsigned NOT NULL,
  `sender_id` int(10) unsigned NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_messages_thread` (`thread_id`),
  KEY `idx_messages_sender` (`sender_id`),
  KEY `idx_messages_created` (`created_at`),
  CONSTRAINT `lms_messages_thread_fk` FOREIGN KEY (`thread_id`) REFERENCES `lms_threads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lms_messages_sender_fk` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
