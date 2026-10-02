-- LMS Phase 11 Migration: Mixed-Type Quiz Builder (CSV import, content generation, text answers)
--
-- Additive only. Existing quizzes, questions, choices, attempts and answers keep their
-- values; every new column has a default that matches the behaviour before this migration.
-- Safe to run more than once on MariaDB 10.0.2+ (XAMPP) because of IF NOT EXISTS.
--
-- Question types after this migration:
--   multiple_choice  choices in lms_question_choices, exactly one is_correct = 1
--   true_false       choices "True" / "False" in lms_question_choices
--   identification   student types a short answer; accepted answers are stored in
--                    lms_question_choices with is_correct = 1 and are never shown to students
--   fill_blank       same as identification, question_text contains exactly one "___" blank

ALTER TABLE `lms_questions`
  MODIFY `question_type` enum('multiple_choice','true_false','identification','fill_blank') NOT NULL DEFAULT 'multiple_choice',
  ADD COLUMN IF NOT EXISTS `case_sensitive` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Text types only: 1 = letter case must match' AFTER `points`,
  ADD COLUMN IF NOT EXISTS `requires_manual_review` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Text types only: 1 = every non-matching answer goes to faculty review' AFTER `case_sensitive`,
  ADD COLUMN IF NOT EXISTS `source_reference` varchar(500) DEFAULT NULL COMMENT 'Where an imported or generated question came from' AFTER `requires_manual_review`;

ALTER TABLE `lms_quiz_answers`
  ADD COLUMN IF NOT EXISTS `answer_text` text DEFAULT NULL COMMENT 'Typed response for identification / fill_blank' AFTER `lms_question_choice_id`,
  ADD COLUMN IF NOT EXISTS `needs_review` tinyint(1) NOT NULL DEFAULT 0 AFTER `points_awarded`,
  ADD COLUMN IF NOT EXISTS `reviewed_by` int(10) unsigned DEFAULT NULL AFTER `needs_review`,
  ADD COLUMN IF NOT EXISTS `reviewed_at` datetime DEFAULT NULL AFTER `reviewed_by`;

-- Attempts that contain answers awaiting review are stored with the existing
-- lms_quiz_attempts.status value 'submitted' (already in the enum). The gradebook
-- only counts 'graded' attempts, so provisional scores stay out of course totals.
