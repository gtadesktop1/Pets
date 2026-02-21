<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'config_database.php';
require_once 'User.php';
require_once 'Pet.php';
require_once 'Shop.php';
require_once 'GameBackup.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$user->id = $_SESSION['user_id'];
// updateIncome nur beim Login, nicht bei jedem Request

$userData = $user->getUserData();
$petCount = $user->getPetCount();
$messageCount = $user->getUnreadMessageCount();

$pet = new Pet($db);
$shop = new Shop($db);

$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Handle actions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['buy_pet'])) {
        $pet_type_id = $_POST['pet_type_id'];
        $pet_name = $_POST['pet_name'];
        
        $pet_types = $shop->getAllPetTypes();
        $selected_type = null;
        foreach($pet_types as $type) {
            if($type['id'] == $pet_type_id) {
                $selected_type = $type;
                break;
            }
        }
        
        if($selected_type && $userData['money'] >= $selected_type['base_price']) {
            $pet->user_id = $_SESSION['user_id'];
            $pet->pet_type_id = $pet_type_id;
            $pet->name = $pet_name;
            
            if($pet->create()) {
                $user->updateMoney(-$selected_type['base_price']);
                header("Location: ?page=home");
                exit();
            }
        }
    } elseif(isset($_POST['set_activity'])) {
        $pet_id = $_POST['pet_id'];
        $activity = $_POST['activity'];
        $pet->setActivity($pet_id, $activity);
        // Redirect to reload page and show updated activity
        header("Location: ?page=" . $page);
        exit();
    } elseif(isset($_POST['feed_pet'])) {
        $pet_id = $_POST['pet_id'];
        $food_id = $_POST['food_id'];
        $pet->feed($pet_id, $food_id);
        // Update inventory after feeding
        header("Location: ?page=" . $page);
        exit();
    } elseif(isset($_POST['upgrade_pet'])) {
        $pet_id = $_POST['pet_id'];
        $pet->upgrade($pet_id);
        header("Location: ?page=" . $page);
        exit();
    } elseif(isset($_POST['buy_food'])) {
        $food_id = $_POST['food_id'];
        $quantity = $_POST['quantity'] ?? 1;
        $shop->buyFood($_SESSION['user_id'], $food_id, $quantity);
        header("Location: ?page=" . $page);
        exit();
    } elseif(isset($_POST['buy_item'])) {
        $item_id = $_POST['item_id'];
        $shop->buyShopItem($_SESSION['user_id'], $item_id);
        header("Location: ?page=" . $page);
        exit();
    } elseif(isset($_POST['battle'])) {
        $_SESSION['battle_result'] = $pet->battle($_POST['attacker_id'], $_POST['defender_id']);
        header("Location: ?page=arena");
        exit();
        // Old code removed
        $attacker_id = $_POST['attacker_id'];
        $defender_id = $_POST['defender_id'];
        $battle_result = $pet->battle($attacker_id, $defender_id);
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PETS - Game</title>
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
        }

        .header {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            padding: 15px 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            position: relative;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            font-size: 48px;
            font-weight: bold;
            color: #8B4513;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-icons {
            display: flex;
            gap: 15px;
            font-size: 40px;
        }

        .user-info {
            background: rgba(255,255,255,0.9);
            padding: 15px 20px;
            border-radius: 10px;
            text-align: right;
            font-size: 14px;
            line-height: 1.6;
        }

        .user-info strong {
            color: #8B4513;
        }

        .nav {
            background: #333;
            padding: 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .nav ul {
            list-style: none;
            display: flex;
            max-width: 1200px;
            margin: 0 auto;
            flex-wrap: wrap;
        }

        .nav li {
            flex: 1;
            min-width: 120px;
        }

        .nav a {
            display: block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            text-align: center;
            font-weight: bold;
            transition: background 0.3s;
            text-transform: uppercase;
            font-size: 13px;
        }

        .nav a:hover, .nav a.active {
            background: #667eea;
        }

        .content {
            max-width: 1200px;
            margin: 30px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            min-height: 500px;
        }

        h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 28px;
        }

        .pet-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .pet-card {
            border: 3px solid #667eea;
            border-radius: 15px;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .pet-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .pet-icon {
            font-size: 60px;
        }

        .pet-info h3 {
            color: #333;
            margin-bottom: 5px;
        }

        .stat-bar {
            background: #ddd;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }

        .stat-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        .energy-fill {
            background: linear-gradient(90deg, #4CAF50 0%, #8BC34A 100%);
        }

        .happiness-fill {
            background: linear-gradient(90deg, #FF9800 0%, #FFC107 100%);
        }

        .activity-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
        }

        .activity-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: transform 0.2s;
            font-size: 14px;
        }

        .btn:hover {
            transform: scale(1.05);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #FF9800 0%, #FFC107 100%);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #f44336 0%, #e91e63 100%);
            color: white;
        }

        .shop-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .shop-item {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            background: #f9f9f9;
        }

        .shop-item-icon {
            font-size: 50px;
            margin-bottom: 10px;
        }

        .price {
            font-size: 18px;
            font-weight: bold;
            color: #4CAF50;
            margin: 10px 0;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .battle-result {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #667eea;
            color: white;
        }

        tr:hover {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">
                🐾 PETS
            </div>
            <div class="header-icons">
                🐶 🐱 🐵 🐘
            </div>
            <div class="user-info">
                <div><strong>Geld:</strong> <?php echo number_format($userData['money'], 2); ?> €</div>
                <div><strong>Einkommen Min:</strong> <?php echo number_format($userData['income_per_minute'], 2); ?> €</div>
                <div><strong>Einkommen Std:</strong> <?php echo number_format($userData['income_per_hour'], 2); ?> €</div>
                <div><strong>Einkommen Tag:</strong> <?php echo number_format($userData['income_per_day'], 2); ?> €</div>
                <div><strong>Pets:</strong> <?php echo $petCount; ?></div>
                <div><a href="logout.php" style="color: #667eea; text-decoration: none; font-weight: bold;">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></div>
            </div>
        </div>
    </div>

    <nav class="nav">
        <ul>
            <li><a href="?page=home" class="<?php echo $page == 'home' ? 'active' : ''; ?>">DEINE PETS</a></li>
            <li><a href="?page=futterkorb" class="<?php echo $page == 'futterkorb' ? 'active' : ''; ?>">FUTTERKORB</a></li>
            <li><a href="?page=shop" class="<?php echo $page == 'shop' ? 'active' : ''; ?>">SHOP</a></li>
            <li><a href="?page=shop&do=showpets" class="<?php echo $page == 'showpets' ? 'active' : ''; ?>">PET KAUFEN</a></li>
            <li><a href="?page=arena" class="<?php echo $page == 'battle' ? 'active' : ''; ?>">ARENA</a></li>
            <li><a href="?page=backups" class="<?php echo $page == 'backups' ? 'active' : ''; ?>">☁️ BACKUPS</a></li>
            <li><a href="?page=settings" class="<?php echo $page == 'settings' ? 'active' : ''; ?>">EINSTELLUNGEN</a></li>
            <li><a href="?page=messages" class="<?php echo $page == 'messages' ? 'active' : ''; ?>">NACHRICHTEN <?php echo $messageCount > 0 ? "($messageCount)" : ''; ?></a></li>
            <li><a href="?page=rangliste" class="<?php echo $page == 'rangliste' ? 'active' : ''; ?>">RANGLISTE</a></li>
        </ul>
    </nav>

    <div class="content">
        <?php include 'pages/' . $page . '.php'; ?>
    </div>
</body>
</html>
