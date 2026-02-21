<?php
// pages/cloud.php - Cloud Backup Verwaltung

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Backup erstellen
if(isset($_POST['create_backup'])) {
    $backup_name = $_POST['backup_name'];
    if(empty($backup_name)) {
        $backup_name = "Backup " . date('d.m.Y H:i');
    }
    
    if($user->createBackup($backup_name)) {
        $success_message = "Spielstand erfolgreich in der Cloud gespeichert!";
    } else {
        $error_message = "Fehler beim Speichern des Spielstands!";
    }
}

// Backup laden
if(isset($_POST['restore_backup'])) {
    $backup_id = $_POST['backup_id'];
    
    if($user->restoreBackup($backup_id)) {
        $success_message = "Spielstand erfolgreich wiederhergestellt!";
        // Seite neu laden
        header("Location: ?page=cloud&restored=1");
        exit();
    } else {
        $error_message = "Fehler beim Wiederherstellen des Spielstands!";
    }
}

// Backup löschen
if(isset($_POST['delete_backup'])) {
    $backup_id = $_POST['backup_id'];
    
    if($user->deleteBackup($backup_id)) {
        $success_message = "Backup erfolgreich gelöscht!";
    } else {
        $error_message = "Fehler beim Löschen des Backups!";
    }
}

$backups = $user->getBackups();
?>

<h2>☁️ Cloud Backup System</h2>

<?php if(isset($_GET['restored'])): ?>
    <div class="success" style="padding: 15px; margin: 20px 0; background: #d4edda; border: 2px solid #28a745; border-radius: 10px; color: #155724;">
        <strong>✓ Spielstand erfolgreich wiederhergestellt!</strong>
        <p>Dein Spielstand wurde aus der Cloud geladen.</p>
    </div>
<?php endif; ?>

<?php if(isset($success_message)): ?>
    <div class="success" style="padding: 15px; margin: 20px 0; background: #d4edda; border: 2px solid #28a745; border-radius: 10px; color: #155724;">
        <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if(isset($error_message)): ?>
    <div class="error" style="padding: 15px; margin: 20px 0; background: #f8d7da; border: 2px solid #dc3545; border-radius: 10px; color: #721c24;">
        <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<div class="info-box" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
    <h3 style="color: white;">Cloud-Integration aktiviert!</h3>
    <p>Dein PETS-Spielstand ist mit deinem Cloud-Account verbunden.</p>
    <p><strong>Benutzername:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
    <p><strong>Account-Typ:</strong> <?php echo $userData['account_type'] ?? 'FREE'; ?></p>
</div>

<!-- Neues Backup erstellen -->
<div class="pet-card" style="margin: 30px 0;">
    <h3>💾 Spielstand speichern</h3>
    <p>Erstelle ein Backup deines aktuellen Spielstands in der Cloud.</p>
    
    <form method="POST" style="margin-top: 20px;">
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Backup-Name:</label>
            <input type="text" name="backup_name" 
                   placeholder="z.B. Mein erster Spielstand"
                   style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px;">
            <small style="color: #666;">Leer lassen für automatischen Namen</small>
        </div>
        
        <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin: 15px 0;">
            <h4 style="margin: 0 0 10px 0;">Was wird gespeichert:</h4>
            <ul style="margin: 0; padding-left: 20px;">
                <li>💰 Kontostand (<?php echo number_format($userData['money'], 2); ?> €)</li>
                <li>🐾 Alle deine Pets (<?php echo $petCount; ?> Stück)</li>
                <li>🎒 Inventar & Futterkorb</li>
                <li>📊 Level, Erfahrung und Statistiken</li>
            </ul>
        </div>
        
        <button type="submit" name="create_backup" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 16px;">
            💾 Jetzt in Cloud speichern
        </button>
    </form>
</div>

<!-- Vorhandene Backups -->
<h3 style="margin-top: 40px;">📦 Gespeicherte Spielstände</h3>

<?php if(empty($backups)): ?>
    <div class="info-box">
        <p>Du hast noch keine Backups in der Cloud gespeichert.</p>
        <p>Erstelle dein erstes Backup, um deinen Spielstand zu sichern!</p>
    </div>
<?php else: ?>
    <p style="margin-bottom: 20px;">Du hast <?php echo count($backups); ?> Backup(s) in der Cloud gespeichert.</p>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        <?php foreach($backups as $backup): ?>
            <div class="pet-card">
                <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 15px;">
                    <div>
                        <h4 style="margin: 0 0 10px 0;">📦 <?php echo htmlspecialchars($backup['backup_name']); ?></h4>
                        <p style="margin: 5px 0; font-size: 13px; color: #666;">
                            <strong>Erstellt:</strong> <?php echo date('d.m.Y H:i', strtotime($backup['backup_date'])); ?>
                        </p>
                        <p style="margin: 5px 0; font-size: 13px; color: #666;">
                            <strong>Größe:</strong> <?php echo round($backup['file_size'] / 1024, 2); ?> KB
                        </p>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px;">
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="backup_id" value="<?php echo $backup['id']; ?>">
                        <button type="submit" name="restore_backup" class="btn btn-success" 
                                style="width: 100%;"
                                onclick="return confirm('Möchtest du diesen Spielstand wirklich laden? Dein aktueller Fortschritt wird überschrieben!');">
                            ⬇️ Laden
                        </button>
                    </form>
                    
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="backup_id" value="<?php echo $backup['id']; ?>">
                        <button type="submit" name="delete_backup" class="btn btn-danger" 
                                style="width: 100%;"
                                onclick="return confirm('Backup wirklich löschen?');">
                            🗑️ Löschen
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Info-Bereich -->
<div class="info-box" style="margin-top: 40px; background: #fff3cd; border-left: 4px solid #ffc107;">
    <h3>ℹ️ Wichtige Hinweise</h3>
    <ul style="padding-left: 20px; margin: 10px 0;">
        <li><strong>Automatische Speicherung:</strong> Dein Spielstand wird NICHT automatisch gespeichert. Du musst manuell Backups erstellen.</li>
        <li><strong>Überschreiben:</strong> Beim Laden eines Backups wird dein aktueller Spielstand überschrieben!</li>
        <li><strong>Cloud-Integration:</strong> Alle Backups sind mit deinem Cloud-Account verknüpft.</li>
        <li><strong>Speicherplatz:</strong> Backups zählen zu deinem Cloud-Speicher (<?php echo $userData['quota_gb'] ?? 20; ?> GB verfügbar).</li>
        <li><strong>Sicherheit:</strong> Deine Spielstände sind sicher in der Cloud gespeichert und können jederzeit wiederhergestellt werden.</li>
    </ul>
</div>

<!-- Statistiken -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px;">
    <div style="background: #e3f2fd; padding: 20px; border-radius: 10px; text-align: center;">
        <div style="font-size: 36px; margin-bottom: 10px;">💾</div>
        <div style="font-size: 24px; font-weight: bold; color: #1976d2;"><?php echo count($backups); ?></div>
        <div style="color: #666; font-size: 14px;">Gespeicherte Backups</div>
    </div>
    
    <div style="background: #e8f5e9; padding: 20px; border-radius: 10px; text-align: center;">
        <div style="font-size: 36px; margin-bottom: 10px;">🐾</div>
        <div style="font-size: 24px; font-weight: bold; color: #388e3c;"><?php echo $petCount; ?></div>
        <div style="color: #666; font-size: 14px;">Aktive Pets</div>
    </div>
    
    <div style="background: #fff3e0; padding: 20px; border-radius: 10px; text-align: center;">
        <div style="font-size: 36px; margin-bottom: 10px;">💰</div>
        <div style="font-size: 24px; font-weight: bold; color: #f57c00;"><?php echo number_format($userData['money'], 0); ?> €</div>
        <div style="color: #666; font-size: 14px;">Kontostand</div>
    </div>
    
    <div style="background: #f3e5f5; padding: 20px; border-radius: 10px; text-align: center;">
        <div style="font-size: 36px; margin-bottom: 10px;">☁️</div>
        <div style="font-size: 24px; font-weight: bold; color: #7b1fa2;"><?php echo $userData['account_type'] ?? 'FREE'; ?></div>
        <div style="color: #666; font-size: 14px;">Account-Typ</div>
    </div>
</div>
