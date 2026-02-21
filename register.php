<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PETS - Registrierung</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Comic Sans MS', cursive, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            font-size: 48px;
            color: #FFD700;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.3);
            background: linear-gradient(45deg, #FF6B6B, #4ECDC4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo p {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }

        .cloud-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            margin: 20px 0;
            text-align: center;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
        }

        button[type="submit"] {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }

        button[type="submit"]:hover {
            transform: scale(1.05);
        }

        .error {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #c62828;
        }

        .success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #4caf50;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-size: 13px;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🐾 PETS 🐾</h1>
            <p>Eine harige Angelegenheit<br>Das Onlinegame für Jung und Alt</p>
        </div>

        <div class="cloud-badge">
            ☁️ Cloud Edition - Registrierung
        </div>

        <?php
        $message = '';
        $message_type = '';

        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            require_once 'config_database.php';

            $database = new Database();
            $db = $database->getConnection();

            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            // Validierung
            if(empty($username) || empty($email) || empty($password)) {
                $message = 'Bitte fülle alle Felder aus!';
                $message_type = 'error';
            } elseif(strlen($username) < 3) {
                $message = 'Benutzername muss mindestens 3 Zeichen lang sein!';
                $message_type = 'error';
            } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = 'Bitte gib eine gültige E-Mail-Adresse ein!';
                $message_type = 'error';
            } elseif(strlen($password) < 6) {
                $message = 'Passwort muss mindestens 6 Zeichen lang sein!';
                $message_type = 'error';
            } elseif($password !== $confirm_password) {
                $message = 'Passwörter stimmen nicht überein!';
                $message_type = 'error';
            } else {
                // Prüfe ob Username bereits existiert
                $check_query = "SELECT id FROM users WHERE username = :username";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(':username', $username);
                $check_stmt->execute();

                if($check_stmt->rowCount() > 0) {
                    $message = 'Benutzername ist bereits vergeben!';
                    $message_type = 'error';
                } else {
                    // Prüfe ob E-Mail bereits existiert
                    $check_email = "SELECT id FROM users WHERE email = :email";
                    $email_stmt = $db->prepare($check_email);
                    $email_stmt->bindParam(':email', $email);
                    $email_stmt->execute();

                    if($email_stmt->rowCount() > 0) {
                        $message = 'E-Mail-Adresse ist bereits registriert!';
                        $message_type = 'error';
                    } else {
                        // Erstelle User in cloud_system.users
                        $password_hash = password_hash($password, PASSWORD_BCRYPT);
                        
                        try {
                            $db->beginTransaction();

                            $insert_query = "INSERT INTO users 
                                           (username, password_hash, email, quota_gb, is_premium, account_type) 
                                           VALUES (:username, :password_hash, :email, 20, 0, 'FREE')";
                            
                            $stmt = $db->prepare($insert_query);
                            $stmt->bindParam(':username', $username);
                            $stmt->bindParam(':password_hash', $password_hash);
                            $stmt->bindParam(':email', $email);
                            $stmt->execute();

                            $user_id = $db->lastInsertId();

                            // Erstelle automatisch Game Data für PETS
                            $game_query = "INSERT INTO user_game_data 
                                         (user_id, money, income_per_minute, income_per_hour, income_per_day) 
                                         VALUES (:user_id, 1500.00, 0.00, 8.00, 192.00)";
                            
                            $game_stmt = $db->prepare($game_query);
                            $game_stmt->bindParam(':user_id', $user_id);
                            $game_stmt->execute();

                            $db->commit();

                            $message = 'Registrierung erfolgreich! Du kannst dich jetzt einloggen.';
                            $message_type = 'success';
                            
                            // Optional: Auto-Login
                            // session_start();
                            // $_SESSION['user_id'] = $user_id;
                            // $_SESSION['username'] = $username;
                            // header("Location: ingame.php");
                            // exit();

                        } catch(PDOException $e) {
                            $db->rollBack();
                            $message = 'Fehler bei der Registrierung: ' . $e->getMessage();
                            $message_type = 'error';
                        }
                    }
                }
            }
        }
        ?>

        <?php if($message): ?>
            <div class="<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Benutzername:</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                       required minlength="3" autofocus>
            </div>

            <div class="form-group">
                <label for="email">E-Mail:</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                       required>
            </div>

            <div class="form-group">
                <label for="password">Passwort:</label>
                <input type="password" id="password" name="password" required minlength="6">
                <small style="color: #666; font-size: 12px;">Mindestens 6 Zeichen</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Passwort bestätigen:</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>

            <button type="submit">Registrieren</button>
        </form>

        <div class="login-link">
            Bereits registriert? <a href="index_cloud.php">Zum Login</a>
        </div>

        <div class="info-box">
            <strong>🎮 Dein PETS Account</strong><br>
            Mit der Registrierung erhältst du:
            <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                <li>Cloud-Account für alle Dienste</li>
                <li>1500€ Startgeld im PETS-Spiel</li>
                <li>8€/Stunde passives Einkommen</li>
                <li>20 GB Cloud-Speicher</li>
                <li>Unbegrenzte Spielstand-Backups</li>
            </ul>
        </div>
    </div>
</body>
</html>
