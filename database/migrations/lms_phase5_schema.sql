CREATE TABLE IF NOT EXISTS `lms_quizzes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_course_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `time_limit` int(11) DEFAULT NULL COMMENT 'In minutes. NULL means unlimited.',
  `max_attempts` int(11) DEFAULT 1,
  `passing_score` decimal(5,2) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_quiz_course` (`lms_course_id`),
  CONSTRAINT `fk_quiz_course` FOREIGN KEY (`lms_course_id`) REFERENCES `lms_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_questions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_quiz_id` int(10) unsigned NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false') NOT NULL DEFAULT 'multiple_choice',
  `points` decimal(5,2) NOT NULL DEFAULT 1.00,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_question_quiz` (`lms_quiz_id`),
  CONSTRAINT `fk_question_quiz` FOREIGN KEY (`lms_quiz_id`) REFERENCES `lms_quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_question_choices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_question_id` int(10) unsigned NOT NULL,
  `choice_text` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `display_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_choice_question` (`lms_question_id`),
  CONSTRAINT `fk_choice_question` FOREIGN KEY (`lms_question_id`) REFERENCES `lms_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_quiz_attempts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_quiz_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `attempt_number` int(11) NOT NULL DEFAULT 1,
  `started_at` datetime NOT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `status` enum('in_progress','submitted','graded') NOT NULL DEFAULT 'in_progress',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_quiz_student_attempt` (`lms_quiz_id`,`student_id`,`attempt_number`),
  KEY `fk_attempt_student` (`student_id`),
  CONSTRAINT `fk_attempt_quiz` FOREIGN KEY (`lms_quiz_id`) REFERENCES `lms_quizzes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attempt_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lms_quiz_answers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lms_quiz_attempt_id` int(10) unsigned NOT NULL,
  `lms_question_id` int(10) unsigned NOT NULL,
  `lms_question_choice_id` int(10) unsigned DEFAULT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `points_awarded` decimal(5,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attempt_question` (`lms_quiz_attempt_id`,`lms_question_id`),
  KEY `fk_answer_question` (`lms_question_id`),
  KEY `fk_answer_choice` (`lms_question_choice_id`),
  CONSTRAINT `fk_answer_attempt` FOREIGN KEY (`lms_quiz_attempt_id`) REFERENCES `lms_quiz_attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_answer_question` FOREIGN KEY (`lms_question_id`) REFERENCES `lms_questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_answer_choice` FOREIGN KEY (`lms_question_choice_id`) REFERENCES `lms_question_choices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
