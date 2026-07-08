<?php
/**
 * AJAX Newsletter Subscription Endpoint
 */
require_once 'config.php';
require_once 'auth.php';

// Enforce POST method only
enforceRequestMethod('POST');

try {
    // Read input (handles both application/json and application/x-www-form-urlencoded)
    $email = '';
    $csrfToken = '';
    
    $rawInput = file_get_contents('php://input');
    if (empty($rawInput) && php_sapi_name() === 'cli') {
        $rawInput = file_get_contents('php://stdin');
    }
    
    $input = json_decode($rawInput, true);
    
    if (isset($input['email'])) {
        $email = $input['email'];
    } elseif (isset($_POST['email'])) {
        $email = $_POST['email'];
    }
    
    if (isset($input['csrf_token'])) {
        $csrfToken = $input['csrf_token'];
    } elseif (isset($_POST['csrf_token'])) {
        $csrfToken = $_POST['csrf_token'];
    }
    
    // Validate CSRF Token
    if (!validateCsrfToken($csrfToken)) {
        sendJsonError('CSRF token validation failed. Unauthorized request.', 403);
    }
    
    // Sanitize and Validate Email
    $email = sanitizeInput($email);
    $emailSanitized = filter_var($email, FILTER_SANITIZE_EMAIL);
    
    if (empty($email) || !filter_var($emailSanitized, FILTER_VALIDATE_EMAIL)) {
        sendJsonError('Please provide a valid email address.', 400);
    }
    
    $pdo = getDbConnection();
    
    // Check if duplicate subscription
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `subscriptions` WHERE `email` = ?");
    $stmt->execute([$emailSanitized]);
    if ($stmt->fetchColumn() > 0) {
        sendJsonError('You are already subscribed to the list!', 400);
    }
    
    // Insert new subscription
    $stmt = $pdo->prepare("INSERT INTO `subscriptions` (`email`) VALUES (?)");
    $stmt->execute([$emailSanitized]);
    
    sendJsonSuccess(null, 'Beast Incoming! ✓');
    
} catch (PDOException $e) {
    sendJsonError(DEBUG_MODE ? 'Database error: ' . $e->getMessage() : 'Database error. Please try again later.', 500);
} catch (Exception $e) {
    sendJsonError(DEBUG_MODE ? 'Error: ' . $e->getMessage() : 'An unexpected error occurred.', 500);
}
