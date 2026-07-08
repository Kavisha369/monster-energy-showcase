<?php require_once 'config.php'; ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Monster Energy Backend — Setup</title>
<link href="https://fonts.googleapis.com/css2?family=Black+Ops+One&family=Barlow:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  body {
    background: #0A0A0A;
    color: #F0F0F0;
    font-family: 'Barlow', sans-serif;
    padding: 3rem 1.5rem;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
  }
  .setup-card {
    background: rgba(255,255,255,0.038);
    border: 1px solid rgba(255,255,255,0.09);
    backdrop-filter: blur(20px);
    border-radius: 16px;
    padding: 2.5rem;
    max-width: 600px;
    width: 100%;
    box-shadow: 0 30px 60px -30px rgba(0,0,0,0.70);
  }
  h1 {
    font-family: 'Black Ops One', cursive;
    color: #3DFF54;
    font-size: 2rem;
    margin-bottom: 1.5rem;
    text-shadow: 0 0 15px rgba(61,255,84,0.4);
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  .status-log {
    background: rgba(0,0,0,0.4);
    border-radius: 8px;
    padding: 1.2rem;
    font-family: monospace;
    font-size: 0.9rem;
    max-height: 300px;
    overflow-y: auto;
    margin: 1.5rem 0;
    border: 1px solid rgba(61,255,84,0.15);
  }
  .log-item {
    margin-bottom: 0.5rem;
    line-height: 1.4;
  }
  .log-success { color: #3DFF54; }
  .log-error { color: #FF3B3B; }
  .log-info { color: #A0A0A0; }
  .btn {
    display: inline-block;
    background: #3DFF54;
    color: #000;
    font-weight: 700;
    text-transform: uppercase;
    padding: 0.8rem 1.8rem;
    border-radius: 4px;
    text-decoration: none;
    text-align: center;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
    letter-spacing: 0.08em;
  }
  .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(61,255,84,0.3);
  }
</style>
</head>
<body>

<div class="setup-card">
  <h1>Database Setup</h1>
  <div class="status-log">
    <?php
    function logStatus($message, $type = 'info') {
        $class = 'log-info';
        if ($type === 'success') {
            $class = 'log-success';
        } elseif ($type === 'error') {
            $class = 'log-error';
        }
        echo "<div class='log-item {$class}'>" . htmlspecialchars($message) . "</div>";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    try {
        // 1. Connect to MySQL Server (Try admin/root first to create database and user)
        logStatus("Connecting to MySQL server as admin (root)...");
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", "root", "", $options);
            logStatus("MySQL Server connected as root successfully.", "success");
        } catch (PDOException $e) {
            logStatus("Root connection failed. Connecting with config.php settings...");
            $pdo = getDbConnection(false);
            logStatus("MySQL Server connected successfully.", "success");
        }

        // 2. Create database if not exists
        logStatus("Creating database '" . DB_NAME . "' if it doesn't exist...");
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        logStatus("Database '" . DB_NAME . "' verified/created successfully.", "success");

        // Create restricted database user and grant privileges
        logStatus("Setting up restricted app user 'app_user'...");
        try {
            $pdo->exec("CREATE USER IF NOT EXISTS 'app_user'@'localhost' IDENTIFIED BY 'MonsterAppSecure2026!'");
            $pdo->exec("GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP ON `" . DB_NAME . "`.* TO 'app_user'@'localhost'");
            $pdo->exec("FLUSH PRIVILEGES");
            logStatus("Restricted user 'app_user' setup completed.", "success");
        } catch (PDOException $userEx) {
            logStatus("Note: 'app_user' setup skipped (already exists or insufficient privileges): " . $userEx->getMessage(), "info");
        }

        // 3. Connect to database using configuration settings
        logStatus("Connecting to database '" . DB_NAME . "'...");
        $pdo = getDbConnection(true);

        // 4. Create users table
        logStatus("Creating table 'users'...");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        logStatus("Table 'users' ready.", "success");

        // 5. Create subscriptions table
        logStatus("Creating table 'subscriptions'...");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `subscriptions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `subscribed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        logStatus("Table 'subscriptions' ready.", "success");

        // 6. Create products table
        logStatus("Creating table 'products'...");
        $pdo->exec("DROP TABLE IF EXISTS `product_benefits`");
        $pdo->exec("DROP TABLE IF EXISTS `benefits`");
        $pdo->exec("DROP TABLE IF EXISTS `products`");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `products` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `line_tag` VARCHAR(100) NOT NULL,
            `category` VARCHAR(50) NOT NULL,
            `tagline` VARCHAR(255) NOT NULL,
            `image_url` VARCHAR(255) DEFAULT NULL,
            `caffeine` INT NOT NULL,
            `calories` INT NOT NULL,
            `sugar` INT NOT NULL,
            `size` VARCHAR(50) NOT NULL,
            `price` DECIMAL(10,2) NOT NULL,
            `volume` VARCHAR(50) NOT NULL,
            `accent_color` VARCHAR(20) NOT NULL,
            `can_accent` VARCHAR(20) DEFAULT NULL,
            `can_band_bg` VARCHAR(20) DEFAULT NULL,
            `can_m_color` VARCHAR(20) DEFAULT NULL,
            `can_m_shadow` VARCHAR(100) DEFAULT NULL,
            `can_label_color` VARCHAR(20) DEFAULT NULL,
            `can_label` VARCHAR(100) DEFAULT NULL,
            `display_order` INT DEFAULT 0,
            `sweetness` INT DEFAULT 50,
            `tartness` INT DEFAULT 50,
            `is_sugar_free` BOOLEAN DEFAULT FALSE,
            `caffeine_level` INT DEFAULT 50,
            `recovery_factor` INT DEFAULT 50,
            `tartness_level` INT DEFAULT 50,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_category` (`category`),
            INDEX `idx_display_order` (`display_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        logStatus("Table 'products' ready.", "success");

        // Create benefits table
        logStatus("Creating table 'benefits'...");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `benefits` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `tag` VARCHAR(50) NOT NULL UNIQUE,
            `name` VARCHAR(100) NOT NULL,
            `icon` VARCHAR(10) NOT NULL,
            `description` VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        logStatus("Table 'benefits' ready.", "success");

        // Create product_benefits table
        logStatus("Creating table 'product_benefits'...");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `product_benefits` (
            `product_id` INT NOT NULL,
            `benefit_id` INT NOT NULL,
            PRIMARY KEY (`product_id`, `benefit_id`),
            FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`benefit_id`) REFERENCES `benefits`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        logStatus("Table 'product_benefits' ready.", "success");

        // 7. Seed Admin user if not exists
        logStatus("Checking for existing administrator accounts...");
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `username` = ?");
        $stmt->execute(['admin']);
        if ($stmt->fetchColumn() == 0) {
            logStatus("Seeding default administrator account ('admin' / 'adminpassword')...");
            $hashedPass = password_hash('adminpassword', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO `users` (`username`, `password`) VALUES (?, ?)");
            $stmt->execute(['admin', $hashedPass]);
            logStatus("Default admin user created successfully.", "success");
        } else {
            logStatus("Admin user 'admin' already exists. Skipping.");
        }

        // 8. Seed Products if empty
        logStatus("Checking for existing products in table...");
        $count = $pdo->query("SELECT COUNT(*) FROM `products`")->fetchColumn();
        if ($count == 0) {
            logStatus("Seeding products...");
            
            $products = [
                // CORE LINE
                [
                    'name' => 'Original Green "OG"',
                    'line_tag' => 'The Original Line',
                    'category' => 'core',
                    'tagline' => 'Sweet and salty. Tastes like Monster.',
                    'image_url' => 'images/product_pasted_6a3dd8541582f.png',
                    'caffeine' => 160,
                    'calories' => 230,
                    'sugar' => 54,
                    'size' => '16oz',
                    'price' => 3.29,
                    'volume' => '473ml can',
                    'accent_color' => '#FF5BA0',
                    'can_accent' => '#1a1a1a',
                    'can_band_bg' => null,
                    'can_m_color' => null,
                    'can_m_shadow' => null,
                    'can_label_color' => null,
                    'can_label' => 'MONSTER<br>ORIGINAL',
                    'display_order' => 1
                ],
                [
                    'name' => 'Zero Sugar',
                    'image_url' => 'images/product_pasted_6a3dd1a35ad5c.png',
                    'line_tag' => 'The Original Line',
                    'category' => 'core',
                    'tagline' => 'Same Monster hit. Zero sugar.',
                    'caffeine' => 160,
                    'calories' => 10,
                    'sugar' => 0,
                    'size' => '16oz',
                    'price' => 3.29,
                    'volume' => '473ml can',
                    'accent_color' => '#3DFF54',
                    'can_accent' => '#0d2e0d',
                    'can_band_bg' => '#0d2e0d',
                    'can_m_color' => null,
                    'can_m_shadow' => null,
                    'can_label_color' => null,
                    'can_label' => 'MONSTER<br>ZERO SUGAR',
                    'display_order' => 2
                ],
                [
                    'name' => 'Lo-Carb',
                    'image_url' => 'images/product_pasted_6a3dd1c9662c6.png',
                    'line_tag' => 'The Original Line',
                    'category' => 'core',
                    'tagline' => 'Half the calories. All the Monster.',
                    'caffeine' => 140,
                    'calories' => 20,
                    'sugar' => 6,
                    'size' => '16oz',
                    'price' => 3.29,
                    'volume' => '473ml can',
                    'accent_color' => '#4AB8FF',
                    'can_accent' => '#0d1e2e',
                    'can_band_bg' => '#0d1e2e',
                    'can_m_color' => '#4AB8FF',
                    'can_m_shadow' => 'rgba(74,184,255,.8)',
                    'can_label_color' => null,
                    'can_label' => 'MONSTER<br>LO-CARB',
                    'display_order' => 3
                ],
                
                // ULTRA GROUP
                [
                    'name' => 'Zero Ultra',
                    'image_url' => 'images/product_pasted_6a3dd212f3e73.png',
                    'line_tag' => 'Monster Ultra',
                    'category' => 'ultra',
                    'tagline' => '"White Monster" — crisp citrus, zero sugar.',
                    'caffeine' => 140,
                    'calories' => 10,
                    'sugar' => 0,
                    'size' => '16oz',
                    'price' => 3.49,
                    'volume' => '473ml can',
                    'accent_color' => '#F5F5F5',
                    'can_accent' => '#1a1a1a',
                    'can_band_bg' => '#1e1e1e',
                    'can_m_color' => '#fff',
                    'can_m_shadow' => null,
                    'can_label_color' => '#bbb',
                    'can_label' => 'ULTRA<br>WHITE',
                    'display_order' => 4
                ],
                [
                    'name' => 'Ultra Red',
                    'image_url' => 'images/product_pasted_6a3dd23f6c97e.png',
                    'line_tag' => 'Monster Ultra',
                    'category' => 'ultra',
                    'tagline' => 'Mixed berry punch, zero sugar fire.',
                    'caffeine' => 140,
                    'calories' => 10,
                    'sugar' => 0,
                    'size' => '16oz',
                    'price' => 3.49,
                    'volume' => '473ml can',
                    'accent_color' => '#FF3B3B',
                    'can_accent' => '#2e0000',
                    'can_band_bg' => '#2e0000',
                    'can_m_color' => '#FF3B3B',
                    'can_m_shadow' => 'rgba(255,59,59,.8)',
                    'can_label_color' => null,
                    'can_label' => 'ULTRA RED',
                    'display_order' => 5
                ],
                [
                    'name' => 'Ultra Sunrise',
                    'image_url' => 'images/product_pasted_6a3dd2516e64a.png',
                    'line_tag' => 'Monster Ultra',
                    'category' => 'ultra',
                    'tagline' => 'Orange-citrus sunrise, zero sugar.',
                    'caffeine' => 140,
                    'calories' => 10,
                    'sugar' => 0,
                    'size' => '16oz',
                    'price' => 3.49,
                    'volume' => '473ml can',
                    'accent_color' => '#FF8F00',
                    'can_accent' => null,
                    'can_band_bg' => '#2e1800',
                    'can_m_color' => '#FF8F00',
                    'can_m_shadow' => 'rgba(255,143,0,.8)',
                    'can_label_color' => null,
                    'can_label' => 'ULTRA SUNRISE',
                    'display_order' => 6
                ],
                [
                    'name' => 'Ultra Violet',
                    'image_url' => 'images/product_pasted_6a3dd27cedf36.png',
                    'line_tag' => 'Monster Ultra',
                    'category' => 'ultra',
                    'tagline' => 'Grape citrus with attitude, zero sugar.',
                    'caffeine' => 140,
                    'calories' => 10,
                    'sugar' => 0,
                    'size' => '16oz',
                    'price' => 3.49,
                    'volume' => '473ml can',
                    'accent_color' => '#9B59FF',
                    'can_accent' => null,
                    'can_band_bg' => '#1a0d2e',
                    'can_m_color' => '#9B59FF',
                    'can_m_shadow' => 'rgba(155,89,255,.8)',
                    'can_label_color' => null,
                    'can_label' => 'ULTRA VIOLET',
                    'display_order' => 7
                ],
                [
                    'name' => 'Ultra Paradise',
                    'image_url' => 'images/product_pasted_6a3dd295331bf.png',
                    'line_tag' => 'Monster Ultra',
                    'category' => 'ultra',
                    'tagline' => 'Kiwi + lime + cucumber, zero sugar.',
                    'caffeine' => 140,
                    'calories' => 10,
                    'sugar' => 0,
                    'size' => '16oz',
                    'price' => 3.49,
                    'volume' => '473ml can',
                    'accent_color' => '#00D4B4',
                    'can_accent' => null,
                    'can_band_bg' => '#002e28',
                    'can_m_color' => '#00D4B4',
                    'can_m_shadow' => 'rgba(0,212,180,.8)',
                    'can_label_color' => null,
                    'can_label' => 'ULTRA PARADISE',
                    'display_order' => 8
                ],
                [
                    'name' => 'Ultra Rosá',
                    'image_url' => 'images/product_pasted_6a3dd2a45e5c0.png',
                    'line_tag' => 'Monster Ultra',
                    'category' => 'ultra',
                    'tagline' => 'Peach, nectarine, passionfruit. Zero sugar.',
                    'caffeine' => 140,
                    'calories' => 10,
                    'sugar' => 0,
                    'size' => '16oz',
                    'price' => 3.49,
                    'volume' => '473ml can',
                    'accent_color' => '#FF5BA0',
                    'can_accent' => null,
                    'can_band_bg' => '#2e0019',
                    'can_m_color' => '#FF5BA0',
                    'can_m_shadow' => 'rgba(255,91,160,.8)',
                    'can_label_color' => null,
                    'can_label' => 'ULTRA ROSÁ',
                    'display_order' => 9
                ],
                
                // JUICE GROUP
                [
                    'name' => 'Mango Loco',
                    'image_url' => 'images/product_pasted_6a3dd3133943f.png',
                    'line_tag' => 'Juice Monster',
                    'category' => 'juice',
                    'tagline' => 'Real mango juice. Tropical overdrive.',
                    'caffeine' => 160,
                    'calories' => 230,
                    'sugar' => 56,
                    'size' => '16oz',
                    'price' => 3.49,
                    'volume' => '473ml can',
                    'accent_color' => '#FF6B35',
                    'can_accent' => null,
                    'can_band_bg' => '#2e1008',
                    'can_m_color' => '#FF6B35',
                    'can_m_shadow' => 'rgba(255,107,53,.8)',
                    'can_label_color' => null,
                    'can_label' => 'MANGO LOCO',
                    'display_order' => 10
                ],
                [
                    'name' => 'Pacific Punch',
                    'image_url' => 'images/product_pasted_6a3dd3252f490.png',
                    'line_tag' => 'Juice Monster',
                    'category' => 'juice',
                    'tagline' => 'Island punch with a savage kick.',
                    'caffeine' => 160,
                    'calories' => 210,
                    'sugar' => 50,
                    'size' => '16oz',
                    'price' => 3.49,
                    'volume' => '473ml can',
                    'accent_color' => '#FF3B6B',
                    'can_accent' => null,
                    'can_band_bg' => '#2e0012',
                    'can_m_color' => '#FF3B6B',
                    'can_m_shadow' => 'rgba(255,59,107,.8)',
                    'can_label_color' => null,
                    'can_label' => 'PACIFIC PUNCH',
                    'display_order' => 11
                ],
                [
                    'name' => 'Voodoo Grape',
                    'image_url' => 'images/product_pasted_6a3dd33ad8eca.png',
                    'line_tag' => 'Juice Monster',
                    'category' => 'juice',
                    'tagline' => 'Dark grape juice, mysteriously smooth.',
                    'caffeine' => 160,
                    'calories' => 220,
                    'sugar' => 52,
                    'size' => '16oz',
                    'price' => 3.49,
                    'volume' => '473ml can',
                    'accent_color' => '#9B59FF',
                    'can_accent' => null,
                    'can_band_bg' => '#1a0d2e',
                    'can_m_color' => '#9B59FF',
                    'can_m_shadow' => 'rgba(155,89,255,.8)',
                    'can_label_color' => null,
                    'can_label' => 'VOODOO GRAPE',
                    'display_order' => 12
                ],
                [
                    'name' => 'Strawberry Lemonade',
                    'image_url' => 'images/product_pasted_6a3dd34c8b675.png',
                    'line_tag' => 'Juice Monster',
                    'category' => 'juice',
                    'tagline' => 'Tart lemonade with real strawberry juice.',
                    'caffeine' => 160,
                    'calories' => 200,
                    'sugar' => 48,
                    'size' => '16oz',
                    'price' => 3.49,
                    'volume' => '473ml can',
                    'accent_color' => '#FF5BA0',
                    'can_accent' => null,
                    'can_band_bg' => '#2e001a',
                    'can_m_color' => '#FF5BA0',
                    'can_m_shadow' => 'rgba(255,91,160,.8)',
                    'can_label_color' => null,
                    'can_label' => 'STRAWBERRY LEMONADE',
                    'display_order' => 13
                ],
                [
                    'name' => 'Bad Apple',
                    'image_url' => 'images/product_pasted_6a3dd35d32005.png',
                    'line_tag' => 'Juice Monster',
                    'category' => 'juice',
                    'tagline' => 'Crisp apple with a rebellious edge.',
                    'caffeine' => 160,
                    'calories' => 220,
                    'sugar' => 50,
                    'size' => '16oz',
                    'price' => 3.49,
                    'volume' => '473ml can',
                    'accent_color' => '#5BA85B',
                    'can_accent' => null,
                    'can_band_bg' => '#0d1f0d',
                    'can_m_color' => '#5BA85B',
                    'can_m_shadow' => 'rgba(91,168,91,.8)',
                    'can_label_color' => null,
                    'can_label' => 'BAD APPLE',
                    'display_order' => 14
                ],
                [
                    'name' => 'Pipeline Punch',
                    'image_url' => 'images/product_pasted_6a3dd36b5ded4.png',
                    'line_tag' => 'Juice Monster',
                    'category' => 'juice',
                    'tagline' => 'Passion fruit + orange + guava — barrel ride.',
                    'caffeine' => 160,
                    'calories' => 220,
                    'sugar' => 50,
                    'size' => '16oz',
                    'price' => 3.49,
                    'volume' => '473ml can',
                    'accent_color' => '#FF8F00',
                    'can_accent' => null,
                    'can_band_bg' => '#2e1800',
                    'can_m_color' => '#FF8F00',
                    'can_m_shadow' => 'rgba(255,143,0,.8)',
                    'can_label_color' => null,
                    'can_label' => 'PIPELINE PUNCH',
                    'display_order' => 15
                ],
                
                // JAVA GROUP
                [
                    'name' => 'Mean Bean',
                    'image_url' => 'images/product_pasted_6a3dd383f0b15.png',
                    'line_tag' => 'Java Monster',
                    'category' => 'java',
                    'tagline' => 'Coffee + cream. The original Monster coffee.',
                    'caffeine' => 188,
                    'calories' => 220,
                    'sugar' => 35,
                    'size' => '15oz',
                    'price' => 3.99,
                    'volume' => '443ml can',
                    'accent_color' => '#8B5E3C',
                    'can_accent' => null,
                    'can_band_bg' => '#1a100a',
                    'can_m_color' => '#8B5E3C',
                    'can_m_shadow' => 'rgba(139,94,60,.8)',
                    'can_label_color' => null,
                    'can_label' => 'JAVA MEAN BEAN',
                    'display_order' => 16
                ],
                [
                    'name' => 'Loca Moca',
                    'image_url' => 'images/product_pasted_6a3dd391dfef6.png',
                    'line_tag' => 'Java Monster',
                    'category' => 'java',
                    'tagline' => 'Mocha coffee. Dark and intense.',
                    'caffeine' => 188,
                    'calories' => 220,
                    'sugar' => 35,
                    'size' => '15oz',
                    'price' => 3.99,
                    'volume' => '443ml can',
                    'accent_color' => '#6B3A3A',
                    'can_accent' => null,
                    'can_band_bg' => '#1a0a0a',
                    'can_m_color' => '#6B3A3A',
                    'can_m_shadow' => 'rgba(107,58,58,.8)',
                    'can_label_color' => null,
                    'can_label' => 'LOCA MOCA',
                    'display_order' => 17
                ],
                [
                    'name' => 'Salted Caramel',
                    'image_url' => 'images/product_pasted_6a3dd3a2dbb64.png',
                    'line_tag' => 'Java Monster',
                    'category' => 'java',
                    'tagline' => 'Sweet caramel, touch of salt, full espresso hit.',
                    'caffeine' => 188,
                    'calories' => 220,
                    'sugar' => 36,
                    'size' => '15oz',
                    'price' => 3.99,
                    'volume' => '443ml can',
                    'accent_color' => '#D4A853',
                    'can_accent' => null,
                    'can_band_bg' => '#2e1e08',
                    'can_m_color' => '#D4A853',
                    'can_m_shadow' => 'rgba(212,168,83,.8)',
                    'can_label_color' => null,
                    'can_label' => 'SALTED CARAMEL',
                    'display_order' => 18
                ],
                [
                    'name' => 'Café Latte',
                    'image_url' => 'images/product_pasted_6a3dd3b2961fd.png',
                    'line_tag' => 'Java Monster',
                    'category' => 'java',
                    'tagline' => 'Smooth latte, energy amp. Morning sorted.',
                    'caffeine' => 188,
                    'calories' => 200,
                    'sugar' => 32,
                    'size' => '15oz',
                    'price' => 3.99,
                    'volume' => '443ml can',
                    'accent_color' => '#B8C4CC',
                    'can_accent' => null,
                    'can_band_bg' => '#141a1e',
                    'can_m_color' => '#B8C4CC',
                    'can_m_shadow' => 'rgba(184,196,204,.5)',
                    'can_label_color' => null,
                    'can_label' => 'CAFÉ LATTE',
                    'display_order' => 19
                ],
                [
                    'name' => 'Irish Crème',
                    'image_url' => 'images/product_pasted_6a3dd3e5f1012.png',
                    'line_tag' => 'Java Monster',
                    'category' => 'java',
                    'tagline' => 'Coffee meets Irish cream. Dangerously smooth.',
                    'caffeine' => 188,
                    'calories' => 220,
                    'sugar' => 35,
                    'size' => '15oz',
                    'price' => 3.99,
                    'volume' => '443ml can',
                    'accent_color' => '#3DCC6B',
                    'can_accent' => null,
                    'can_band_bg' => '#0a1f12',
                    'can_m_color' => '#3DCC6B',
                    'can_m_shadow' => 'rgba(61,204,107,.8)',
                    'can_label_color' => null,
                    'can_label' => 'IRISH CRÈME',
                    'display_order' => 20
                ],
                [
                    'name' => 'Killer Brew',
                    'image_url' => 'images/product_pasted_6a3dd3fe21f9e.png',
                    'line_tag' => 'Java Monster',
                    'category' => 'java',
                    'tagline' => 'Cold brew intensity. No mercy.',
                    'caffeine' => 200,
                    'calories' => 210,
                    'sugar' => 30,
                    'size' => '15oz',
                    'price' => 3.99,
                    'volume' => '443ml can',
                    'accent_color' => '#8B5E3C',
                    'can_accent' => null,
                    'can_band_bg' => '#0f0a05',
                    'can_m_color' => '#8B5E3C',
                    'can_m_shadow' => 'rgba(139,94,60,.8)',
                    'can_label_color' => null,
                    'can_label' => 'KILLER BREW MEAN BEAN',
                    'display_order' => 21
                ],
                
                // REHAB GROUP
                [
                    'name' => 'Tea + Lemonade',
                    'image_url' => 'images/product_pasted_6a3dd411a753b.png',
                    'line_tag' => 'Rehab Monster',
                    'category' => 'rehab',
                    'tagline' => 'Non-carbonated. Tea, lemonade, electrolytes.',
                    'caffeine' => 150,
                    'calories' => 25,
                    'sugar' => 3,
                    'size' => '16oz',
                    'price' => 3.49,
                    'volume' => '473ml can',
                    'accent_color' => '#F5C842',
                    'can_accent' => null,
                    'can_band_bg' => '#2e2408',
                    'can_m_color' => '#F5C842',
                    'can_m_shadow' => 'rgba(245,200,66,.8)',
                    'can_label_color' => null,
                    'can_label' => 'REHAB TEA + LEMONADE',
                    'display_order' => 22
                ],
                [
                    'name' => 'Peach Tea',
                    'image_url' => 'images/product_pasted_6a3dd420e5e8b.png',
                    'line_tag' => 'Rehab Monster',
                    'category' => 'rehab',
                    'tagline' => 'Sweet peach tea energy. Smooth recovery.',
                    'caffeine' => 150,
                    'calories' => 25,
                    'sugar' => 3,
                    'size' => '16oz',
                    'price' => 3.49,
                    'volume' => '473ml can',
                    'accent_color' => '#FF8F4B',
                    'can_accent' => null,
                    'can_band_bg' => '#2e150a',
                    'can_m_color' => '#FF8F4B',
                    'can_m_shadow' => 'rgba(255,143,75,.8)',
                    'can_label_color' => null,
                    'can_label' => 'REHAB PEACH TEA',
                    'display_order' => 23
                ],
                [
                    'name' => 'Wild Berry Tea',
                    'image_url' => 'images/product_pasted_6a3dd43423a75.png',
                    'line_tag' => 'Rehab Monster',
                    'category' => 'rehab',
                    'tagline' => 'Mixed berry tea, electrolytes, no carbonation.',
                    'caffeine' => 150,
                    'calories' => 25,
                    'sugar' => 3,
                    'size' => '16oz',
                    'price' => 3.49,
                    'volume' => '473ml can',
                    'accent_color' => '#8A4FBF',
                    'can_accent' => null,
                    'can_band_bg' => '#160a25',
                    'can_m_color' => '#8A4FBF',
                    'can_m_shadow' => 'rgba(138,79,191,.8)',
                    'can_label_color' => null,
                    'can_label' => 'REHAB WILD BERRY',
                    'display_order' => 24
                ],
                [
                    'name' => 'Green Tea',
                    'image_url' => 'images/product_pasted_6a3dd44bbdb19.png',
                    'line_tag' => 'Rehab Monster',
                    'category' => 'rehab',
                    'tagline' => 'Antioxidant green tea meet monster energy.',
                    'caffeine' => 150,
                    'calories' => 25,
                    'sugar' => 3,
                    'size' => '16oz',
                    'price' => 3.49,
                    'volume' => '473ml can',
                    'accent_color' => '#3DCC6B',
                    'can_accent' => null,
                    'can_band_bg' => '#0a1f12',
                    'can_m_color' => '#3DCC6B',
                    'can_m_shadow' => 'rgba(61,204,107,.8)',
                    'can_label_color' => null,
                    'can_label' => 'REHAB GREEN TEA',
                    'display_order' => 25
                ]
            ];
            
            $stmt = $pdo->prepare("INSERT INTO `products` (
                `name`, `line_tag`, `category`, `tagline`, `image_url`, `caffeine`, `calories`, `sugar`, 
                `size`, `price`, `volume`, `accent_color`, `can_accent`, `can_band_bg`, 
                `can_m_color`, `can_m_shadow`, `can_label_color`, `can_label`, `display_order`,
                `sweetness`, `tartness`, `is_sugar_free`, `caffeine_level`, `recovery_factor`, `tartness_level`
            ) VALUES (
                :name, :line_tag, :category, :tagline, :image_url, :caffeine, :calories, :sugar, 
                :size, :price, :volume, :accent_color, :can_accent, :can_band_bg, 
                :can_m_color, :can_m_shadow, :can_label_color, :can_label, :display_order,
                :sweetness, :tartness, :is_sugar_free, :caffeine_level, :recovery_factor, :tartness_level
            )");
            
            foreach ($products as $prod) {
                if (!isset($prod['image_url'])) {
                    $prod['image_url'] = null;
                }
                
                // Derive sweetness and tartness based on product category & sugar content
                $sugar = isset($prod['sugar']) ? (int)$prod['sugar'] : 0;
                $cat = isset($prod['category']) ? $prod['category'] : '';
                
                if ($cat === 'java') {
                    $prod['sweetness'] = rand(75, 95);
                    $prod['tartness'] = rand(10, 20);
                } elseif ($sugar === 0) {
                    $prod['sweetness'] = rand(30, 48);
                    $prod['tartness'] = rand(45, 75);
                } else {
                    $prod['sweetness'] = min(100, max(20, $sugar * 1.4 + rand(-8, 8)));
                    $prod['tartness'] = rand(20, 55);
                }
                
                // Derive Benefit Tags and Power Specs metrics
                $prod['is_sugar_free'] = ($sugar === 0) ? 1 : 0;
                $prod['caffeine_level'] = min(100, round(($prod['caffeine'] / 160) * 100));
                
                if ($cat === 'rehab') {
                    $prod['recovery_factor'] = rand(80, 98);
                } elseif ($cat === 'juice') {
                    $prod['recovery_factor'] = rand(50, 75);
                } else {
                    $prod['recovery_factor'] = rand(20, 48);
                }
                
                $prod['tartness_level'] = $prod['tartness'];
                
                $stmt->execute($prod);
            }
            logStatus("Seeded " . count($products) . " products successfully.", "success");

            // Seed benefits
            logStatus("Seeding benefits lookup data...");
            $benefitsSeed = [
                ['tag' => 'caffeine', 'name' => 'High Caffeine', 'icon' => '⚡', 'description' => 'Provides sharp focus and rapid energy boost.'],
                ['tag' => 'taurine', 'name' => 'Taurine', 'icon' => '◆', 'description' => 'Supports cell hydration and muscular endurance.'],
                ['tag' => 'ginseng', 'name' => 'Panax Ginseng', 'icon' => '🌿', 'description' => 'Traditional adaptogen for metabolic endurance.'],
                ['tag' => 'electrolytes', 'name' => 'Electrolytes', 'icon' => '💧', 'description' => 'Hydration blend for quick recovery.']
            ];
            
            $bInsertStmt = $pdo->prepare("INSERT INTO `benefits` (`tag`, `name`, `icon`, `description`) VALUES (:tag, :name, :icon, :description)");
            foreach ($benefitsSeed as $bSeed) {
                $bInsertStmt->execute($bSeed);
            }
            logStatus("Benefits seeded successfully.", "success");
            
            // Get inserted benefit IDs mapped by tag
            $benefitIds = [];
            $bSelect = $pdo->query("SELECT id, tag FROM benefits")->fetchAll();
            foreach ($bSelect as $row) {
                $benefitIds[$row['tag']] = $row['id'];
            }
            
            // Link benefits to products
            logStatus("Linking benefits to products in junction table...");
            $allProds = $pdo->query("SELECT id, category FROM products")->fetchAll();
            $pbInsert = $pdo->prepare("INSERT INTO product_benefits (product_id, benefit_id) VALUES (?, ?)");
            
            foreach ($allProds as $prodRow) {
                $pId = $prodRow['id'];
                $cat = $prodRow['category'];
                
                // Every product gets caffeine, taurine, ginseng
                $pbInsert->execute([$pId, $benefitIds['caffeine']]);
                $pbInsert->execute([$pId, $benefitIds['taurine']]);
                $pbInsert->execute([$pId, $benefitIds['ginseng']]);
                
                // Rehab products also get electrolytes
                if ($cat === 'rehab') {
                    $pbInsert->execute([$pId, $benefitIds['electrolytes']]);
                }
            }
            logStatus("Junction table product_benefits seeded successfully.", "success");
        } else {
            logStatus("Products table already populated. Skipping seeding.");
        }

        logStatus("All migrations and seeding successfully completed!", "success");

    } catch (Exception $e) {
        logStatus("SETUP FAILED: " . $e->getMessage(), "error");
    }
    ?>
  </div>
  <a href="index.php" class="btn">View Live Page</a>
  <a href="admin.php" class="btn" style="background:#fff; color:#000; margin-left:10px;">Admin Panel</a>
</div>

</body>
</html>
