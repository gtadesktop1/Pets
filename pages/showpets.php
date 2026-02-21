<?php
// pages/showpets.php
$pet_types = $shop->getAllPetTypes();
?>

<h2>Neues - Pet kaufen</h2>

<div class="info-box">
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
