<?php
class ResultController {
    
    /**
     * Teacher: Upload/Edit Results - FIXED VERSION
     */
    public function upload() {
        Auth::requireRole('teacher');
        
        $db = db();
        $teacher = Auth::user();
        $term = getCurrentTerm();
        
        if (!$term) {
            setFlash('warning', 'No active academic term. Please contact admin.');
        }
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                setFlash('danger', 'Invalid request');
                redirect('?page=results');
            }
            
            if (!$term) {
                setFlash('danger', 'Cannot upload results without an active term');
                redirect('?page=results');
            }
            
            $subjectId = (int)($_POST['subject_id'] ?? 0);
            $classId = (int)($_POST['class_id'] ?? 0);
            $published = isset($_POST['publish']) ? 1 : 0;
            
            // Validate subject belongs to this teacher
            $subjectCheck = $db->prepare("SELECT id FROM subjects WHERE id = ? AND teacher_id = ?");
            $subjectCheck->execute([$subjectId, Auth::id()]);
            if (!$subjectCheck->fetch()) {
                setFlash('danger', 'Invalid subject selection');
                redirect('?page=results');
            }
            
            try {
                $db->beginTransaction();
                $saved = 0;
                
                foreach ($_POST['scores'] ?? [] as $studentId => $scores) {
                    $ca = min(40, max(0, (float)($scores['ca'] ?? 0)));     // Max CA: 40
                    $exam = min(60, max(0, (float)($scores['exam'] ?? 0))); // Max Exam: 60
                    $total = $ca + $exam;
                    $gradeData = getGrade($total);
                    
                    $stmt = $db->prepare("INSERT INTO results 
                        (student_id, subject_id, term_id, ca_score, exam_score, total_score, grade, remark, is_published, uploaded_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                            ca_score = VALUES(ca_score),
                            exam_score = VALUES(exam_score),
                            total_score = VALUES(total_score),
                            grade = VALUES(grade),
                            remark = VALUES(remark),
                            is_published = VALUES(is_published),
                            uploaded_by = VALUES(uploaded_by)");
                    $stmt->execute([
                        (int)$studentId, $subjectId, $term['id'],
                        $ca, $exam, $total,
                        $gradeData['grade'], $gradeData['remark'],
                        $published, Auth::id()
                    ]);
                    $saved++;
                }
                
                $db->commit();
                Auth::logActivity(Auth::id(), 'teacher', 'upload_results', 
                    "Uploaded results for subject #{$subjectId} ({$saved} students)");
                
                // Notify parents if published
                if ($published && $saved > 0) {
                    $parentIds = $db->prepare("SELECT DISTINCT s.parent_id 
                        FROM students s WHERE s.id IN (" . 
                        implode(',', array_keys($_POST['scores'] ?? [])) . ")");
                    if (!empty($_POST['scores'])) {
                        $parentIds->execute();
                        $subjectName = $db->prepare("SELECT subject_name FROM subjects WHERE id = ?");
                        $subjectName->execute([$subjectId]);
                        $subName = $subjectName->fetchColumn();
                        
                        foreach ($parentIds->fetchAll() as $p) {
                            $db->prepare("INSERT INTO notifications 
                                (user_id, user_type, title, message, type, reference_id) 
                                VALUES (?, 'parent', ?, ?, 'result', ?)")
                                ->execute([
                                    $p['parent_id'],
                                    'New Results Published',
                                    "Results for {$subName} ({$term['term_name']}) have been published.",
                                    $subjectId
                                ]);
                        }
                    }
                }
                
                setFlash('success', "Results saved for {$saved} students" . ($published ? ' and published' : ' (draft)'));
            } catch (Exception $e) {
                $db->rollBack();
                setFlash('danger', 'Error: ' . $e->getMessage());
            }
            
            redirect('?page=results&subject_id=' . $subjectId . '&class_id=' . $classId);
        }
        
        // ============================================================
        // Load form data
        // ============================================================
        
        // Get subjects taught by this teacher with class names
        $subjects = $db->prepare("SELECT s.id, s.subject_name, s.subject_code, s.class_id, c.class_name 
            FROM subjects s 
            JOIN classes c ON s.class_id = c.id 
            WHERE s.teacher_id = ? 
            ORDER BY c.class_name, s.subject_name");
        $subjects->execute([Auth::id()]);
        $subjects = $subjects->fetchAll();
        
        // Get selected subject/class
        $selectedSubject = (int)($_GET['subject_id'] ?? 0);
        $selectedClass = (int)($_GET['class_id'] ?? 0);
        
        // Auto-select first subject if none selected
        if ($selectedSubject === 0 && !empty($subjects)) {
            $selectedSubject = (int)$subjects[0]['id'];
            $selectedClass = (int)$subjects[0]['class_id'];
        }
        
        // Get students and their results
        $students = [];
        $subjectInfo = null;
        
        if ($selectedSubject > 0 && $selectedClass > 0 && $term) {
            // Get subject info
            $subjStmt = $db->prepare("SELECT s.*, c.class_name FROM subjects s 
                JOIN classes c ON s.class_id = c.id WHERE s.id = ? AND s.teacher_id = ?");
            $subjStmt->execute([$selectedSubject, Auth::id()]);
            $subjectInfo = $subjStmt->fetch();
            
            if ($subjectInfo) {
                // Get students with their results
                $stmt = $db->prepare("SELECT s.id, s.full_name, s.admission_no,
                    r.ca_score, r.exam_score, r.total_score, r.grade, r.remark, r.is_published
                    FROM students s
                    LEFT JOIN results r ON s.id = r.student_id 
                        AND r.subject_id = ? AND r.term_id = ?
                    WHERE s.class_id = ? AND s.is_active = 1
                    ORDER BY s.full_name");
                $stmt->execute([$selectedSubject, $term['id'], $selectedClass]);
                $students = $stmt->fetchAll();
            }
        }
        
        require APP_PATH . '/views/teacher/results.php';
    }
    
    /**
     * Parent: View child results
     */
    public function parentView() {
        Auth::requireRole('parent');
        
        $db = db();
        $term = getCurrentTerm();
        
        // Get children
        $stmt = $db->prepare("SELECT s.*, c.class_name FROM students s 
            JOIN classes c ON s.class_id = c.id 
            WHERE s.parent_id = ? AND s.is_active = 1");
        $stmt->execute([Auth::id()]);
        $children = $stmt->fetchAll();
        
        // Get results for each child
        $results = [];
        foreach ($children as $child) {
            $stmt = $db->prepare("SELECT r.*, sub.subject_name 
                FROM results r 
                JOIN subjects sub ON r.subject_id = sub.id 
                WHERE r.student_id = ? AND r.term_id = ? AND r.is_published = 1
                ORDER BY sub.subject_name");
            $stmt->execute([$child['id'], $term['id'] ?? 0]);
            $results[$child['id']] = $stmt->fetchAll();
        }
        
        require APP_PATH . '/views/parent/results.php';
    }
}