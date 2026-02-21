<?php
class Pet {
    private $conn;
    private $table_name = "pets";
    
    public $id;
    public $user_id;
    public $pet_type_id;
    public $name;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, pet_type_id, name, level, experience, energy, happiness, points, current_activity) 
                  VALUES (:user_id, :pet_type_id, :name, 1, 0, 100, 100, 0, 'nichts')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":pet_type_id", $this->pet_type_id);
        $stmt->bindParam(":name", $this->name);
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
    
    // WICHTIG: Holt NUR Daten, KEIN updateActivity!
    public function getUserPets($user_id) {
        $query = "SELECT p.*, pt.name as type_name, pt.icon_emoji
                  FROM " . $this->table_name . " p
                  JOIN pet_types pt ON p.pet_type_id = pt.id
                  WHERE p.user_id = :user_id
                  ORDER BY p.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPetById($id) {
        $query = "SELECT p.*, pt.name as type_name, pt.icon_emoji, u.username as owner_name
                  FROM " . $this->table_name . " p
                  JOIN pet_types pt ON p.pet_type_id = pt.id
                  JOIN users u ON p.user_id = u.id
                  WHERE p.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function setActivity($pet_id, $activity) {
        $this->updateActivity($pet_id);
        $query = "UPDATE " . $this->table_name . " 
                  SET current_activity = :activity, 
                      activity_started_at = NOW() 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":activity", $activity);
        $stmt->bindParam(":id", $pet_id);
        return $stmt->execute();
    }
    
    public function updateActivity($pet_id) {
        $pet = $this->getPetById($pet_id);
        if($pet['current_activity'] == 'nichts') return;
        
        $hours_passed = 0;
        if($pet['activity_started_at']) {
            $start_time = strtotime($pet['activity_started_at']);
            $now = time();
            $hours_passed = ($now - $start_time) / 3600;
        }
        if($hours_passed < 0.01) return;
        
        $ep_gain = 0;
        $happiness_gain = 0;
        $energy_change = 0;
        $money_gain = 0;
        
        switch($pet['current_activity']) {
            case 'trainieren':
                $ep_gain = floor($hours_passed * 50);
                $energy_change = -floor($hours_passed * 5);
                break;
            case 'spielen':
                $happiness_gain = floor($hours_passed * 10);
                $energy_change = -floor($hours_passed * 3);
                break;
            case 'schlafen':
                $energy_change = floor($hours_passed * 20);
                break;
            case 'arbeiten':
                $money_gain = floor($hours_passed * 10);
                $energy_change = -floor($hours_passed * 8);
                break;
        }
        
        $new_energy = max(0, min(100, $pet['energy'] + $energy_change));
        $new_activity = $pet['current_activity'];
        if($new_energy == 0 && $pet['current_activity'] != 'schlafen') {
            $new_activity = 'schlafen';
        }
        
        $query = "UPDATE " . $this->table_name . " 
                  SET experience = LEAST(experience + :ep_gain, 9999),
                      happiness = LEAST(GREATEST(happiness + :happiness_gain, 0), 100),
                      energy = :new_energy,
                      current_activity = :new_activity,
                      activity_started_at = NOW()
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ep_gain", $ep_gain);
        $stmt->bindParam(":happiness_gain", $happiness_gain);
        $stmt->bindParam(":new_energy", $new_energy);
        $stmt->bindParam(":new_activity", $new_activity);
        $stmt->bindParam(":id", $pet_id);
        $stmt->execute();
        
        if($money_gain > 0) {
            $user_query = "UPDATE user_game_data SET money = money + :money WHERE user_id = :user_id";
            $user_stmt = $this->conn->prepare($user_query);
            $user_stmt->bindParam(":money", $money_gain);
            $user_stmt->bindParam(":user_id", $pet['user_id']);
            $user_stmt->execute();
        }
    }
    
    // FIX: Holt Pet-Daten DIREKT ohne getPetById
    public function feed($pet_id, $food_item_id) {
        $food_query = "SELECT * FROM food_items WHERE id = :id";
        $food_stmt = $this->conn->prepare($food_query);
        $food_stmt->bindParam(":id", $food_item_id);
        $food_stmt->execute();
        $food = $food_stmt->fetch(PDO::FETCH_ASSOC);
        if(!$food) return false;
        
        // DIREKT holen ohne Joins
        $pet_query = "SELECT user_id FROM pets WHERE id = :id";
        $pet_stmt = $this->conn->prepare($pet_query);
        $pet_stmt->bindParam(":id", $pet_id);
        $pet_stmt->execute();
        $pet = $pet_stmt->fetch(PDO::FETCH_ASSOC);
        if(!$pet) return false;
        
        $inv_query = "SELECT * FROM user_inventory 
                      WHERE user_id = :user_id AND food_item_id = :food_id AND quantity > 0";
        $inv_stmt = $this->conn->prepare($inv_query);
        $inv_stmt->bindParam(":user_id", $pet['user_id']);
        $inv_stmt->bindParam(":food_id", $food_item_id);
        $inv_stmt->execute();
        if($inv_stmt->rowCount() == 0) return false;
        
        // DIREKT updaten
        $query = "UPDATE " . $this->table_name . " 
                  SET energy = LEAST(energy + :energy_boost, 100),
                      happiness = LEAST(happiness + :happiness_boost, 100)
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":energy_boost", $food['energy_boost']);
        $stmt->bindParam(":happiness_boost", $food['happiness_boost']);
        $stmt->bindParam(":id", $pet_id);
        
        if($stmt->execute()) {
            $reduce_inv = "UPDATE user_inventory SET quantity = quantity - 1 
                          WHERE user_id = :user_id AND food_item_id = :food_id";
            $reduce_stmt = $this->conn->prepare($reduce_inv);
            $reduce_stmt->bindParam(":user_id", $pet['user_id']);
            $reduce_stmt->bindParam(":food_id", $food_item_id);
            $reduce_stmt->execute();
            
            $delete_inv = "DELETE FROM user_inventory 
                          WHERE user_id = :user_id AND food_item_id = :food_id AND quantity <= 0";
            $delete_stmt = $this->conn->prepare($delete_inv);
            $delete_stmt->bindParam(":user_id", $pet['user_id']);
            $delete_stmt->bindParam(":food_id", $food_item_id);
            $delete_stmt->execute();
            return true;
        }
        return false;
    }
    
    public function upgrade($pet_id) {
        $pet = $this->getPetById($pet_id);
        $ep_needed = $pet['level'] * 100 + 30;
        if($pet['experience'] >= $ep_needed) {
            $query = "UPDATE " . $this->table_name . " 
                      SET level = level + 1, experience = experience - :ep_needed, points = points + 10
                      WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":ep_needed", $ep_needed);
            $stmt->bindParam(":id", $pet_id);
            return $stmt->execute();
        }
        return false;
    }
    
    // NEU: Prüft ob Pet kämpfen kann
    public function canBattle($pet_id) {
        $query = "SELECT energy, happiness FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $pet_id);
        $stmt->execute();
        $pet = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!$pet) return false;
        return $pet['energy'] >= 10 && $pet['happiness'] >= 80;
    }
    
    public function battle($attacker_id, $defender_id) {
        // Direkt holen
        $att_query = "SELECT p.*, pt.name as type_name, u.username as owner_name 
                     FROM " . $this->table_name . " p
                     JOIN pet_types pt ON p.pet_type_id = pt.id
                     JOIN users u ON p.user_id = u.id
                     WHERE p.id = :id";
        $att_stmt = $this->conn->prepare($att_query);
        $att_stmt->bindParam(":id", $attacker_id);
        $att_stmt->execute();
        $attacker = $att_stmt->fetch(PDO::FETCH_ASSOC);
        
        $def_stmt = $this->conn->prepare($att_query);
        $def_stmt->bindParam(":id", $defender_id);
        $def_stmt->execute();
        $defender = $def_stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$attacker || !$defender) {
            return ['success' => false, 'message' => 'Pet nicht gefunden'];
        }
        
        if(!$this->canBattle($attacker_id)) {
            return ['success' => false, 'message' => 'Nicht genug Energie (10%) oder Freude (80%)!'];
        }
        
        $level_diff = $attacker['level'] - $defender['level'];
        $success_rate = max(10, min(90, 50 + ($level_diff * 5)));
        $success = rand(1, 100) <= $success_rate;
        $damage = rand(5, 15) + floor($attacker['level'] / 2);
        
        $attacker_happiness_loss = 80;
        if($success) {
            $attacker_energy_loss = 10;
            $attacker_xp_gain = 8;
            $defender_energy_loss = $damage;
            $defender_xp_gain = 0;
            $winner_id = $attacker_id;
            $result = 'won';
        } else {
            $attacker_energy_loss = 20;
            $attacker_xp_gain = 0;
            $defender_energy_loss = 0;
            $defender_xp_gain = 8;
            $winner_id = $defender_id;
            $result = 'lost';
        }
        
        $new_attacker_energy = max(0, $attacker['energy'] - $attacker_energy_loss);
        $new_attacker_happiness = max(0, $attacker['happiness'] - $attacker_happiness_loss);
        $new_attacker_xp = $attacker['experience'] + $attacker_xp_gain;
        
        $update_attacker = "UPDATE " . $this->table_name . " 
                           SET energy = :energy, happiness = :happiness, experience = :experience
                           WHERE id = :id";
        $stmt_att = $this->conn->prepare($update_attacker);
        $stmt_att->bindParam(":energy", $new_attacker_energy);
        $stmt_att->bindParam(":happiness", $new_attacker_happiness);
        $stmt_att->bindParam(":experience", $new_attacker_xp);
        $stmt_att->bindParam(":id", $attacker_id);
        $stmt_att->execute();
        
        $new_defender_energy = max(0, $defender['energy'] - $defender_energy_loss);
        $new_defender_xp = $defender['experience'] + $defender_xp_gain;
        
        $update_defender = "UPDATE " . $this->table_name . " 
                           SET energy = :energy, experience = :experience WHERE id = :id";
        $stmt_def = $this->conn->prepare($update_defender);
        $stmt_def->bindParam(":energy", $new_defender_energy);
        $stmt_def->bindParam(":experience", $new_defender_xp);
        $stmt_def->bindParam(":id", $defender_id);
        $stmt_def->execute();
        
        $battle_query = "INSERT INTO battles 
                        (attacker_pet_id, defender_pet_id, winner_pet_id, damage_dealt) 
                        VALUES (:attacker_id, :defender_id, :winner_id, :damage)";
        $battle_stmt = $this->conn->prepare($battle_query);
        $battle_stmt->bindParam(":attacker_id", $attacker_id);
        $battle_stmt->bindParam(":defender_id", $defender_id);
        $battle_stmt->bindParam(":winner_id", $winner_id);
        $battle_stmt->bindParam(":damage", $damage);
        $battle_stmt->execute();
        
        return [
            'success' => true,
            'result' => $result,
            'attacker' => [
                'name' => $attacker['name'],
                'level' => $attacker['level'],
                'energy_before' => $attacker['energy'],
                'energy_after' => $new_attacker_energy,
                'energy_loss' => $attacker_energy_loss,
                'happiness_before' => $attacker['happiness'],
                'happiness_after' => $new_attacker_happiness,
                'happiness_loss' => $attacker_happiness_loss,
                'xp_gain' => $attacker_xp_gain
            ],
            'defender' => [
                'name' => $defender['name'],
                'level' => $defender['level'],
                'energy_before' => $defender['energy'],
                'energy_after' => $new_defender_energy,
                'energy_loss' => $defender_energy_loss,
                'xp_gain' => $defender_xp_gain
            ],
            'damage' => $damage,
            'success_rate' => $success_rate
        ];
    }
    
    public function getAllPetsExceptUser($user_id) {
        $query = "SELECT p.*, pt.name as type_name, pt.icon_emoji, u.username as owner_name
                  FROM " . $this->table_name . " p
                  JOIN pet_types pt ON p.pet_type_id = pt.id
                  JOIN users u ON p.user_id = u.id
                  WHERE p.user_id != :user_id
                  ORDER BY p.level DESC, p.points DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function countWorkingPets($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND current_activity = 'arbeiten'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}
?>
