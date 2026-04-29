<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SESSION['role'] !== 'professor') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$professorId = (int) $_SESSION['user_id'];

switch ($action) {
    case 'courses':
        // GET: Get courses assigned to professor for a semester
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
        }
        
        $semesterId = (int) ($_GET['semester_id'] ?? 0);
        
        if (!$semesterId) {
            http_response_code(400);
            echo json_encode(['error' => 'Semester ID required']);
            break;
        }
        
        $stmt = $pdo->prepare(
            'SELECT c.id, c.name, c.credits 
             FROM courses c
             JOIN assignments a ON a.course_id = c.id
             WHERE a.professor_id = :professor_id AND a.semester_id = :semester_id
             ORDER BY c.name'
        );
        $stmt->execute([
            ':professor_id' => $professorId,
            ':semester_id' => $semesterId,
        ]);
        $courses = $stmt->fetchAll();
        
        echo json_encode($courses);
        break;
        
    case 'students':
        // GET: Get enrolled students with existing grades for a course
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
        }
        
        $semesterId = (int) ($_GET['semester_id'] ?? 0);
        $courseId = (int) ($_GET['course_id'] ?? 0);
        
        if (!$semesterId || !$courseId) {
            http_response_code(400);
            echo json_encode(['error' => 'Semester ID and Course ID required']);
            break;
        }
        
        // Verify professor is assigned
        $stmt = $pdo->prepare(
            'SELECT 1 FROM assignments WHERE professor_id = :professor_id AND course_id = :course_id AND semester_id = :semester_id'
        );
        $stmt->execute([
            ':professor_id' => $professorId,
            ':course_id' => $courseId,
            ':semester_id' => $semesterId,
        ]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Not assigned to this course']);
            break;
        }
        
        // Get enrolled students
        $stmt = $pdo->prepare(
            'SELECT u.id, u.name 
             FROM users u
             JOIN enrollments e ON e.student_id = u.id
             WHERE e.semester_id = :semester_id AND u.role = "student"
             ORDER BY u.name'
        );
        $stmt->execute([':semester_id' => $semesterId]);
        $students = $stmt->fetchAll();
        
        // Add existing grades
        foreach ($students as &$student) {
            $stmt = $pdo->prepare(
                'SELECT grade FROM grades WHERE student_id = :student_id AND course_id = :course_id AND semester_id = :semester_id'
            );
            $stmt->execute([
                ':student_id' => $student['id'],
                ':course_id' => $courseId,
                ':semester_id' => $semesterId,
            ]);
            $result = $stmt->fetch();
            $student['grade'] = $result ? (string) $result['grade'] : null;
        }
        
        echo json_encode($students);
        break;
        
    case 'save':
        // POST: Save grades
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
        }
        
        $semesterId = (int) ($_POST['semester_id'] ?? 0);
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $grades = $_POST['grades'] ?? [];
        
        if (!$semesterId || !$courseId) {
            http_response_code(400);
            echo json_encode(['error' => 'Semester ID and Course ID required']);
            break;
        }
        
        // Verify professor is assigned
        $stmt = $pdo->prepare(
            'SELECT 1 FROM assignments WHERE professor_id = :professor_id AND course_id = :course_id AND semester_id = :semester_id'
        );
        $stmt->execute([
            ':professor_id' => $professorId,
            ':course_id' => $courseId,
            ':semester_id' => $semesterId,
        ]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Not assigned to this course']);
            break;
        }
        
        $saved = 0;
        $validGrades = ['0.0', '1.0', '2.0', '3.0', '4.0'];
        
        foreach ($grades as $entry) {
            $studentId = (int) ($entry['student_id'] ?? 0);
            $grade = trim($entry['grade'] ?? '');
            
            if (!$studentId || !in_array($grade, $validGrades, true)) {
                continue;
            }
            
            // Upsert grade
            $stmt = $pdo->prepare(
                'INSERT INTO grades (student_id, course_id, semester_id, professor_id, grade) 
                 VALUES (:student_id, :course_id, :semester_id, :professor_id, :grade)
                 ON DUPLICATE KEY UPDATE 
                     professor_id = VALUES(professor_id), 
                     grade = VALUES(grade),
                     entered_at = CURRENT_TIMESTAMP'
            );
            $stmt->execute([
                ':student_id' => $studentId,
                ':course_id' => $courseId,
                ':semester_id' => $semesterId,
                ':professor_id' => $professorId,
                ':grade' => $grade,
            ]);
            
            // Recompute GPA
            $stmt = $pdo->prepare(
                'SELECT g.grade, c.credits 
                 FROM grades g 
                 JOIN courses c ON g.course_id = c.id 
                 WHERE g.student_id = :student_id AND g.semester_id = :semester_id'
            );
            $stmt->execute([
                ':student_id' => $studentId,
                ':semester_id' => $semesterId,
            ]);
            
            $allGrades = $stmt->fetchAll();
            $totalPoints = 0;
            $totalCredits = 0;
            
            foreach ($allGrades as $row) {
                $totalPoints += (float) $row['grade'] * (int) $row['credits'];
                $totalCredits += (int) $row['credits'];
            }
            
            $gpa = $totalCredits > 0 ? round($totalPoints / $totalCredits, 2) : null;
            
            if ($gpa !== null) {
                $stmt = $pdo->prepare(
                    'INSERT INTO gpa_records (student_id, semester_id, gpa) 
                     VALUES (:student_id, :semester_id, :gpa)
                     ON DUPLICATE KEY UPDATE gpa = VALUES(gpa), computed_at = CURRENT_TIMESTAMP'
                );
                $stmt->execute([
                    ':student_id' => $studentId,
                    ':semester_id' => $semesterId,
                    ':gpa' => $gpa,
                ]);
            }
            
            $saved++;
        }
        
        echo json_encode(['success' => true, 'saved' => $saved]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}