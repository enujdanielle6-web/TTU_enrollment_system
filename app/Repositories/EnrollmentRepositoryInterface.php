<?php
namespace App\Repositories;

interface EnrollmentRepositoryInterface
{
    /**
     * Fetch active LMS courses that the given user is enrolled in.
     * 
     * @param int $userId The student's user ID.
     * @return array Array of LMS courses with section, subject, and instructor details.
     */
    public function getActiveStudentCourses(int $userId): array;

    /**
     * Check if a student is authorized to access a specific LMS course.
     * 
     * @param int $userId The student's user ID.
     * @param int $lmsCourseId The requested LMS course ID.
     * @return bool True if authorized, false otherwise.
     */
    public function isStudentAuthorizedForCourse(int $userId, int $lmsCourseId): bool;
}
