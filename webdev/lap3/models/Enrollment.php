<?php

class Enrollment {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getStudentsBySemester(int $semesterId): array {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.name 
             FROM users u
             JOIN enrollments e ON e.student_id = u.id
             WHERE e.semester_id = :semester_id AND u.role = "student"
             ORDER BY u.name'
        );
        $stmt->execute([':semester_id' => $semesterId]);
        return $stmt->fetchAll();
    }
}