<?php
// classes/Shop.php - Mit Geld-Prüfung
class Shop {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllFoodItems() {
        $query = "SELECT * FROM food_items ORDER BY price ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllShopItems() {
        $query = "SELECT * FROM shop_items ORDER BY price ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllPetTypes() {
        $query = "SELECT * FROM pet_types ORDER BY base_price ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buyFood($user_id, $food_id, $quantity = 1) {
        // Hole Food-Info
        $food_query = "SELECT * FROM food_items WHERE id = :id";
        $food_stmt = $this->conn->prepare($food_query);
        $food_stmt->bindParam(":id", $food_id);
        $food_stmt->execute();
        $food = $food_stmt->fetch(PDO::FETCH_ASSOC);

        if(!$food) return false;

        $total_price = $food['price'] * $quantity;

        // Prüfe Geld
        $money_query = "SELECT money FROM user_game_data WHERE user_id = :user_id";
        $money_stmt = $this->conn->prepare($money_query);
        $money_stmt->bindParam(":user_id", $user_id);
        $money_stmt->execute();
        $user_data = $money_stmt->fetch(PDO::FETCH_ASSOC);

        if($user_data['money'] < $total_price) {
            return false; // Nicht genug Geld
        }

        try {
            $this->conn->beginTransaction();

            // Abbuchen
            $update_money = "UPDATE user_game_data 
                            SET money = money - :price 
                            WHERE user_id = :user_id";
            $money_stmt = $this->conn->prepare($update_money);
            $money_stmt->bindParam(":price", $total_price);
            $money_stmt->bindParam(":user_id", $user_id);
            $money_stmt->execute();

            // Prüfe ob schon im Inventar
            $check_inv = "SELECT id, quantity FROM user_inventory 
                         WHERE user_id = :user_id AND food_item_id = :food_id";
            $check_stmt = $this->conn->prepare($check_inv);
            $check_stmt->bindParam(":user_id", $user_id);
            $check_stmt->bindParam(":food_id", $food_id);
            $check_stmt->execute();

            if($check_stmt->rowCount() > 0) {
                // Update Menge
                $update_inv = "UPDATE user_inventory 
                              SET quantity = quantity + :quantity 
                              WHERE user_id = :user_id AND food_item_id = :food_id";
                $inv_stmt = $this->conn->prepare($update_inv);
                $inv_stmt->bindParam(":quantity", $quantity);
                $inv_stmt->bindParam(":user_id", $user_id);
                $inv_stmt->bindParam(":food_id", $food_id);
                $inv_stmt->execute();
            } else {
                // Neu einfügen
                $insert_inv = "INSERT INTO user_inventory (user_id, food_item_id, quantity) 
                              VALUES (:user_id, :food_id, :quantity)";
                $inv_stmt = $this->conn->prepare($insert_inv);
                $inv_stmt->bindParam(":user_id", $user_id);
                $inv_stmt->bindParam(":food_id", $food_id);
                $inv_stmt->bindParam(":quantity", $quantity);
                $inv_stmt->execute();
            }

            $this->conn->commit();
            return true;

        } catch(Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function buyShopItem($user_id, $item_id) {
        $item_query = "SELECT * FROM shop_items WHERE id = :id";
        $item_stmt = $this->conn->prepare($item_query);
        $item_stmt->bindParam(":id", $item_id);
        $item_stmt->execute();
        $item = $item_stmt->fetch(PDO::FETCH_ASSOC);

        if(!$item) return false;

        // Prüfe Geld
        $money_query = "SELECT money FROM user_game_data WHERE user_id = :user_id";
        $money_stmt = $this->conn->prepare($money_query);
        $money_stmt->bindParam(":user_id", $user_id);
        $money_stmt->execute();
        $user_data = $money_stmt->fetch(PDO::FETCH_ASSOC);

        if($user_data['money'] < $item['price']) {
            return false;
        }

        try {
            $this->conn->beginTransaction();

            $update_money = "UPDATE user_game_data 
                            SET money = money - :price 
                            WHERE user_id = :user_id";
            $money_stmt = $this->conn->prepare($update_money);
            $money_stmt->bindParam(":price", $item['price']);
            $money_stmt->bindParam(":user_id", $user_id);
            $money_stmt->execute();

            $check_inv = "SELECT id, quantity FROM user_inventory 
                         WHERE user_id = :user_id AND shop_item_id = :item_id";
            $check_stmt = $this->conn->prepare($check_inv);
            $check_stmt->bindParam(":user_id", $user_id);
            $check_stmt->bindParam(":item_id", $item_id);
            $check_stmt->execute();

            if($check_stmt->rowCount() > 0) {
                $update_inv = "UPDATE user_inventory 
                              SET quantity = quantity + 1 
                              WHERE user_id = :user_id AND shop_item_id = :item_id";
                $inv_stmt = $this->conn->prepare($update_inv);
                $inv_stmt->bindParam(":user_id", $user_id);
                $inv_stmt->bindParam(":item_id", $item_id);
                $inv_stmt->execute();
            } else {
                $insert_inv = "INSERT INTO user_inventory (user_id, shop_item_id, quantity) 
                              VALUES (:user_id, :item_id, 1)";
                $inv_stmt = $this->conn->prepare($insert_inv);
                $inv_stmt->bindParam(":user_id", $user_id);
                $inv_stmt->bindParam(":item_id", $item_id);
                $inv_stmt->execute();
            }

            $this->conn->commit();
            return true;

        } catch(Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getUserInventory($user_id) {
        $query = "SELECT ui.*, f.name as food_name, f.icon_emoji as food_icon, f.energy_boost, f.happiness_boost,
                  s.name as shop_name, s.icon_emoji as shop_icon
                  FROM user_inventory ui
                  LEFT JOIN food_items f ON ui.food_item_id = f.id
                  LEFT JOIN shop_items s ON ui.shop_item_id = s.id
                  WHERE ui.user_id = :user_id AND ui.quantity > 0
                  ORDER BY ui.id DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
