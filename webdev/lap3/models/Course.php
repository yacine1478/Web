<?php

class Course {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAll(): array {
        $stmt = $this->pdo->query(
            'SELECT c.*, s.label as semester_label, s.academic_year 
             FROM courses c
             JOIN semesters s ON c.semester_id = s.id
             ORDER BY s.id DESC, c.name'
        );
        return $stmt->fetchAll();
    }
    
    public function getBySemester(int $semesterId): array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM courses WHERE semester_id = :semester_id ORDER BY name'
        );
        $stmt->execute([':semester_id' => $semesterId]);
        return $stmt->fetchAll();
    }
    
    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM courses WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $course = $stmt->fetch();
        return $course ?: null;
    }
    
    public function create(string $name, int $credits, int $semesterId): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO courses (name, credits, semester_id) VALUES (:name, :credits, :semester_id)'
        );
        $stmt->execute([
            ':name' => $name,
            ':credits' => $credits,
            ':semester_id' => $semesterId
        ]);
        return (int) $this->pdo->lastInsertId();
    }
    
    public function update(int $id, string $name, int $credits, int $semesterId): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE courses SET name = :name, credits = :credits, semester_id = :semester_id WHERE id = :id'
        );
        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':credits' => $credits,
            ':semester_id' => $semesterId
        ]);
    }
    
    public function delete(int $id): bool {
        // Check if course has grades
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM grades WHERE course_id = :id');
        $stmt->execute([':id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            return false;
        }
        $stmt = $this->pdo->prepare('DELETE FROM courses WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
    
    public function countBySemester(int $semesterId): int {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM courses WHERE semester_id = :semester_id');
        $stmt->execute([':semester_id' => $semesterId]);
        return (int) $stmt->fetchColumn();
    }
}