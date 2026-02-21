<?php
// pages/battle.php
$userPets = $pet->getUserPets($_SESSION['user_id']);
$otherPets = $pet->getAllPetsExceptUser($_SESSION['user_id']);

if(isset($battle_result)):
?>
    <div class="battle-result">
        <h3><?php echo htmlspecialchars($battle_result['attacker']['name']); ?> vs <?php echo htmlspecialchars($battle_result['defender']['name']); ?></h3>
        
        <table style="margin: 20px 0;">
            <tr>
                <th></th>
                <th><?php echo htmlspecialchars($battle_result['attacker']['name']); ?></th>
                <th><?php echo htmlspecialchars($battle_result['defender']['name']); ?></th>
            </tr>
            <tr>
                <td><strong>Level</strong></td>
                <td><?php echo $battle_result['attacker']['level']; ?></td>
                <td><?php echo $battle_result['defender']['level']; ?></td>
            </tr>
            <tr>
                <td><strong>EP</strong></td>
                <td><?php echo $battle_result['attacker']['experience']; ?></td>
                <td><?php echo $battle_result['defender']['experience']; ?></td>
            </tr>
            <tr>
                <td><strong>Energie</strong></td>
                <td><?php echo $battle_result['attacker']['energy']; ?>%</td>
                <td><?php echo $battle_result['defender']['energy']; ?>%</td>
            </tr>
            <tr>
                <td><strong>Erfolgsquote</strong></td>
                <td><?php echo $battle_result['success_rate']; ?>%</td>
                <td><?php echo 100 - $battle_result['success_rate']; ?>%</td>
            </tr>
        </table>

        <?php if($battle_result['success']): ?>
            <p style="color: #4CAF50; font-size: 18px; font-weight: bold;">
                ✓ Der Angriff war ein Erfolg! Du hast <?php echo htmlspecialchars($battle_result['defender']['name']); ?> 
                <?php echo $battle_result['damage']; ?> Schaden zugefügt!
            </p>
        <?php else: ?>
            <p style="color: #f44336; font-size: 18px; font-weight: bold;">
                ✗ Der Angriff ist fehlgeschlagen!
            </p>
        <?php endif; ?>

        <a href="?page=battle" class="btn btn-primary" style="display: inline-block; text-decoration: none; margin-top: 10px;">
            Weiter
        </a>
    </div>
<?php endif; ?>

<h2>Bugtracker / Kampf-Arena</h2>

<?php if(empty($userPets)): ?>
    <div class="info-box">
        <p>Du musst mindestens ein Pet besitzen, um kämpfen zu können.</p>
    </div>
<?php elseif(empty($otherPets)): ?>
    <div class="info-box">
        <p>Es gibt momentan keine anderen Pets zum Bekämpfen.</p>
    </div>
<?php else: ?>
    <div class="info-box">
        <p>Wähle eines deiner Pets aus und greife andere Pets an, um Erfahrungspunkte zu sammeln!</p>
    </div>

    <h3>Deine Pets</h3>
    <div class="shop-grid">
        <?php foreach($userPets as $p): ?>
            <div class="shop-item">
                <div class="shop-item-icon"><?php echo $p['icon_emoji']; ?></div>
                <h4><?php echo htmlspecialchars($p['name']); ?></h4>
                <p>Level: <?php echo $p['level']; ?> | EP: <?php echo $p['experience']; ?></p>
                <p>Energie: <?php echo $p['energy']; ?>%</p>
                <a href="#gegner_<?php echo $p['id']; ?>" class="btn btn-danger" style="display: block; text-decoration: none;">
                    Gegner wählen
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <?php foreach($userPets as $p): ?>
        <div id="gegner_<?php echo $p['id']; ?>" style="margin-top: 40px;">
            <h3>Gegner für <?php echo htmlspecialchars($p['name']); ?></h3>
            <div class="shop-grid">
                <?php foreach($otherPets as $opponent): ?>
                    <div class="shop-item">
                        <div class="shop-item-icon"><?php echo $opponent['icon_emoji']; ?></div>
                        <h4><?php echo htmlspecialchars($opponent['name']); ?></h4>
                        <p>Besitzer: <?php echo htmlspecialchars($opponent['owner_name']); ?></p>
                        <p>Level: <?php echo $opponent['level']; ?> | Energie: <?php echo $opponent['energy']; ?>%</p>
                        
                        <form method="POST">
                            <input type="hidden" name="attacker_id" value="<?php echo $p['id']; ?>">
                            <input type="hidden" name="defender_id" value="<?php echo $opponent['id']; ?>">
                            <button type="submit" name="battle" class="btn btn-danger" style="width: 100%;">
                                Angreifen!
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
