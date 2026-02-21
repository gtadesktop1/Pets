<?php
// pages/shop.php
$pet_types = $shop->getAllPetTypes();
$food_items = $shop->getAllFoodItems();
$shop_items = $shop->getAllShopItems();
?>

<h2>Shop</h2>

<?php if(isset($_GET['do']) && $_GET['do'] == 'showpets'): ?>
    <div class="info-box">
        <h3>Kaufe dein erstes Pet!</h3>
        <p>Wähle eine Tierart und gib deinem neuen Freund einen Namen.</p>
    </div>

    <h3>Verfügbare Haustiere</h3>
    <div class="shop-grid">
        <?php foreach($pet_types as $type): ?>
            <div class="shop-item">
                <div class="shop-item-icon"><?php echo $type['icon_emoji']; ?></div>
                <h4><?php echo $type['name']; ?></h4>
                <p><?php echo $type['description']; ?></p>
                <div class="price"><?php echo number_format($type['base_price'], 2); ?> €</div>
                
                <?php if($userData['money'] >= $type['base_price']): ?>
                    <form method="POST">
                        <input type="hidden" name="pet_type_id" value="<?php echo $type['id']; ?>">
                        <input type="text" name="pet_name" placeholder="Name des Pets" required 
                               style="width: 100%; padding: 8px; margin: 10px 0; border: 2px solid #ddd; border-radius: 5px;">
                        <button type="submit" name="buy_pet" class="btn btn-success" style="width: 100%;">
                            Kaufen
                        </button>
                    </form>
                <?php else: ?>
                    <button class="btn" style="width: 100%; background: #ccc; cursor: not-allowed;">
                        Zu teuer
                    </button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

<?php else: ?>
    <div class="info-box">
        <p>Hier hast du die Möglichkeit, Futter für deine Pets zu kaufen, um sie später damit zu füttern. 
        Mit der Zeit kannst du immer mehr Nahrung freischalten, welche immer effektiver sein wird.</p>
    </div>

    <h3>Tierfutter</h3>
    <div class="shop-grid">
        <?php foreach($food_items as $food): ?>
            <div class="shop-item">
                <div class="shop-item-icon"><?php echo $food['icon_emoji']; ?></div>
                <h4><?php echo $food['name']; ?></h4>
                <p>Energie: +<?php echo $food['energy_boost']; ?>% | Freude: +<?php echo $food['happiness_boost']; ?>%</p>
                <div class="price"><?php echo number_format($food['price'], 2); ?> €</div>
                
                <?php if($userData['money'] >= $food['price']): ?>
                    <form method="POST">
                        <input type="hidden" name="food_id" value="<?php echo $food['id']; ?>">
                        <input type="number" name="quantity" value="1" min="1" max="99" 
                               style="width: 100%; padding: 8px; margin: 10px 0; border: 2px solid #ddd; border-radius: 5px;">
                        <button type="submit" name="buy_food" class="btn btn-success" style="width: 100%;">
                            Kaufen
                        </button>
                    </form>
                <?php else: ?>
                    <button class="btn" style="width: 100%; background: #ccc; cursor: not-allowed;">
                        Zu teuer
                    </button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <h3 style="margin-top: 40px;">Tools</h3>
    <div class="shop-grid">
        <?php foreach($shop_items as $item): ?>
            <div class="shop-item">
                <div class="shop-item-icon"><?php echo $item['icon_emoji']; ?></div>
                <h4><?php echo $item['name']; ?></h4>
                <p><?php echo $item['description']; ?></p>
                <div class="price"><?php echo number_format($item['price'], 2); ?> €</div>
                
                <?php if($userData['money'] >= $item['price']): ?>
                    <form method="POST">
                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                        <button type="submit" name="buy_item" class="btn btn-warning" style="width: 100%;">
                            Kaufen
                        </button>
                    </form>
                <?php else: ?>
                    <button class="btn" style="width: 100%; background: #ccc; cursor: not-allowed;">
                        Zu teuer
                    </button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
