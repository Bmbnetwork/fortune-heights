<?php
$pageTitle = 'Messages';
Auth::requireRole('teacher');
require_once APP_PATH . '/views/layouts/header.php';

// Get teacher's students' parents for context
$teacher = Auth::user();
$childrenOfParents = [];
if ($teacher['class_id']) {
    $stmt = $db->prepare("SELECT s.full_name as student_name, p.id as parent_id, p.full_name as parent_name 
        FROM students s JOIN parents p ON s.parent_id = p.id 
        WHERE s.class_id = ? AND s.is_active = 1 ORDER BY p.full_name");
    $stmt->execute([$teacher['class_id']]);
    $childrenOfParents = $stmt->fetchAll();
}
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-envelope"></i> Messages</h2>
                    <p>Communicate with parents of your students</p>
                </div>
                <button class="btn btn-primary" onclick="openNewMessageModal()">
                    <i class="fas fa-plus"></i> New Message
                </button>
            </div>
            
            <?php displayFlash(); ?>
            
            <div class="messaging-container">
                <!-- Conversations List -->
                <div class="conversation-list">
                    <div class="conversation-header">
                        <i class="fas fa-comments"></i> Conversations
                        <span class="badge badge-primary" style="float:right;"><?= count($conversations) ?></span>
                    </div>
                    <?php if (empty($conversations)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No conversations yet</p>
                            <small class="text-muted">Start a new message with a parent</small>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): 
                            $isActive = $activeContact 
                                && $activeContact['id'] == $conv['contact_id'] 
                                && $activeContact['type'] == $conv['contact_type'];
                        ?>
                            <a href="?page=messages&contact=<?= $conv['contact_id'] ?>&type=<?= $conv['contact_type'] ?>" 
                               class="conversation-item <?= $isActive ? 'active' : '' ?>">
                                <div class="avatar" style="background:linear-gradient(135deg,#10b981,#059669);">
                                    <?= strtoupper(substr($conv['contact_name'], 0, 1)) ?>
                                </div>
                                <div class="conv-info">
                                    <div class="d-flex justify-between align-center">
                                        <div class="conv-name">
                                            <?= e($conv['contact_name']) ?>
                                            <?php if ($conv['unread_count'] > 0): ?>
                                                <span class="badge" style="background:var(--danger);color:white;font-size:10px;padding:1px 6px;border-radius:10px;margin-left:5px;">
                                                    <?= $conv['unread_count'] ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($conv['last_message_time']): ?>
                                            <small class="text-muted" style="font-size:10px;">
                                                <?= timeAgo($conv['last_message_time']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="conv-preview">
                                        <?= e(substr($conv['last_message'] ?: 'No messages yet', 0, 45)) ?>
                                    </div>
                                    <small style="color:#10b981;font-size:10px;text-transform:uppercase;font-weight:600;">
                                        <i class="fas fa-user"></i> Parent
                                    </small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Chat Area -->
                <div class="chat-area">
                    <?php if ($activeContact): ?>
                        <div class="chat-header">
                            <div class="user-avatar" style="background:linear-gradient(135deg,#10b981,#059669);">
                                <?= strtoupper(substr($activeContact['data']['full_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <strong><?= e($activeContact['data']['full_name']) ?></strong>
                                <small class="text-muted d-block">
                                    <i class="fas fa-user"></i> <?= e(ucfirst($activeContact['type'])) ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="chat-messages" id="chatMessages">
                            <?php if (empty($messages)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-comment-slash"></i>
                                    <p>No messages yet. Start the conversation!</p>
                                </div>
                            <?php else: ?>
                                <?php 
                                $lastDate = '';
                                foreach ($messages as $msg): 
                                    $isMine = $msg['sender_id'] == Auth::id() && $msg['sender_type'] == Auth::role();
                                    $msgDate = date('M d, Y', strtotime($msg['created_at']));
                                    
                                    if ($msgDate !== $lastDate): 
                                        $lastDate = $msgDate;
                                ?>
                                    <div style="text-align:center;margin:15px 0;">
                                        <span style="background:var(--gray-200);color:var(--gray-600);padding:4px 12px;border-radius:12px;font-size:11px;">
                                            <?= $msgDate ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                    <div class="message-bubble <?= $isMine ? 'sent' : 'received' ?>">
                                        <div><?= nl2br(e($msg['message'])) ?></div>
                                        <span class="message-time">
                                            <?= date('h:i A', strtotime($msg['created_at'])) ?>
                                            <?php if ($isMine): ?>
                                                <i class="fas fa-<?= $msg['is_read'] ? 'check-double' : 'check' ?>" 
                                                   style="color:<?= $msg['is_read'] ? '#60a5fa' : 'inherit' ?>;"></i>
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
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="empty-state" style="height:100%;display:flex;flex-direction:column;justify-content:center;">
                            <i class="fas fa-comments" style="font-size:60px;"></i>
                            <h3>Select a conversation</h3>
                            <p>Choose a parent from the list or start a new message</p>
                            <button class="btn btn-primary mt-3" onclick="openNewMessageModal()">
                                <i class="fas fa-plus"></i> New Message
                            </button>
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
            <h3><i class="fas fa-paper-plane text-primary"></i> New Message to Parent</h3>
            <button class="modal-close" onclick="closeModal('newMessageModal')">&times;</button>
        </div>
        <form method="POST" action="?page=message-send">
            <?= Security::csrfField() ?>
            <div class="modal-body">
                <?php if (empty($potentialRecipients)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        No parents available to message. You may not be assigned to a class yet.
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label>Select Parent *</label>
                        <select name="receiver_id" class="form-control" required id="recipientSelect">
                            <option value="">Choose a parent...</option>
                            <?php foreach ($potentialRecipients as $p): ?>
                                <option value="<?= $p['id'] ?>" data-type="parent"><?= e($p['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="receiver_type" id="receiverType" value="parent">
                    </div>
                    
                    <div class="form-group">
                        <label>Subject *</label>
                        <input type="text" name="subject" class="form-control" required 
                               placeholder="e.g., Regarding your child's progress">
                    </div>
                    
                    <div class="form-group">
                        <label>Message *</label>
                        <textarea name="message" class="form-control" rows="6" required 
                                  placeholder="Write your message here..."></textarea>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('newMessageModal')">Cancel</button>
                <?php if (!empty($potentialRecipients)): ?>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script>
const chatMessages = document.getElementById('chatMessages');
if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;

function openNewMessageModal() { 
    document.getElementById('newMessageModal').classList.add('show'); 
}
function closeModal(id) { 
    document.getElementById(id).classList.remove('show'); 
}

document.getElementById('recipientSelect')?.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    document.getElementById('receiverType').value = selected.dataset.type || 'parent';
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal('newMessageModal');
});

document.getElementById('newMessageModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeModal('newMessageModal');
});
</script>

<?php require_once APP_PATH . '/views/layouts/footer.php'; ?>