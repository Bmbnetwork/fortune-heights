<?php
class MessageController {
    
    /**
     * Show messaging interface - FIXED VERSION
     * Uses simple, separate queries instead of complex alias-referencing SQL
     */
    public function index() {
        Auth::requireAuth();
        
        $db = db();
        $userId = Auth::id();
        $userType = Auth::role();
        
        // ============================================================
        // STEP 1: Get unique contacts (who has this user talked to?)
        // ============================================================
        $contactStmt = $db->prepare("SELECT DISTINCT
            CASE 
                WHEN sender_id = ? AND sender_type = ? THEN receiver_id 
                ELSE sender_id 
            END as contact_id,
            CASE 
                WHEN sender_id = ? AND sender_type = ? THEN receiver_type 
                ELSE sender_type 
            END as contact_type
            FROM messages
            WHERE (sender_id = ? AND sender_type = ?) 
               OR (receiver_id = ? AND receiver_type = ?)");
        $contactStmt->execute([
            $userId, $userType,
            $userId, $userType,
            $userId, $userType,
            $userId, $userType
        ]);
        $contacts = $contactStmt->fetchAll();
        
        // ============================================================
        // STEP 2: For each contact, fetch name + last message + unread count
        // ============================================================
        $conversations = [];
        foreach ($contacts as $contact) {
            $contactId = (int)$contact['contact_id'];
            $contactType = $contact['contact_type'];
            
            // Determine correct table
            if ($contactType === 'teacher') {
                $table = 'teachers';
            } elseif ($contactType === 'parent') {
                $table = 'parents';
            } elseif ($contactType === 'admin') {
                $table = 'admins';
            } else {
                continue; // Skip invalid types
            }
            
            // Fetch contact name
            $nameStmt = $db->prepare("SELECT full_name FROM {$table} WHERE id = ? LIMIT 1");
            $nameStmt->execute([$contactId]);
            $contactName = $nameStmt->fetchColumn();
            
            if (!$contactName) continue; // Skip if contact no longer exists
            
            // Fetch last message in this conversation
            $msgStmt = $db->prepare("SELECT message, created_at FROM messages WHERE
                ((sender_id = ? AND sender_type = ?) AND (receiver_id = ? AND receiver_type = ?)) OR
                ((sender_id = ? AND sender_type = ?) AND (receiver_id = ? AND receiver_type = ?))
                ORDER BY created_at DESC LIMIT 1");
            $msgStmt->execute([
                $userId, $userType, $contactId, $contactType,
                $contactId, $contactType, $userId, $userType
            ]);
            $lastMsg = $msgStmt->fetch();
            
            // Count unread messages
            $unreadStmt = $db->prepare("SELECT COUNT(*) FROM messages 
                WHERE sender_id = ? AND sender_type = ? 
                  AND receiver_id = ? AND receiver_type = ? 
                  AND is_read = 0");
            $unreadStmt->execute([$contactId, $contactType, $userId, $userType]);
            $unreadCount = (int)$unreadStmt->fetchColumn();
            
            $conversations[] = [
                'contact_id'        => $contactId,
                'contact_type'      => $contactType,
                'contact_name'      => $contactName,
                'last_message'      => $lastMsg['message'] ?? '',
                'last_message_time' => $lastMsg['created_at'] ?? '',
                'unread_count'      => $unreadCount
            ];
        }
        
        // Sort by most recent
        usort($conversations, function($a, $b) {
            return strtotime($b['last_message_time'] ?: '0') - strtotime($a['last_message_time'] ?: '0');
        });
        
        // ============================================================
        // STEP 3: Load active conversation if selected
        // ============================================================
        $activeContact = null;
        $messages = [];
        
        if (isset($_GET['contact']) && isset($_GET['type'])) {
            $activeContact = [
                'id'   => (int)$_GET['contact'],
                'type' => $_GET['type']
            ];
            
            // Validate contact type
            if (!in_array($activeContact['type'], ['teacher', 'parent', 'admin'])) {
                setFlash('danger', 'Invalid contact type');
                redirect('?page=messages');
            }
            
            $table = match($activeContact['type']) {
                'teacher' => 'teachers',
                'parent'  => 'parents',
                'admin'   => 'admins'
            };
            
            $s = $db->prepare("SELECT full_name, email FROM {$table} WHERE id = ? LIMIT 1");
            $s->execute([$activeContact['id']]);
            $activeContact['data'] = $s->fetch();
            
            if (!$activeContact['data']) {
                setFlash('danger', 'Contact not found');
                redirect('?page=messages');
            }
            
            // Fetch all messages between user and contact
            $msgStmt = $db->prepare("SELECT * FROM messages WHERE
                ((sender_id = ? AND sender_type = ?) AND (receiver_id = ? AND receiver_type = ?)) OR
                ((sender_id = ? AND sender_type = ?) AND (receiver_id = ? AND receiver_type = ?))
                ORDER BY created_at ASC");
            $msgStmt->execute([
                $userId, $userType, $activeContact['id'], $activeContact['type'],
                $activeContact['id'], $activeContact['type'], $userId, $userType
            ]);
            $messages = $msgStmt->fetchAll();
            
            // Mark as read
            $db->prepare("UPDATE messages SET is_read = 1 
                WHERE receiver_id = ? AND receiver_type = ? 
                  AND sender_id = ? AND sender_type = ?")
                ->execute([$userId, $userType, $activeContact['id'], $activeContact['type']]);
        }
        
        // ============================================================
        // STEP 4: Load potential recipients for "New Message" modal
        // ============================================================
        $potentialRecipients = [];
        if ($userType === 'teacher') {
            // Teachers can message parents of their students
            $classId = Auth::user()['class_id'] ?? null;
            if ($classId) {
                $parents = $db->prepare("SELECT DISTINCT p.id, p.full_name 
                    FROM parents p 
                    JOIN students s ON p.id = s.parent_id 
                    WHERE s.class_id = ? AND s.is_active = 1 
                    ORDER BY p.full_name");
                $parents->execute([$classId]);
                $potentialRecipients = $parents->fetchAll();
            } else {
                // If no class assigned, show all parents
                $potentialRecipients = $db->query("SELECT id, full_name FROM parents WHERE is_active = 1 ORDER BY full_name")->fetchAll();
            }
        } elseif ($userType === 'parent') {
            $potentialRecipients = $db->query("SELECT id, full_name FROM teachers WHERE is_active = 1 ORDER BY full_name")->fetchAll();
        }
        
        // Load the view based on role
        require APP_PATH . '/views/' . $userType . '/messages.php';
    }
    
    /**
     * Send a message - FIXED VERSION
     */
    public function send() {
        Auth::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('?page=messages');
        }
        
        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlash('danger', 'Invalid request. Please try again.');
            redirect('?page=messages');
        }
        
        $receiverId = (int)($_POST['receiver_id'] ?? 0);
        $receiverType = $_POST['receiver_type'] ?? '';
        $subject = Security::clean($_POST['subject'] ?? 'No Subject');
        $message = trim($_POST['message'] ?? '');
        
        // Validate
        if ($receiverId <= 0) {
            setFlash('danger', 'Please select a recipient');
            redirect('?page=messages');
        }
        
        if (!in_array($receiverType, ['teacher', 'parent', 'admin'])) {
            setFlash('danger', 'Invalid recipient type');
            redirect('?page=messages');
        }
        
        if (empty($message)) {
            setFlash('danger', 'Message cannot be empty');
            redirect('?page=messages&contact=' . $receiverId . '&type=' . $receiverType);
        }
        
        // Verify recipient exists
        $db = db();
        $table = match($receiverType) {
            'teacher' => 'teachers',
            'parent'  => 'parents',
            'admin'   => 'admins'
        };
        
        $check = $db->prepare("SELECT id FROM {$table} WHERE id = ? LIMIT 1");
        $check->execute([$receiverId]);
        if (!$check->fetch()) {
            setFlash('danger', 'Recipient not found');
            redirect('?page=messages');
        }
        
        try {
            $stmt = $db->prepare("INSERT INTO messages 
                (sender_id, sender_type, receiver_id, receiver_type, subject, message) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                Auth::id(), 
                Auth::role(), 
                $receiverId, 
                $receiverType, 
                $subject, 
                $message
            ]);
            $messageId = $db->lastInsertId();
            
            // Create notification for recipient
            $senderName = Auth::name();
            $stmt = $db->prepare("INSERT INTO notifications 
                (user_id, user_type, title, message, type, reference_id) 
                VALUES (?, ?, ?, ?, 'message', ?)");
            $stmt->execute([
                $receiverId,
                $receiverType,
                'New Message from ' . $senderName,
                substr($message, 0, 100) . (strlen($message) > 100 ? '...' : ''),
                $messageId
            ]);
            
            Auth::logActivity(Auth::id(), Auth::role(), 'send_message', 
                "Sent message to {$receiverType} #{$receiverId}");
            
            setFlash('success', 'Message sent successfully');
        } catch (Exception $e) {
            setFlash('danger', 'Error sending message: ' . $e->getMessage());
        }
        
        redirect('?page=messages&contact=' . $receiverId . '&type=' . $receiverType);
    }
}