<?php
require_once APP_PATH . '/models/Attendance.php';

class AttendanceController {
    private $model;
    
    public function __construct() {
        $this->model = new Attendance();
    }
    
    // Teacher: Mark attendance page
    public function mark() {
        Auth::requireRole('teacher');
        
        $teacher = Auth::user();
        $classId = $_GET['class_id'] ?? $teacher['class_id'];
        $date = $_GET['date'] ?? date('Y-m-d');
        
        if (!$classId) {
            setFlash('danger', 'No class assigned');
            redirect('?page=dashboard');
        }
        
        $db = db();
        $students = $db->prepare("SELECT * FROM students WHERE class_id = ? AND is_active = 1 ORDER BY full_name");
        $students->execute([$classId]);
        $students = $students->fetchAll();
        
        // Get existing attendance
        $existing = [];
        $stmt = $db->prepare("SELECT student_id, status FROM attendance WHERE class_id = ? AND date = ?");
        $stmt->execute([$classId, $date]);
        foreach ($stmt->fetchAll() as $row) {
            $existing[$row['student_id']] = $row['status'];
        }
        
        $class = $db->prepare("SELECT * FROM classes WHERE id = ?");
        $class->execute([$classId]);
        $class = $class->fetch();
        
        require APP_PATH . '/views/teacher/attendance.php';
    }
    
    // Save attendance
    public function save() {
        Auth::requireRole('teacher');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('?page=attendance');
        }
        
        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlash('danger', 'Invalid request');
            redirect('?page=attendance');
        }
        
        $classId = (int)$_POST['class_id'];
        $date = $_POST['date'];
        $term = getCurrentTerm();
        
        if (!$term) {
            setFlash('danger', 'No active academic term');
            redirect('?page=attendance');
        }
        
        $db = db();
        $teacherId = Auth::id();
        
        try {
            $db->beginTransaction();
            
            foreach ($_POST['attendance'] ?? [] as $studentId => $status) {
                if (!in_array($status, ['Present', 'Absent', 'Late'])) continue;
                
                $stmt = $db->prepare("INSERT INTO attendance 
                    (student_id, class_id, date, status, marked_by, term_id) 
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by)");
                $stmt->execute([$studentId, $classId, $date, $status, $teacherId, $term['id']]);
                
                // Send notification to parent if absent
                if ($status === 'Absent') {
                    $this->notifyParentAbsent($studentId, $date);
                }
            }
            
            $db->commit();
            Auth::logActivity(Auth::id(), 'teacher', 'mark_attendance', 
                "Marked attendance for class {$classId} on {$date}");
            
            setFlash('success', 'Attendance saved successfully');
        } catch (Exception $e) {
            $db->rollBack();
            setFlash('danger', 'Error saving attendance: ' . $e->getMessage());
        }
        
        redirect('?page=attendance&class_id=' . $classId . '&date=' . $date);
    }
    
    // Notify parent of absence
    private function notifyParentAbsent($studentId, $date) {
        $db = db();
        $stmt = $db->prepare("SELECT s.*, p.id as parent_id, p.full_name as parent_name, c.class_name
            FROM students s 
            JOIN parents p ON s.parent_id = p.id 
            JOIN classes c ON s.class_id = c.id 
            WHERE s.id = ?");
        $stmt->execute([$studentId]);
        $data = $stmt->fetch();
        
        if ($data) {
            $stmt = $db->prepare("INSERT INTO notifications 
                (user_id, user_type, title, message, type, reference_id) 
                VALUES (?, 'parent', ?, ?, 'attendance', ?)");
            $stmt->execute([
                $data['parent_id'],
                'Absence Alert - ' . $data['full_name'],
                "Your child {$data['full_name']} ({$data['class_name']}) was marked absent on " . formatDate($date),
                $studentId
            ]);
        }
    }
    
    // View attendance history
    public function history() {
        Auth::requireRole('teacher');
        
        $classId = $_GET['class_id'] ?? Auth::user()['class_id'];
        $month = $_GET['month'] ?? date('Y-m');
        
        $db = db();
        $stmt = $db->prepare("SELECT s.full_name, s.admission_no,
            COUNT(*) as total_days,
            SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent,
            SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late
            FROM students s
            LEFT JOIN attendance a ON s.id = a.student_id 
                AND a.date LIKE ?
            WHERE s.class_id = ? AND s.is_active = 1
            GROUP BY s.id
            ORDER BY s.full_name");
        $stmt->execute([$month . '%', $classId]);
        $records = $stmt->fetchAll();
        
        require APP_PATH . '/views/teacher/attendance_history.php';
    }
    
    // Parent: View child attendance
    public function parentView() {
        Auth::requireRole('parent');
        
        $db = db();
        $stmt = $db->prepare("SELECT s.*, c.class_name FROM students s 
            JOIN classes c ON s.class_id = c.id 
            WHERE s.parent_id = ?");
        $stmt->execute([Auth::id()]);
        $children = $stmt->fetchAll();
        
        $attendanceData = [];
        foreach ($children as $child) {
            $attendanceData[$child['id']] = [
                'child' => $child,
                'percentage' => calculateAttendancePercentage($child['id']),
                'records' => $db->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY date DESC LIMIT 30")
                    ->execute([$child['id']]) ? [] : []
            ];
            
            $stmt = $db->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY date DESC LIMIT 30");
            $stmt->execute([$child['id']]);
            $attendanceData[$child['id']]['records'] = $stmt->fetchAll();
        }
        
        require APP_PATH . '/views/parent/attendance.php';
    }
}