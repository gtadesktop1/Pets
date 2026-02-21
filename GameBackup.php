<?php
// classes/GameBackup.php
class GameBackup {
    private $conn;
    private $table_name = "game_backups";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createBackup($user_id, $backup_name = null) {
        // Automatischer Backup-Name falls nicht angegeben
        if(!$backup_name) {
            $backup_name = "Backup " . date('d.m.Y H:i:s');
        }

        // Hole alle Game Data
        $game_data_query = "SELECT * FROM user_game_data WHERE user_id = :user_id";
        $game_stmt = $this->conn->prepare($game_data_query);
        $game_stmt->bindParam(":user_id", $user_id);
        $game_stmt->execute();
        $game_data = $game_stmt->fetch(PDO::FETCH_ASSOC);

        // Hole alle Pets
        $pets_query = "SELECT * FROM pets WHERE user_id = :user_id";
        $pets_stmt = $this->conn->prepare($pets_query);
        $pets_stmt->bindParam(":user_id", $user_id);
        $pets_stmt->execute();
        $pets_data = $pets_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Hole Inventar
        $inventory_query = "SELECT * FROM user_inventory WHERE user_id = :user_id";
        $inv_stmt = $this->conn->prepare($inventory_query);
        $inv_stmt->bindParam(":user_id", $user_id);
        $inv_stmt->execute();
        $inventory_data = $inv_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Hole Nachrichten
        $messages_query = "SELECT * FROM messages WHERE sender_id = :user_id OR recipient_id = :user_id";
        $msg_stmt = $this->conn->prepare($messages_query);
        $msg_stmt->bindParam(":user_id", $user_id);
        $msg_stmt->execute();
        $messages_data = $msg_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Konvertiere zu JSON
        $game_json = json_encode($game_data);
        $pets_json = json_encode($pets_data);
        $inventory_json = json_encode($inventory_data);
        $messages_json = json_encode($messages_data);

        // Berechne Dateigröße
        $file_size = strlen($game_json) + strlen($pets_json) + strlen($inventory_json) + strlen($messages_json);

        // Speichere Backup
        $insert_query = "INSERT INTO " . $this->table_name . " 
                        (user_id, backup_name, game_data, pets_data, inventory_data, messages_data, file_size) 
                        VALUES (:user_id, :backup_name, :game_data, :pets_data, :inventory_data, :messages_data, :file_size)";
        
        $stmt = $this->conn->prepare($insert_query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":backup_name", $backup_name);
        $stmt->bindParam(":game_data", $game_json);
        $stmt->bindParam(":pets_data", $pets_json);
        $stmt->bindParam(":inventory_data", $inventory_json);
        $stmt->bindParam(":messages_data", $messages_json);
        $stmt->bindParam(":file_size", $file_size);

        if($stmt->execute()) {
            return [
                'success' => true,
                'backup_id' => $this->conn->lastInsertId(),
                'backup_name' => $backup_name,
                'file_size' => $file_size
            ];
        }

        return ['success' => false];
    }

    public function restoreBackup($backup_id, $user_id) {
        // Hole Backup
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $backup_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        if($stmt->rowCount() == 0) {
            return ['success' => false, 'message' => 'Backup nicht gefunden'];
        }

        $backup = $stmt->fetch(PDO::FETCH_ASSOC);

        // Dekodiere JSON
        $game_data = json_decode($backup['game_data'], true);
        $pets_data = json_decode($backup['pets_data'], true);
        $inventory_data = json_decode($backup['inventory_data'], true);
        $messages_data = isset($backup['messages_data']) ? json_decode($backup['messages_data'], true) : [];

        try {
            // Start Transaction
            $this->conn->beginTransaction();

            // Lösche aktuelle Daten
            $delete_pets = "DELETE FROM pets WHERE user_id = :user_id";
            $del_pets_stmt = $this->conn->prepare($delete_pets);
            $del_pets_stmt->bindParam(":user_id", $user_id);
            $del_pets_stmt->execute();

            $delete_inv = "DELETE FROM user_inventory WHERE user_id = :user_id";
            $del_inv_stmt = $this->conn->prepare($delete_inv);
            $del_inv_stmt->bindParam(":user_id", $user_id);
            $del_inv_stmt->execute();

            $delete_msg = "DELETE FROM messages WHERE sender_id = :user_id OR recipient_id = :user_id";
            $del_msg_stmt = $this->conn->prepare($delete_msg);
            $del_msg_stmt->bindParam(":user_id", $user_id);
            $del_msg_stmt->execute();

            // Restore Game Data
            $update_game = "UPDATE user_game_data SET 
                           money = :money,
                           income_per_minute = :income_per_minute,
                           income_per_hour = :income_per_hour,
                           income_per_day = :income_per_day
                           WHERE user_id = :user_id";
            $game_stmt = $this->conn->prepare($update_game);
            $game_stmt->bindParam(":money", $game_data['money']);
            $game_stmt->bindParam(":income_per_minute", $game_data['income_per_minute']);
            $game_stmt->bindParam(":income_per_hour", $game_data['income_per_hour']);
            $game_stmt->bindParam(":income_per_day", $game_data['income_per_day']);
            $game_stmt->bindParam(":user_id", $user_id);
            $game_stmt->execute();

            // Restore Pets
            foreach($pets_data as $pet) {
                $insert_pet = "INSERT INTO pets 
                              (user_id, pet_type_id, name, level, experience, energy, happiness, points, current_activity, activity_started_at) 
                              VALUES (:user_id, :pet_type_id, :name, :level, :experience, :energy, :happiness, :points, :current_activity, :activity_started_at)";
                $pet_stmt = $this->conn->prepare($insert_pet);
                $pet_stmt->bindParam(":user_id", $user_id);
                $pet_stmt->bindParam(":pet_type_id", $pet['pet_type_id']);
                $pet_stmt->bindParam(":name", $pet['name']);
                $pet_stmt->bindParam(":level", $pet['level']);
                $pet_stmt->bindParam(":experience", $pet['experience']);
                $pet_stmt->bindParam(":energy", $pet['energy']);
                $pet_stmt->bindParam(":happiness", $pet['happiness']);
                $pet_stmt->bindParam(":points", $pet['points']);
                $pet_stmt->bindParam(":current_activity", $pet['current_activity']);
                $pet_stmt->bindParam(":activity_started_at", $pet['activity_started_at']);
                $pet_stmt->execute();
            }

            // Restore Inventory
            foreach($inventory_data as $item) {
                $insert_inv = "INSERT INTO user_inventory 
                              (user_id, food_item_id, shop_item_id, quantity) 
                              VALUES (:user_id, :food_item_id, :shop_item_id, :quantity)";
                $inv_stmt = $this->conn->prepare($insert_inv);
                $inv_stmt->bindParam(":user_id", $user_id);
                $inv_stmt->bindParam(":food_item_id", $item['food_item_id']);
                $inv_stmt->bindParam(":shop_item_id", $item['shop_item_id']);
                $inv_stmt->bindParam(":quantity", $item['quantity']);
                $inv_stmt->execute();
            }

            // Restore Messages
            foreach($messages_data as $msg) {
                $insert_msg = "INSERT INTO messages 
                              (sender_id, recipient_id, subject, message, is_read, sent_at, parent_id) 
                              VALUES (:sender_id, :recipient_id, :subject, :message, :is_read, :sent_at, :parent_id)";
                $msg_stmt = $this->conn->prepare($insert_msg);
                $msg_stmt->bindParam(":sender_id", $msg['sender_id']);
                $msg_stmt->bindParam(":recipient_id", $msg['recipient_id']);
                $msg_stmt->bindParam(":subject", $msg['subject']);
                $msg_stmt->bindParam(":message", $msg['message']);
                $msg_stmt->bindParam(":is_read", $msg['is_read']);
                $msg_stmt->bindParam(":sent_at", $msg['sent_at']);
                $parent_id = isset($msg['parent_id']) ? $msg['parent_id'] : null;
                $msg_stmt->bindParam(":parent_id", $parent_id);
                $msg_stmt->execute();
            }

            // Commit Transaction
            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Spielstand erfolgreich wiederhergestellt',
                'backup_name' => $backup['backup_name'],
                'backup_date' => $backup['backup_date']
            ];

        } catch(Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => 'Fehler beim Wiederherstellen: ' . $e->getMessage()
            ];
        }
    }

    public function getUserBackups($user_id) {
        $query = "SELECT id, backup_name, backup_date, file_size 
                  FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                  ORDER BY backup_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteBackup($backup_id, $user_id) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $backup_id);
        $stmt->bindParam(":user_id", $user_id);
        
        return $stmt->execute();
    }

    public function exportBackup($backup_id, $user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $backup_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        if($stmt->rowCount() == 0) {
            return null;
        }

        $backup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Erstelle Export-Datei
        $export_data = [
            'backup_name' => $backup['backup_name'],
            'backup_date' => $backup['backup_date'],
            'game_data' => json_decode($backup['game_data'], true),
            'pets_data' => json_decode($backup['pets_data'], true),
            'inventory_data' => json_decode($backup['inventory_data'], true)
        ];

        return $export_data;
    }
}
?>
