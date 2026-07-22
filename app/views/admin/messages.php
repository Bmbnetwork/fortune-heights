<?php
$pageTitle = 'Messages';
require_once APP_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-envelope"></i> Messages</h2>
                    <p>Communicate with parents and colleagues</p>
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
                    </div>
                    <?php if (empty($conversations)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No conversations yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): 
                            $isActive = $activeContact && $activeContact['id'] == $conv['contact_id'] && $activeContact['type'] == $conv['contact_type'];
                        ?>
                            <a href="?page=messages&contact=<?= $conv['contact_id'] ?>&type=<?= $conv['contact_type'] ?>" 
                               class="conversation-item <?= $isActive ? 'active' : '' ?>">
                                <div class="avatar">
                                    <?= strtoupper(substr($conv['contact_name'], 0, 1)) ?>
                                </div>
                                <div class="conv-info">
                                    <div class="conv-name"><?= e($conv['contact_name']) ?></div>
                                    <div class="conv-preview"><?= e(substr($conv['last_message'], 0, 40)) ?>...</div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Chat Area -->
                <div class="chat-area">
                    <?php if ($activeContact): ?>
                        <div class="chat-header">
                            <div class="user-avatar">
                                <?= strtoupper(substr($activeContact['data']['full_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <strong><?= e($activeContact['data']['full_name']) ?></strong>
                                <small class="text-muted d-block"><?= e($activeContact['type']) ?></small>
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
                                    $isMine = $msg['sender_id'] == Auth::id() && $msg['sender_type'] == Auth::role();
                                ?>
                                    <div class="message-bubble <?= $isMine ? 'sent' : 'received' ?>">
                                        <?php if (!$isMine): ?>
                                            <strong style="display:block;font-size:12px;margin-bottom:4px;">
                                                <?= e($msg['sender_type'] === Auth::role() ? 'You' : $activeContact['data']['full_name']) ?>
                                            </strong>
                                        <?php endif; ?>
                                        <div><?= nl2br(e($msg['message'])) ?></div>
                                        <span class="message-time">
                                            <?= date('h:i A', strtotime($msg['created_at'])) ?>
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
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="empty-state" style="height:100%;display:flex;flex-direction:column;justify-content:center;">
                            <i class="fas fa-comments"></i>
                            <h3>Select a conversation</h3>
                            <p>Choose a contact from the list or start a new message</p>
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
            <h3>New Message</h3>
            <button class="modal-close" onclick="closeModal('newMessageModal')">&times;</button>
        </div>
        <form method="POST" action="?page=message-send">
            <?= Security::csrfField() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label>Recipient</label>
                    <select name="receiver_id" class="form-control" required id="recipientSelect">
                        <option value="">Select recipient...</option>
                        <?php
                        // Get potential recipients based on role
                        if (Auth::role() === 'teacher') {
                            $parents = $db->query("SELECT id, full_name FROM parents WHERE is_active = 1 ORDER BY full_name")->fetchAll();
                            foreach ($parents as $p):
                        ?>
                            <option value="<?= $p['id'] ?>" data-type="parent"><?= e($p['full_name']) ?> (Parent)</option>
                        <?php endforeach;
                        } ?>
                    </select>
                    <input type="hidden" name="receiver_type" id="receiverType">
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="subject" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" class="form-control" rows="6" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('newMessageModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-scroll to bottom of chat
const chatMessages = document.getElementById('chatMessages');
if (chatMessages) {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function openNewMessageModal() {
    document.getElementById('newMessageModal').classList.add('show');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

// Update receiver type when recipient changes
document.getElementById('recipientSelect')?.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    document.getElementById('receiverType').value = selected.dataset.type || '';
});
</script>

<?php require_once APP_PATH . '/views/layouts/footer.php'; ?>