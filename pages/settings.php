<?php
// pages/settings.php - Mit Last Login
$userData = $user->getUserData();
$last_login = $user->getLastLogin();

// Get pet stats
$pet_stats_query = "SELECT 
                    COUNT(*) as total_pets,
                    SUM(level) as total_levels,
                    SUM(experience) as total_exp,
                    SUM(points) as total_points
                    FROM pets WHERE user_id = :user_id";
$stats_stmt = $db->prepare($pet_stats_query);
$stats_stmt->bindParam(":user_id", $_SESSION['user_id']);
$stats_stmt->execute();
$pet_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Count working pets
$working_query = "SELECT COUNT(*) as working FROM pets 
                  WHERE user_id = :user_id AND current_activity = 'arbeiten'";
$working_stmt = $db->prepare($working_query);
$working_stmt->bindParam(":user_id", $_SESSION['user_id']);
$working_stmt->execute();
$working_stats = $working_stmt->fetch(PDO::FETCH_ASSOC);
?>

<h2>⚙️ Einstellungen</h2>

<div class="card">
    <h3>👤 Benutzerinformationen</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 15px; font-weight: bold; width: 250px;">Benutzername:</td>
            <td style="padding: 15px;"><?php echo htmlspecialchars($userData['username']); ?></td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 15px; font-weight: bold;">E-Mail:</td>
            <td style="padding: 15px;"><?php echo htmlspecialchars($userData['email']); ?></td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 15px; font-weight: bold;">Account-Typ:</td>
            <td style="padding: 15px;">
                <?php 
                $account_type = $userData['account_type'] ?? 'FREE';
                $is_premium = $userData['is_premium'] ?? 0;
                echo $is_premium ? '⭐ PREMIUM' : '🆓 ' . $account_type; 
                ?>
            </td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 15px; font-weight: bold;">Cloud-Speicher:</td>
            <td style="padding: 15px;">
                <?php 
                $quota_gb = $userData['quota_gb'] ?? 20;
                $used_gb = round(($userData['used_bytes'] ?? 0) / 1024 / 1024 / 1024, 2);
                echo $used_gb . ' GB / ' . $quota_gb . ' GB';
                ?>
            </td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 15px; font-weight: bold;">Mitglied seit:</td>
            <td style="padding: 15px;">
                <?php 
                $created_at = $userData['created_at'] ?? $userData['game_started_at'];
                echo date('d.m.Y', strtotime($created_at)); 
                ?>
            </td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 15px; font-weight: bold;">Letzter Login:</td>
            <td style="padding: 15px;">
                <?php 
                if($last_login) {
                    echo date('d.m.Y H:i:s', strtotime($last_login));
                } else {
                    echo 'Nie';
                }
                ?>
            </td>
        </tr>
    </table>
</div>

<div class="card" style="margin-top: 20px;">
    <h3>💰 Finanzen</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 15px; font-weight: bold; width: 250px;">Aktuelles Guthaben:</td>
            <td style="padding: 15px; color: #4CAF50; font-weight: bold; font-size: 18px;">
                <?php echo number_format($userData['money'], 2); ?> €
            </td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 15px; font-weight: bold;">Einkommen pro Stunde:</td>
            <td style="padding: 15px;">
                <?php echo number_format($userData['income_per_hour'], 2); ?> €/Std
                <?php if($working_stats['working'] > 0): ?>
                    <span style="color: #4CAF50; font-size: 12px;">
                        (Basis: 8€ + <?php echo $working_stats['working']; ?> arbeitende<?php echo $working_stats['working'] > 1 ? ' Pets' : ' Pet'; ?>: <?php echo $working_stats['working'] * 10; ?>€)
                    </span>
                <?php endif; ?>
            </td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 15px; font-weight: bold;">Einkommen pro Tag:</td>
            <td style="padding: 15px;"><?php echo number_format($userData['income_per_day'], 2); ?> €/Tag</td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 15px; font-weight: bold;">Arbeitende Pets:</td>
            <td style="padding: 15px;"><?php echo $working_stats['working']; ?> Pet<?php echo $working_stats['working'] != 1 ? 's' : ''; ?></td>
        </tr>
    </table>
</div>

<div class="card" style="margin-top: 20px;">
    <h3>📊 Statistiken</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 15px; font-weight: bold; width: 250px;">Anzahl Pets:</td>
            <td style="padding: 15px;"><?php echo $pet_stats['total_pets'] ?? 0; ?></td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 15px; font-weight: bold;">Gesamte Level:</td>
            <td style="padding: 15px;"><?php echo $pet_stats['total_levels'] ?? 0; ?></td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 15px; font-weight: bold;">Gesamte Erfahrung:</td>
            <td style="padding: 15px;"><?php echo number_format($pet_stats['total_exp'] ?? 0); ?> EP</td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 15px; font-weight: bold;">Gesamte Punkte:</td>
            <td style="padding: 15px;"><?php echo number_format($pet_stats['total_points'] ?? 0); ?></td>
        </tr>
    </table>
</div>

<div class="info-box" style="margin-top: 30px;">
    <h3>🎮 Spielanleitung</h3>
    <p><strong>Aktivitäten:</strong></p>
    <ul style="line-height: 1.8;">
        <li><strong>Trainieren:</strong> +50 EP/Stunde, -5% Energie/Stunde</li>
        <li><strong>Spielen:</strong> +10% Freude/Stunde, -3% Energie/Stunde</li>
        <li><strong>Schlafen:</strong> +20% Energie/Stunde</li>
        <li><strong>Arbeiten:</strong> +10€/Stunde, -8% Energie/Stunde (erhöht dein Einkommen!)</li>
    </ul>
    
    <p style="margin-top: 20px;"><strong>Kampf-System:</strong></p>
    <ul style="line-height: 1.8;">
        <li><strong>Angriff:</strong> Kostet immer 10% Energie und 80% Freude</li>
        <li><strong>Sieg:</strong> +8 XP für dein Pet</li>
        <li><strong>Niederlage:</strong> -20% Energie (statt 10%), Verteidiger bekommt 8 XP</li>
        <li><strong>Auto-Schlaf:</strong> Bei 0 Energie wechselt dein Pet automatisch zu "Schlafen"</li>
    </ul>
    
    <p style="margin-top: 20px;"><strong>Leveln:</strong></p>
    <ul style="line-height: 1.8;">
        <li>Benötigte EP = Level × 100 + 30</li>
        <li>Level-Up gibt +10 Punkte</li>
    </ul>
</div>
