<?php
class Attendance {
    private $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    // Get attendance for a student
    public function getByStudent($studentId, $termId = null) {
        if ($termId) {
            $stmt = $this->db->prepare("SELECT * FROM attendance WHERE student_id = ? AND term_id = ? ORDER BY date DESC");
            $stmt->execute([$studentId, $termId]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY date DESC");
            $stmt->execute([$studentId]);
        }
        return $stmt->fetchAll();
    }
    
    // Get attendance for a class on a date
    public function getByClassAndDate($classId, $date) {
        $stmt = $this->db->prepare("SELECT a.*, s.full_name, s.admission_no 
            FROM attendance a 
            JOIN students s ON a.student_id = s.id 
            WHERE a.class_id = ? AND a.date = ?
            ORDER BY s.full_name");
        $stmt->execute([$classId, $date]);
        return $stmt->fetchAll();
    }
    
    // Get statistics
    public function getStatistics($classId = null, $termId = null) {
        $where = "1=1";
        $params = [];
        
        if ($classId) {
            $where .= " AND a.class_id = ?";
            $params[] = $classId;
        }
        if ($termId) {
            $where .= " AND a.term_id = ?";
            $params[] = $termId;
        }
        
        $stmt = $this->db->prepare("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent,
            SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late
            FROM attendance a WHERE {$where}");
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    // Get daily trend
    public function getDailyTrend($days = 30, $classId = null) {
        $where = $classId ? "AND class_id = ?" : "";
        $params = $classId ? [$classId] : [];
        
        $stmt = $this->db->prepare("SELECT 
            DATE(date) as day,
            COUNT(*) as total,
            SUM(CASE WHEN status IN ('Present','Late') THEN 1 ELSE 0 END) as present
            FROM attendance 
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) {$where}
            GROUP BY DATE(date) 
            ORDER BY day ASC");
        array_unshift($params, $days);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    // Get students at risk
    public function getAtRiskStudents($threshold = 75, $termId = null) {
        $termWhere = $termId ? "AND a.term_id = ?" : "";
        $params = $termId ? [$termId] : [];
        
        $stmt = $this->db->prepare("SELECT 
            s.id, s.full_name, s.admission_no, c.class_name,
            COUNT(*) as total_days,
            SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END) as present_days,
            ROUND((SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as percentage
            FROM students s
            JOIN attendance a ON s.id = a.student_id {$termWhere}
            JOIN classes c ON s.class_id = c.id
            WHERE s.is_active = 1
            GROUP BY s.id
            HAVING percentage < ?
            ORDER BY percentage ASC");
        $params[] = $threshold;
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    // Check consecutive absences
    public function getConsecutiveAbsences($studentId, $days = 3) {
        $stmt = $this->db->prepare("SELECT date, status FROM attendance 
            WHERE student_id = ? 
            ORDER BY date DESC LIMIT ?");
        $stmt->execute([$studentId, $days]);
        $records = $stmt->fetchAll();
        
        if (count($records) < $days) return false;
        
        foreach ($records as $record) {
            if ($record['status'] !== 'Absent') return false;
        }
        
        return true;
    }
}