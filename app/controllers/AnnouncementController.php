<?php
class AnnouncementController {
    
    public function index() {
        Auth::requireAuth();
        
        $db = db();
        $role = Auth::role();
        
        if ($role === 'admin') {
            $announcements = $db->query("SELECT a.*, ad.full_name as created_by_name 
                FROM announcements a 
                JOIN admins ad ON a.created_by = ad.id 
                ORDER BY a.created_at DESC")->fetchAll();
        } else {
            // Parents and teachers see published announcements
            $targetWhere = $role === 'parent' ? "target_audience IN ('all','parents')" : "target_audience IN ('all','teachers')";
            $stmt = $db->prepare("SELECT * FROM announcements 
                WHERE status = 'published' AND {$targetWhere} 
                ORDER BY published_at DESC");
            $stmt->execute();
            $announcements = $stmt->fetchAll();
        }
        
        require APP_PATH . '/views/' . $role . '/announcements.php';
    }
    
    public function create() {
        Auth::requireRole('admin');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                setFlash('danger', 'Invalid request');
                redirect('?page=announcements');
            }
            
            $title = Security::clean($_POST['title']);
            $content = Security::cleanHtml($_POST['content']);
            $target = $_POST['target_audience'];
            $priority = $_POST['priority'];
            $status = $_POST['status'];
            $scheduledAt = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null;
            
            if ($status === 'scheduled' && $scheduledAt) {
                $status = 'scheduled';
            } elseif ($status === 'published') {
                $status = 'published';
            } else {
                $status = 'draft';
            }
            
            $db = db();
            $stmt = $db->prepare("INSERT INTO announcements 
                (title, content, target_audience, priority, status, scheduled_at, published_at, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $title, $content, $target, $priority, $status,
                $scheduledAt,
                $status === 'published' ? date('Y-m-d H:i:s') : null,
                Auth::id()
            ]);
            
            $announcementId = $db->lastInsertId();
            
            // Notify users if published
            if ($status === 'published') {
                $this->notifyUsers($announcementId, $target, $title, $content);
            }
            
            Auth::logActivity(Auth::id(), 'admin', 'create_announcement', "Created: {$title}");
            setFlash('success', 'Announcement created successfully');
            redirect('?page=announcements');
        }
        
        require APP_PATH . '/views/admin/announcement_form.php';
    }
    
    private function notifyUsers($announcementId, $target, $title, $content) {
        $db = db();
        
        if ($target === 'all' || $target === 'parents') {
            $parents = $db->query("SELECT id FROM parents WHERE is_active = 1")->fetchAll();
            foreach ($parents as $p) {
                $db->prepare("INSERT INTO notifications (user_id, user_type, title, message, type, reference_id) 
                    VALUES (?, 'parent', ?, ?, 'announcement', ?)")
                    ->execute([$p['id'], $title, substr($content, 0, 100), $announcementId]);
            }
        }
        
        if ($target === 'all' || $target === 'teachers') {
            $teachers = $db->query("SELECT id FROM teachers WHERE is_active = 1")->fetchAll();
            foreach ($teachers as $t) {
                $db->prepare("INSERT INTO notifications (user_id, user_type, title, message, type, reference_id) 
                    VALUES (?, 'teacher', ?, ?, 'announcement', ?)")
                    ->execute([$t['id'], $title, substr($content, 0, 100), $announcementId]);
            }
        }
    }
}