<?php
/**
 * Monster Energy — Product JSON API Endpoint
 * Serves product records grouped by category, individual ID, or benefit criteria.
 */
require_once 'config.php';

// Enforce GET method only
enforceRequestMethod('GET');

$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$benefit = isset($_GET['benefit']) ? sanitizeInput($_GET['benefit']) : '';

try {
    $pdo = getDbConnection(true);
    
    if ($id > 0) {
        $product = getProductById($pdo, $id);
        
        if ($product) {
            sendJsonSuccess($product);
        } else {
            sendJsonError('Product not found', 404);
        }
    } else {
        $conditions = [];
        $params = [];
        
        if (!empty($category)) {
            $conditions[] = "`category` = ?";
            $params[] = $category;
        }
        
        if (!empty($benefit)) {
            if ($benefit === 'zero-sugar') {
                $conditions[] = "`sugar` = 0";
            } elseif ($benefit === 'extreme-energy') {
                $conditions[] = "`caffeine` >= 160";
            } elseif ($benefit === 'recovery') {
                $conditions[] = "`category` = 'rehab'";
            }
        }
        
        $products = getProducts($pdo, $conditions, $params, "`display_order` ASC, `id` ASC");
        sendJsonSuccess($products);
    }
} catch (Exception $e) {
    sendJsonError(DEBUG_MODE ? 'Database error occurred: ' . $e->getMessage() : 'Database error occurred.', 500);
}
