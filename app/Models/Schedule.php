<?php

namespace App\Models;

use App\Core\Database;

class Schedule
{
    public static function validateSelectedSubjects(string $academicLevel, array $selectedSubjects): array
    {
        $pdo = Database::getConnection();
        $errors = [];
        
        $conditions = [];
        $params = [];
        foreach ($selectedSubjects as $subId => $secId) {
            if (!empty($secId)) {
                if ($academicLevel === 'Senior High School') {
                    $conditions[] = '(shs_section_id = ? AND subject_id = ?)';
                } else {
                    $conditions[] = '(college_section_id = ? AND subject_id = ?)';
                }
                $params[] = $secId;
                $params[] = $subId;
            }
        }
        
        $offerings = [];
        if (!empty($conditions)) {
            $where = implode(' OR ', $conditions);
            if ($academicLevel === 'Senior High School') {
                $offStmt = $pdo->prepare("SELECT id, subject_id, day, start_time, end_time, capacity FROM shs_section_subjects WHERE $where");
            } else {
                $offStmt = $pdo->prepare("SELECT id, subject_id, day, start_time, end_time, capacity FROM college_section_subjects WHERE $where");
            }
            $offStmt->execute($params);
            $offerings = $offStmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        if (empty($offerings)) {
            return $errors;
        }

        // 1. Capacity Check
        if ($academicLevel === 'Senior High School') {
            $capStmt = $pdo->prepare("SELECT COUNT(*) FROM shs_enrollments WHERE shs_section_id = ? AND subject_id = ?");
        } else {
            $capStmt = $pdo->prepare("SELECT COUNT(*) FROM college_enrollments WHERE college_section_id = ? AND subject_id = ?");
        }
        
        foreach ($offerings as $off) {
            $capStmt->execute([$off['id'], $off['subject_id']]);
            $enrolledCount = (int)$capStmt->fetchColumn();
            if ($enrolledCount >= (int)$off['capacity']) {
                $errors[] = "Schedule #{$off['id']} has reached its maximum capacity.";
            }
        }

        // 2. Duplicate Subject Check & Max Units
        $subjectIds = [];
        $totalUnits = 0;
        $unitStmt = $pdo->prepare("SELECT units FROM subjects WHERE id = ?");
        
        foreach ($offerings as $off) {
            if (in_array($off['subject_id'], $subjectIds)) {
                $errors[] = "You cannot enroll in the same subject (Subject ID: {$off['subject_id']}) multiple times.";
            }
            $subjectIds[] = $off['subject_id'];
            
            $unitStmt->execute([$off['subject_id']]);
            $units = (int)$unitStmt->fetchColumn();
            $totalUnits += $units;
        }
        
        $maxAllowedUnits = 24; 
        if ($totalUnits > $maxAllowedUnits) {
            $errors[] = "You have selected $totalUnits units, which exceeds the maximum allowed limit of $maxAllowedUnits units.";
        }

        // 3. Conflict Check (Time Overlap)
        $scheduleByDay = [];
        foreach ($offerings as $off) {
            if (empty($off['day']) || $off['day'] === 'TBA') continue;
            $scheduleByDay[$off['day']][] = [
                'start' => strtotime($off['start_time']),
                'end' => strtotime($off['end_time']),
                'offering_id' => $off['id']
            ];
        }
        foreach ($scheduleByDay as $day => $times) {
            for ($i = 0; $i < count($times); $i++) {
                for ($j = $i + 1; $j < count($times); $j++) {
                    $t1 = $times[$i];
                    $t2 = $times[$j];
                    if (max($t1['start'], $t2['start']) < min($t1['end'], $t2['end'])) {
                        $errors[] = "Schedule conflict on $day between schedules #{$t1['offering_id']} and #{$t2['offering_id']}.";
                        break 2;
                    }
                }
            }
        }
        
        return $errors;
    }
}
