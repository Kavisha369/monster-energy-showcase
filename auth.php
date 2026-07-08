<?php
/**
 * Monster Energy — Authentication & CSRF Middleware
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF Token if not already set
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = md5(uniqid(rand(), true)); // Fallback if random_bytes fails
    }
}

/**
 * Checks if the current session has admin privileges.
 * Halts execution and returns 403 JSON or redirects to the login page.
 */
function requireAdmin() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
                  || (strpos($acceptHeader, 'application/json') !== false);
        
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'status' => 'error',
                'message' => 'Access Denied: Administrator privileges required.',
                'code' => 403
            ]);
            exit;
        } else {
            header('Location: admin.php?error=unauthorized');
            exit;
        }
    }
}

/**
 * Outputs a hidden input field containing the CSRF token.
 */
function csrfInput() {
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'] ?? '') . '">';
}

/**
 * Validates a CSRF token against the current session.
 * 
 * @param string $token Token to validate
 * @return bool True if valid, false otherwise
 */
function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
