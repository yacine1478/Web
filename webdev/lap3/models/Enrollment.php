<?php

class Enrollment {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function enroll(int $studentId, int $semesterId): bool {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO enrollments (student_id, semester_id) VALUES (:student_id, :semester_id)'
        );
        return $stmt->execute([':student_id' => $studentId, ':semester_id' => $semesterId]);
    }
    
    public function unenroll(int $studentId, int $semesterId): bool {
        $stmt = $this->pdo->prepare('DELETE FROM enrollments WHERE student_id = :student_id AND semester_id = :semester_id');
        return $stmt->execute([':student_id' => $studentId, ':semester_id' => $semesterId]);
    }
    
    public function isEnrolled(int $studentId, int $semesterId): bool {
        $stmt = $this->pdo->prepare('SELECT 1 FROM enrollments WHERE student_id = :student_id AND semester_id = :semester_id');
        $stmt->execute([':student_id' => $studentId, ':semester_id' => $semesterId]);
        return (bool) $stmt->fetch();
    }
    
    public function getStudentsBySemester(int $semesterId): array {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.name, u.email 
             FROM users u
             JOIN enrollments e ON e.student_id = u.id
             WHERE e.semester_id = :semester_id AND u.role = "student"
             ORDER BY u.name'
        );
        $stmt->execute([':semester_id' => $semesterId]);
        return $stmt->fetchAll();
    }
    
    public function getSemestersByStudent(int $studentId): array {
        $stmt = $this->pdo->prepare(
            'SELECT s.* FROM semesters s
             JOIN enrollments e ON e.semester_id = s.id
             WHERE e.student_id = :student_id
             ORDER BY s.id DESC'
        );
        $stmt->execute([':student_id' => $studentId]);
        return $stmt->fetchAll();
    }
    
    public function getEnrollmentStatus(int $studentId): array {
        $allSemesters = $this->getAllSemesters();
        $enrolled = [];
        foreach ($allSemesters as $semester) {
            $enrolled[$semester['id']] = $this->isEnrolled($studentId, $semester['id']);
        }
        return $enrolled;
    }
    
    private function getAllSemesters(): array {
        $stmt = $this->pdo->query('SELECT id, label, academic_year FROM semesters ORDER BY id DESC');
        return $stmt->fetchAll();
    }
}