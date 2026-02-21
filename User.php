<?php
// classes/User.php - Fixed: Geld nicht bei jedem Reload
class User {
    private $conn;
    private $users_table = "users";
    private $game_data_table = "user_game_data";

    public $id;
    public $username;
    public $password;
    public $password_hash;
    public $email;
    public $money;
    public $income_per_minute;
    public $income_per_hour;
    public $income_per_day;
    public $last_income_update;
    public $game_started_at;
    public $last_login;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login() {
        $query = "SELECT id, username, password_hash, email 
                  FROM " . $this->users_table . " 
                  WHERE username = :username LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $this->username);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($this->password, $row['password_hash'])) {
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->email = $row['email'];

                // Update last_login
                $update_login = "UPDATE " . $this->users_table . " 
                                SET last_attempt = NOW() 
                                WHERE id = :id";
                $login_stmt = $this->conn->prepare($update_login);
                $login_stmt->bindParam(":id", $this->id);
                $login_stmt->execute();

                $this->ensureGameData();
                $this->loadGameData();
                $this->updateIncome();
                $this->updateIncomeRates();

                return true;
            }
        }
        return false;
    }

    private function ensureGameData() {
        $check_query = "SELECT id FROM " . $this->game_data_table . " WHERE user_id = :user_id";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(":user_id", $this->id);
        $check_stmt->execute();

        if($check_stmt->rowCount() == 0) {
            $insert_query = "INSERT INTO " . $this->game_data_table . " 
                            (user_id, money, income_per_minute, income_per_hour, income_per_day) 
                            VALUES (:user_id, 1500.00, 0.00, 8.00, 192.00)";
            $insert_stmt = $this->conn->prepare($insert_query);
            $insert_stmt->bindParam(":user_id", $this->id);
            $insert_stmt->execute();
        }
    }

    private function loadGameData() {
        $query = "SELECT money, income_per_minute, income_per_hour, income_per_day, 
                  last_income_update, game_started_at 
                  FROM " . $this->game_data_table . " 
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->money = $row['money'];
            $this->income_per_minute = $row['income_per_minute'];
            $this->income_per_hour = $row['income_per_hour'];
            $this->income_per_day = $row['income_per_day'];
            $this->last_income_update = $row['last_income_update'];
            $this->game_started_at = $row['game_started_at'];
        }
    }

    public function updateIncomeRates() {
        $pet_query = "SELECT COUNT(*) as working_count FROM pets 
                     WHERE user_id = :user_id AND current_activity = 'arbeiten'";
        $pet_stmt = $this->conn->prepare($pet_query);
        $pet_stmt->bindParam(":user_id", $this->id);
        $pet_stmt->execute();
        $result = $pet_stmt->fetch(PDO::FETCH_ASSOC);
        $working_pets = $result['working_count'];

        $income_per_hour = 8 + ($working_pets * 10);
        $income_per_day = $income_per_hour * 24;
        $income_per_minute = $income_per_hour / 60;

        $update_query = "UPDATE " . $this->game_data_table . " 
                        SET income_per_hour = :per_hour,
                            income_per_day = :per_day,
                            income_per_minute = :per_minute
                        WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($update_query);
        $stmt->bindParam(":per_hour", $income_per_hour);
        $stmt->bindParam(":per_day", $income_per_day);
        $stmt->bindParam(":per_minute", $income_per_minute);
        $stmt->bindParam(":user_id", $this->id);
        $stmt->execute();

        $this->income_per_hour = $income_per_hour;
        $this->income_per_day = $income_per_day;
        $this->income_per_minute = $income_per_minute;
    }

    public function updateIncome() {
        $query = "SELECT last_income_update, income_per_minute 
                  FROM " . $this->game_data_table . " 
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $last_update = strtotime($row['last_income_update']);
        $now = time();
        $minutes_passed = ($now - $last_update) / 60;
        
        $income = $minutes_passed * $row['income_per_minute'];
        
        $update_query = "UPDATE " . $this->game_data_table . " 
                        SET money = money + :income, 
                            last_income_update = NOW() 
                        WHERE user_id = :user_id";
        
        $update_stmt = $this->conn->prepare($update_query);
        $update_stmt->bindParam(":income", $income);
        $update_stmt->bindParam(":user_id", $this->id);
        
        return $update_stmt->execute();
    }

    public function getUserData() {
        $query = "SELECT u.*, g.money, g.income_per_minute, g.income_per_hour, 
                  g.income_per_day, g.last_income_update, g.game_started_at
                  FROM " . $this->users_table . " u
                  LEFT JOIN " . $this->game_data_table . " g ON u.id = g.user_id
                  WHERE u.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateMoney($amount) {
        $query = "UPDATE " . $this->game_data_table . " 
                  SET money = GREATEST(money + :amount, 0)
                  WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":amount", $amount);
        $stmt->bindParam(":user_id", $this->id);
        return $stmt->execute();
    }

    public function hasMoney($amount) {
        $query = "SELECT money FROM " . $this->game_data_table . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['money'] >= $amount;
    }

    public function getPetCount() {
        $query = "SELECT COUNT(*) as count FROM pets WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }

    public function getUnreadMessageCount() {
        $query = "SELECT COUNT(*) as count FROM messages 
                  WHERE recipient_id = :user_id AND is_read = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }

    public function getLastLogin() {
        $query = "SELECT last_attempt FROM " . $this->users_table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['last_attempt'];
    }
}
?>
