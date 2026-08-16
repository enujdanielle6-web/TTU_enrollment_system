CREATE TABLE IF NOT EXISTS `lms_attendance_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_course_id` int(10) unsigned NOT NULL,
  `session_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `lms_course_id` (`lms_course_id`),
  CONSTRAINT `lms_attendance_sessions_ibfk_1` FOREIGN KEY (`lms_course_id`) REFERENCES `lms_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_attendance_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_attendance_session_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL DEFAULT 'present',
  `remarks` varchar(255) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attendance_student` (`lms_attendance_session_id`,`student_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `lms_attendance_records_ibfk_1` FOREIGN KEY (`lms_attendance_session_id`) REFERENCES `lms_attendance_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lms_attendance_records_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
