<?php
// pages/messages.php - Neues Nachrichten-System

// Handle message actions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['send_message'])) {
        $recipient_username = trim($_POST['recipient_username']);
        $subject = trim($_POST['subject']);
        $message_text = trim($_POST['message']);

        // Find recipient by username
        $find_user = "SELECT id FROM users WHERE username = :username";
        $find_stmt = $db->prepare($find_user);
        $find_stmt->bindParam(":username", $recipient_username);
        $find_stmt->execute();

        if($find_stmt->rowCount() > 0) {
            $recipient = $find_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Send message
            $send_query = "INSERT INTO messages (sender_id, recipient_id, subject, message, is_read) 
                          VALUES (:sender_id, :recipient_id, :subject, :message, 0)";
            $send_stmt = $db->prepare($send_query);
            $send_stmt->bindParam(":sender_id", $_SESSION['user_id']);
            $send_stmt->bindParam(":recipient_id", $recipient['id']);
            $send_stmt->bindParam(":subject", $subject);
            $send_stmt->bindParam(":message", $message_text);
            $send_stmt->execute();

            $success_message = "Nachricht erfolgreich gesendet!";
        } else {
            $error_message = "Benutzer '$recipient_username' nicht gefunden!";
        }
    } elseif(isset($_POST['reply_message'])) {
        $original_msg_id = $_POST['message_id'];
        $reply_text = trim($_POST['reply']);

        // Get original message
        $get_msg = "SELECT * FROM messages WHERE id = :id";
        $msg_stmt = $db->prepare($get_msg);
        $msg_stmt->bindParam(":id", $original_msg_id);
        $msg_stmt->execute();
        $original = $msg_stmt->fetch(PDO::FETCH_ASSOC);

        if($original) {
            // Send reply as new message with parent_id
            $reply_subject = "RE: " . $original['subject'];
            $send_reply = "INSERT INTO messages (sender_id, recipient_id, subject, message, is_read, parent_id) 
                          VALUES (:sender_id, :recipient_id, :subject, :message, 0, :parent_id)";
            $reply_stmt = $db->prepare($send_reply);
            $reply_stmt->bindParam(":sender_id", $_SESSION['user_id']);
            $reply_stmt->bindParam(":recipient_id", $original['sender_id']);
            $reply_stmt->bindParam(":subject", $reply_subject);
            $reply_stmt->bindParam(":message", $reply_text);
            $reply_stmt->bindParam(":parent_id", $original_msg_id);
            $reply_stmt->execute();

            $success_message = "Antwort gesendet!";
        }
    } elseif(isset($_POST['mark_read'])) {
        $msg_id = $_POST['message_id'];
        $mark_query = "UPDATE messages SET is_read = 1 WHERE id = :id AND recipient_id = :user_id";
        $mark_stmt = $db->prepare($mark_query);
        $mark_stmt->bindParam(":id", $msg_id);
        $mark_stmt->bindParam(":user_id", $_SESSION['user_id']);
        $mark_stmt->execute();
    } elseif(isset($_POST['delete_message'])) {
        $msg_id = $_POST['message_id'];
        $delete_query = "DELETE FROM messages WHERE id = :id AND recipient_id = :user_id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindParam(":id", $msg_id);
        $delete_stmt->bindParam(":user_id", $_SESSION['user_id']);
        $delete_stmt->execute();
        $success_message = "Nachricht gelöscht!";
    }
}

// Get all messages for current user
$messages_query = "SELECT m.*, 
                   sender.username as sender_name,
                   recipient.username as recipient_name
                   FROM messages m
                   LEFT JOIN users sender ON m.sender_id = sender.id
                   LEFT JOIN users recipient ON m.recipient_id = recipient.id
                   WHERE m.recipient_id = :user_id OR m.sender_id = :user_id
                   ORDER BY m.sent_at DESC";

$messages_stmt = $db->prepare($messages_query);
$messages_stmt->bindParam(":user_id", $_SESSION['user_id']);
$messages_stmt->execute();
$messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);

// Group messages by conversation
$conversations = [];
foreach($messages as $msg) {
    if($msg['parent_id']) {
        if(!isset($conversations[$msg['parent_id']])) {
            $conversations[$msg['parent_id']] = [];
        }
        $conversations[$msg['parent_id']][] = $msg;
    }
}
?>

<h2>📧 Nachrichten</h2>

<?php if(isset($success_message)): ?>
    <div style="background: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #4caf50;">
        ✓ <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if(isset($error_message)): ?>
    <div style="background: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #f44336;">
        ✗ <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<!-- Neue Nachricht schreiben -->
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 15px; margin-bottom: 30px;">
    <h3 style="color: white; margin-bottom: 20px;">✉️ Neue Nachricht schreiben</h3>
    <form method="POST">
        <div style="margin-bottom: 15px;">
            <label style="color: white; display: block; margin-bottom: 5px; font-weight: bold;">An (Benutzername):</label>
            <input type="text" name="recipient_username" required 
                   style="width: 100%; padding: 12px; border: 2px solid white; border-radius: 8px; font-size: 14px;"
                   placeholder="Benutzername des Empfängers">
        </div>
        <div style="margin-bottom: 15px;">
            <label style="color: white; display: block; margin-bottom: 5px; font-weight: bold;">Betreff:</label>
            <input type="text" name="subject" required 
                   style="width: 100%; padding: 12px; border: 2px solid white; border-radius: 8px; font-size: 14px;"
                   placeholder="Betreff der Nachricht">
        </div>
        <div style="margin-bottom: 15px;">
            <label style="color: white; display: block; margin-bottom: 5px; font-weight: bold;">Nachricht:</label>
            <textarea name="message" required rows="5"
                      style="width: 100%; padding: 12px; border: 2px solid white; border-radius: 8px; font-size: 14px; resize: vertical;"
                      placeholder="Deine Nachricht..."></textarea>
        </div>
        <button type="submit" name="send_message" class="btn btn-success" style="width: 100%; padding: 15px;">
            📤 Nachricht senden
        </button>
    </form>
</div>

<!-- Nachrichtenliste -->
<h3>📬 Deine Nachrichten</h3>

<?php if(empty($messages)): ?>
    <div class="info-box">
        <p>Du hast noch keine Nachrichten. Schreibe jemandem eine Nachricht!</p>
    </div>
<?php else: ?>
    <?php foreach($messages as $msg): 
        // Nur Hauptnachrichten anzeigen (nicht Antworten)
        if($msg['parent_id']) continue;
        
        $is_received = ($msg['recipient_id'] == $_SESSION['user_id']);
        $is_unread = !$msg['is_read'] && $is_received;
        ?>
        
        <div style="background: <?php echo $is_unread ? '#fff9c4' : 'white'; ?>; 
                    border: 2px solid <?php echo $is_unread ? '#fbc02d' : '#ddd'; ?>; 
                    border-radius: 10px; 
                    padding: 20px; 
                    margin-bottom: 20px;">
            
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                <div>
                    <strong style="font-size: 18px; color: #333;">
                        <?php echo $is_unread ? '🆕 ' : ''; ?>
                        <?php echo htmlspecialchars($msg['subject']); ?>
                    </strong>
                    <br>
                    <span style="color: #666; font-size: 14px;">
                        <?php if($is_received): ?>
                            Von: <strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong>
                        <?php else: ?>
                            An: <strong><?php echo htmlspecialchars($msg['recipient_name']); ?></strong>
                        <?php endif; ?>
                        • <?php echo date('d.m.Y H:i', strtotime($msg['sent_at'])); ?>
                    </span>
                </div>
                <div>
                    <?php if($is_received): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                            <button type="submit" name="delete_message" class="btn btn-danger" 
                                    onclick="return confirm('Nachricht wirklich löschen?');"
                                    style="padding: 5px 10px; font-size: 12px;">
                                🗑️ Löschen
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div style="padding: 15px; background: #f5f5f5; border-radius: 8px; margin-bottom: 15px;">
                <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
            </div>

            <!-- Antworten anzeigen -->
            <?php if(isset($conversations[$msg['id']])): ?>
                <div style="margin-left: 30px; margin-top: 15px; border-left: 3px solid #667eea; padding-left: 15px;">
                    <strong style="color: #667eea;">💬 Antworten:</strong>
                    <?php foreach($conversations[$msg['id']] as $reply): ?>
                        <div style="background: #f0f4ff; padding: 15px; border-radius: 8px; margin-top: 10px;">
                            <div style="color: #666; font-size: 13px; margin-bottom: 5px;">
                                <strong><?php echo htmlspecialchars($reply['sender_name']); ?></strong>
                                • <?php echo date('d.m.Y H:i', strtotime($reply['sent_at'])); ?>
                            </div>
                            <?php echo nl2br(htmlspecialchars($reply['message'])); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Antworten-Formular -->
            <?php if($is_received): ?>
                <div style="margin-top: 15px; padding: 15px; background: #e3f2fd; border-radius: 8px;">
                    <form method="POST">
                        <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">
                            💬 Antworten:
                        </label>
                        <textarea name="reply" required rows="3"
                                  style="width: 100%; padding: 10px; border: 2px solid #2196F3; border-radius: 8px; font-size: 14px; resize: vertical;"
                                  placeholder="Deine Antwort..."></textarea>
                        <button type="submit" name="reply_message" class="btn btn-primary" style="margin-top: 10px;">
                            📨 Antwort senden
                        </button>
                        <?php if($is_unread): ?>
                            <button type="submit" name="mark_read" class="btn" style="margin-top: 10px; background: #fbc02d;">
                                ✓ Als gelesen markieren
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
