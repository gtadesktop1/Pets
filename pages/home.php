<?php
// pages/home.php
$userPets = $pet->getUserPets($_SESSION['user_id']);

// Update all pets' activities
foreach($userPets as $p) {
    $pet->updateActivity($p['id']);
}

// Refresh pets data after update
$userPets = $pet->getUserPets($_SESSION['user_id']);

if(empty($userPets)):
?>
    <div class="info-box">
        <h3>Du besitzt noch keine Pets.</h3>
        <p>Bitte klicke <a href="?page=shop" style="color: #667eea; font-weight: bold;">hier</a>, um dir jetzt dein erstes Pet zuzulegen.</p>
    </div>
<?php else: ?>
    <h2>Deine Pets</h2>
    <div class="pet-grid">
        <?php foreach($userPets as $p): 
            $ep_needed = $p['level'] * 100 + 30;
        ?>
            <div class="pet-card">
                <div class="pet-header">
                    <div class="pet-icon"><?php echo $p['icon_emoji']; ?></div>
                    <div class="pet-info">
                        <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                        <p><?php echo $p['type_name']; ?> | Level <?php echo $p['level']; ?></p>
                    </div>
                </div>

                <div class="stats">
                    <div><strong>Energie:</strong></div>
                    <div class="stat-bar">
                        <div class="stat-fill energy-fill" style="width: <?php echo $p['energy']; ?>%;">
                            <?php echo $p['energy']; ?>%
                        </div>
                    </div>

                    <div><strong>Freude:</strong></div>
                    <div class="stat-bar">
                        <div class="stat-fill happiness-fill" style="width: <?php echo $p['happiness']; ?>%;">
                            <?php echo $p['happiness']; ?>%
                        </div>
                    </div>

                    <div><strong>Erfahrung:</strong> <?php echo $p['experience']; ?> EP</div>
                    <div class="stat-bar">
                        <div class="stat-fill" style="width: <?php echo min(100, ($p['experience'] / $ep_needed) * 100); ?>%;">
                            <?php echo min(100, round(($p['experience'] / $ep_needed) * 100)); ?>%
                        </div>
                    </div>
                    
                    <div style="margin-top: 10px;">
                        <strong>Nächstes Level:</strong> <?php echo $ep_needed; ?> EP<br>
                        <strong>Punkte:</strong> <?php echo $p['points']; ?>
                    </div>

                    <?php if($p['experience'] >= $ep_needed): ?>
                        <form method="POST" style="margin-top: 10px;">
                            <input type="hidden" name="pet_id" value="<?php echo $p['id']; ?>">
                            <button type="submit" name="upgrade_pet" class="btn btn-success" style="width: 100%;">
                                Level Up! (Kostet <?php echo $ep_needed; ?> EP)
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="activity-section">
                    <strong>Aktivität:</strong> 
                    <span style="color: <?php echo $p['current_activity'] == 'nichts' ? '#999' : '#4CAF50'; ?>; font-weight: bold;">
                        <?php 
                        $activities = [
                            'nichts' => 'Nichts',
                            'trainieren' => 'Trainieren (50 EP/Std)',
                            'spielen' => 'Spielen',
                            'schlafen' => 'Schlafen',
                            'arbeiten' => 'Arbeiten (10€/Std)'
                        ];
                        echo $activities[$p['current_activity']] ?? 'Unbekannt';
                        ?>
                    </span>

                    <form method="POST" style="margin-top: 10px;">
                        <input type="hidden" name="pet_id" value="<?php echo $p['id']; ?>">
                        <select name="activity" class="btn btn-primary" style="width: 100%; margin-bottom: 10px; text-align: center;">
                            <option value="nichts" <?php echo $p['current_activity'] == 'nichts' ? 'selected' : ''; ?>>Nichts</option>
                            <option value="trainieren" <?php echo $p['current_activity'] == 'trainieren' ? 'selected' : ''; ?>>Trainieren</option>
                            <option value="spielen" <?php echo $p['current_activity'] == 'spielen' ? 'selected' : ''; ?>>Spielen</option>
                            <option value="schlafen" <?php echo $p['current_activity'] == 'schlafen' ? 'selected' : ''; ?>>Schlafen</option>
                            <option value="arbeiten" <?php echo $p['current_activity'] == 'arbeiten' ? 'selected' : ''; ?>>Arbeiten</option>
                        </select>
                        <button type="submit" name="set_activity" class="btn btn-primary" style="width: 100%;">
                            Aktivität ändern
                        </button>
                    </form>
                </div>

                <div style="margin-top: 15px;">
                    <a href="?page=petdetails&id=<?php echo $p['id']; ?>" class="btn btn-primary" style="display: block; text-align: center; text-decoration: none;">
                        Details anzeigen
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
