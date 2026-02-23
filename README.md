# 🐾 Pets – Browser-Based Pet Game

A browser-based pet game built with PHP and MySQL.  
This project is a reverse-engineered recreation of an early pet game concept, rebuilt as a technical and architectural challenge.

The focus of this project was to reconstruct core mechanics, refine structure, and explore how the original idea could evolve with additional features and improvements.

---

## 🎮 Features

- 🐶 Create and manage virtual pets  
- 🛒 In-game shop system  
- ⚔️ Arena & battle system  
- 🏆 Ranking system  
- 💬 Messaging system  
- 💾 Backup / cloud functionality  
- 👤 User registration & login system  
- ⚙️ Account settings  

---

## 🛠️ Tech Stack

- PHP  
- MySQL  
- HTML / CSS  
- Session-based authentication  
- Server-side game logic  

---

## 📂 Project Structure

```
Pets/
│
├── index_cloud.php        # Entry point
├── ingame.php             # Main game logic
├── register.php           # User registration
├── logout.php             # Logout system
├── config_database.php    # Database configuration
│
├── Pet.php                # Pet model
├── User.php               # User model
├── Shop.php               # Shop system
├── GameBackup.php         # Backup logic
│
└── pages/
    ├── arena.php
    ├── battle.php
    ├── home.php
    ├── shop.php
    ├── rangliste.php
    ├── petdetails.php
    ├── showpets.php
    ├── messages.php
    ├── settings.php
    ├── futterkorb.php
    ├── backups.php
    └── cloud.php
```

---

## ⚙️ Installation

### Requirements

- PHP 7.4+  
- MySQL / MariaDB  
- Local server environment (e.g. XAMPP, MAMP, WAMP)

### Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/pets.git
   ```

2. Move the project into your web server directory  
   (e.g. `htdocs` for XAMPP)

3. Create a new MySQL database.

4. Update your database credentials in:

   `config_database.php`

   ```php
   $host = "localhost";
   $user = "your_user";
   $password = "your_password";
   $database = "your_database";
   ```

5. Import the required database structure (if applicable).

6. Start your local server and open:

   ```
   http://localhost/Pets
   ```

---

## 🧠 Project Goal

This project serves as:

- A reverse-engineering challenge  
- A learning experience in PHP game architecture  
- A technical reconstruction of an early game concept  
- A foundation for future feature expansion  

---

## 🚀 Possible Future Improvements

- Refactored MVC architecture  
- Improved security (prepared statements, validation)  
- API-based backend structure  
- Modern frontend (React / Vue)  
- Real-time battle mechanics  
- Achievement system  
- Pet skill trees and leveling system  
- Multiplayer events  

---

## 📜 License

This project is for educational and demonstration purposes.
