<?php
/**
 * Monster Energy Backend — Admin Dashboard
 */
require_once 'config.php';
require_once 'auth.php';

$error = '';
$success = '';

// CSRF validation on any POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        header('HTTP/1.1 403 Forbidden');
        die('Error: CSRF token validation failed.');
    }
}

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Handle Login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_btn'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Both username and password are required.';
    } else {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $user['username'];
                
                // Regenerate CSRF token upon successful login for security
                try {
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } catch (Exception $ex) {
                    $_SESSION['csrf_token'] = md5(uniqid(rand(), true));
                }
                
                header('Location: admin.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database connection error: ' . $e->getMessage();
        }
    }
}

// Is Admin Logged In?
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if (!$isLoggedIn) {
    if (isset($_GET['action']) || ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['login_btn']))) {
        requireAdmin();
    }
} else {
    if (isset($_GET['action']) && $_GET['action'] !== 'logout') {
        requireAdmin();
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['login_btn'])) {
        requireAdmin();
    }
}

// Handle CRUD Operations (only if logged in)
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getDbConnection();
    
    // Save Product (Add or Update)
    if (isset($_POST['save_product'])) {
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $name = sanitizeInput($_POST['name'] ?? '');
        $line_tag = sanitizeInput($_POST['line_tag'] ?? '');
        $category = sanitizeInput($_POST['category'] ?? '');
        $tagline = sanitizeInput($_POST['tagline'] ?? '');
        $image_url = !empty($_POST['image_url']) ? sanitizeInput($_POST['image_url']) : null;
        
        // Handle pasted base64 image data
        $pasted_image = $_POST['pasted_image'] ?? '';
        if (!empty($pasted_image) && strpos($pasted_image, 'data:image/') === 0) {
            preg_match('/^data:image\/(\w+);base64,/', $pasted_image, $matches);
            $ext = $matches[1] ?? 'png';
            if ($ext === 'jpeg') $ext = 'jpg';
            
            $base64_data = substr($pasted_image, strpos($pasted_image, ',') + 1);
            $decoded_data = base64_decode($base64_data);
            
            if ($decoded_data !== false) {
                $target_dir = 'images/';
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }
                
                $filename = $target_dir . 'product_pasted_' . uniqid() . '.' . $ext;
                if (file_put_contents($filename, $decoded_data) !== false) {
                    $image_url = $filename;
                }
            }
        }

        $caffeine = (int)($_POST['caffeine'] ?? 0);
        $calories = (int)($_POST['calories'] ?? 0);
        $sugar = (int)($_POST['sugar'] ?? 0);
        $size = sanitizeInput($_POST['size'] ?? '');
        $price = (float)($_POST['price'] ?? 0.0);
        $volume = sanitizeInput($_POST['volume'] ?? '');
        $accent_color = sanitizeInput($_POST['accent_color'] ?? '#3DFF54');
        $can_accent = !empty($_POST['can_accent']) ? sanitizeInput($_POST['can_accent']) : null;
        $can_band_bg = !empty($_POST['can_band_bg']) ? sanitizeInput($_POST['can_band_bg']) : null;
        $can_m_color = !empty($_POST['can_m_color']) ? sanitizeInput($_POST['can_m_color']) : null;
        $can_m_shadow = !empty($_POST['can_m_shadow']) ? sanitizeInput($_POST['can_m_shadow']) : null;
        $can_label_color = !empty($_POST['can_label_color']) ? sanitizeInput($_POST['can_label_color']) : null;
        $can_label = !empty($_POST['can_label']) ? sanitizeInput($_POST['can_label'], '<br>') : null;
        $display_order = (int)($_POST['display_order'] ?? 0);
        
        // Derive specs metrics dynamically for consistency
        $is_sugar_free = ($sugar === 0) ? 1 : 0;
        $caffeine_level = min(100, round(($caffeine / 160) * 100));
        
        if ($category === 'rehab') {
            $recovery_factor = 90;
        } elseif ($category === 'juice') {
            $recovery_factor = 65;
        } else {
            $recovery_factor = 35;
        }
        
        if ($category === 'java') {
            $sweetness = 85;
            $tartness = 15;
        } elseif ($sugar === 0) {
            $sweetness = 40;
            $tartness = 60;
        } else {
            $sweetness = min(100, max(20, (int)round($sugar * 1.4 + 5)));
            $tartness = 35;
        }
        $tartness_level = $tartness;
        
        if (empty($name) || empty($category) || empty($line_tag)) {
            $error = 'Name, Line Tag, and Category are required.';
        } else {
            try {
                if ($productId === 0) {
                    // INSERT
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
                    $stmt->execute([
                        'name' => $name, 'line_tag' => $line_tag, 'category' => $category, 'tagline' => $tagline, 'image_url' => $image_url,
                        'caffeine' => $caffeine, 'calories' => $calories, 'sugar' => $sugar, 'size' => $size,
                        'price' => $price, 'volume' => $volume, 'accent_color' => $accent_color, 'can_accent' => $can_accent,
                        'can_band_bg' => $can_band_bg, 'can_m_color' => $can_m_color, 'can_m_shadow' => $can_m_shadow,
                        'can_label_color' => $can_label_color, 'can_label' => $can_label, 'display_order' => $display_order,
                        'sweetness' => $sweetness, 'tartness' => $tartness, 'is_sugar_free' => $is_sugar_free,
                        'caffeine_level' => $caffeine_level, 'recovery_factor' => $recovery_factor, 'tartness_level' => $tartness_level
                    ]);
                    $productId = $pdo->lastInsertId();
                    $success = "Product '{$name}' created successfully.";
                } else {
                    // UPDATE
                    $stmt = $pdo->prepare("UPDATE `products` SET 
                        `name` = :name, `line_tag` = :line_tag, `category` = :category, `tagline` = :tagline, `image_url` = :image_url, 
                        `caffeine` = :caffeine, `calories` = :calories, `sugar` = :sugar, `size` = :size, 
                        `price` = :price, `volume` = :volume, `accent_color` = :accent_color, `can_accent` = :can_accent, 
                        `can_band_bg` = :can_band_bg, `can_m_color` = :can_m_color, `can_m_shadow` = :can_m_shadow, 
                        `can_label_color` = :can_label_color, `can_label` = :can_label, `display_order` = :display_order,
                        `sweetness` = :sweetness, `tartness` = :tartness, `is_sugar_free` = :is_sugar_free,
                        `caffeine_level` = :caffeine_level, `recovery_factor` = :recovery_factor, `tartness_level` = :tartness_level
                        WHERE `id` = :id");
                    $stmt->execute([
                        'name' => $name, 'line_tag' => $line_tag, 'category' => $category, 'tagline' => $tagline, 'image_url' => $image_url,
                        'caffeine' => $caffeine, 'calories' => $calories, 'sugar' => $sugar, 'size' => $size,
                        'price' => $price, 'volume' => $volume, 'accent_color' => $accent_color, 'can_accent' => $can_accent,
                        'can_band_bg' => $can_band_bg, 'can_m_color' => $can_m_color, 'can_m_shadow' => $can_m_shadow,
                        'can_label_color' => $can_label_color, 'can_label' => $can_label, 'display_order' => $display_order,
                        'sweetness' => $sweetness, 'tartness' => $tartness, 'is_sugar_free' => $is_sugar_free,
                        'caffeine_level' => $caffeine_level, 'recovery_factor' => $recovery_factor, 'tartness_level' => $tartness_level,
                        'id' => $productId
                    ]);
                    $success = "Product '{$name}' updated successfully.";
                }
                
                // Synchronize product benefits in junction table
                $bSelect = $pdo->query("SELECT id, tag FROM benefits")->fetchAll();
                $benefitIds = [];
                foreach ($bSelect as $row) {
                    $benefitIds[$row['tag']] = $row['id'];
                }
                
                $pbDelete = $pdo->prepare("DELETE FROM product_benefits WHERE product_id = ?");
                $pbDelete->execute([$productId]);
                
                $pbInsert = $pdo->prepare("INSERT INTO product_benefits (product_id, benefit_id) VALUES (?, ?)");
                if (isset($benefitIds['caffeine'])) $pbInsert->execute([$productId, $benefitIds['caffeine']]);
                if (isset($benefitIds['taurine'])) $pbInsert->execute([$productId, $benefitIds['taurine']]);
                if (isset($benefitIds['ginseng'])) $pbInsert->execute([$productId, $benefitIds['ginseng']]);
                
                if ($category === 'rehab' && isset($benefitIds['electrolytes'])) {
                    $pbInsert->execute([$productId, $benefitIds['electrolytes']]);
                }
                
            } catch (PDOException $e) {
                $error = 'Failed to save product: ' . $e->getMessage();
            }
        }
    }
    
    // Delete Product
    if (isset($_POST['delete_product'])) {
        $productId = (int)($_POST['product_id'] ?? 0);
        if ($productId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM `products` WHERE `id` = ?");
                $stmt->execute([$productId]);
                $success = "Product deleted successfully.";
            } catch (PDOException $e) {
                $error = 'Failed to delete product: ' . $e->getMessage();
            }
        }
    }
}

// Fetch all database elements if logged in
$products = [];
$subscribers = [];
$stats = ['products' => 0, 'subscribers' => 0];

if ($isLoggedIn) {
    try {
        $pdo = getDbConnection();
        // Products
        $products = $pdo->query("SELECT * FROM `products` ORDER BY `category`, `display_order`, `name`")->fetchAll();
        
        // Subscribers
        $subscribers = $pdo->query("SELECT * FROM `subscriptions` ORDER BY `subscribed_at` DESC")->fetchAll();
        
        // Count Stats
        $stats['products'] = count($products);
        $stats['subscribers'] = count($subscribers);
        
    } catch (PDOException $e) {
        $error = 'Error loading dashboard elements: ' . $e->getMessage();
    }
}

// Get editing product data if requested via GET
$editingProduct = null;
if ($isLoggedIn && isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editId = (int)$_GET['id'];
    foreach ($products as $p) {
        if ((int)$p['id'] === $editId) {
            $editingProduct = $p;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Monster Energy — Admin Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Black+Ops+One&family=Outfit:wght@300;400;500;600;700&family=Barlow+Condensed:wght@400;600;700&display=swap" rel="stylesheet">
<style>
/* ============================================================
   TOKENS & CORE VARIABLES
   ============================================================ */
:root {
  --bg:         #030303;
  --bg-deep:    #070707;
  --green:      #3DFF54;
  --green-dim:  rgba(61,255,84,.08);
  --green-glow: rgba(61,255,84,.40);
  --white:      #ffffff;
  --white-dim:  rgba(255,255,255,0.6);
  
  --glass:      rgba(255, 255, 255, 0.03);
  --glass-hi:   rgba(255, 255, 255, 0.06);
  --glass-bd:   rgba(255, 255, 255, 0.1);
  --blur:       15px;

  --font-d:     'Black Ops One', cursive;
  --font-b:     'Outfit', sans-serif;
  --font-c:     'Barlow Condensed', sans-serif;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  background: var(--bg);
  color: var(--white);
  font-family: var(--font-b);
  line-height: 1.5;
  min-height: 100vh;
  overflow-x: hidden;
}

/* ============================================================
   GLASSMORPHISM CARD ATOMS
   ============================================================ */
.glass-card {
  background: var(--glass);
  border: 1px solid var(--glass-bd);
  backdrop-filter: blur(var(--blur));
  -webkit-backdrop-filter: blur(var(--blur));
  border-radius: 12px;
  box-shadow: 0 20px 50px -25px rgba(0,0,0,0.8);
  padding: 2.2rem;
  margin-bottom: 2rem;
}

/* ============================================================
   LAYOUT SYSTEM (SIDEBAR + MAIN)
   ============================================================ */
.dashboard-container {
  display: flex;
  min-height: 100vh;
}

/* Sidebar styling */
.sidebar {
  width: 280px;
  background: var(--bg-deep);
  border-right: 1px solid var(--glass-bd);
  display: flex;
  flex-direction: column;
  padding: 2.5rem 1.8rem;
  position: fixed;
  top: 0; bottom: 0; left: 0;
  z-index: 100;
  justify-content: space-between;
}

.sidebar-top {
  display: flex;
  flex-direction: column;
  gap: 2.5rem;
}

.sidebar-brand {
  display: flex;
  flex-direction: column;
  line-height: 1;
  text-decoration: none;
}
.sidebar-brand .logo-main {
  font-family: var(--font-d);
  font-size: 1.6rem;
  letter-spacing: 0.06em;
  color: var(--white);
}
.sidebar-brand .logo-m { color: var(--green); }
.sidebar-brand .logo-sub {
  font-family: var(--font-c);
  font-size: 0.6rem;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: var(--white-dim);
  margin-top: 6px;
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.85rem 1.2rem;
  border-radius: 8px;
  font-family: var(--font-c);
  font-weight: 700;
  font-size: 0.95rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--white-dim);
  background: transparent;
  border: 1px solid transparent;
  cursor: pointer;
  text-align: left;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  text-decoration: none;
}
.nav-item:hover, .nav-item.active {
  color: var(--green);
  background: var(--green-dim);
  border-color: rgba(61,255,84,0.2);
}
.nav-icon {
  font-size: 1.15rem;
}

.nav-item.nav-logout {
  border-color: rgba(255, 59, 59, 0.1);
  color: rgba(255, 255, 255, 0.5);
}
.nav-item.nav-logout:hover {
  color: #FF3B3B;
  background: rgba(255, 59, 59, 0.08);
  border-color: rgba(255, 59, 59, 0.25);
  box-shadow: 0 0 15px rgba(255, 59, 59, 0.1);
}

.sidebar-footer {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--glass-bd);
}

.admin-profile {
  display: flex;
  align-items: center;
  gap: 0.8rem;
}
.profile-avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: var(--green);
  color: #000;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-d);
  font-size: 1.1rem;
  box-shadow: 0 0 10px var(--green-glow);
}
.profile-meta .username {
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--white);
}
.profile-meta .role {
  font-size: 0.72rem;
  color: var(--white-dim);
  font-family: var(--font-c);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Main Content Wrapper */
.main-wrapper {
  flex: 1;
  margin-left: 280px;
  padding: 3rem 4rem;
  min-width: 0;
  background: radial-gradient(circle at 10% 20%, #0c0c0c 0%, #030303 100%);
  min-height: 100vh;
}

/* Responsive adjustments */
@media (max-width: 992px) {
  .dashboard-container {
    flex-direction: column;
  }
  .sidebar {
    width: 100%;
    position: static;
    border-right: none;
    border-bottom: 1px solid var(--glass-bd);
    padding: 1.5rem;
    gap: 1.5rem;
  }
  .sidebar-top {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
  }
  .sidebar-nav {
    flex-direction: row;
    gap: 0.5rem;
  }
  .sidebar-footer {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    border-top: none;
    padding-top: 0;
  }
  .main-wrapper {
    margin-left: 0;
    padding: 2rem 1.5rem;
  }
}
@media (max-width: 768px) {
  .sidebar-top {
    flex-direction: column;
    align-items: stretch;
  }
  .sidebar-nav {
    flex-wrap: wrap;
  }
  .sidebar-footer {
    border-top: 1px solid var(--glass-bd);
    padding-top: 1.2rem;
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
}

/* ============================================================
   CONTENT COMPONENT STYLING (Cards, Tables, Forms)
   ============================================================ */
.tab-content {
  display: none;
}
.tab-content.active {
  display: block;
  animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.page-title {
  font-family: var(--font-d);
  font-size: 2rem;
  letter-spacing: 0.02em;
  margin-bottom: 2rem;
  text-shadow: 0 0 15px rgba(255,255,255,0.06);
}

.glass-card h3 {
  font-family: var(--font-d);
  font-size: 1.25rem;
  color: var(--white);
  margin-bottom: 1.8rem;
  letter-spacing: 0.02em;
  border-left: 3px solid var(--green);
  padding-left: 0.8rem;
  line-height: 1;
}

/* Stats Cards Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.8rem;
  margin-bottom: 2.5rem;
}
.stat-card {
  padding: 2.2rem;
  display: flex;
  align-items: center;
  gap: 1.8rem;
  position: relative;
  overflow: hidden;
  background: var(--glass);
  border: 1px solid var(--glass-bd);
  backdrop-filter: blur(var(--blur));
  -webkit-backdrop-filter: blur(var(--blur));
  border-radius: 12px;
  box-shadow: 0 20px 50px -25px rgba(0,0,0,0.8);
}
.stat-card::before {
  content: '';
  position: absolute;
  top: 0; right: 0;
  width: 100px; height: 100px;
  background: radial-gradient(circle, var(--green-glow), transparent 70%);
  opacity: 0.15;
  pointer-events: none;
}
.stat-icon {
  font-size: 2.4rem;
  background: rgba(255,255,255,0.02);
  border: 1px solid var(--glass-bd);
  width: 64px; height: 64px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.stat-info {
  display: flex;
  flex-direction: column;
}
.stat-num {
  font-family: var(--font-d);
  font-size: 2.4rem;
  color: var(--green);
  line-height: 1.1;
  text-shadow: 0 0 12px rgba(61,255,84,0.25);
}
.stat-label {
  font-family: var(--font-c);
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--white-dim);
  margin-top: 4px;
}

/* ============================================================
   DATA TABLE STYLING
   ============================================================ */
.table-responsive {
  width: 100%;
  overflow-x: auto;
  border-radius: 10px;
  border: 1px solid var(--glass-bd);
  background: rgba(0, 0, 0, 0.2);
}

table.admin-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}
table.admin-table th, table.admin-table td {
  padding: 1.2rem 1.5rem;
  font-size: 0.9rem;
  border-bottom: 1px solid var(--glass-bd);
  vertical-align: middle;
}
table.admin-table th {
  font-family: var(--font-c);
  font-weight: 700;
  text-transform: uppercase;
  font-size: 0.78rem;
  letter-spacing: 0.12em;
  color: var(--green);
  background: rgba(0,0,0,0.5);
  border-bottom: 1px solid rgba(255, 255, 255, 0.15);
}
table.admin-table tbody tr {
  transition: all 0.2s ease;
}
/* Alternating Row Color */
table.admin-table tbody tr:nth-child(even) {
  background: rgba(255, 255, 255, 0.02);
}
table.admin-table tbody tr:hover {
  background: rgba(61, 255, 84, 0.04);
}

.product-meta {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.product-color-indicator {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  border: 1px solid rgba(255,255,255,0.25);
  box-shadow: 0 0 8px currentColor;
}
.product-name-label {
  font-weight: 600;
  color: var(--white);
  font-size: 0.95rem;
}

/* ============================================================
   FORM & TWO-COLUMN FORM LAYOUT
   ============================================================ */
.form-grid-layout {
  display: grid;
  grid-template-columns: 1.2fr 0.8fr;
  gap: 2.2rem;
  align-items: start;
}
@media (max-width: 950px) {
  .form-grid-layout {
    grid-template-columns: 1fr;
  }
}

.form-group {
  margin-bottom: 1.4rem;
}
.form-group label {
  display: block;
  font-family: var(--font-c);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--white-dim);
  margin-bottom: 0.5rem;
}
.form-group input[type="text"],
.form-group input[type="number"],
.form-group input[type="password"],
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.9rem 1.2rem;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  background: #111111;
  color: #ffffff;
  font-family: var(--font-b);
  font-size: 0.95rem;
  outline: none;
  transition: all 0.25s ease;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  border-color: var(--green);
  box-shadow: 0 0 10px rgba(61,255,84,0.3);
  background: #080808;
}

/* Category Segmented Control */
.segmented-control {
  display: flex;
  background: rgba(0, 0, 0, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 4px;
  gap: 4px;
  width: 100%;
}
.segmented-control input[type="radio"] {
  display: none;
}
.segmented-control label {
  flex: 1;
  text-align: center;
  padding: 0.6rem 0.5rem;
  border-radius: 6px;
  cursor: pointer;
  font-family: var(--font-c);
  font-weight: 600;
  font-size: 0.82rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--white-dim);
  transition: all 0.25s ease;
  margin-bottom: 0 !important;
}
.segmented-control input[type="radio"]:checked + label {
  background: var(--green);
  color: #000000;
  font-weight: 700;
  box-shadow: 0 0 10px rgba(61, 255, 84, 0.4);
}
.segmented-control label:hover {
  color: var(--white);
  background: rgba(255, 255, 255, 0.05);
}
.segmented-control input[type="radio"]:checked + label:hover {
  background: var(--green);
  color: #000000;
}

/* Form section grouping headers */
.form-section-header {
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  margin-top: 2rem;
  padding-top: 1.5rem;
  margin-bottom: 1.2rem;
}
.form-section-header h4 {
  font-family: var(--font-c);
  font-size: 0.9rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--green);
}
.form-section-header p {
  font-size: 0.75rem;
  color: var(--white-dim);
  margin-top: 2px;
}

.form-grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.2rem;
}
.form-grid-3 {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 1.2rem;
}
@media (max-width: 500px) {
  .form-grid-2, .form-grid-3 {
    grid-template-columns: 1fr;
    gap: 0.8rem;
  }
}

/* Image Paste Preview Zone */
.image-paste-zone {
  border: 2px dashed rgba(61,255,84,0.25);
  background: rgba(61,255,84,0.01);
  border-radius: 8px;
  padding: 2rem 1.5rem;
  text-align: center;
  cursor: pointer;
  position: relative;
  min-height: 200px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  transition: all 0.25s ease;
  margin-bottom: 1.2rem;
}
.image-paste-zone:hover, .image-paste-zone.dragover {
  border-color: var(--green);
  background: rgba(61,255,84,0.04);
  box-shadow: 0 0 20px rgba(61,255,84,0.12) inset;
}
.image-paste-zone img {
  max-height: 180px;
  max-width: 100%;
  object-fit: contain;
  border-radius: 4px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.6);
  transition: transform 0.2s;
}
.image-paste-zone img:hover {
  transform: scale(1.02);
}

/* ============================================================
   BUTTONS STYLING with Glow Hover
   ============================================================ */
.btn {
  display: inline-flex; align-items: center; justify-content: center;
  padding: 0.75rem 1.5rem;
  border-radius: 4px;
  font-family: var(--font-c);
  font-weight: 700;
  font-size: 0.88rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  cursor: pointer;
  border: 1px solid transparent;
  transition: all 0.25s ease;
  text-decoration: none;
}
.btn-solid {
  background: var(--green);
  color: #000;
}
.btn-solid:hover {
  transform: translateY(-2px);
  background: #50ff65;
  box-shadow: 0 0 20px rgba(61, 255, 84, 0.8), 0 4px 15px rgba(61, 255, 84, 0.4);
}
.btn-danger {
  background: #FF3B3B;
  color: #fff;
}
.btn-danger:hover {
  transform: translateY(-2px);
  background: #ff5252;
  box-shadow: 0 0 20px rgba(255, 59, 59, 0.8), 0 4px 15px rgba(255, 59, 59, 0.4);
}
.btn-ghost {
  background: transparent;
  border-color: var(--glass-bd);
  color: var(--white);
}
.btn-ghost:hover {
  background: var(--green-dim);
  border-color: var(--green);
  color: var(--green);
  box-shadow: 0 0 15px rgba(61, 255, 84, 0.3);
}

/* ============================================================
   ALERTS & LOGINS
   ============================================================ */
.alert {
  padding: 1.1rem 1.4rem;
  border-radius: 8px;
  margin-bottom: 2rem;
  font-size: 0.92rem;
  border: 1px solid transparent;
}
.alert-error {
  background: rgba(255, 59, 59, 0.05);
  border-color: rgba(255, 59, 59, 0.25);
  color: #FFA3A3;
  box-shadow: 0 0 15px rgba(255,59,59,0.05);
}
.alert-success {
  background: rgba(61, 255, 84, 0.05);
  border-color: rgba(61, 255, 84, 0.25);
  color: #A6FFAA;
  box-shadow: 0 0 15px rgba(61,255,84,0.05);
}

/* Login Layout Card */
.login-body {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 2rem;
  background: radial-gradient(circle at center, #0f1c11 0%, #030303 100%);
}
.login-card {
  width: 100%;
  max-width: 440px;
  padding: 3rem 2.5rem;
  position: relative;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(15px);
  -webkit-backdrop-filter: blur(15px);
  border-radius: 16px;
  box-shadow: 0 30px 60px rgba(0, 0, 0, 0.8), 0 0 40px rgba(61, 255, 84, 0.05);
}
.login-card::after {
  content: '';
  position: absolute;
  inset: -1px;
  border-radius: inherit;
  background: linear-gradient(135deg, rgba(61,255,84,0.3), transparent 60%);
  pointer-events: none;
  z-index: -1;
}
.login-title {
  font-family: var(--font-d);
  font-size: 1.8rem;
  text-align: center;
  margin-bottom: 2.2rem;
  letter-spacing: 0.05em;
  color: var(--white);
  line-height: 1.1;
}
.login-title .logo-m {
  color: var(--green);
}

/* Subscribers filter search headers */
.subs-header-panel {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
  margin-bottom: 1.8rem;
}
.search-input-wrapper {
  max-width: 320px;
  width: 100%;
}
</style>
</head>
<body class="<?php echo !$isLoggedIn ? 'login-body' : ''; ?>">

<?php if (!$isLoggedIn): ?>
  <!-- ── SLEEK LOGIN CARD ── -->
  <div class="login-card">
    <h2 class="login-title"><span class="logo-m">M</span>ONSTER CONTROL</h2>
    
    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="admin.php">
      <?php csrfInput(); ?>
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autocomplete="username">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
      </div>
      <button type="submit" name="login_btn" class="btn btn-solid" style="width: 100%; margin-top: 1.5rem;">Unleash Access</button>
    </form>
  </div>

<?php else: ?>
  <!-- ── PREMIUM SIDEBAR DASHBOARD ── -->
  <div class="dashboard-container">
    
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
      <div class="sidebar-top">
        <a class="sidebar-brand" href="index.php">
          <span class="logo-main"><span class="logo-m">M</span>ONSTER</span>
          <span class="logo-sub">ADMIN SYSTEM</span>
        </a>
        
        <nav class="sidebar-nav">
          <button class="nav-item active" onclick="switchTab(evtClick(event), 'tab-dashboard')">
            <span class="nav-icon">📊</span> Dashboard
          </button>
          <button class="nav-item" id="nav-products" onclick="switchTab(evtClick(event), 'tab-products')">
            <span class="nav-icon">🥫</span> Products
          </button>
          <button class="nav-item" id="nav-subscribers" onclick="switchTab(evtClick(event), 'tab-subscribers')">
            <span class="nav-icon">✉️</span> Subscriptions
          </button>
          <a href="admin.php?action=logout" class="nav-item nav-logout" onclick="return confirm('Are you sure you want to logout?');">
            <span class="nav-icon">🚪</span> Logout
          </a>
        </nav>
      </div>

      <div class="sidebar-footer">
        <div class="admin-profile">
          <div class="profile-avatar">A</div>
          <div class="profile-meta">
            <div class="username"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
            <div class="role">Administrator</div>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="main-wrapper">
      
      <!-- Staged success/error alert logs -->
      <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>

      <!-- ── TAB: DASHBOARD ── -->
      <div id="tab-dashboard" class="tab-content active">
        <h2 class="page-title">Overview Dashboard</h2>
        
        <!-- Summary Counters -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">🥫</div>
            <div class="stat-info">
              <div class="stat-num"><?php echo $stats['products']; ?></div>
              <div class="stat-label">Total Products</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">✉️</div>
            <div class="stat-info">
              <div class="stat-num"><?php echo $stats['subscribers']; ?></div>
              <div class="stat-label">Subscribers</div>
            </div>
          </div>
        </div>

        <!-- Latest Subscriptions Mini log -->
        <div class="glass-card">
          <h3>Recent Subscriptions</h3>
          <?php if (empty($subscribers)): ?>
            <div style="color: var(--white-dim); font-size: 0.9rem;">No subscriptions logs found.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="admin-table">
                <thead>
                  <tr>
                    <th>Email Address</th>
                    <th>Subscribed Date</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $recentSubs = array_slice($subscribers, 0, 5);
                  foreach ($recentSubs as $sub): 
                  ?>
                    <tr>
                      <td style="font-weight: 600; color:#fff;"><?php echo htmlspecialchars($sub['email']); ?></td>
                      <td style="color: var(--white-dim);"><?php echo htmlspecialchars($sub['subscribed_at']); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div style="margin-top: 1.5rem; text-align: right;">
              <button class="btn btn-ghost" onclick="triggerTab('nav-subscribers')">View All Subscriptions</button>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ── TAB: PRODUCTS ── -->
      <div id="tab-products" class="tab-content">
        <h2 class="page-title">Manage Showcase Products</h2>
        
        <!-- Form Add/Edit Staged Card -->
        <div class="glass-card" id="form-section">
          <h3><?php echo $editingProduct ? 'Modify Active Product' : 'Add New Showcase Product'; ?></h3>
          
          <form method="POST" action="admin.php">
            <?php csrfInput(); ?>
            <?php if ($editingProduct): ?>
              <input type="hidden" name="product_id" value="<?php echo $editingProduct['id']; ?>">
            <?php endif; ?>
            
            <!-- Two-Column form structure: left side inputs, right side preview -->
            <div class="form-grid-layout">
              
              <!-- Left Column: Inputs -->
              <div class="form-inputs-column">
                <div class="form-group">
                  <label for="p_name">Product Name *</label>
                  <input type="text" id="p_name" name="name" required value="<?php echo htmlspecialchars($editingProduct['name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                  <label>Category *</label>
                  <div class="segmented-control">
                    <input type="radio" id="cat_core" name="category" value="core" <?php echo (!$editingProduct || $editingProduct['category'] === 'core') ? 'checked' : ''; ?> required>
                    <label for="cat_core">Core</label>
                    
                    <input type="radio" id="cat_ultra" name="category" value="ultra" <?php echo ($editingProduct && $editingProduct['category'] === 'ultra') ? 'checked' : ''; ?> required>
                    <label for="cat_ultra">Ultra</label>
                    
                    <input type="radio" id="cat_juice" name="category" value="juice" <?php echo ($editingProduct && $editingProduct['category'] === 'juice') ? 'checked' : ''; ?> required>
                    <label for="cat_juice">Juice</label>
                    
                    <input type="radio" id="cat_java" name="category" value="java" <?php echo ($editingProduct && $editingProduct['category'] === 'java') ? 'checked' : ''; ?> required>
                    <label for="cat_java">Java</label>
                    
                    <input type="radio" id="cat_rehab" name="category" value="rehab" <?php echo ($editingProduct && $editingProduct['category'] === 'rehab') ? 'checked' : ''; ?> required>
                    <label for="cat_rehab">Rehab</label>
                  </div>
                </div>

                <div class="form-group">
                  <label for="p_linetag">Line Tag * (e.g. "Monster Ultra")</label>
                  <input type="text" id="p_linetag" name="line_tag" required value="<?php echo htmlspecialchars($editingProduct['line_tag'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                  <label for="p_tagline">Tagline</label>
                  <input type="text" id="p_tagline" name="tagline" value="<?php echo htmlspecialchars($editingProduct['tagline'] ?? ''); ?>">
                </div>

                <div class="form-grid-3">
                  <div class="form-group">
                    <label for="p_caffeine">Caffeine (mg)</label>
                    <input type="number" id="p_caffeine" name="caffeine" value="<?php echo htmlspecialchars($editingProduct['caffeine'] ?? 160); ?>">
                  </div>
                  <div class="form-group">
                    <label for="p_calories">Calories</label>
                    <input type="number" id="p_calories" name="calories" value="<?php echo htmlspecialchars($editingProduct['calories'] ?? 230); ?>">
                  </div>
                  <div class="form-group">
                    <label for="p_sugar">Sugar (g)</label>
                    <input type="number" id="p_sugar" name="sugar" value="<?php echo htmlspecialchars($editingProduct['sugar'] ?? 54); ?>">
                  </div>
                </div>

                <div class="form-grid-3">
                  <div class="form-group">
                    <label for="p_size">Size Label (e.g. 16oz)</label>
                    <input type="text" id="p_size" name="size" value="<?php echo htmlspecialchars($editingProduct['size'] ?? '16oz'); ?>">
                  </div>
                  <div class="form-group">
                    <label for="p_price">Price ($)</label>
                    <input type="number" id="p_price" name="price" step="0.01" value="<?php echo htmlspecialchars($editingProduct['price'] ?? 3.29); ?>">
                  </div>
                  <div class="form-group">
                    <label for="p_volume">Volume (e.g. 473ml)</label>
                    <input type="text" id="p_volume" name="volume" value="<?php echo htmlspecialchars($editingProduct['volume'] ?? '473ml can'); ?>">
                  </div>
                </div>

                <div class="form-group">
                  <label for="p_order">Display Ordering Order</label>
                  <input type="number" id="p_order" name="display_order" value="<?php echo htmlspecialchars($editingProduct['display_order'] ?? 0); ?>">
                </div>

                <div class="form-section-header">
                  <h4>Can Styling &amp; CSS Customizer</h4>
                  <p>Configure the physical looks of the fallback 3D can renderer</p>
                </div>

                <div class="form-grid-2">
                  <div class="form-group">
                    <label for="p_accent">Accent Color (Glow/Text)</label>
                    <input type="text" id="p_accent" name="accent_color" placeholder="#3DFF54" value="<?php echo htmlspecialchars($editingProduct['accent_color'] ?? '#3DFF54'); ?>">
                  </div>
                  <div class="form-group">
                    <label for="p_canaccent">Can Accent (Glow overlay)</label>
                    <input type="text" id="p_canaccent" name="can_accent" placeholder="#1a1a1a" value="<?php echo htmlspecialchars($editingProduct['can_accent'] ?? ''); ?>">
                  </div>
                </div>

                <div class="form-grid-2">
                  <div class="form-group">
                    <label for="p_band_bg">Can Band Background</label>
                    <input type="text" id="p_band_bg" name="can_band_bg" placeholder="#1e1e1e" value="<?php echo htmlspecialchars($editingProduct['can_band_bg'] ?? ''); ?>">
                  </div>
                  <div class="form-group">
                    <label for="p_m_color">Can "M" Color</label>
                    <input type="text" id="p_m_color" name="can_m_color" placeholder="#fff" value="<?php echo htmlspecialchars($editingProduct['can_m_color'] ?? ''); ?>">
                  </div>
                </div>

                <div class="form-grid-2">
                  <div class="form-group">
                    <label for="p_m_shadow">Can "M" Text Shadow</label>
                    <input type="text" id="p_m_shadow" name="can_m_shadow" placeholder="rgba(61,255,84,.8)" value="<?php echo htmlspecialchars($editingProduct['can_m_shadow'] ?? ''); ?>">
                  </div>
                  <div class="form-group">
                    <label for="p_lbl_color">Can Label Text Color</label>
                    <input type="text" id="p_lbl_color" name="can_label_color" placeholder="#bbb" value="<?php echo htmlspecialchars($editingProduct['can_label_color'] ?? ''); ?>">
                  </div>
                </div>

                <div class="form-group">
                  <label for="p_canlabel">Can Label HTML</label>
                  <input type="text" id="p_canlabel" name="can_label" placeholder="ULTRA<br>WHITE" value="<?php echo htmlspecialchars($editingProduct['can_label'] ?? ''); ?>">
                </div>
              </div>

              <!-- Right Column: Real-time Image Preview & Paste Zone -->
              <div class="form-preview-column" style="position: sticky; top: 2rem;">
                <label style="display: block; font-family: var(--font-c); font-size: 0.72rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: var(--white-dim); margin-bottom: 0.6rem;">Can Visual Image</label>
                
                <!-- Hidden inputs -->
                <input type="file" id="p_file_input" accept="image/*" style="display: none;" onchange="handleFileSelect(this)">
                <input type="hidden" id="p_pasted_image" name="pasted_image">
                
                <!-- Image Paste zone container -->
                <div id="image_paste_zone" class="image-paste-zone">
                  <img id="p_preview_img" src="<?php echo !empty($editingProduct['image_url']) ? htmlspecialchars($editingProduct['image_url']) : ''; ?>" 
                       style="display: <?php echo !empty($editingProduct['image_url']) ? 'block' : 'none'; ?>;">
                  <div id="p_preview_text" style="display: <?php echo !empty($editingProduct['image_url']) ? 'none' : 'block'; ?>;">
                    <span style="font-size: 2.5rem; color: var(--green); display: block; margin-bottom: 0.6rem;">⚡</span>
                    <span style="font-family: var(--font-c); font-size: 0.85rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; display: block; color: #fff;">Click to select file</span>
                    <span style="font-size: 0.75rem; color: var(--white-dim); display:block; margin-top: 4px;">Or paste image with Ctrl+V / Drag here</span>
                  </div>
                  
                  <!-- Clear trigger -->
                  <button type="button" id="p_clear_btn" class="btn btn-danger" onclick="removePreview(event)" 
                          style="position: absolute; top: 12px; right: 12px; padding: 0.4rem 0.8rem; font-size: 0.65rem; border-radius: 4px; display: <?php echo !empty($editingProduct['image_url']) ? 'inline-flex' : 'none'; ?>;">
                    Clear
                  </button>
                </div>

                <div class="form-group">
                  <label for="p_image_url">Image Server Path</label>
                  <input type="text" id="p_image_url" name="image_url" placeholder="Staged via upload/paste automatically" value="<?php echo htmlspecialchars($editingProduct['image_url'] ?? ''); ?>">
                </div>

                <div style="margin-top: 2rem; display: flex; flex-direction: column; gap: 0.8rem;">
                  <button type="submit" name="save_product" class="btn btn-solid" style="width: 100%;">Save Product</button>
                  <?php if ($editingProduct): ?>
                    <a href="admin.php" class="btn btn-ghost" style="width: 100%;">Cancel Edit</a>
                  <?php endif; ?>
                </div>
              </div>

            </div>
          </form>
        </div>

        <!-- Product Table list -->
        <div class="glass-card">
          <h3>Dynamic Product Portfolio</h3>
          
          <?php if (empty($products)): ?>
            <div style="color: var(--white-dim); font-size: 0.9rem; text-align: center; padding: 2rem 0;">No products found in the database. Add one above.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="admin-table">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Volume</th>
                    <th>Display Order</th>
                    <th style="text-align: right;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($products as $prod): ?>
                    <tr>
                      <td>
                        <div class="product-meta">
                          <div class="product-color-indicator" style="background: <?php echo htmlspecialchars($prod['accent_color']); ?>; color: <?php echo htmlspecialchars($prod['accent_color']); ?>;"></div>
                          <div>
                            <div class="product-name-label"><?php echo htmlspecialchars($prod['name']); ?></div>
                            <div style="font-size: 0.72rem; color: var(--white-dim);"><?php echo htmlspecialchars($prod['line_tag']); ?></div>
                          </div>
                        </div>
                      </td>
                      <td style="font-family: var(--font-c); font-weight:700; text-transform: uppercase; font-size:0.8rem;"><?php echo htmlspecialchars($prod['category']); ?></td>
                      <td style="font-weight: 600; color: var(--green); font-family: var(--font-c); font-size:1rem;">$<?php echo htmlspecialchars($prod['price']); ?></td>
                      <td style="color: var(--white-dim);"><?php echo htmlspecialchars($prod['volume']); ?></td>
                      <td style="color: var(--white-dim); font-family: var(--font-c);"><?php echo htmlspecialchars($prod['display_order']); ?></td>
                      <td>
                        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                          <a href="admin.php?action=edit&id=<?php echo $prod['id']; ?>#form-section" class="btn btn-ghost" style="padding: 0.4rem 0.8rem; font-size: 0.72rem;">Edit</a>
                          <form method="POST" action="admin.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                            <?php csrfInput(); ?>
                            <input type="hidden" name="product_id" value="<?php echo $prod['id']; ?>">
                            <button type="submit" name="delete_product" class="btn btn-danger" style="padding: 0.4rem 0.8rem; font-size: 0.72rem;">Delete</button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ── TAB: SUBSCRIBERS ── -->
      <div id="tab-subscribers" class="tab-content">
        <h2 class="page-title">Newsletter Subscriptions</h2>
        
        <div class="glass-card">
          <div class="subs-header-panel">
            <h3>Registered Subscribers</h3>
            
            <div style="display: flex; gap: 1rem; align-items: center; flex-grow: 1; justify-content: flex-end; flex-wrap: wrap;">
              <div class="search-input-wrapper">
                <input type="text" id="search_input" placeholder="Search email address..." onkeyup="filterSubscribers()" style="width: 100%; padding: 0.6rem 1rem; border-radius: 4px; border: 1px solid rgba(255,255,255,0.08); background: rgba(0,0,0,0.30); color: #fff; outline:none;" onfocus="this.style.borderColor='var(--green)'" onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
              </div>
              <a href="export-subscribers.php?csrf_token=<?php echo urlencode($_SESSION['csrf_token'] ?? ''); ?>" class="btn btn-solid">Export sheet (.CSV)</a>
            </div>
          </div>

          <?php if (empty($subscribers)): ?>
            <div style="color: var(--white-dim); font-size: 0.9rem; text-align: center; padding: 2rem 0;">No active newsletter subscriptions found.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="admin-table">
                <thead>
                  <tr>
                    <th>Email Address</th>
                    <th>Subscribed At</th>
                  </tr>
                </thead>
                <tbody id="subscribers_tbody">
                  <?php foreach ($subscribers as $sub): ?>
                    <tr class="subscriber-row">
                      <td class="sub-email" style="font-weight: 600; color: #fff;"><?php echo htmlspecialchars($sub['email']); ?></td>
                      <td style="color: var(--white-dim);"><?php echo htmlspecialchars($sub['subscribed_at']); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </main>
  </div>

  <script>
    // Tab switching/routing logic mapped to sidebar buttons
    function switchTab(evt, tabId) {
      document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
      });
      document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
      });
      document.getElementById(tabId).classList.add('active');
      
      // Update sidebar nav active style if matched
      if (evt && evt.currentTarget) {
        evt.currentTarget.classList.add('active');
      } else {
        // Fallback matching by ID
        let navBtn = null;
        if (tabId === 'tab-products') navBtn = document.getElementById('nav-products');
        else if (tabId === 'tab-subscribers') navBtn = document.getElementById('nav-subscribers');
        else navBtn = document.querySelector('.sidebar-nav button');
        
        if (navBtn) navBtn.classList.add('active');
      }
      
      sessionStorage.setItem('admin_active_tab', tabId);
    }
    
    // Helper function to return event object securely
    function evtClick(e) {
      return e;
    }
    
    // Trigger tab from child elements
    function triggerTab(navBtnId) {
      const btn = document.getElementById(navBtnId);
      if (btn) btn.click();
    }
    
    // Restore active tab states on reload/save redirection
    window.addEventListener('DOMContentLoaded', () => {
      // Check if URL has #form-section or edit state in GET params
      const urlParams = new URLSearchParams(window.location.search);
      const isEdit = urlParams.has('action') && urlParams.get('action') === 'edit';
      const isHashForm = window.location.hash === '#form-section';
      
      if (isEdit || isHashForm) {
        switchTab(null, 'tab-products');
      } else {
        const activeTab = sessionStorage.getItem('admin_active_tab');
        if (activeTab) {
          switchTab(null, activeTab);
        } else {
          switchTab(null, 'tab-dashboard');
        }
      }
    });

    // Client-side Subscriber search filter
    function filterSubscribers() {
      const query = document.getElementById('search_input').value.toLowerCase();
      const rows = document.querySelectorAll('.subscriber-row');
      
      rows.forEach(row => {
        const email = row.querySelector('.sub-email').textContent.toLowerCase();
        if (email.includes(query)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }

    // Image Upload & Clipboard Paste Zone handling logic
    const pasteZone = document.getElementById('image_paste_zone');
    const pastedInput = document.getElementById('p_pasted_image');
    const imageUrlInput = document.getElementById('p_image_url');
    const previewImg = document.getElementById('p_preview_img');
    const previewText = document.getElementById('p_preview_text');
    const clearBtn = document.getElementById('p_clear_btn');

    if (pasteZone) {
      // Trigger file browse when clicking paste zone
      pasteZone.addEventListener('click', (e) => {
        if (e.target.id === 'p_clear_btn') return;
        document.getElementById('p_file_input').click();
      });

      // Handle paste events globally inside the window
      document.addEventListener('paste', (e) => {
        // Only trigger if products tab is active
        const prodTab = document.getElementById('tab-products');
        if (!prodTab || !prodTab.classList.contains('active')) return;

        const items = (e.clipboardData || e.originalEvent.clipboardData).items;
        for (let i = 0; i < items.length; i++) {
          if (items[i].type.indexOf('image') !== -1) {
            const blob = items[i].getAsFile();
            handleImageFile(blob);
            break;
          }
        }
      });

      // Drag over / leave states
      pasteZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        pasteZone.classList.add('dragover');
      });

      pasteZone.addEventListener('dragleave', () => {
        pasteZone.classList.remove('dragover');
      });

      // Drop file
      pasteZone.addEventListener('drop', (e) => {
        e.preventDefault();
        pasteZone.classList.remove('dragover');
        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
          const file = e.dataTransfer.files[0];
          if (file.type.indexOf('image') !== -1) {
            handleImageFile(file);
          }
        }
      });
    }

    // Real-time URL text input preview update
    if (imageUrlInput) {
      imageUrlInput.addEventListener('input', () => {
        const val = imageUrlInput.value.trim();
        if (val && val !== '[Pasted/Uploaded Image Staged]') {
          previewImg.src = val;
          previewImg.style.display = 'block';
          previewText.style.display = 'none';
          clearBtn.style.display = 'inline-flex';
        } else if (!val) {
          removePreview(null);
        }
      });
    }

    function handleFileSelect(input) {
      if (input.files && input.files[0]) {
        handleImageFile(input.files[0]);
      }
    }

    function handleImageFile(file) {
      const reader = new FileReader();
      reader.onload = function (e) {
        previewImg.src = e.target.result;
        previewImg.style.display = 'block';
        previewText.style.display = 'none';
        clearBtn.style.display = 'inline-flex';
        
        pastedInput.value = e.target.result;
        imageUrlInput.value = '[Pasted/Uploaded Image Staged]';
      };
      reader.readAsDataURL(file);
    }

    function removePreview(e) {
      if (e) e.stopPropagation();
      previewImg.src = '';
      previewImg.style.display = 'none';
      previewText.style.display = 'block';
      clearBtn.style.display = 'none';
      pastedInput.value = '';
      imageUrlInput.value = '';
      document.getElementById('p_file_input').value = '';
    }
  </script>
<?php endif; ?>

</body>
</html>
