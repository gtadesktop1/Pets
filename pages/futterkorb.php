<?php
// pages/futterkorb.php
$inventory = $shop->getUserInventory($_SESSION['user_id']);
$userPets = $pet->getUserPets($_SESSION['user_id']);
?>

<h2>Futterkorb</h2>

<?php if(empty($inventory)): ?>
    <div class="info-box">
        <p>Dein Futterkorb ist leer. Kaufe Futter im <a href="?page=shop" style="color: #667eea; font-weight: bold;">Shop</a>!</p>
    </div>
<?php else: ?>
    <div class="shop-grid">
        <?php foreach($inventory as $item): 
            if($item['food_item_id']):
        ?>
            <div class="shop-item">
                <div class="shop-item-icon"><?php echo $item['food_icon']; ?></div>
                <h4><?php echo $item['food_name']; ?></h4>
                <p>Anzahl: <?php echo $item['quantity']; ?></p>
                
                <?php if(!empty($userPets)): ?>
                    <form method="POST">
                        <input type="hidden" name="food_id" value="<?php echo $item['food_item_id']; ?>">
                        <select name="pet_id" required 
                                style="width: 100%; padding: 8px; margin: 10px 0; border: 2px solid #ddd; border-radius: 5px;">
                            <option value="">Pet auswählen</option>
                            <?php foreach($userPets as $p): ?>
                                <option value="<?php echo $p['id']; ?>">
                                    <?php echo htmlspecialchars($p['name']); ?> (<?php echo $p['energy']; ?>% Energie)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="feed_pet" class="btn btn-success" style="width: 100%;">
                            Füttern
                        </button>
                    </form>
                <?php else: ?>
                    <p style="color: #999; font-style: italic;">Keine Pets vorhanden</p>
                <?php endif; ?>
            </div>
        <?php 
            endif;
        endforeach; ?>
    </div>
<?php endif; ?>

<?php
$shop_inventory = array_filter($inventory, function($item) {
    return $item['shop_item_id'] != null;
});

if(!empty($shop_inventory)):
?>
    <h3 style="margin-top: 40px;">Deine Tools</h3>
    <div class="shop-grid">
        <?php foreach($shop_inventory as $item): ?>
            <div class="shop-item">
                <div class="shop-item-icon"><?php echo $item['shop_icon']; ?></div>
                <h4><?php echo $item['shop_name']; ?></h4>
                <p>Anzahl: <?php echo $item['quantity']; ?></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
