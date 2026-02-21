<?php
if(isset($_SESSION['battle_result'])) {
    $battle = $_SESSION['battle_result'];
    unset($_SESSION['battle_result']);
    
    if(!$battle['success']) {
        echo '<div style="background: #ffebee; color: #c62828; padding: 20px; border-radius: 10px; margin-bottom: 20px; border: 3px solid #f44336;">';
        echo '<h3>❌ Kampf nicht möglich</h3>';
        echo '<p>' . htmlspecialchars($battle['message']) . '</p>';
        echo '<a href="?page=arena" class="btn btn-primary">Zurück</a>';
        echo '</div>';
    } else {
        ?>
        <h2>⚔️ Kampf-Ergebnis</h2>
        <div style="background: <?php echo $battle['result'] == 'won' ? '#e8f5e9' : '#ffebee'; ?>; 
                    padding: 30px; border-radius: 15px; margin-bottom: 30px;
                    border: 3px solid <?php echo $battle['result'] == 'won' ? '#4caf50' : '#f44336'; ?>;">
            <h3 style="text-align: center; font-size: 32px; margin-bottom: 20px;">
                <?php echo $battle['result'] == 'won' ? '🏆 SIEG! 🏆' : '💔 NIEDERLAGE 💔'; ?>
            </h3>
            <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 20px; align-items: center;">
                <div style="text-align: center; background: white; padding: 20px; border-radius: 10px;">
                    <h4 style="color: #667eea;">⚔️ <?php echo htmlspecialchars($battle['attacker']['name']); ?></h4>
                    <p><strong>Level:</strong> <?php echo $battle['attacker']['level']; ?></p>
                    <div style="margin: 15px 0; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                        <strong style="color: #d32f2f;">Verloren:</strong><br>
                        <span style="color: #ff6b6b;">⚡ -<?php echo $battle['attacker']['energy_loss']; ?>% Energie</span><br>
                        <small>(<?php echo $battle['attacker']['energy_before']; ?>% → <?php echo $battle['attacker']['energy_after']; ?>%)</small><br>
                        <span style="color: #ff6b6b;">😢 -<?php echo $battle['attacker']['happiness_loss']; ?>% Freude</span>
                    </div>
                    <?php if($battle['attacker']['xp_gain'] > 0): ?>
                        <div style="background: #e8f5e9; padding: 10px; border-radius: 8px;">
                            <strong style="color: #4caf50;">+<?php echo $battle['attacker']['xp_gain']; ?> XP</strong>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="text-align: center; font-size: 48px; font-weight: bold;">VS</div>
                <div style="text-align: center; background: white; padding: 20px; border-radius: 10px;">
                    <h4 style="color: #f44336;">🛡️ <?php echo htmlspecialchars($battle['defender']['name']); ?></h4>
                    <p><strong>Level:</strong> <?php echo $battle['defender']['level']; ?></p>
                    <?php if($battle['defender']['energy_loss'] > 0): ?>
                        <div style="margin: 15px 0; padding: 15px; background: #ffebee; border-radius: 8px;">
                            <span style="color: #ff6b6b;">💥 -<?php echo $battle['damage']; ?> Energie</span>
                        </div>
                    <?php endif; ?>
                    <?php if($battle['defender']['xp_gain'] > 0): ?>
                        <div style="background: #e8f5e9; padding: 10px; border-radius: 8px;">
                            <strong style="color: #4caf50;">+<?php echo $battle['defender']['xp_gain']; ?> XP</strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    echo '<div style="text-align: center;"><a href="?page=arena" class="btn btn-primary">🔄 Neuer Kampf</a></div>';
    return;
}

$user_pets = $pet->getUserPets($_SESSION['user_id']);
$opponent_pets = $pet->getAllPetsExceptUser($_SESSION['user_id']);
?>

<h2>⚔️ Arena</h2>

<div class="info-box">
    <h3>⚔️ Kampf-Regeln</h3>
    <ul>
        <li><strong>Voraussetzung:</strong> Min. 10% Energie und 80% Freude</li>
        <li><strong>Kosten:</strong> 10% Energie + 80% Freude (immer)</li>
        <li><strong>Bei Sieg:</strong> +8 XP</li>
        <li><strong>Bei Niederlage:</strong> 20% Energie, Gegner +8 XP</li>
    </ul>
</div>

<?php if(empty($user_pets)): ?>
    <div class="info-box">
        <p>Du hast noch keine Pets!</p>
        <a href="?page=shop&do=showpets" class="btn btn-primary">Zum Shop</a>
    </div>
<?php else: ?>
    <?php foreach($user_pets as $my_pet): 
        $can_battle = $pet->canBattle($my_pet['id']);
        ?>
        <div style="background: white; border: 2px solid <?php echo $can_battle ? '#667eea' : '#999'; ?>; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <span style="font-size: 48px;"><?php echo $my_pet['icon_emoji']; ?></span>
                    <strong style="font-size: 24px;"><?php echo htmlspecialchars($my_pet['name']); ?></strong>
                    <span style="color: #666;">Level <?php echo $my_pet['level']; ?></span>
                </div>
                <div>
                    <div>⚡ <?php echo $my_pet['energy']; ?>%</div>
                    <div>😊 <?php echo $my_pet['happiness']; ?>%</div>
                </div>
            </div>

            <?php if(!$can_battle): ?>
                <div style="background: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin: 15px 0;">
                    ⚠️ <strong>Nicht kampfbereit!</strong> Braucht min. 10% Energie und 80% Freude.
                </div>
            <?php else: ?>
                <h4 style="margin-top: 20px;">Wähle Gegner:</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                    <?php foreach($opponent_pets as $opp): ?>
                        <div style="background: #f5f5f5; padding: 15px; border-radius: 8px;">
                            <div style="text-align: center; font-size: 32px;"><?php echo $opp['icon_emoji']; ?></div>
                            <strong><?php echo htmlspecialchars($opp['name']); ?></strong><br>
                            <small>Besitzer: <?php echo htmlspecialchars($opp['owner_name']); ?></small><br>
                            <small>Level <?php echo $opp['level']; ?></small>
                            <form method="POST" style="margin-top: 10px;">
                                <input type="hidden" name="attacker_id" value="<?php echo $my_pet['id']; ?>">
                                <input type="hidden" name="defender_id" value="<?php echo $opp['id']; ?>">
                                <button type="submit" name="battle" class="btn btn-danger" style="width: 100%;">⚔️ Angreifen</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
