<?php
// config/database.php
class Database {
    private $host = "localhost";
    private $db_name = "cloud_system";
    private $username = "dein_cloud_user";
    private $password = "DeinSicheresPasswort";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Verbindungsfehler: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>
