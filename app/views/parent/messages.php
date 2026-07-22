<?php
$pageTitle = 'Messages';
Auth::requireRole('parent');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$userId = Auth::id();
$userType = 'parent';

// ============================================================
// GET CONVERSATIONS (unique contacts)
// ============================================================
$stmt = $db->prepare("SELECT 
    CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END as contact_id,
    CASE WHEN m.sender_id = ? THEN m.receiver_type ELSE m.sender_type END as contact_type,
    MAX(m.created_at) as last_message_time,
    (SELECT message FROM messages m2 WHERE 
        (m2.sender_id = ? AND m2.sender_type = 'parent' AND m2.receiver_id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END AND m2.receiver_type = CASE WHEN m.sender_id = ? THEN m.receiver_type ELSE m.sender_type END) OR
        (m2.sender_id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END AND m2.sender_type = CASE WHEN m.sender_id = ? THEN m.receiver_type ELSE m.sender_type END AND m2.receiver_id = ? AND m2.receiver_type = 'parent')
        ORDER BY m2.created_at DESC LIMIT 1) as last_message
    FROM messages m
    WHERE (m.sender_id = ? AND m.sender_type = 'parent') OR (m.receiver_id = ? AND m.receiver_type = 'parent')
    GROUP BY contact_id, contact_type
    ORDER BY last_message_time DESC");
$stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId]);
$conversations = $stmt->fetchAll();

// Enrich with contact names
foreach ($conversations as &$conv) {
    $table = $conv['contact_type'] === 'teacher' ? 'teachers' : 'admins';
    $s = $db->prepare("SELECT full_name FROM {$table} WHERE id = ?");
    $s->execute([$conv['contact_id']]);
    $conv['contact_name'] = $s->fetchColumn() ?: 'Unknown';
}

// ============================================================
// ACTIVE CONVERSATION
// ============================================================
$activeContact = null;
$messages = [];
if (isset($_GET['contact']) && isset($_GET['type'])) {
    $activeContact = ['id' => (int)$_GET['contact'], 'type' => $_GET['type']];
    $table = $activeContact['type'] === 'teacher' ? 'teachers' : 'admins';
    $s = $db->prepare("SELECT full_name, email FROM {$table} WHERE id = ?");
    $s->execute([$activeContact['id']]);
    $activeContact['data'] = $s->fetch();
    
    if (!$activeContact['data']) {
        setFlash('danger', 'Contact not found');
        redirect('?page=messages');
    }
    
    // Get messages between parent and contact
    $stmt = $db->prepare("SELECT * FROM messages WHERE
        ((sender_id = ? AND sender_type = 'parent') AND (receiver_id = ? AND receiver_type = ?)) OR
        ((sender_id = ? AND sender_type = ?) AND (receiver_id = ? AND receiver_type = 'parent'))
        ORDER BY created_at ASC");
    $stmt->execute([$userId, $activeContact['id'], $activeContact['type'],
                    $activeContact['id'], $activeContact['type'], $userId]);
    $messages = $stmt->fetchAll();
    
    // Mark messages as read
    $db->prepare("UPDATE messages SET is_read = 1 
        WHERE receiver_id = ? AND receiver_type = 'parent' AND sender_id = ? AND sender_type = ?")
        ->execute([$userId, $activeContact['id'], $activeContact['type']]);
}

// ============================================================
// GET TEACHERS (for new message modal) - ✅ FIXED QUERY
// ============================================================
$teachers = $db->query("SELECT id, full_name FROM teachers WHERE is_active = 1 ORDER BY full_name")->fetchAll();

// ============================================================
// GET CHILDREN - ✅ FIXED: Use table aliases to avoid ambiguity
// ============================================================
$childrenStmt = $db->prepare("SELECT 
    s.id,           -- ✅ Explicitly specify students.id
    s.full_name,    -- ✅ From students table
    c.class_name    -- ✅ From classes table
    FROM students s 
    JOIN classes c ON s.class_id = c.id 
    WHERE s.parent_id = ? AND s.is_active = 1 
    ORDER BY s.full_name");
$childrenStmt->execute([Auth::id()]);
$children = $childrenStmt->fetchAll();
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-envelope"></i> Messages</h2>
                    <p>Communicate with your child's teachers</p>
                </div>
                <button class="btn btn-primary" onclick="openNewMessageModal()">
                    <i class="fas fa-plus"></i> New Message
                </button>
            </div>
            
            <?php displayFlash(); ?>
            
            <div class="messaging-container">
                <!-- Conversations List -->
                <div class="conversation-list">
                    <div class="conversation-header"><i class="fas fa-comments"></i> Conversations</div>
                    <?php if (empty($conversations)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No conversations yet</p>
                            <small class="text-muted">Start a new message with a teacher</small>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): 
                            $isActive = $activeContact && $activeContact['id'] == $conv['contact_id'] && $activeContact['type'] == $conv['contact_type'];
                        ?>
                            <a href="?page=messages&contact=<?= $conv['contact_id'] ?>&type=<?= $conv['contact_type'] ?>" 
                               class="conversation-item <?= $isActive ? 'active' : '' ?>">
                                <div class="avatar"><?= strtoupper(substr($conv['contact_name'], 0, 1)) ?></div>
                                <div class="conv-info">
                                    <div class="conv-name"><?= e($conv['contact_name']) ?></div>
                                    <div class="conv-preview"><?= e(substr($conv['last_message'] ?? '', 0, 40)) ?>...</div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Chat Area -->
                <div class="chat-area">
                    <?php if ($activeContact): ?>
                        <div class="chat-header">
                            <div class="user-avatar"><?= strtoupper(substr($activeContact['data']['full_name'], 0, 1)) ?></div>
                            <div>
                                <strong><?= e($activeContact['data']['full_name']) ?></strong>
                                <small class="text-muted d-block"><?= e(ucfirst($activeContact['type'])) ?></small>
                            </div>
                        </div>
                        
                        <div class="chat-messages" id="chatMessages">
                            <?php if (empty($messages)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-comment-slash"></i>
                                    <p>No messages yet. Start the conversation!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): 
                                    $isMine = $msg['sender_id'] == Auth::id() && $msg['sender_type'] == 'parent';
                                ?>
                                    <div class="message-bubble <?= $isMine ? 'sent' : 'received' ?>">
                                        <div><?= nl2br(e($msg['message'])) ?></div>
                                        <span class="message-time">
                                            <?= date('M d, h:i A', strtotime($msg['created_at'])) ?>
                                            <?php if ($isMine): ?>
                                                <i class="fas fa-<?= $msg['is_read'] ? 'check-double' : 'check' ?>"></i>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <form method="POST" action="?page=message-send" class="chat-input">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="receiver_id" value="<?= $activeContact['id'] ?>">
                            <input type="hidden" name="receiver_type" value="<?= $activeContact['type'] ?>">
                            <input type="hidden" name="subject" value="Re: Conversation">
                            <input type="text" name="message" placeholder="Type your message..." required autofocus>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
                        </form>
                    <?php else: ?>
                        <div class="empty-state" style="height:100%;display:flex;flex-direction:column;justify-content:center;">
                            <i class="fas fa-comments"></i>
                            <h3>Select a conversation</h3>
                            <p>Choose a teacher from the list or start a new message</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>

<!-- New Message Modal -->
<div class="modal-backdrop" id="newMessageModal">
    <div class="modal">
        <div class="modal-header">
            <h3>New Message to Teacher</h3>
            <button class="modal-close" onclick="closeModal('newMessageModal')">&times;</button>
        </div>
        <form method="POST" action="?page=message-send">
            <?= Security::csrfField() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label>Select Teacher *</label>
                    <select name="receiver_id" class="form-control" required id="recipientSelect">
                        <option value="">Choose a teacher...</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>" data-type="teacher"><?= e($t['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="receiver_type" id="receiverType" value="teacher">
                </div>
                <?php if (!empty($children)): ?>
                    <div class="form-group">
                        <label>Regarding Child</label>
                        <select class="form-control" id="childSelect">
                            <?php foreach ($children as $c): ?>
                                <option value="<?= e($c['full_name']) ?> (<?= e($c['class_name']) ?>)">
                                    <?= e($c['full_name']) ?> - <?= e($c['class_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> No children registered. Please contact admin.
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Subject *</label>
                    <input type="text" name="subject" class="form-control" required placeholder="e.g., Question about homework">
                </div>
                <div class="form-group">
                    <label>Message *</label>
                    <textarea name="message" class="form-control" rows="6" required placeholder="Write your message here..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('newMessageModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Message</button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-scroll to latest message
const chatMessages = document.getElementById('chatMessages');
if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;

function openNewMessageModal() { 
    document.getElementById('newMessageModal').classList.add('show'); 
}
function closeModal(id) { 
    document.getElementById(id).classList.remove('show'); 
}

// Update receiver type when teacher is selected
document.getElementById('recipientSelect')?.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    document.getElementById('receiverType').value = selected.dataset.type || 'teacher';
});

// Auto-prepend child name to message
document.getElementById('childSelect')?.addEventListener('change', function() {
    const textarea = document.querySelector('#newMessageModal textarea[name="message"]');
    if (textarea && !textarea.value.trim()) {
        textarea.value = 'Dear Teacher,\n\nRegarding ' + this.value + ':\n\n';
        textarea.focus();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal('newMessageModal');
    }
});

// Close modal on backdrop click
document.getElementById('newMessageModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeModal('newMessageModal');
});
</script>