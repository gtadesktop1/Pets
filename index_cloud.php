<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PETS - Cloud Login</title>
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
            max-width: 400px;
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

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-size: 13px;
        }

        .info-box strong {
            color: #1976D2;
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
            ☁️ Cloud Edition
        </div>

        <?php
        session_start();
        require_once 'config_database.php';
        require_once 'User.php';

        $message = '';

        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $database = new Database();
            $db = $database->getConnection();
            $user = new User($db);

            $user->username = $_POST['username'];
            $user->password = $_POST['password'];

            if($user->login()) {
                $_SESSION['user_id'] = $user->id;
                $_SESSION['username'] = $user->username;
                header("Location: ingame.php");
                exit();
            } else {
                $message = 'Ungültiger Benutzername oder Passwort!';
            }
        }
        ?>

        <?php if($message): ?>
            <div class="error">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Benutzername:</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Passwort:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Einloggen</button>
        </form>

        <div class="info-box">
            <strong>ℹ️ Cloud-Integration</strong><br>
            Nutze deinen Cloud-Account um dich einzuloggen. Deine Spielstände werden automatisch in der Cloud gespeichert und synchronisiert!
        </div>

        <div class="login-link" style="text-align: center; margin-top: 20px; color: #666;">
            Noch kein Account? <a href="register.php" style="color: #667eea; text-decoration: none; font-weight: bold;">Jetzt registrieren</a>
        </div>

        <div class="info-box" style="background: #fff3cd; border-left-color: #ffc107;">
            <strong>🎮 Spielstand-Backups</strong><br>
            Erstelle Backups deines Spielstandes und stelle sie jederzeit wieder her. Deine Fortschritte sind sicher in der Cloud!
        </div>
    </div>
</body>
</html>
