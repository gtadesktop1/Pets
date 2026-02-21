<?php
// pages/rangliste.php
$all_pets = $pet->getAllPetsExceptUser(0); // Get all pets including current user's
?>

<h2>Rangliste</h2>

<div class="info-box">
    <p>Hier siehst du die stärksten Pets im Spiel, sortiert nach Level und Punkten.</p>
</div>

<?php if(empty($all_pets)): ?>
    <p>Es gibt noch keine Pets in der Rangliste.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Rang</th>
                <th>Pet</th>
                <th>Typ</th>
                <th>Besitzer</th>
                <th>Level</th>
                <th>Punkte</th>
                <th>Erfahrung</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $rank = 1;
            foreach($all_pets as $p): 
                $is_own = ($p['user_id'] == $_SESSION['user_id']);
            ?>
                <tr style="<?php echo $is_own ? 'background: #e8f5e9; font-weight: bold;' : ''; ?>">
                    <td><?php echo $rank++; ?></td>
                    <td>
                        <span style="font-size: 24px;"><?php echo $p['icon_emoji']; ?></span>
                        <?php echo htmlspecialchars($p['name']); ?>
                        <?php if($is_own): ?>
                            <span style="color: #4caf50;">★</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $p['type_name']; ?></td>
                    <td><?php echo htmlspecialchars($p['owner_name']); ?></td>
                    <td><?php echo $p['level']; ?></td>
                    <td><?php echo $p['points']; ?></td>
                    <td><?php echo $p['experience']; ?> EP</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h3 style="margin-top: 40px;">Benutzer-Rangliste</h3>
<?php
$user_ranking_query = "SELECT u.username, u.money, COUNT(p.id) as pet_count, 
                       COALESCE(SUM(p.level), 0) as total_level,
                       COALESCE(SUM(p.points), 0) as total_points
                       FROM users u
                       LEFT JOIN pets p ON u.id = p.user_id
                       GROUP BY u.id, u.username, u.money
                       ORDER BY total_points DESC, total_level DESC
                       LIMIT 20";
$user_ranking_stmt = $db->prepare($user_ranking_query);
$user_ranking_stmt->execute();
$user_rankings = $user_ranking_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<table>
    <thead>
        <tr>
            <th>Rang</th>
            <th>Spieler</th>
            <th>Geld</th>
            <th>Pets</th>
            <th>Gesamt-Level</th>
            <th>Gesamt-Punkte</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $user_rank = 1;
        foreach($user_rankings as $u): 
            $is_current_user = ($u['username'] == $_SESSION['username']);
        ?>
            <tr style="<?php echo $is_current_user ? 'background: #e3f2fd; font-weight: bold;' : ''; ?>">
                <td><?php echo $user_rank++; ?></td>
                <td>
                    <?php echo htmlspecialchars($u['username']); ?>
                    <?php if($is_current_user): ?>
                        <span style="color: #2196f3;">★</span>
                    <?php endif; ?>
                </td>
                <td><?php echo number_format($u['money'], 2); ?> €</td>
                <td><?php echo $u['pet_count']; ?></td>
                <td><?php echo $u['total_level']; ?></td>
                <td><?php echo $u['total_points']; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
