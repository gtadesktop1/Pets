<?php
// pages/backups.php
require_once 'GameBackup.php';

$backup = new GameBackup($db);

// Handle Actions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['create_backup'])) {
        $backup_name = $_POST['backup_name'] ?? null;
        $result = $backup->createBackup($_SESSION['user_id'], $backup_name);
        
        if($result['success']) {
            $success_message = "Backup '{$result['backup_name']}' erfolgreich erstellt! (Größe: " . 
                              number_format($result['file_size'] / 1024, 2) . " KB)";
        } else {
            $error_message = "Fehler beim Erstellen des Backups!";
        }
    } elseif(isset($_POST['restore_backup'])) {
        $backup_id = $_POST['backup_id'];
        $result = $backup->restoreBackup($backup_id, $_SESSION['user_id']);
        
        if($result['success']) {
            $success_message = "Spielstand von '{$result['backup_name']}' erfolgreich wiederhergestellt!";
            // Reload page to show updated data
            echo "<script>setTimeout(function(){ window.location.href = '?page=backups'; }, 2000);</script>";
        } else {
            $error_message = $result['message'];
        }
    } elseif(isset($_POST['delete_backup'])) {
        $backup_id = $_POST['backup_id'];
        if($backup->deleteBackup($backup_id, $_SESSION['user_id'])) {
            $success_message = "Backup erfolgreich gelöscht!";
        } else {
            $error_message = "Fehler beim Löschen des Backups!";
        }
    } elseif(isset($_POST['export_backup'])) {
        $backup_id = $_POST['backup_id'];
        $export_data = $backup->exportBackup($backup_id, $_SESSION['user_id']);
        
        if($export_data) {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="pets_backup_' . date('Y-m-d_H-i-s') . '.json"');
            echo json_encode($export_data, JSON_PRETTY_PRINT);
            exit();
        }
    }
}

$backups = $backup->getUserBackups($_SESSION['user_id']);
?>

<h2>☁️ Spielstand-Backups</h2>

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

<div class="info-box">
    <h3>💾 Cloud-Backup-System</h3>
    <p>Sichere deinen Spielstand in der Cloud und stelle ihn jederzeit wieder her. 
    Deine Backups werden in der Cloud-Datenbank gespeichert und sind von überall zugänglich.</p>
    <ul style="list-style: none; padding-left: 0; margin-top: 10px;">
        <li>✓ <strong>Automatische Synchronisation</strong> - Deine Daten sind immer aktuell</li>
        <li>✓ <strong>Unbegrenzte Backups</strong> - Erstelle so viele Backups wie du möchtest</li>
        <li>✓ <strong>Schnelle Wiederherstellung</strong> - Stelle Spielstände mit einem Klick wieder her</li>
        <li>✓ <strong>Export-Funktion</strong> - Lade Backups als JSON-Datei herunter</li>
    </ul>
</div>

<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 15px; margin: 20px 0;">
    <h3 style="color: white; margin-bottom: 20px;">🆕 Neues Backup erstellen</h3>
    <form method="POST" style="display: flex; gap: 15px; align-items: flex-end;">
        <div style="flex: 1;">
            <label style="display: block; color: white; margin-bottom: 5px; font-weight: bold;">Backup-Name (optional):</label>
            <input type="text" name="backup_name" placeholder="z.B. Vor dem großen Kampf" 
                   style="width: 100%; padding: 12px; border: 2px solid white; border-radius: 8px; font-size: 14px;">
        </div>
        <button type="submit" name="create_backup" class="btn btn-success" style="padding: 12px 30px; white-space: nowrap;">
            💾 Backup erstellen
        </button>
    </form>
    <p style="color: white; margin-top: 10px; font-size: 13px;">
        Leer lassen für automatischen Namen mit aktuellem Datum/Uhrzeit
    </p>
</div>

<h3 style="margin-top: 40px;">📂 Meine Backups</h3>

<?php if(empty($backups)): ?>
    <div class="info-box">
        <p>Du hast noch keine Backups erstellt. Erstelle jetzt dein erstes Backup!</p>
    </div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Backup-Name</th>
                <th>Erstellt am</th>
                <th>Größe</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($backups as $b): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($b['backup_name']); ?></strong></td>
                    <td><?php echo date('d.m.Y H:i:s', strtotime($b['backup_date'])); ?></td>
                    <td><?php echo number_format($b['file_size'] / 1024, 2); ?> KB</td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Möchtest du diesen Spielstand wirklich wiederherstellen? Dein aktueller Fortschritt wird überschrieben!');">
                                <input type="hidden" name="backup_id" value="<?php echo $b['id']; ?>">
                                <button type="submit" name="restore_backup" class="btn btn-primary" style="padding: 8px 15px; font-size: 13px;">
                                    🔄 Wiederherstellen
                                </button>
                            </form>
                            
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="backup_id" value="<?php echo $b['id']; ?>">
                                <button type="submit" name="export_backup" class="btn btn-warning" style="padding: 8px 15px; font-size: 13px;">
                                    📥 Download
                                </button>
                            </form>
                            
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Möchtest du dieses Backup wirklich löschen?');">
                                <input type="hidden" name="backup_id" value="<?php echo $b['id']; ?>">
                                <button type="submit" name="delete_backup" class="btn btn-danger" style="padding: 8px 15px; font-size: 13px;">
                                    🗑️ Löschen
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<div class="info-box" style="margin-top: 30px; background: #fff3cd; border-left-color: #ffc107;">
    <h3>💡 Tipps für Backups</h3>
    <ul style="margin-top: 10px; line-height: 1.8;">
        <li><strong>Regelmäßige Backups:</strong> Erstelle Backups vor wichtigen Aktionen wie großen Kämpfen oder teuren Käufen</li>
        <li><strong>Beschreibende Namen:</strong> Vergib aussagekräftige Namen, um Backups später leicht zu finden</li>
        <li><strong>Export als Sicherung:</strong> Lade wichtige Backups als JSON-Datei herunter für zusätzliche Sicherheit</li>
        <li><strong>Alte Backups löschen:</strong> Lösche nicht mehr benötigte Backups, um Ordnung zu halten</li>
    </ul>
</div>
