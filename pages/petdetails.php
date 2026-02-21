<?php
// pages/petdetails.php
if(!isset($_GET['id'])) {
    header("Location: ?page=home");
    exit();
}

$pet_id = $_GET['id'];
$petData = $pet->getPetById($pet_id);

if(!$petData || $petData['user_id'] != $_SESSION['user_id']) {
    echo "<p>Pet nicht gefunden oder keine Berechtigung.</p>";
    return;
}

$pet->updateActivity($pet_id);
$petData = $pet->getPetById($pet_id);

$ep_needed = $petData['level'] * 100 + 30;
?>

<h2>Details: <?php echo htmlspecialchars($petData['name']); ?></h2>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; margin-top: 20px;">
    <div>
        <div style="text-align: center; font-size: 120px; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 40px; border-radius: 15px;">
            <?php echo $petData['icon_emoji']; ?>
        </div>
        
        <div style="margin-top: 20px; background: #f9f9f9; padding: 20px; border-radius: 10px;">
            <h3>Grundinformationen</h3>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($petData['name']); ?></p>
            <p><strong>Typ:</strong> <?php echo $petData['type_name']; ?></p>
            <p><strong>Level:</strong> <?php echo $petData['level']; ?></p>
            <p><strong>Punkte:</strong> <?php echo $petData['points']; ?></p>
            <p><strong>Erstellt am:</strong> <?php echo date('d.m.Y H:i', strtotime($petData['created_at'])); ?></p>
        </div>
    </div>

    <div>
        <div class="pet-card">
            <h3>Statistiken</h3>
            
            <div style="margin: 20px 0;">
                <div><strong>Energie:</strong></div>
                <div class="stat-bar">
                    <div class="stat-fill energy-fill" style="width: <?php echo $petData['energy']; ?>%;">
                        <?php echo $petData['energy']; ?>%
                    </div>
                </div>

                <div><strong>Freude:</strong></div>
                <div class="stat-bar">
                    <div class="stat-fill happiness-fill" style="width: <?php echo $petData['happiness']; ?>%;">
                        <?php echo $petData['happiness']; ?>%
                    </div>
                </div>

                <div><strong>Erfahrung:</strong> <?php echo $petData['experience']; ?> EP</div>
                <div class="stat-bar">
                    <div class="stat-fill" style="width: <?php echo min(100, ($petData['experience'] / $ep_needed) * 100); ?>%;">
                        <?php echo min(100, round(($petData['experience'] / $ep_needed) * 100)); ?>%
                    </div>
                </div>
                
                <p style="margin-top: 10px;">
                    <strong>Nächstes Level:</strong> <?php echo $ep_needed; ?> EP<br>
                    <strong>Noch benötigt:</strong> <?php echo max(0, $ep_needed - $petData['experience']); ?> EP
                </p>

                <?php if($petData['experience'] >= $ep_needed): ?>
                    <form method="POST" style="margin-top: 10px;">
                        <input type="hidden" name="pet_id" value="<?php echo $petData['id']; ?>">
                        <button type="submit" name="upgrade_pet" class="btn btn-success" style="width: 100%;">
                            🎉 Level Up! (Kostet <?php echo $ep_needed; ?> EP)
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="pet-card" style="margin-top: 20px;">
            <h3>Aktuelle Aktivität</h3>
            
            <p style="font-size: 18px; color: #667eea; font-weight: bold; margin: 15px 0;">
                <?php 
                $activities = [
                    'nichts' => '😴 Nichts',
                    'trainieren' => '💪 Trainieren (50 EP/Std)',
                    'spielen' => '🎮 Spielen (+10% Freude/Std)',
                    'schlafen' => '😴 Schlafen (+20% Energie/Std)',
                    'arbeiten' => '💼 Arbeiten (10€/Std)'
                ];
                echo $activities[$petData['current_activity']] ?? 'Unbekannt';
                ?>
            </p>

            <?php if($petData['activity_started_at']): ?>
                <p><strong>Gestartet:</strong> <?php echo date('d.m.Y H:i', strtotime($petData['activity_started_at'])); ?></p>
                <p><strong>Dauer:</strong> 
                    <?php 
                    $start = strtotime($petData['activity_started_at']);
                    $now = time();
                    $duration = $now - $start;
                    $hours = floor($duration / 3600);
                    $minutes = floor(($duration % 3600) / 60);
                    echo $hours . "h " . $minutes . "m";
                    ?>
                </p>
            <?php endif; ?>

            <form method="POST" style="margin-top: 20px;">
                <input type="hidden" name="pet_id" value="<?php echo $petData['id']; ?>">
                <select name="activity" class="btn btn-primary" style="width: 100%; margin-bottom: 10px; text-align: center;">
                    <option value="nichts" <?php echo $petData['current_activity'] == 'nichts' ? 'selected' : ''; ?>>😴 Nichts</option>
                    <option value="trainieren" <?php echo $petData['current_activity'] == 'trainieren' ? 'selected' : ''; ?>>💪 Trainieren</option>
                    <option value="spielen" <?php echo $petData['current_activity'] == 'spielen' ? 'selected' : ''; ?>>🎮 Spielen</option>
                    <option value="schlafen" <?php echo $petData['current_activity'] == 'schlafen' ? 'selected' : ''; ?>>😴 Schlafen</option>
                    <option value="arbeiten" <?php echo $petData['current_activity'] == 'arbeiten' ? 'selected' : ''; ?>>💼 Arbeiten</option>
                </select>
                <button type="submit" name="set_activity" class="btn btn-primary" style="width: 100%;">
                    Aktivität ändern
                </button>
            </form>
        </div>

        <div class="pet-card" style="margin-top: 20px;">
            <h3>Füttern</h3>
            <?php
            $inventory = $shop->getUserInventory($_SESSION['user_id']);
            $food_inventory = array_filter($inventory, function($item) {
                return $item['food_item_id'] != null;
            });

            if(empty($food_inventory)):
            ?>
                <p>Kein Futter im Futterkorb. <a href="?page=shop" style="color: #667eea; font-weight: bold;">Zum Shop</a></p>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="pet_id" value="<?php echo $petData['id']; ?>">
                    <select name="food_id" required style="width: 100%; padding: 10px; margin: 10px 0; border: 2px solid #ddd; border-radius: 8px;">
                        <option value="">Futter auswählen</option>
                        <?php foreach($food_inventory as $food): ?>
                            <option value="<?php echo $food['food_item_id']; ?>">
                                <?php echo $food['food_icon'] . ' ' . $food['food_name']; ?> (<?php echo $food['quantity']; ?>x)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="feed_pet" class="btn btn-success" style="width: 100%;">
                        🍽️ Füttern
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div style="margin-top: 20px;">
            <a href="?page=home" class="btn btn-primary" style="display: inline-block; text-decoration: none;">
                ← Zurück zu meinen Pets
            </a>
        </div>
    </div>
</div>
