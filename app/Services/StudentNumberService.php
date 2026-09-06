<?php

namespace App\Services;

use PDO;
use PDOException;

/**
 * Service responsible for generating unique, atomic, and concurrency-safe
 * student identification numbers in the format YYYY-XXXXXX.
 */
class StudentNumberService
{
    /**
     * Generates a new student number atomically for the specified year.
     *
     * @param int $year Academic or calendar year (e.g. 2026)
     * @param PDO $pdo Active database connection
     * @return string Formatted student number (e.g. 2026-000003)
     */
    public static function generate(int $year, PDO $pdo): string
    {
        try {
            // 1. Ensure initial seed if year is missing
            $checkStmt = $pdo->prepare("SELECT current_value FROM student_number_sequences WHERE sequence_year = :year LIMIT 1");
            $checkStmt->execute(['year' => $year]);
            $existingVal = $checkStmt->fetchColumn();

            if ($existingVal === false) {
                // Find highest existing sequence in users table for this year
                $prefix = $year . '-%';
                $lastStmt = $pdo->prepare("SELECT student_number FROM users WHERE student_number LIKE :prefix ORDER BY student_number DESC LIMIT 1");
                $lastStmt->execute(['prefix' => $prefix]);
                $lastSN = $lastStmt->fetchColumn();

                $initSeq = 0;
                if ($lastSN && preg_match('/^(\d{4})-(\d+)$/', $lastSN, $matches)) {
                    $initSeq = (int)$matches[2];
                }

                $seedStmt = $pdo->prepare("
                    INSERT INTO student_number_sequences (sequence_year, current_value) 
                    VALUES (:year, :val) 
                    ON DUPLICATE KEY UPDATE current_value = GREATEST(current_value, :val_update)
                ");
                $seedStmt->execute([
                    'year' => $year,
                    'val' => $initSeq,
                    'val_update' => $initSeq
                ]);
            }

            // 3. Atomically increment sequence for this year
            $incStmt = $pdo->prepare("
                INSERT INTO student_number_sequences (sequence_year, current_value) 
                VALUES (:year, 1) 
                ON DUPLICATE KEY UPDATE current_value = current_value + 1
            ");
            $incStmt->execute(['year' => $year]);

            // 4. Retrieve atomically incremented sequence
            $fetchStmt = $pdo->prepare("SELECT current_value FROM student_number_sequences WHERE sequence_year = :year LIMIT 1");
            $fetchStmt->execute(['year' => $year]);
            $seq = (int)$fetchStmt->fetchColumn();

            return sprintf("%04d-%06d", $year, $seq);

        } catch (PDOException $e) {
            error_log('StudentNumberService::generate failed: ' . $e->getMessage());

            // Resilient fallback to max(users.student_number)
            $prefix = $year . '-%';
            $fallbackStmt = $pdo->prepare("SELECT student_number FROM users WHERE student_number LIKE :prefix ORDER BY student_number DESC LIMIT 1");
            $fallbackStmt->execute(['prefix' => $prefix]);
            $lastSN = $fallbackStmt->fetchColumn();

            $nextSeq = 1;
            if ($lastSN && preg_match('/^(\d{4})-(\d+)$/', $lastSN, $matches)) {
                $nextSeq = (int)$matches[2] + 1;
            }

            return sprintf("%04d-%06d", $year, $nextSeq);
        }
    }

    /**
     * Instance wrapper for dependency injection or object usage.
     */
    public function generateNumber(int $year, PDO $pdo): string
    {
        return self::generate($year, $pdo);
    }

    /**
     * Retrieves the current highest sequence allocated for a year without incrementing.
     */
    public static function getCurrentSequence(int $year, PDO $pdo): int
    {
        try {
            $stmt = $pdo->prepare("SELECT current_value FROM student_number_sequences WHERE sequence_year = :year LIMIT 1");
            $stmt->execute(['year' => $year]);
            $val = $stmt->fetchColumn();
            return $val !== false ? (int)$val : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
}
