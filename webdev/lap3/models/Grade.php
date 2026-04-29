

/*class Grade
{
    private const LETTER_GRADE_POINTS = [
        'A+' => 4.0,
        'A'  => 4.0,
        'A-' => 3.7,
        'B+' => 3.3,
        'B'  => 3.0,
        'B-' => 2.7,
        'C+' => 2.3,
        'C'  => 2.0,
        'C-' => 1.7,
        'D+' => 1.3,
        'D'  => 1.0,
        'D-' => 0.7,
        'F'  => 0.0,
    ];

    public static function upsert(int $studentId, int $courseId, int $semesterId, int $professorId, string $grade): void
    {
        global $pdo;

        $sql = "INSERT INTO grades (student_id, course_id, semester_id, professor_id, grade) VALUES (:student_id, :course_id, :semester_id, :professor_id, :grade)
            ON DUPLICATE KEY UPDATE professor_id = VALUES(professor_id), grade = VALUES(grade)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':student_id' => $studentId,
            ':course_id' => $courseId,
            ':semester_id' => $semesterId,
            ':professor_id' => $professorId,
            ':grade' => $grade,
        ]);
    }

    public static function get(int $studentId, int $courseId, int $semesterId): ?string
    {
        global $pdo;

        $stmt = $pdo->prepare('SELECT grade FROM grades WHERE student_id = :student_id AND course_id = :course_id AND semester_id = :semester_id');
        $stmt->execute([
            ':student_id' => $studentId,
            ':course_id' => $courseId,
            ':semester_id' => $semesterId,
        ]);

        $result = $stmt->fetch();
        return $result ? $result['grade'] : null;
    }

    public static function getAllStudents(): array
    {
        global $pdo;
        $stmt = $pdo->query('SELECT id, name FROM students ORDER BY id');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAllCourses(): array
    {
        global $pdo;
        $stmt = $pdo->query('SELECT id, name, credits FROM courses ORDER BY name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAllSemesters(): array
    {
        global $pdo;
        $stmt = $pdo->query('SELECT id, label FROM semesters ORDER BY id');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAllProfessors(): array
    {
        global $pdo;
        $stmt = $pdo->query('SELECT id, name FROM professors ORDER BY name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAllRecords(int $studentId, int $semesterId): array
    {
        global $pdo;

        $stmt = $pdo->prepare(
            'SELECT g.grade, c.credits, c.name AS course_name, p.name AS professor_name
             FROM grades g
             JOIN courses c ON g.course_id = c.id
             JOIN professors p ON g.professor_id = p.id
             WHERE g.student_id = :student_id AND g.semester_id = :semester_id
             ORDER BY c.name'
        );
        $stmt->execute([
            ':student_id' => $studentId,
            ':semester_id' => $semesterId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function isValidGrade(string $grade): bool
    {
        return self::gradePointValue($grade) !== null;
    }

    public static function gradePointValue(string $grade): ?float
    {
        $grade = trim(strtoupper($grade));

        if (isset(self::LETTER_GRADE_POINTS[$grade])) {
            return self::LETTER_GRADE_POINTS[$grade];
        }

        if (is_numeric($grade)) {
            $value = (float) $grade;
            if ($value < 0 || $value > 100) {
                return null;
            }

            if ($value >= 90) {
                return 4.0;
            }
            if ($value >= 80) {
                return 3.0;
            }
            if ($value >= 70) {
                return 2.0;
            }
            if ($value >= 60) {
                return 1.0;
            }

            return 0.0;
        }

        return null;
    }
}
*/
<?php

class Grade {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function upsert(int $studentId, int $courseId, int $semesterId, int $professorId, float $grade): void {
        $sql = "INSERT INTO grades (student_id, course_id, semester_id, professor_id, grade) 
                VALUES (:student_id, :course_id, :semester_id, :professor_id, :grade)
                ON DUPLICATE KEY UPDATE 
                    professor_id = VALUES(professor_id), 
                    grade = VALUES(grade),
                    entered_at = CURRENT_TIMESTAMP";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':student_id' => $studentId,
            ':course_id' => $courseId,
            ':semester_id' => $semesterId,
            ':professor_id' => $professorId,
            ':grade' => $grade,
        ]);
    }
    
    public function get(int $studentId, int $courseId, int $semesterId): ?float {
        $stmt = $this->pdo->prepare(
            'SELECT grade FROM grades WHERE student_id = :student_id AND course_id = :course_id AND semester_id = :semester_id'
        );
        $stmt->execute([
            ':student_id' => $studentId,
            ':course_id' => $courseId,
            ':semester_id' => $semesterId,
        ]);
        $result = $stmt->fetch();
        return $result ? (float) $result['grade'] : null;
    }
    
    public function getStudentGrades(int $studentId, int $semesterId): array {
        $stmt = $this->pdo->prepare(
            'SELECT g.grade, g.course_id, c.name as course_name, c.credits, p.name as professor_name
             FROM grades g
             JOIN courses c ON g.course_id = c.id
             JOIN users p ON g.professor_id = p.id
             WHERE g.student_id = :student_id AND g.semester_id = :semester_id
             ORDER BY c.name'
        );
        $stmt->execute([
            ':student_id' => $studentId,
            ':semester_id' => $semesterId,
        ]);
        return $stmt->fetchAll();
    }
    
    public function deleteByStudent(int $studentId): void {
        $stmt = $this->pdo->prepare('DELETE FROM grades WHERE student_id = :student_id');
        $stmt->execute([':student_id' => $studentId]);
    }
    
    public function deleteByCourse(int $courseId): void {
        $stmt = $this->pdo->prepare('DELETE FROM grades WHERE course_id = :course_id');
        $stmt->execute([':course_id' => $courseId]);
    }
    
    public function countByCourse(int $courseId): int {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM grades WHERE course_id = :course_id');
        $stmt->execute([':course_id' => $courseId]);
        return (int) $stmt->fetchColumn();
    }
    
    public function countByStudentSemester(int $studentId, int $semesterId): int {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM grades WHERE student_id = :student_id AND semester_id = :semester_id'
        );
        $stmt->execute([
            ':student_id' => $studentId,
            ':semester_id' => $semesterId,
        ]);
        return (int) $stmt->fetchColumn();
    }
}