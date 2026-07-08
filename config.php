<?php
/**
 * Database and Session Configuration
 */

// Simple native .env loader
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            
            // Strip quotes
            if (preg_match('/^"([^"]*)"$/', $val, $matches) || preg_match('/^\'([^\']*)\'$/', $val, $matches)) {
                $val = $matches[1];
            }
            
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

// Load Environment Variables
loadEnv(__DIR__ . '/.env');

// Database credentials
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_USER', getenv('DB_USER') ?: 'app_user');
define('DB_PASS', getenv('DB_PASS') ?: 'MonsterAppSecure2026!');
define('DB_NAME', getenv('DB_NAME') ?: 'monster_db');

// Error reporting - change to false in production
define('DEBUG_MODE', filter_var(getenv('DEBUG_MODE') ?: 'true', FILTER_VALIDATE_BOOLEAN));

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Start Session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Establishes a connection to the database.
 * If the database doesn't exist yet, it connects to host only.
 * 
 * @param bool $selectDb Whether to select the specific database
 * @return PDO
 */
function getDbConnection($selectDb = true) {
    $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    if ($selectDb) {
        $dsn .= ";dbname=" . DB_NAME;
    }
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    try {
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        throw new PDOException("Database Connection Error: " . $e->getMessage(), (int)$e->getCode());
    }
}

/**
 * Centralized JSON Error Response function
 *
 * @param string $message Error message
 * @param int $code HTTP response code (default 400)
 */
function sendJsonError($message, $code = 400) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => $message,
        'code' => $code
    ]);
    exit;
}

/**
 * Centralized JSON Success Response function
 *
 * @param mixed $data Data payload
 * @param string|null $message Optional message
 * @param int $code HTTP response code (default 200)
 */
function sendJsonSuccess($data = null, $message = null, $code = 200) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    
    $response = [
        'success' => true,
        'status' => 'success',
        'code' => $code
    ];
    if ($message !== null) {
        $response['message'] = $message;
    }
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Enforce specific HTTP request methods
 *
 * @param string|array $allowedMethods Method or array of allowed methods
 */
function enforceRequestMethod($allowedMethods) {
    if (!is_array($allowedMethods)) {
        $allowedMethods = [$allowedMethods];
    }
    
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($requestMethod, $allowedMethods)) {
        header('Allow: ' . implode(', ', $allowedMethods));
        sendJsonError("Method Not Allowed. Only " . implode(' or ', $allowedMethods) . " requests are accepted.", 405);
    }
}

/**
 * Sanitizes and strips HTML/JS tags from input data to prevent XSS.
 *
 * @param mixed $data Raw input data
 * @param string $allowedTags HTML tags to allow (default none)
 * @return mixed Sanitized data
 */
function sanitizeInput($data, $allowedTags = '') {
    if (is_array($data)) {
        return array_map(function($val) use ($allowedTags) {
            return sanitizeInput($val, $allowedTags);
        }, $data);
    }
    // Remove null bytes
    $data = str_replace(chr(0), '', $data);
    // Strip HTML/JS tags
    $data = strip_tags($data, $allowedTags);
    return trim($data);
}

/**
 * Fetches products from database including their relational benefit tags.
 *
 * @param PDO $pdo PDO connection
 * @param array $conditions SQL where clauses (e.g. ["category = ?"])
 * @param array $params Parameter values corresponding to conditions
 * @param string $orderBy SQL order by clause
 * @return array List of products with nested benefits
 */
function getProducts($pdo, $conditions = [], $params = [], $orderBy = '`display_order` ASC, `id` ASC') {
    $sql = "SELECT * FROM `products`";
    if (count($conditions) > 0) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    if ($orderBy) {
        $sql .= " ORDER BY " . $orderBy;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    if (empty($products)) {
        return [];
    }
    
    // Fetch benefits for these products
    $productIds = array_column($products, 'id');
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
    $bStmt = $pdo->prepare("
        SELECT pb.product_id, b.tag, b.name, b.icon, b.description 
        FROM product_benefits pb 
        JOIN benefits b ON pb.benefit_id = b.id 
        WHERE pb.product_id IN ($placeholders)
    ");
    $bStmt->execute($productIds);
    $benefitsList = $bStmt->fetchAll();
    
    $benefitsMap = [];
    foreach ($benefitsList as $b) {
        $benefitsMap[$b['product_id']][] = [
            'tag' => $b['tag'],
            'name' => $b['name'],
            'icon' => $b['icon'],
            'description' => $b['description']
        ];
    }
    
    foreach ($products as &$p) {
        $p['benefits'] = isset($benefitsMap[$p['id']]) ? $benefitsMap[$p['id']] : [];
        $p['caffeine_percentage'] = min(100, round(($p['caffeine'] / 160) * 100));
    }
    
    return $products;
}

/**
 * Fetches a single product by ID including its benefits.
 *
 * @param PDO $pdo PDO connection
 * @param int $id Product ID
 * @return array|null Product details or null if not found
 */
function getProductById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM `products` WHERE `id` = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) {
        return null;
    }
    
    $bStmt = $pdo->prepare("
        SELECT b.tag, b.name, b.icon, b.description 
        FROM product_benefits pb 
        JOIN benefits b ON pb.benefit_id = b.id 
        WHERE pb.product_id = ?
    ");
    $bStmt->execute([$id]);
    $product['benefits'] = $bStmt->fetchAll();
    $product['caffeine_percentage'] = min(100, round(($product['caffeine'] / 160) * 100));
    
    return $product;
}
